<?php
/**
 * Upload and Attachment Lifecycle Hooks for Super Optimizer
 *
 * @package SuperOptimizer
 */

namespace SuperOptimizer;

use SuperOptimizer\Database\Repository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Connects into WordPress upload lifecycle and media deletion triggers.
 */
class UploadHook
{
    /**
     * Initializes WordPress action and filter hooks.
     */
    public static function init(): void
    {
        // Intercept metadata generation on upload to optimize automatically
        add_filter('wp_generate_attachment_metadata', [__CLASS__, 'on_attachment_metadata_generated'], 10, 2);

        // Intercept media deletion to purge generated next-gen files and database records
        add_action('delete_attachment', [__CLASS__, 'on_delete_attachment'], 10, 1);
    }

    /**
     * Triggered when WordPress finishes generating thumbnail sizes for an uploaded attachment.
     *
     * @param array $metadata Attachment metadata array.
     * @param int $attachment_id WordPress attachment ID.
     * @return array Unmodified or updated metadata.
     */
    public static function on_attachment_metadata_generated($metadata, $attachment_id)
    {
        if (!wp_attachment_is_image($attachment_id)) {
            return $metadata;
        }

        if (!Settings::get('optimize_on_upload', 1)) {
            return $metadata;
        }

        // Execute optimization pipeline with the metadata WordPress is about to save
        $result = Optimizer::optimize_attachment((int) $attachment_id, false, is_array($metadata) ? $metadata : []);

        if (!empty($result['success']) && is_array($result['metadata'] ?? null)) {
            return $result['metadata'];
        }

        return $metadata;
    }

    /**
     * Cascading cleanup triggered whenever an attachment is deleted from WordPress.
     *
     * @param int $attachment_id WordPress attachment ID being deleted.
     */
    public static function on_delete_attachment($attachment_id)
    {
        $attachment_id = (int) $attachment_id;
        if ($attachment_id <= 0) {
            return;
        }

        $item = Repository::get_item_by_attachment($attachment_id);
        if ($item) {
            $subsizes = Repository::get_subsizes_by_item((int) $item->id);

            // Clean up all sibling WebP and AVIF files from filesystem
            foreach ($subsizes as $subsize) {
                if (!empty($subsize->webp_path) && file_exists($subsize->webp_path)) {
                    @unlink($subsize->webp_path);
                }
                if (!empty($subsize->avif_path) && file_exists($subsize->avif_path)) {
                    @unlink($subsize->avif_path);
                }
            }

            // Remove database records from custom relational tables
            Repository::delete_by_attachment_id($attachment_id);
        }
    }
}
