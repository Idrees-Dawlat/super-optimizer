<?php
/**
 * Plugin Name:       Super Optimizer
 * Plugin URI:        https://github.com/Idrees-Dawlat/super-optimizer
 * Description:       High-performance, local WordPress image optimization engine. Lossy compression, WebP and AVIF generation, auto-resizing, and crash-proof bulk processing without cloud dependencies.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Super Optimizer Team
 * Author URI:        https://github.com/Idrees-Dawlat
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       super-optimizer
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Plugin Constants
if (!defined('SUPER_OPTIMIZER_VERSION')) {
    define('SUPER_OPTIMIZER_VERSION', '1.0.0');
}
if (!defined('SUPER_OPTIMIZER_DB_VERSION')) {
    define('SUPER_OPTIMIZER_DB_VERSION', '1.0.0');
}
if (!defined('SUPER_OPTIMIZER_FILE')) {
    define('SUPER_OPTIMIZER_FILE', __FILE__);
}
if (!defined('SUPER_OPTIMIZER_PATH')) {
    define('SUPER_OPTIMIZER_PATH', plugin_dir_path(__FILE__));
}
if (!defined('SUPER_OPTIMIZER_URL')) {
    define('SUPER_OPTIMIZER_URL', plugin_dir_url(__FILE__));
}

/**
 * Autoloader for SuperOptimizer classes.
 */
spl_autoload_register(function ($class) {
    $prefix = 'SuperOptimizer\\';
    $base_dir = SUPER_OPTIMIZER_PATH . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    $raw_name = array_pop($parts);

    // Convert CamelCase to WordPress kebab-case (e.g. BulkRunner -> bulk-runner, GDEngine -> gd-engine)
    $kebab = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z][a-z])|([a-z0-9])(?=[A-Z])/', '$1$2-', $raw_name));
    $kebab = str_replace('_', '-', $kebab);

    $file_name = 'class-' . $kebab . '.php';

    // Handle interface naming convention
    if ($kebab === 'engine-interface') {
        $file_name = 'interface-engine.php';
    }

    $sub_dir = '';
    if (!empty($parts)) {
        $sub_dir = strtolower(implode('/', $parts)) . '/';
    }

    $file = $base_dir . $sub_dir . $file_name;

    if (file_exists($file)) {
        require_once $file;
    }
});

// Lifecycle Hooks
register_activation_hook(__FILE__, ['\SuperOptimizer\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['\SuperOptimizer\Plugin', 'deactivate']);

// Boot Plugin
add_action('plugins_loaded', ['\SuperOptimizer\Plugin', 'init']);
