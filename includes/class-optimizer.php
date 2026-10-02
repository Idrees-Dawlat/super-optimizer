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
 */
class Optimizer
{
    /**
     * Executes complete optimization pipeline on a WordPress attachment.
     *
     * @param int $attachment_id WordPress attachment ID.
     * @param bool $webp_only If true, skips re-compressing original file and only generates WebP.
     * @return array Result summary with status and byte savings.
     */
    public static function optimize_attachment(int $attachment_id, bool $webp_only = false): array
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
                'error'   => 'Attachment file not found on disk.',
            ];
        }

        $mime_type = get_post_mime_type($attachment_id);
        $settings  = Settings::get_all();
        $engine    = EngineFactory::get_engine($settings['selected_engine'] ?? 'auto');

        if (!$engine || !$engine->is_available()) {
            return [
                'success' => false,
                'error'   => 'No image processing engine (Imagick or GD) is active on this host.',
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
                'error'   => 'Unsupported MIME type: ' . $mime_type,
            ];
        }

        // Determine quality based on MIME type
        $quality = (int) $settings['quality_jpeg'];
        if (strpos($mime_type, 'png') !== false) {
            $quality = (int) $settings['quality_png'];
        }

        $strip_metadata = !empty($settings['remove_metadata']) || !empty($settings['strip_metadata']);
        $metadata       = wp_get_attachment_metadata($attachment_id) ?: [];

        // 1. Initial Master Item Record (Mark as processing)
        $initial_original_bytes = (int) @filesize($file_path);
        $item_id = Repository::upsert_item([
            'attachment_id' => $attachment_id,
            'status'        => 'processing',
            'mime_type'     => $mime_type,
            'original_size' => $initial_original_bytes,
            'has_webp'      => !empty($settings['convert_webp']) ? 1 : 0,
            'has_avif'      => !empty($settings['convert_avif']) ? 1 : 0,
        ]);

        $total_original_bytes  = 0;
        $total_optimized_bytes = 0;
        $generated_webp_count  = 0;
        $generated_avif_count  = 0;

        // 2. Auto-downscale original if dimensions exceed configured maximums (unless in webp_only mode)
        if (!empty($settings['auto_resize']) && !$webp_only) {
            $max_w = (int) $settings['max_width'];
            $max_h = (int) $settings['max_height'];

            $img_info = @getimagesize($file_path);
            if ($img_info && ($img_info[0] > $max_w || $img_info[1] > $max_h)) {
                $engine->resize($file_path, $file_path, $max_w, $max_h, $quality);
                $new_info = @getimagesize($file_path);
                if ($new_info) {
                    $metadata['width']  = $new_info[0];
                    $metadata['height'] = $new_info[1];
                }
            }
        }

        // 3. Optimize Master Full-Size Image (Skip direct compression if in WebP-only mode)
        $full_orig_size = (int) @filesize($file_path);
        $total_original_bytes += $full_orig_size;

        $temp_full = $file_path . '.tmp_opt';
        $full_final_size = $full_orig_size;

        if (!$webp_only && $engine->optimize($file_path, $temp_full, $mime_type, $quality, $strip_metadata)) {
            $temp_size = (int) @filesize($temp_full);
            // Only replace if optimized file is smaller or equal
            if ($temp_size > 0 && $temp_size < $full_orig_size) {
                @rename($temp_full, $file_path);
                $full_final_size = $temp_size;
            } else {
                @unlink($temp_full);
            }
        } else {
            @unlink($temp_full);
        }

        $total_optimized_bytes += $full_final_size;
        $full_saved = max(0, $full_orig_size - $full_final_size);

        // 4. Generate Master WebP / AVIF Siblings
        $full_webp_path = null;
        $full_webp_size = 0;
        if (!empty($settings['convert_webp']) && $engine->supports_webp()) {
            $full_webp_path = $file_path . '.webp';
            if ($engine->convert_to_webp($file_path, $full_webp_path, (int) $settings['quality_webp'])) {
                $full_webp_size = (int) @filesize($full_webp_path);
                $generated_webp_count++;
            }
        }

        $full_avif_path = null;
        $full_avif_size = 0;
        if (!empty($settings['convert_avif']) && $engine->supports_avif()) {
            $full_avif_path = $file_path . '.avif';
            if ($engine->convert_to_avif($file_path, $full_avif_path, (int) $settings['quality_avif'])) {
                $full_avif_size = (int) @filesize($full_avif_path);
                $generated_avif_count++;
            }
        }

        // Calculate master file savings (direct disk or next-gen WebP transfer savings)
        $full_direct_saved = max(0, $full_orig_size - $full_final_size);
        $full_webp_saved   = ($full_webp_size > 0 && $full_webp_size < $full_orig_size) ? ($full_orig_size - $full_webp_size) : 0;
        $full_saved        = max($full_direct_saved, $full_webp_saved);

        $effective_full_optimized = $full_orig_size - $full_saved;
        $total_optimized_bytes += $effective_full_optimized;

        // Record master subsize in relational table
        Repository::upsert_subsize([
            'item_id'        => $item_id,
            'attachment_id'  => $attachment_id,
            'size_key'       => 'full',
            'file_path'      => $file_path,
            'width'          => $metadata['width'] ?? null,
            'height'         => $metadata['height'] ?? null,
            'original_size'  => $full_orig_size,
            'optimized_size' => $effective_full_optimized,
            'bytes_saved'    => $full_saved,
            'webp_path'      => $full_webp_path,
            'webp_size'      => $full_webp_size,
            'avif_path'      => $full_avif_path,
            'avif_size'      => $full_avif_size,
            'status'         => 'completed',
        ]);

        // 5. Optimize all registered WordPress Sub-Sizes (Thumbnails)
        $upload_dir = wp_upload_dir();
        $base_dir   = dirname($file_path);

        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_key => $size_info) {
                if (empty($size_info['file'])) {
                    continue;
                }

                $thumb_path = path_join($base_dir, $size_info['file']);
                if (!file_exists($thumb_path)) {
                    continue;
                }

                $thumb_orig_size = (int) @filesize($thumb_path);
                $total_original_bytes += $thumb_orig_size;

                $thumb_temp = $thumb_path . '.tmp_opt';
                $thumb_final_size = $thumb_orig_size;

                if (!$webp_only && $engine->optimize($thumb_path, $thumb_temp, $size_info['mime-type'] ?? $mime_type, $quality, $strip_metadata)) {
                    $t_size = (int) @filesize($thumb_temp);
                    if ($t_size > 0 && $t_size < $thumb_orig_size) {
                        @rename($thumb_temp, $thumb_path);
                        $thumb_final_size = $t_size;
                    } else {
                        @unlink($thumb_temp);
                    }
                } else {
                    @unlink($thumb_temp);
                }

                // WebP Sibling for thumbnail
                $thumb_webp_path = null;
                $thumb_webp_size = 0;
                if (!empty($settings['convert_webp']) && $engine->supports_webp()) {
                    $thumb_webp_path = $thumb_path . '.webp';
                    if ($engine->convert_to_webp($thumb_path, $thumb_webp_path, (int) $settings['quality_webp'])) {
                        $thumb_webp_size = (int) @filesize($thumb_webp_path);
                        $generated_webp_count++;
                    }
                }

                // AVIF Sibling for thumbnail
                $thumb_avif_path = null;
                $thumb_avif_size = 0;
                if (!empty($settings['convert_avif']) && $engine->supports_avif()) {
                    $thumb_avif_path = $thumb_path . '.avif';
                    if ($engine->convert_to_avif($thumb_path, $thumb_avif_path, (int) $settings['quality_avif'])) {
                        $thumb_avif_size = (int) @filesize($thumb_avif_path);
                        $generated_avif_count++;
                    }
                }

                $thumb_direct_saved = max(0, $thumb_orig_size - $thumb_final_size);
                $thumb_webp_saved   = ($thumb_webp_size > 0 && $thumb_webp_size < $thumb_orig_size) ? ($thumb_orig_size - $thumb_webp_size) : 0;
                $thumb_saved        = max($thumb_direct_saved, $thumb_webp_saved);

                $effective_thumb_optimized = $thumb_orig_size - $thumb_saved;
                $total_optimized_bytes += $effective_thumb_optimized;

                // Record each thumbnail subsize row
                Repository::upsert_subsize([
                    'item_id'        => $item_id,
                    'attachment_id'  => $attachment_id,
                    'size_key'       => sanitize_text_field($size_key),
                    'file_path'      => $thumb_path,
                    'width'          => $size_info['width'] ?? null,
                    'height'         => $size_info['height'] ?? null,
                    'original_size'  => $thumb_orig_size,
                    'optimized_size' => $effective_thumb_optimized,
                    'bytes_saved'    => $thumb_saved,
                    'webp_path'      => $thumb_webp_path,
                    'webp_size'      => $thumb_webp_size,
                    'avif_path'      => $thumb_avif_path,
                    'avif_size'      => $thumb_avif_size,
                    'status'         => 'completed',
                ]);
            }
        }

        // 6. Final Aggregate Calculations & Master Item Update
        $net_bytes_saved = max(0, $total_original_bytes - $total_optimized_bytes);
        $ratio = 0.0;
        if ($total_original_bytes > 0 && $net_bytes_saved > 0) {
            $ratio = round(($net_bytes_saved / $total_original_bytes) * 100, 2);
        }

        Repository::upsert_item([
            'attachment_id'     => $attachment_id,
            'status'            => 'completed',
            'mime_type'         => $mime_type,
            'original_size'     => $total_original_bytes,
            'optimized_size'    => $total_optimized_bytes,
            'bytes_saved'       => $net_bytes_saved,
            'compression_ratio' => $ratio,
            'has_webp'          => $generated_webp_count > 0 ? 1 : 0,
            'has_avif'          => $generated_avif_count > 0 ? 1 : 0,
            'last_error'        => null,
        ]);

        // Persist updated metadata in WordPress if dimensions were altered
        wp_update_attachment_metadata($attachment_id, $metadata);

        return [
            'success'               => true,
            'attachment_id'         => $attachment_id,
            'original_bytes'        => $total_original_bytes,
            'optimized_bytes'       => $total_optimized_bytes,
            'bytes_saved'           => $net_bytes_saved,
            'compression_ratio'     => $ratio,
            'generated_webp_count'  => $generated_webp_count,
            'generated_avif_count'  => $generated_avif_count,
        ];
    }
}
