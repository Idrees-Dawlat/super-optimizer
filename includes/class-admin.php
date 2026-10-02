<?php
/**
 * Admin Interface Controller for Super Optimizer
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
 * Manages admin menu, assets enqueueing, settings submission, and view routing.
 */
class Admin
{
    /**
     * Initializes admin hooks.
     */
    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_action('admin_init', [__CLASS__, 'handle_settings_save']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    /**
     * Registers top-level admin menu page.
     */
    public static function register_admin_menu(): void
    {
        $hook = add_menu_page(
            __('Super Optimizer', 'super-optimizer'),
            __('Super Optimizer', 'super-optimizer'),
            'manage_options',
            'super-optimizer',
            [__CLASS__, 'render_main_page'],
            'dashicons-performance',
            68
        );
    }

    /**
     * Enqueues admin stylesheet and JavaScript runner on Super Optimizer pages.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public static function enqueue_assets($hook_suffix): void
    {
        if (strpos($hook_suffix, 'super-optimizer') === false) {
            return;
        }

        wp_enqueue_style(
            'super-optimizer-admin-css',
            SUPER_OPTIMIZER_URL . 'admin/css/admin.css',
            [],
            SUPER_OPTIMIZER_VERSION
        );

        wp_enqueue_script(
            'super-optimizer-bulk-js',
            SUPER_OPTIMIZER_URL . 'admin/js/bulk-optimizer.js',
            ['jquery'],
            SUPER_OPTIMIZER_VERSION,
            true
        );

        $stats = Repository::get_global_stats();

        wp_localize_script('super-optimizer-bulk-js', 'superOptimizerData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('super_optimizer_bulk_nonce'),
            'stats'   => $stats,
            'i18n'    => [
                'ready'       => __('Ready to start bulk optimization.', 'super-optimizer'),
                'optimizing'  => __('Optimizing media library...', 'super-optimizer'),
                'paused'      => __('Bulk optimization paused.', 'super-optimizer'),
                'completed'   => __('All media library images successfully optimized.', 'super-optimizer'),
                'confirmReset'=> __('Are you sure you want to reset the optimization database index? This will mark all images as pending.', 'super-optimizer'),
                'error'       => __('An unexpected error occurred during processing.', 'super-optimizer'),
            ],
        ]);
    }

    /**
     * Processes settings submission.
     */
    public static function handle_settings_save(): void
    {
        if (!isset($_POST['super_optimizer_save_settings'])) {
            return;
        }

        check_admin_referer('super_optimizer_settings_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized action.', 'super-optimizer'));
        }

        $input = $_POST['settings'] ?? [];
        Settings::sanitize_and_save($input);

        wp_safe_redirect(add_query_arg([
            'page'    => 'super-optimizer',
            'tab'     => sanitize_key($_POST['current_tab'] ?? 'settings'),
            'updated' => '1',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Formats bytes into human-readable notation (KB, MB, GB).
     *
     * @param int $bytes Number of bytes.
     * @param int $precision Decimals.
     * @return string
     */
    public static function format_bytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base  = log($bytes, 1024);
        $floor = floor($base);

        return round(pow(1024, $base - $floor), $precision) . ' ' . ($units[$floor] ?? 'B');
    }

    /**
     * Renders main plugin admin page.
     */
    public static function render_main_page(): void
    {
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
        $stats       = Repository::get_global_stats();
        $settings    = Settings::get_all();
        $diagnostics = EngineFactory::get_system_diagnostics();
        $is_updated  = isset($_GET['updated']) && $_GET['updated'] === '1';

        require_once SUPER_OPTIMIZER_PATH . 'admin/views/main-view.php';
    }
}
