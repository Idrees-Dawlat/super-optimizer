<?php
/**
 * Database Schema Management for Super Optimizer
 *
 * @package SuperOptimizer\Database
 */

namespace SuperOptimizer\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages custom relational tables for image optimization tracking.
 */
class Schema
{
    /**
     * Database version for migrations.
     */
    public const DB_VERSION = '1.0.0';

    /**
     * Table name keys.
     */
    public const TABLE_ITEMS    = 'super_optimizer_items';
    public const TABLE_SUBSIZES = 'super_optimizer_subsizes';

    /**
     * Returns full prefixed table name for master items.
     *
     * @return string
     */
    public static function get_items_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_ITEMS;
    }

    /**
     * Returns full prefixed table name for subsizes.
     *
     * @return string
     */
    public static function get_subsizes_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_SUBSIZES;
    }

    /**
     * Installs or upgrades database tables using dbDelta.
     */
    public static function install(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_items    = self::get_items_table();
        $table_subsizes = self::get_subsizes_table();

        // 1. Master Items Table: Tracks master WordPress attachment status & cumulative metrics
        $sql_items = "CREATE TABLE {$table_items} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            attachment_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            mime_type VARCHAR(64) NOT NULL DEFAULT '',
            original_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            optimized_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            bytes_saved BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            compression_ratio DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            has_webp TINYINT(1) NOT NULL DEFAULT 0,
            has_avif TINYINT(1) NOT NULL DEFAULT 0,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            last_error TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY uq_attachment_id (attachment_id),
            KEY idx_status (status),
            KEY idx_bytes_saved (bytes_saved),
            KEY idx_updated_at (updated_at)
        ) {$charset_collate};";

        // 2. Subsizes Table: Strict 1-to-many child entity tracking each generated thumbnail dimension
        $sql_subsizes = "CREATE TABLE {$table_subsizes} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT(20) UNSIGNED NOT NULL,
            attachment_id BIGINT(20) UNSIGNED NOT NULL,
            size_key VARCHAR(64) NOT NULL DEFAULT 'full',
            file_path VARCHAR(255) NOT NULL,
            width INT UNSIGNED NULL,
            height INT UNSIGNED NULL,
            original_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            optimized_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            bytes_saved BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            webp_path VARCHAR(255) NULL,
            webp_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            avif_path VARCHAR(255) NULL,
            avif_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY idx_item_id (item_id),
            KEY idx_attachment_size (attachment_id, size_key),
            KEY idx_status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_items);
        dbDelta($sql_subsizes);

        update_option('super_optimizer_db_version', self::DB_VERSION);
    }

    /**
     * Drops custom tables upon complete uninstallation (if configured).
     */
    public static function drop_tables(): void
    {
        global $wpdb;
        $table_items    = self::get_items_table();
        $table_subsizes = self::get_subsizes_table();

        $wpdb->query("DROP TABLE IF EXISTS {$table_subsizes};");
        $wpdb->query("DROP TABLE IF EXISTS {$table_items};");

        delete_option('super_optimizer_db_version');
    }
}
