<?php
/**
 * Settings Management for Super Optimizer
 *
 * @package SuperOptimizer
 */

namespace SuperOptimizer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin configuration options, sanitization, and defaults.
 */
class Settings
{
    public const OPTION_NAME = 'super_optimizer_settings';

    /**
     * Default configuration options.
     *
     * @var array
     */
    protected static array $defaults = [
        // Metadata & Dimensions
        'remove_metadata'        => 1,
        'max_width'              => 1920,
        'max_height'             => 1920,
        'auto_resize'            => 1,

        // Quality Sliders
        'quality_jpeg'           => 82,
        'quality_png'            => 82,
        'quality_webp'           => 80,
        'selected_engine'        => 'auto',

        // WebP Conversion & Delivery
        'convert_webp'           => 1,
        'webp_delivery_method'   => 'picture', // 'picture', 'rewrite', 'disabled'
        'webp_exclusions'        => "logo.png\npage:/checkout/",

        // Lazy Loading & LCP Optimization
        'lazy_load'              => 1,
        'lazy_load_above_fold'   => 3,
        'lazy_load_exclusions'   => "logo.png\nskip-lazy",

        // Bulk Optimization Defaults
        'bulk_force_reoptimize'  => 0,
        'bulk_webp_only'         => 0,
        'bulk_pause_seconds'     => 0,
        'bulk_background_mode'   => 0,
    ];

    /**
     * Retrieves all settings merged with defaults.
     *
     * @return array
     */
    public static function get_all(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return wp_parse_args($saved, self::$defaults);
    }

    /**
     * Retrieves a single setting value.
     *
     * @param string $key Setting key.
     * @param mixed $default Fallback value.
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $all = self::get_all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? (self::$defaults[$key] ?? null);
    }

    /**
     * Sanitizes and saves settings array.
     *
     * @param array $input Raw input values.
     * @return array Sanitized settings.
     */
    public static function sanitize_and_save(array $input): array
    {
        $sanitized = [];

        // Metadata & Sizing
        $sanitized['remove_metadata'] = !empty($input['remove_metadata']) ? 1 : 0;
        $sanitized['auto_resize']     = !empty($input['auto_resize']) || !empty($input['max_width']) ? 1 : 0;
        $sanitized['max_width']       = max(400, min(8000, (int) ($input['max_width'] ?? 1920)));
        $sanitized['max_height']      = max(400, min(8000, (int) ($input['max_height'] ?? 1920)));

        // Quality
        $sanitized['quality_jpeg'] = max(30, min(100, (int) ($input['quality_jpeg'] ?? 82)));
        $sanitized['quality_png']  = max(30, min(100, (int) ($input['quality_png'] ?? 82)));
        $sanitized['quality_webp'] = max(30, min(100, (int) ($input['quality_webp'] ?? 80)));

        $engines = ['auto', 'imagick', 'gd'];
        $sanitized['selected_engine'] = in_array($input['selected_engine'] ?? '', $engines, true)
            ? $input['selected_engine']
            : 'auto';

        // WebP
        $sanitized['convert_webp'] = !empty($input['convert_webp']) ? 1 : 0;
        $delivery_methods = ['picture', 'rewrite', 'disabled'];
        $sanitized['webp_delivery_method'] = in_array($input['webp_delivery_method'] ?? '', $delivery_methods, true)
            ? $input['webp_delivery_method']
            : 'picture';
        $sanitized['webp_exclusions'] = sanitize_textarea_field($input['webp_exclusions'] ?? "logo.png\npage:/checkout/");

        // Lazy Loading
        $sanitized['lazy_load']            = !empty($input['lazy_load']) ? 1 : 0;
        $sanitized['lazy_load_above_fold'] = max(0, min(20, (int) ($input['lazy_load_above_fold'] ?? 3)));
        $sanitized['lazy_load_exclusions'] = sanitize_textarea_field($input['lazy_load_exclusions'] ?? "logo.png\nskip-lazy");

        // Bulk Defaults
        $sanitized['bulk_force_reoptimize'] = !empty($input['bulk_force_reoptimize']) ? 1 : 0;
        $sanitized['bulk_webp_only']        = !empty($input['bulk_webp_only']) ? 1 : 0;
        $sanitized['bulk_pause_seconds']    = max(0, min(10, (int) ($input['bulk_pause_seconds'] ?? 0)));
        $sanitized['bulk_background_mode']  = !empty($input['bulk_background_mode']) ? 1 : 0;

        update_option(self::OPTION_NAME, $sanitized);

        return $sanitized;
    }
}
