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
 * Manages admin menu hooks, asset loading, settings submission, and view routing.
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
     * Registers menu items under Media (Bulk Optimize) and Settings (Super Optimizer).
     */
    public static function register_admin_menu(): void
    {
        // 1. Media -> Bulk Optimize (Matches EWWW pattern requested by user)
        add_media_page(
            __('Bulk Optimize', 'super-optimizer'),
            __('Bulk Optimize', 'super-optimizer'),
            'manage_options',
            'super-optimizer-bulk',
            [__CLASS__, 'render_bulk_page']
        );

        // 2. Settings -> Super Optimizer
        add_options_page(
            __('Super Optimizer', 'super-optimizer'),
            __('Super Optimizer', 'super-optimizer'),
            'manage_options',
            'super-optimizer-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Enqueues stylesheet and JavaScript runner on Super Optimizer admin pages.
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
                'ready'       => __('Ready to start optimization.', 'super-optimizer'),
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

        $redirect_to = !empty($_POST['redirect_page']) ? sanitize_key($_POST['redirect_page']) : 'super-optimizer-settings';

        wp_safe_redirect(add_query_arg([
            'page'    => $redirect_to,
            'updated' => '1',
        ], admin_url(strpos($redirect_to, 'bulk') !== false ? 'upload.php' : 'options-general.php')));
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
     * Renders Media -> Bulk Optimize page.
     */
    public static function render_bulk_page(): void
    {
        $stats       = Repository::get_global_stats();
        $settings    = Settings::get_all();
        $diagnostics = EngineFactory::get_system_diagnostics();
        $is_updated  = isset($_GET['updated']) && $_GET['updated'] === '1';

        $queue_count = Repository::count_queue_attachments(false);
        $total_count = Repository::count_total_library_images();
        $max_thumbs  = Repository::get_max_subsizes_per_upload();

        require_once SUPER_OPTIMIZER_PATH . 'admin/views/bulk-view.php';
    }

    /**
     * Renders Settings -> Super Optimizer page.
     */
    public static function render_settings_page(): void
    {
        $stats       = Repository::get_global_stats();
        $settings    = Settings::get_all();
        $diagnostics = EngineFactory::get_system_diagnostics();
        $is_updated  = isset($_GET['updated']) && $_GET['updated'] === '1';

        require_once SUPER_OPTIMIZER_PATH . 'admin/views/settings-view.php';
    }
}
