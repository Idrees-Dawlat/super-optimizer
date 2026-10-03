<?php
/**
 * Bulk Optimization AJAX Controller for Super Optimizer
 *
 * @package SuperOptimizer
 */

namespace SuperOptimizer;

use SuperOptimizer\Database\Repository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles batch processing AJAX requests for resilient bulk media optimization.
 */
class BulkRunner
{
    /**
     * Registers AJAX actions.
     */
    public static function init(): void
    {
        add_action('wp_ajax_super_optimizer_bulk_scan', [__CLASS__, 'ajax_scan_library']);
        add_action('wp_ajax_super_optimizer_bulk_get_queue', [__CLASS__, 'ajax_get_queue']);
        add_action('wp_ajax_super_optimizer_bulk_process_item', [__CLASS__, 'ajax_process_item']);
        add_action('wp_ajax_super_optimizer_bulk_reset', [__CLASS__, 'ajax_reset_queue']);
    }

    /**
     * Scans media library and returns real-time diagnostics on unoptimized assets.
     */
    public static function ajax_scan_library(): void
    {
        check_ajax_referer('super_optimizer_bulk_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized permission level.'], 403);
        }

        $force = !empty($_POST['force_reoptimize']);
        $total_library = Repository::count_total_library_images();
        $pending_count = Repository::count_queue_attachments(false);
        $max_thumbs    = Repository::get_max_subsizes_per_upload();
        $stats         = Repository::get_global_stats();

        $active_queue_count = $force ? $total_library : $pending_count;

        if ($active_queue_count > 0) {
            $message = sprintf(
                __('Scan complete: %1$d image attachments (%2$d thumbnails) ready for optimization.', 'super-optimizer'),
                $active_queue_count,
                $active_queue_count * $max_thumbs
            );
        } else {
            $message = sprintf(
                __('Scan complete: All %d media library images are currently optimized with WebP siblings.', 'super-optimizer'),
                $total_library
            );
        }

        wp_send_json_success([
            'total_library'      => $total_library,
            'pending_count'      => $pending_count,
            'active_queue_count' => $active_queue_count,
            'max_thumbs'         => $max_thumbs,
            'stats'              => $stats,
            'message'            => $message,
        ]);
    }

    /**
     * Fetches queue of un-optimized attachment IDs.
     */
    public static function ajax_get_queue(): void
    {
        check_ajax_referer('super_optimizer_bulk_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized permission level.'], 403);
        }

        $limit  = isset($_POST['limit']) ? min(1000, max(1, (int) $_POST['limit'])) : 500;
        $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
        $force  = !empty($_POST['force_reoptimize']);

        $ids   = Repository::get_queue_attachment_ids($limit, $offset, $force);
        $total = Repository::count_queue_attachments($force);

        wp_send_json_success([
            'ids'         => $ids,
            'total_queue' => $total,
            'count'       => count($ids),
        ]);
    }

    /**
     * Processes a single attachment atomically to prevent timeouts.
     */
    public static function ajax_process_item(): void
    {
        check_ajax_referer('super_optimizer_bulk_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized permission level.'], 403);
        }

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        if ($attachment_id <= 0) {
            wp_send_json_error(['message' => 'Invalid attachment ID provided.'], 400);
        }

        $webp_only = !empty($_POST['webp_only']);
        $result    = Optimizer::optimize_attachment($attachment_id, $webp_only);

        if (!$result['success']) {
            wp_send_json_error([
                'attachment_id' => $attachment_id,
                'message'       => $result['error'] ?? 'Optimization failed.',
            ]);
        }

        $file_name = basename((string) get_attached_file($attachment_id));
        $stats     = Repository::get_global_stats();

        wp_send_json_success([
            'attachment_id'     => $attachment_id,
            'filename'          => $file_name,
            'original_bytes'    => $result['original_bytes'],
            'optimized_bytes'   => $result['optimized_bytes'],
            'bytes_saved'       => $result['bytes_saved'],
            'compression_ratio' => $result['compression_ratio'],
            'webp_count'        => $result['generated_webp_count'],
            'stats'             => $stats,
        ]);
    }

    /**
     * Resets optimization database records.
     */
    public static function ajax_reset_queue(): void
    {
        check_ajax_referer('super_optimizer_bulk_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized permission level.'], 403);
        }

        Repository::reset_all_records();

        wp_send_json_success([
            'message' => 'Optimization database index reset successfully.',
            'stats'   => Repository::get_global_stats(),
        ]);
    }
}
