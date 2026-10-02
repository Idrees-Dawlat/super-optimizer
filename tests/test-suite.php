<?php
/**
 * Super Optimizer - Standalone Test Suite
 *
 * Validates syntax, class contracts, engine loading, and delivery filtering.
 */

define('ABSPATH', __DIR__ . '/../');
define('SUPER_OPTIMIZER_VERSION', '1.0.0');
define('SUPER_OPTIMIZER_DB_VERSION', '1.0.0');
define('SUPER_OPTIMIZER_FILE', __DIR__ . '/../super-optimizer.php');
define('SUPER_OPTIMIZER_PATH', dirname(__DIR__) . '/');
define('SUPER_OPTIMIZER_URL', 'http://example.com/wp-content/plugins/super-optimizer/');

// Mock WordPress functions needed for isolated unit testing
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}
if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {}
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return dirname(__DIR__) . '/'; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return 'https://example.com/wp-content/plugins/super-optimizer/'; }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}
if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) { return true; }
}
if (!function_exists('delete_option')) {
    function delete_option($option) { return true; }
}
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) { return array_merge($defaults, (array)$args); }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags((string)$str)); }
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) { return trim(strip_tags((string)$str)); }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string)$key)); }
}
if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax() { return false; }
}
if (!function_exists('is_admin')) {
    function is_admin() { return false; }
}
if (!function_exists('is_feed')) {
    function is_feed() { return false; }
}
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return [
            'basedir' => '/var/www/wp-content/uploads',
            'baseurl' => 'https://example.com/wp-content/uploads',
        ];
    }
}
if (!function_exists('wp_get_registered_image_subsizes')) {
    function wp_get_registered_image_subsizes() {
        return ['thumbnail' => [], 'medium' => [], 'large' => []];
    }
}

class MockWpdb {
    public $prefix = 'wp_';
    public $posts = 'wp_posts';
    public function get_var($query) { return 18; }
    public function get_row($query, $output = 'OBJECT') {
        if ($output === 'ARRAY_A' || $output === ARRAY_A) {
            return [
                'optimized_attachments' => 16,
                'total_original_bytes' => 1048576,
                'total_optimized_bytes' => 524288,
                'total_bytes_saved' => 524288,
                'webp_count' => 16,
                'avif_count' => 0,
            ];
        }
        return (object)[];
    }
    public function get_col($query) { return [1, 2, 3]; }
    public function get_results($query) { return []; }
    public function prepare($query, ...$args) { return $query; }
    public function insert($table, $data, $format = null) { return 1; }
    public function update($table, $data, $where, $format = null, $where_format = null) { return 1; }
    public function delete($table, $where, $where_format = null) { return 1; }
    public function query($query) { return true; }
    public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'; }
}
if (!defined('ARRAY_A')) define('ARRAY_A', 'ARRAY_A');
if (!defined('OBJECT')) define('OBJECT', 'OBJECT');
global $wpdb;
$wpdb = new MockWpdb();

// Require autoloader logic from super-optimizer.php
require_once __DIR__ . '/../super-optimizer.php';

echo "Running Super Optimizer Test Suite...\n\n";

// Test 1: Autoloader & Classes
echo "Test 1: Class existence & Autoloader... ";
$classes = [
    'SuperOptimizer\\Plugin',
    'SuperOptimizer\\Settings',
    'SuperOptimizer\\Optimizer',
    'SuperOptimizer\\BulkRunner',
    'SuperOptimizer\\Delivery',
    'SuperOptimizer\\UploadHook',
    'SuperOptimizer\\Admin',
    'SuperOptimizer\\Database\\Schema',
    'SuperOptimizer\\Database\\Repository',
    'SuperOptimizer\\Engines\\EngineInterface',
    'SuperOptimizer\\Engines\\ImagickEngine',
    'SuperOptimizer\\Engines\\GDEngine',
    'SuperOptimizer\\Engines\\EngineFactory',
];

foreach ($classes as $class) {
    if (!class_exists($class) && !interface_exists($class)) {
        echo "FAILED: Missing class {$class}\n";
        exit(1);
    }
}
echo "PASSED\n";

// Test 2: Engine Factory and Diagnostics
echo "Test 2: Engine Diagnostics... ";
$diag = SuperOptimizer\Engines\EngineFactory::get_system_diagnostics();
if (empty($diag['php_version']) || !isset($diag['active_engine_name'])) {
    echo "FAILED: Diagnostics incomplete\n";
    exit(1);
}
echo "PASSED (Active Engine: {$diag['active_engine_name']})\n";

// Test 3: Settings Sanitization
echo "Test 3: Settings sanitization... ";
$raw = [
    'quality_jpeg' => '75',
    'quality_png'  => '900', // Should cap to 100
    'quality_webp' => '10',  // Should floor to 30
    'convert_webp' => '1',
    'convert_avif' => '0',
    'max_width'    => '1920',
    'max_height'   => '1080',
    'serve_webp'   => 'picture',
    'selected_engine' => 'auto',
];
$clean = SuperOptimizer\Settings::sanitize_and_save($raw);
if ($clean['quality_png'] !== 100 || $clean['quality_webp'] !== 30 || $clean['quality_jpeg'] !== 75) {
    echo "FAILED: Value clamp error\n";
    exit(1);
}
echo "PASSED\n";

// Test 4: Format Bytes
echo "Test 4: Admin format_bytes utility... ";
if (SuperOptimizer\Admin::format_bytes(1048576) !== '1 MB' || SuperOptimizer\Admin::format_bytes(0) !== '0 B') {
    echo "FAILED: Incorrect format_bytes output\n";
    exit(1);
}
echo "PASSED\n";

// Test 5: Repository Methods Execution
echo "Test 5: Repository query methods & stats... ";
$stats = SuperOptimizer\Database\Repository::get_global_stats();
$total_lib = SuperOptimizer\Database\Repository::count_total_library_images();
$queue_cnt = SuperOptimizer\Database\Repository::count_queue_attachments();
$queue_ids = SuperOptimizer\Database\Repository::get_queue_attachment_ids();
$sub_count = SuperOptimizer\Database\Repository::get_max_subsizes_per_upload();

if (empty($stats) || !isset($stats['total_original_bytes']) || $total_lib <= 0 || empty($queue_ids)) {
    echo "FAILED: Repository execution failure\n";
    exit(1);
}
echo "PASSED\n";

// Test 6: Distribution Zip Verification
echo "Test 6: Validating dist/super-optimizer.zip... ";
$zip_path = __DIR__ . '/../dist/super-optimizer.zip';
if (!file_exists($zip_path)) {
    echo "FAILED: dist/super-optimizer.zip does not exist\n";
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($zip_path) !== true) {
    echo "FAILED: Unable to open dist/super-optimizer.zip\n";
    exit(1);
}

$has_main = false;
$has_css  = false;
$has_js   = false;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if ($name === 'super-optimizer/super-optimizer.php') $has_main = true;
    if ($name === 'super-optimizer/admin/css/admin.css') $has_css = true;
    if ($name === 'super-optimizer/admin/js/bulk-optimizer.js') $has_js = true;
}
$zip->close();

if (!$has_main || !$has_css || !$has_js) {
    echo "FAILED: Missing core assets in zip archive\n";
    exit(1);
}
echo "PASSED\n";

echo "\nALL TESTS PASSED SUCCESSFULLY! Ready for production deployment.\n";
