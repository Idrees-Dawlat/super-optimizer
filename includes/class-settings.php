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
 * Handles plugin configuration options and defaults.
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
        'quality_jpeg'       => 82,
        'quality_png'        => 82,
        'quality_webp'       => 80,
        'quality_avif'       => 75,
        'convert_webp'       => 1,
        'convert_avif'       => 0,
        'auto_resize'        => 1,
        'max_width'          => 2048,
        'max_height'         => 2048,
        'strip_metadata'     => 1,
        'optimize_on_upload' => 1,
        'serve_webp'         => 'picture', // 'picture', 'rewrite', 'disabled'
        'selected_engine'    => 'auto',    // 'auto', 'imagick', 'gd'
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
     * Sanitizes and saves settings.
     *
     * @param array $input Raw input values.
     * @return array Sanitized settings.
     */
    public static function sanitize_and_save(array $input): array
    {
        $sanitized = [];

        $sanitized['quality_jpeg'] = max(30, min(100, (int) ($input['quality_jpeg'] ?? 82)));
        $sanitized['quality_png']  = max(30, min(100, (int) ($input['quality_png'] ?? 82)));
        $sanitized['quality_webp'] = max(30, min(100, (int) ($input['quality_webp'] ?? 80)));
        $sanitized['quality_avif'] = max(30, min(100, (int) ($input['quality_avif'] ?? 75)));

        $sanitized['convert_webp']       = !empty($input['convert_webp']) ? 1 : 0;
        $sanitized['convert_avif']       = !empty($input['convert_avif']) ? 1 : 0;
        $sanitized['auto_resize']        = !empty($input['auto_resize']) ? 1 : 0;
        $sanitized['max_width']          = max(500, min(8000, (int) ($input['max_width'] ?? 2048)));
        $sanitized['max_height']         = max(500, min(8000, (int) ($input['max_height'] ?? 2048)));
        $sanitized['strip_metadata']     = !empty($input['strip_metadata']) ? 1 : 0;
        $sanitized['optimize_on_upload'] = !empty($input['optimize_on_upload']) ? 1 : 0;

        $serve_modes = ['picture', 'rewrite', 'disabled'];
        $sanitized['serve_webp'] = in_array($input['serve_webp'] ?? '', $serve_modes, true)
            ? $input['serve_webp']
            : 'picture';

        $engines = ['auto', 'imagick', 'gd'];
        $sanitized['selected_engine'] = in_array($input['selected_engine'] ?? '', $engines, true)
            ? $input['selected_engine']
            : 'auto';

        update_option(self::OPTION_NAME, $sanitized);

        return $sanitized;
    }
}
