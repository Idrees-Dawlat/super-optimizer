<?php
/**
 * Engine Factory for Super Optimizer
 *
 * @package SuperOptimizer\Engines
 */

namespace SuperOptimizer\Engines;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolves and provides the optimal graphics driver for the current host environment.
 */
class EngineFactory
{
    /**
     * Cached engine instance.
     *
     * @var EngineInterface|null
     */
    protected static ?EngineInterface $active_engine = null;

    /**
     * Resolves the configured or best available image engine.
     *
     * @param string $preference 'auto', 'imagick', or 'gd'.
     * @return EngineInterface|null
     */
    public static function get_engine(string $preference = 'auto'): ?EngineInterface
    {
        if (self::$active_engine !== null && $preference === 'auto') {
            return self::$active_engine;
        }

        $imagick = new ImagickEngine();
        $gd      = new GDEngine();

        if ($preference === 'imagick') {
            if ($imagick->is_available()) {
                self::$active_engine = $imagick;
                return $imagick;
            }
        } elseif ($preference === 'gd') {
            if ($gd->is_available()) {
                self::$active_engine = $gd;
                return $gd;
            }
        }

        // Auto mode: Imagick is preferred for color accuracy and compression quality
        if ($imagick->is_available()) {
            self::$active_engine = $imagick;
            return $imagick;
        }

        if ($gd->is_available()) {
            self::$active_engine = $gd;
            return $gd;
        }

        return null;
    }

    /**
     * Collects environmental diagnostics about image processing capabilities.
     *
     * @return array
     */
    public static function get_system_diagnostics(): array
    {
        $imagick = new ImagickEngine();
        $gd      = new GDEngine();

        $imagick_version = 'Not Installed';
        if ($imagick->is_available()) {
            try {
                $v = \Imagick::getVersion();
                $imagick_version = $v['versionString'] ?? 'Installed';
            } catch (\Throwable $e) {
                $imagick_version = 'Available (Error reading version string)';
            }
        }

        $gd_version = 'Not Installed';
        if ($gd->is_available()) {
            $info = function_exists('gd_info') ? gd_info() : [];
            $gd_version = $info['GD Version'] ?? 'Installed';
        }

        $active = self::get_engine();

        return [
            'php_version'        => PHP_VERSION,
            'memory_limit'       => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize'=> ini_get('upload_max_filesize'),
            'post_max_size'      => ini_get('post_max_size'),
            'imagick_available'  => $imagick->is_available(),
            'imagick_version'    => $imagick_version,
            'imagick_webp'       => $imagick->supports_webp(),
            'imagick_avif'       => $imagick->supports_avif(),
            'gd_available'       => $gd->is_available(),
            'gd_version'         => $gd_version,
            'gd_webp'            => $gd->supports_webp(),
            'gd_avif'            => $gd->supports_avif(),
            'active_engine_name' => $active ? $active->get_name() : 'None (No graphics extension detected)',
        ];
    }
}
