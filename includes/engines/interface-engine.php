<?php
/**
 * Image Processing Engine Interface
 *
 * @package SuperOptimizer\Engines
 */

namespace SuperOptimizer\Engines;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defines standard operations required for an optimization driver.
 */
interface EngineInterface
{
    /**
     * Engine display identifier.
     */
    public function get_name(): string;

    /**
     * Verifies if PHP environment has required extensions loaded.
     */
    public function is_available(): bool;

    /**
     * Checks if given mime type can be processed.
     */
    public function supports_mime_type(string $mime): bool;

    /**
     * Verifies if engine can encode WebP format.
     */
    public function supports_webp(): bool;

    /**
     * Verifies if engine can encode AVIF format.
     */
    public function supports_avif(): bool;

    /**
     * Compresses image file in-place or to destination.
     *
     * @param string $source_path Absolute path to source.
     * @param string $target_path Absolute destination path.
     * @param string $mime_type MIME type of image.
     * @param int $quality Compression quality (1-100).
     * @param bool $strip_metadata Whether to strip EXIF and comments.
     * @return bool True on success.
     */
    public function optimize(
        string $source_path,
        string $target_path,
        string $mime_type,
        int $quality,
        bool $strip_metadata = true
    ): bool;

    /**
     * Converts image to WebP format sibling.
     *
     * @param string $source_path Source file path.
     * @param string $target_path WebP target file path.
     * @param int $quality Quality setting (1-100).
     * @return bool
     */
    public function convert_to_webp(string $source_path, string $target_path, int $quality = 80): bool;

    /**
     * Converts image to AVIF format sibling.
     *
     * @param string $source_path Source file path.
     * @param string $target_path AVIF target file path.
     * @param int $quality Quality setting (1-100).
     * @return bool
     */
    public function convert_to_avif(string $source_path, string $target_path, int $quality = 75): bool;

    /**
     * Downscales an image if dimensions exceed limits.
     *
     * @param string $source_path Source file path.
     * @param string $target_path Destination file path.
     * @param int $max_width Maximum allowed width.
     * @param int $max_height Maximum allowed height.
     * @param int $quality Compression quality.
     * @return bool True if resized or preserved.
     */
    public function resize(
        string $source_path,
        string $target_path,
        int $max_width,
        int $max_height,
        int $quality = 82
    ): bool;
}
