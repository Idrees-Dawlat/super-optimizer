<?php
/**
 * Database Repository for Super Optimizer
 *
 * @package SuperOptimizer\Database
 */

namespace SuperOptimizer\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encapsulates all database persistence and queries for optimization entities.
 */
class Repository
{
    /**
     * Upserts an item record for a given WordPress attachment.
     *
     * @param array $data Item fields.
     * @return int Primary key ID of the item record.
     */
    public static function upsert_item(array $data): int
    {
        global $wpdb;
        $table = Schema::get_items_table();

        $attachment_id = isset($data['attachment_id']) ? (int) $data['attachment_id'] : 0;
        if ($attachment_id <= 0) {
            return 0;
        }

        $now = current_time('mysql', 1);

        $existing_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE attachment_id = %d LIMIT 1", $attachment_id)
        );

        $payload = [
            'attachment_id'     => $attachment_id,
            'status'            => $data['status'] ?? 'pending',
            'mime_type'         => sanitize_text_field($data['mime_type'] ?? ''),
            'original_size'     => (int) ($data['original_size'] ?? 0),
            'optimized_size'    => (int) ($data['optimized_size'] ?? 0),
            'bytes_saved'       => (int) ($data['bytes_saved'] ?? 0),
            'compression_ratio' => (float) ($data['compression_ratio'] ?? 0.0),
            'has_webp'          => !empty($data['has_webp']) ? 1 : 0,
            'has_avif'          => !empty($data['has_avif']) ? 1 : 0,
            'attempts'          => (int) ($data['attempts'] ?? 0),
            'last_error'        => !empty($data['last_error']) ? sanitize_textarea_field($data['last_error']) : null,
            'updated_at'        => $now,
        ];

        $format = ['%d', '%s', '%s', '%d', '%d', '%d', '%f', '%d', '%d', '%d', '%s', '%s'];

        if ($existing_id) {
            $wpdb->update($table, $payload, ['id' => (int) $existing_id], $format, ['%d']);
            return (int) $existing_id;
        }

        $payload['created_at'] = $now;
        $format[] = '%s';

        $wpdb->insert($table, $payload, $format);
        return (int) $wpdb->insert_id;
    }

    /**
     * Upserts a subsize record.
     *
     * @param array $data Subsize fields.
     * @return int Primary key ID of the subsize record.
     */
    public static function upsert_subsize(array $data): int
    {
        global $wpdb;
        $table = Schema::get_subsizes_table();

        $attachment_id = (int) ($data['attachment_id'] ?? 0);
        $size_key      = sanitize_text_field($data['size_key'] ?? 'full');

        if ($attachment_id <= 0 || empty($size_key)) {
            return 0;
        }

        $now = current_time('mysql', 1);

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attachment_id = %d AND size_key = %s LIMIT 1",
                $attachment_id,
                $size_key
            )
        );

        $payload = [
            'item_id'        => (int) ($data['item_id'] ?? 0),
            'attachment_id'  => $attachment_id,
            'size_key'       => $size_key,
            'file_path'      => sanitize_text_field($data['file_path'] ?? ''),
            'width'          => isset($data['width']) ? (int) $data['width'] : null,
            'height'         => isset($data['height']) ? (int) $data['height'] : null,
            'original_size'  => (int) ($data['original_size'] ?? 0),
            'optimized_size' => (int) ($data['optimized_size'] ?? 0),
            'bytes_saved'    => (int) ($data['bytes_saved'] ?? 0),
            'webp_path'      => !empty($data['webp_path']) ? sanitize_text_field($data['webp_path']) : null,
            'webp_size'      => (int) ($data['webp_size'] ?? 0),
            'avif_path'      => !empty($data['avif_path']) ? sanitize_text_field($data['avif_path']) : null,
            'avif_size'      => (int) ($data['avif_size'] ?? 0),
            'status'         => sanitize_text_field($data['status'] ?? 'completed'),
            'updated_at'     => $now,
        ];

        $format = ['%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s'];

        if ($existing_id) {
            $wpdb->update($table, $payload, ['id' => (int) $existing_id], $format, ['%d']);
            return (int) $existing_id;
        }

        $payload['created_at'] = $now;
        $format[] = '%s';

        $wpdb->insert($table, $payload, $format);
        return (int) $wpdb->insert_id;
    }

    /**
     * Retrieves an item record by attachment ID.
     *
     * @param int $attachment_id WordPress attachment ID.
     * @return object|null Database row or null if not found.
     */
    public static function get_item_by_attachment(int $attachment_id): ?object
    {
        global $wpdb;
        $table = Schema::get_items_table();

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE attachment_id = %d LIMIT 1", $attachment_id)
        );

        return $row ?: null;
    }

    /**
     * Retrieves all sub-sizes for a given item ID.
     *
     * @param int $item_id Parent item ID.
     * @return array List of subsize database objects.
     */
    public static function get_subsizes_by_item(int $item_id): array
    {
        global $wpdb;
        $table = Schema::get_subsizes_table();

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE item_id = %d ORDER BY id ASC", $item_id)
        );

        return $rows ?: [];
    }

    /**
     * Deletes all records associated with a WordPress attachment.
     * Cascades from items to subsizes.
     *
     * @param int $attachment_id WordPress attachment ID.
     * @return void
     */
    public static function delete_by_attachment_id(int $attachment_id): void
    {
        global $wpdb;
        $table_items    = Schema::get_items_table();
        $table_subsizes = Schema::get_subsizes_table();

        // 1. Delete subsizes
        $wpdb->delete($table_subsizes, ['attachment_id' => $attachment_id], ['%d']);

        // 2. Delete master item
        $wpdb->delete($table_items, ['attachment_id' => $attachment_id], ['%d']);
    }

    /**
     * Queries un-optimized or pending attachment IDs from WordPress media library.
     *
     * @param int $limit Number of IDs to fetch.
     * @param int $offset Offset.
     * @return int[]
     */
    /**
     * Queries un-optimized or pending attachment IDs from WordPress media library.
     *
     * @param int $limit Number of IDs to fetch.
     * @param int $offset Offset.
     * @param bool $force_all If true, returns all image attachment IDs regardless of previous optimization status.
     * @return int[]
     */
    public static function get_queue_attachment_ids(int $limit = 500, int $offset = 0, bool $force_all = false): array
    {
        global $wpdb;
        $table_items = Schema::get_items_table();

        if ($force_all) {
            $sql = "
                SELECT ID
                FROM {$wpdb->posts}
                WHERE post_type = 'attachment'
                  AND post_mime_type IN ('image/jpeg', 'image/jpg', 'image/png', 'image/webp')
                ORDER BY ID DESC
                LIMIT %d OFFSET %d
            ";
            $results = $wpdb->get_col($wpdb->prepare($sql, $limit, $offset));
        } else {
            $sql = "
                SELECT p.ID
                FROM {$wpdb->posts} p
                LEFT JOIN {$table_items} i ON p.ID = i.attachment_id
                WHERE p.post_type = 'attachment'
                  AND p.post_mime_type IN ('image/jpeg', 'image/jpg', 'image/png', 'image/webp')
                  AND (i.id IS NULL OR i.status IN ('pending', 'failed'))
                ORDER BY p.ID DESC
                LIMIT %d OFFSET %d
            ";
            $results = $wpdb->get_col($wpdb->prepare($sql, $limit, $offset));
        }

        return array_map('intval', $results);
    }

    /**
     * Backward-compatible alias for get_queue_attachment_ids.
     */
    public static function get_unoptimized_attachment_ids(int $limit = 500, int $offset = 0): array
    {
        return self::get_queue_attachment_ids($limit, $offset, false);
    }

    /**
     * Returns total count of image attachments needing optimization.
     *
     * @param bool $force_all If true, counts all image attachments.
     * @return int
     */
    public static function count_queue_attachments(bool $force_all = false): int
    {
        global $wpdb;
        $table_items = Schema::get_items_table();

        if ($force_all) {
            return self::count_total_library_images();
        }

        $sql = "
            SELECT COUNT(p.ID)
            FROM {$wpdb->posts} p
            LEFT JOIN {$table_items} i ON p.ID = i.attachment_id
            WHERE p.post_type = 'attachment'
              AND p.post_mime_type IN ('image/jpeg', 'image/jpg', 'image/png', 'image/webp')
              AND (i.id IS NULL OR i.status IN ('pending', 'failed'))
        ";

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Backward-compatible alias for count_queue_attachments.
     */
    public static function count_unoptimized_attachments(): int
    {
        return self::count_queue_attachments(false);
    }

    /**
     * Returns total count of all supported image attachments in the media library.
     *
     * @return int
     */
    public static function count_total_library_images(): int
    {
        global $wpdb;
        $sql = "
            SELECT COUNT(ID)
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
              AND post_mime_type IN ('image/jpeg', 'image/jpg', 'image/png', 'image/webp')
        ";

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Calculates the maximum/average registered thumbnail variations per upload.
     *
     * @return int
     */
    public static function get_max_subsizes_per_upload(): int
    {
        $sizes = wp_get_registered_image_subsizes();
        $count = count($sizes);
        return max(1, $count > 0 ? $count + 1 : 6);
    }

    /**
     * Calculates aggregate optimization statistics across all completed items.
     *
     * @return array
     */
    public static function get_global_stats(): array
    {
        global $wpdb;
        $table_items    = Schema::get_items_table();
        $table_subsizes = Schema::get_subsizes_table();

        $total_library_images = self::count_total_library_images();

        $sql_items = "
            SELECT
                COUNT(id) AS optimized_attachments,
                COALESCE(SUM(original_size), 0) AS total_original_bytes,
                COALESCE(SUM(optimized_size), 0) AS total_optimized_bytes,
                COALESCE(SUM(bytes_saved), 0) AS total_bytes_saved,
                COALESCE(SUM(has_webp), 0) AS webp_count,
                COALESCE(SUM(has_avif), 0) AS avif_count
            FROM {$table_items}
            WHERE status = 'completed'
        ";

        $stats = $wpdb->get_row($sql_items, ARRAY_A);
        if (!$stats) {
            $stats = [
                'optimized_attachments' => 0,
                'total_original_bytes'  => 0,
                'total_optimized_bytes' => 0,
                'total_bytes_saved'     => 0,
                'webp_count'            => 0,
                'avif_count'            => 0,
            ];
        }

        // Subsizes count
        $subsizes_count = (int) $wpdb->get_var(
            "SELECT COUNT(id) FROM {$table_subsizes} WHERE status = 'completed'"
        );

        $original_bytes = (int) $stats['total_original_bytes'];
        $saved_bytes    = (int) $stats['total_bytes_saved'];

        $percentage = 0.0;
        if ($original_bytes > 0 && $saved_bytes > 0) {
            $percentage = round(($saved_bytes / $original_bytes) * 100, 1);
        }

        // Calculate estimated bandwidth & page load time saved
        // Standard baseline: average 4G / mobile throughput (1.5 MB/sec ~ 12 Mbps)
        $time_saved_ms = 0;
        $time_saved_formatted = '0 ms';
        if ($saved_bytes > 0) {
            $seconds = ($saved_bytes / 1572864); // 1.5 MB/s
            $time_saved_ms = (int) round($seconds * 1000);
            if ($seconds < 1.0) {
                $time_saved_formatted = $time_saved_ms . ' ms';
            } else {
                $time_saved_formatted = round($seconds, 1) . ' s';
            }
        }

        $pending_count = self::count_unoptimized_attachments();

        return [
            'total_library_images'  => $total_library_images,
            'optimized_attachments' => (int) $stats['optimized_attachments'],
            'optimized_subsizes'    => $subsizes_count,
            'pending_count'         => $pending_count,
            'total_original_bytes'  => $original_bytes,
            'total_optimized_bytes' => (int) $stats['total_optimized_bytes'],
            'total_bytes_saved'     => $saved_bytes,
            'percentage_saved'      => $percentage,
            'time_saved_ms'         => $time_saved_ms,
            'time_saved_formatted'  => $time_saved_formatted,
            'webp_count'            => (int) $stats['webp_count'],
            'avif_count'            => (int) $stats['avif_count'],
        ];
    }

    /**
     * Resets all optimization records and restores statuses to pending.
     */
    public static function reset_all_records(): void
    {
        global $wpdb;
        $table_items    = Schema::get_items_table();
        $table_subsizes = Schema::get_subsizes_table();

        $wpdb->query("TRUNCATE TABLE {$table_subsizes};");
        $wpdb->query("TRUNCATE TABLE {$table_items};");
    }
}
