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
        add_action('wp_ajax_super_optimizer_bulk_get_queue', [__CLASS__, 'ajax_get_queue']);
        add_action('wp_ajax_super_optimizer_bulk_process_item', [__CLASS__, 'ajax_process_item']);
        add_action('wp_ajax_super_optimizer_bulk_reset', [__CLASS__, 'ajax_reset_queue']);
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

        $ids   = Repository::get_unoptimized_attachment_ids($limit, $offset);
        $total = Repository::count_unoptimized_attachments();

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

        $result = Optimizer::optimize_attachment($attachment_id);

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
            'avif_count'        => $result['generated_avif_count'],
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
