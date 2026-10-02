<?php
/**
 * Plugin Main Orchestrator for Super Optimizer
 *
 * @package SuperOptimizer
 */

namespace SuperOptimizer;

use SuperOptimizer\Database\Schema;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Boots core plugin services, initializes hooks, and manages plugin lifecycle.
 */
class Plugin
{
    /**
     * Bootstraps all plugin subsystems.
     */
    public static function init(): void
    {
        // 1. Check and run database migrations if needed
        self::check_db_version();

        // 2. Initialize attachment upload & delete hooks
        UploadHook::init();

        // 3. Initialize next-generation WebP delivery engine
        Delivery::init();

        // 4. Initialize bulk processing AJAX handlers
        if (wp_doing_ajax()) {
            BulkRunner::init();
        }

        // 5. Initialize admin dashboard if in admin context
        if (is_admin()) {
            Admin::init();
        }
    }

    /**
     * Executes upon plugin activation.
     */
    public static function activate(): void
    {
        Schema::install();
    }

    /**
     * Executes upon plugin deactivation.
     */
    public static function deactivate(): void
    {
        // Clean up transient flags if any
        delete_transient('super_optimizer_bulk_lock');
    }

    /**
     * Verifies that the installed database version matches the codebase.
     */
    protected static function check_db_version(): void
    {
        $installed = get_option('super_optimizer_db_version');
        if ($installed !== Schema::DB_VERSION) {
            Schema::install();
        }
    }
}
