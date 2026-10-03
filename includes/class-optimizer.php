<?php
/**
 * Main Optimization Coordinator for Super Optimizer
 *
 * @package SuperOptimizer
 */

namespace SuperOptimizer;

use SuperOptimizer\Database\Repository;
use SuperOptimizer\Engines\EngineFactory;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Orchestrates full image optimization pipelines for WordPress media attachments.
 *
 * Reported savings are real: they are the difference in bytes between the file
 * before and after optimization on disk. WebP copies are extra files and are
 * never counted as savings.
 */
class Optimizer
{
    /**
     * Executes complete optimization pipeline on a WordPress attachment.
     *
     * @param int        $attachment_id WordPress attachment ID.
     * @param bool       $webp_only     If true, skips re-compressing originals and only generates WebP.
     * @param array|null $metadata      Attachment metadata, when called before WordPress has saved it (uploads).
     * @return array Result summary with status and byte savings.
     */
    public static function optimize_attachment(int $attachment_id, bool $webp_only = false, ?array $metadata = null): array
    {
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            Repository::upsert_item([
                'attachment_id' => $attachment_id,
                'status'        => 'failed',
                'last_error'    => 'Attachment file not found on disk.',
            ]);

            return [
                'success' => false,
                'error'   => 'The image file is missing on disk.',
            ];
        }

        $mime_type = (string) get_post_mime_type($attachment_id);
        $settings  = Settings::get_all();
        $engine    = EngineFactory::get_engine($settings['selected_engine'] ?? 'auto');

        if (!$engine || !$engine->is_available()) {
            return [
                'success' => false,
                'error'   => 'No image engine (Imagick or GD) is available on this server.',
            ];
        }

        if (!$engine->supports_mime_type($mime_type)) {
            Repository::upsert_item([
                'attachment_id' => $attachment_id,
                'status'        => 'skipped',
                'mime_type'     => $mime_type,
                'last_error'    => 'Unsupported MIME type: ' . $mime_type,
            ]);

            return [
                'success' => false,
                'error'   => 'Unsupported image type: ' . $mime_type,
            ];
        }

        $quality = (int) $settings['quality_jpeg'];
        if (strpos($mime_type, 'png') !== false) {
            $quality = (int) $settings['quality_png'];
        } elseif (strpos($mime_type, 'webp') !== false) {
            $quality = (int) $settings['quality_webp'];
        }

        $strip_metadata    = !empty($settings['remove_metadata']);
        $webp_quality      = (int) $settings['quality_webp'];
        $make_webp         = !empty($settings['convert_webp']) && $engine->supports_webp();
        $metadata_supplied = $metadata !== null;
        $metadata          = $metadata ?? (wp_get_attachment_metadata($attachment_id) ?: []);

        $original_bytes_before = (int) @filesize($file_path);
        $item_id = Repository::upsert_item([
            'attachment_id' => $attachment_id,
            'status'        => 'processing',
            'mime_type'     => $mime_type,
            'original_size' => $original_bytes_before,
            'has_webp'      => $make_webp ? 1 : 0,
        ]);

        $total_original  = 0;
        $total_optimized = 0;
        $webp_files      = 0;

        // 1. Downscale oversized originals (never in WebP-only mode)
        if (!empty($settings['auto_resize']) && !$webp_only) {
            $max_w = (int) $settings['max_width'];
            $max_h = (int) $settings['max_height'];

            $img_info = @getimagesize($file_path);
            if ($img_info && ($img_info[0] > $max_w || $img_info[1] > $max_h)) {
                $engine->resize($file_path, $file_path, $max_w, $max_h, max($quality, 92));
                $new_info = @getimagesize($file_path);
                if ($new_info) {
                    $metadata['width']  = $new_info[0];
                    $metadata['height'] = $new_info[1];
                }
            }
        }

        // 2. Full-size image. Baseline is the size before any change, so resize savings count.
        $full = self::process_file(
            $engine,
            $file_path,
            $mime_type,
            $quality,
            $strip_metadata,
            $webp_only,
            $make_webp,
            $webp_quality,
            $original_bytes_before
        );
        $total_original  += $full['original'];
        $total_optimized += $full['optimized'];
        $webp_files      += $full['webp_size'] > 0 ? 1 : 0;

        Repository::upsert_subsize([
            'item_id'        => $item_id,
            'attachment_id'  => $attachment_id,
            'size_key'       => 'full',
            'file_path'      => $file_path,
            'width'          => $metadata['width'] ?? null,
            'height'         => $metadata['height'] ?? null,
            'original_size'  => $full['original'],
            'optimized_size' => $full['optimized'],
            'bytes_saved'    => $full['original'] - $full['optimized'],
            'webp_path'      => $full['webp_path'],
            'webp_size'      => $full['webp_size'],
            'status'         => 'completed',
        ]);

        // 3. Registered thumbnail sizes
        $base_dir = dirname($file_path);
        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_key => $size_info) {
                if (empty($size_info['file'])) {
                    continue;
                }

                $thumb_path = path_join($base_dir, $size_info['file']);
                if (!file_exists($thumb_path) || $thumb_path === $file_path) {
                    continue;
                }

                $thumb_mime = $size_info['mime-type'] ?? $mime_type;
                $thumb_q    = $quality;
                if (strpos($thumb_mime, 'png') !== false) {
                    $thumb_q = (int) $settings['quality_png'];
                }

                $thumb = self::process_file(
                    $engine,
                    $thumb_path,
                    $thumb_mime,
                    $thumb_q,
                    $strip_metadata,
                    $webp_only,
                    $make_webp,
                    $webp_quality,
                    (int) @filesize($thumb_path)
                );
                $total_original  += $thumb['original'];
                $total_optimized += $thumb['optimized'];
                $webp_files      += $thumb['webp_size'] > 0 ? 1 : 0;

                Repository::upsert_subsize([
                    'item_id'        => $item_id,
                    'attachment_id'  => $attachment_id,
                    'size_key'       => sanitize_text_field($size_key),
                    'file_path'      => $thumb_path,
                    'width'          => $size_info['width'] ?? null,
                    'height'         => $size_info['height'] ?? null,
                    'original_size'  => $thumb['original'],
                    'optimized_size' => $thumb['optimized'],
                    'bytes_saved'    => $thumb['original'] - $thumb['optimized'],
                    'webp_path'      => $thumb['webp_path'],
                    'webp_size'      => $thumb['webp_size'],
                    'status'         => 'completed',
                ]);
            }
        }

        // 4. Totals
        $net_bytes_saved = max(0, $total_original - $total_optimized);
        $ratio = ($total_original > 0 && $net_bytes_saved > 0)
            ? round(($net_bytes_saved / $total_original) * 100, 1)
            : 0.0;

        Repository::upsert_item([
            'attachment_id'     => $attachment_id,
            'status'            => 'completed',
            'mime_type'         => $mime_type,
            'original_size'     => $total_original,
            'optimized_size'    => $total_optimized,
            'bytes_saved'       => $net_bytes_saved,
            'compression_ratio' => $ratio,
            'has_webp'          => $webp_files > 0 ? 1 : 0,
            'has_avif'          => 0,
            'last_error'        => null,
        ]);

        if (!$metadata_supplied) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        return [
            'success'              => true,
            'attachment_id'        => $attachment_id,
            'original_bytes'       => $total_original,
            'optimized_bytes'      => $total_optimized,
            'bytes_saved'          => $net_bytes_saved,
            'compression_ratio'    => $ratio,
            'generated_webp_count' => $webp_files,
            'metadata'             => $metadata,
        ];
    }

    /**
     * Optimizes one file on disk and optionally creates its WebP copy.
     *
     * The compressed result only replaces the file if it is actually smaller.
     *
     * @return array{original:int,optimized:int,webp_path:?string,webp_size:int}
     */
    protected static function process_file(
        $engine,
        string $path,
        string $mime,
        int $quality,
        bool $strip_metadata,
        bool $webp_only,
        bool $make_webp,
        int $webp_quality,
        int $baseline_bytes
    ): array {
        $before = (int) @filesize($path);
        $final  = $before;

        if (!$webp_only) {
            $temp = $path . '.tmp_opt';
            if ($engine->optimize($path, $temp, $mime, $quality, $strip_metadata)) {
                $temp_size = (int) @filesize($temp);
                if ($temp_size > 0 && $temp_size < $before) {
                    @rename($temp, $path);
                    $final = $temp_size;
                } else {
                    @unlink($temp);
                }
            } elseif (file_exists($temp)) {
                @unlink($temp);
            }
        }

        $webp_path = null;
        $webp_size = 0;
        // WebP originals do not need a second WebP copy
        if ($make_webp && stripos($mime, 'webp') === false) {
            $candidate = $path . '.webp';
            if ($engine->convert_to_webp($path, $candidate, $webp_quality)) {
                $size = (int) @filesize($candidate);
                // Keep the copy only when it is genuinely smaller than the file it replaces for browsers
                if ($size > 0 && $size < $final) {
                    $webp_path = $candidate;
                    $webp_size = $size;
                } else {
                    @unlink($candidate);
                }
            }
        }

        return [
            'original'  => max($baseline_bytes, $final),
            'optimized' => $final,
            'webp_path' => $webp_path,
            'webp_size' => $webp_size,
        ];
    }
}
