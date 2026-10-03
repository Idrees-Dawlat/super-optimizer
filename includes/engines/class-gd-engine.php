<?php
/**
 * GD Image Processing Engine
 *
 * @package SuperOptimizer\Engines
 */

namespace SuperOptimizer\Engines;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standard GD image driver fallback.
 */
class GDEngine implements EngineInterface
{
    /**
     * Engine display identifier.
     */
    public function get_name(): string
    {
        return 'GD Library';
    }

    /**
     * Checks if PHP GD extension is active.
     */
    public function is_available(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    /**
     * Checks if MIME type is supported by GD.
     */
    public function supports_mime_type(string $mime): bool
    {
        $supported = [
            'image/jpeg',
            'image/jpg',
            'image/png',
        ];

        if ($this->supports_webp()) {
            $supported[] = 'image/webp';
        }

        if ($this->supports_avif()) {
            $supported[] = 'image/avif';
        }

        return in_array(strtolower($mime), $supported, true);
    }

    /**
     * Checks if GD has WebP encoding capabilities.
     */
    public function supports_webp(): bool
    {
        return function_exists('imagewebp');
    }

    /**
     * Checks if GD has AVIF encoding capabilities (PHP 8.1+).
     */
    public function supports_avif(): bool
    {
        return function_exists('imageavif');
    }

    /**
     * Helper to load GD resource based on file type.
     *
     * @param string $path File path.
     * @return \GdImage|resource|false
     */
    protected function load_image(string $path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $info = @getimagesize($path);
        if (!$info) {
            return false;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($path);
                return $img ? $this->apply_exif_orientation($img, $path) : false;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($path);
                if ($img) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                }
                return $img;
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            case IMAGETYPE_AVIF:
                return function_exists('imagecreatefromavif') ? @imagecreatefromavif($path) : false;
            default:
                return false;
        }
    }

    /**
     * GD discards EXIF data on save, so rotate pixels to match the orientation tag first.
     *
     * @param \GdImage|resource $image
     * @return \GdImage|resource
     */
    protected function apply_exif_orientation($image, string $path)
    {
        if (!function_exists('exif_read_data') || !function_exists('imagerotate')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angles = [3 => 180, 6 => -90, 8 => 90];

        if (!isset($angles[$orientation])) {
            return $image;
        }

        $rotated = @imagerotate($image, $angles[$orientation], 0);
        if ($rotated) {
            imagedestroy($image);
            return $rotated;
        }

        return $image;
    }

    /**
     * Compresses image using GD.
     */
    public function optimize(
        string $source_path,
        string $target_path,
        string $mime_type,
        int $quality,
        bool $strip_metadata = true
    ): bool {
        if (!$this->is_available() || !file_exists($source_path)) {
            return false;
        }

        $image = $this->load_image($source_path);
        if (!$image) {
            return false;
        }

        $mime = strtolower($mime_type);
        $result = false;

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $result = imagejpeg($image, $target_path, $quality);
        } elseif ($mime === 'image/png') {
            // PNG is always lossless; level 9 is the smallest lossless output
            $png_quality = 9;
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $result = imagepng($image, $target_path, $png_quality);
        } elseif ($mime === 'image/webp' && $this->supports_webp()) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $result = imagewebp($image, $target_path, $quality);
        }

        if (is_resource($image) || (is_object($image) && $image instanceof \GdImage)) {
            imagedestroy($image);
        }

        return (bool) $result;
    }

    /**
     * Converts source image to WebP format via GD.
     */
    public function convert_to_webp(string $source_path, string $target_path, int $quality = 80): bool
    {
        if (!$this->supports_webp() || !file_exists($source_path)) {
            return false;
        }

        $image = $this->load_image($source_path);
        if (!$image) {
            return false;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $result = imagewebp($image, $target_path, $quality);

        if (is_resource($image) || (is_object($image) && $image instanceof \GdImage)) {
            imagedestroy($image);
        }

        return (bool) $result;
    }

    /**
     * Converts source image to AVIF format via GD.
     */
    public function convert_to_avif(string $source_path, string $target_path, int $quality = 75): bool
    {
        if (!$this->supports_avif() || !file_exists($source_path)) {
            return false;
        }

        $image = $this->load_image($source_path);
        if (!$image) {
            return false;
        }

        $result = imageavif($image, $target_path, $quality);

        if (is_resource($image) || (is_object($image) && $image instanceof \GdImage)) {
            imagedestroy($image);
        }

        return (bool) $result;
    }

    /**
     * Downscales an image preserving aspect ratio with truecolor resampling.
     */
    public function resize(
        string $source_path,
        string $target_path,
        int $max_width,
        int $max_height,
        int $quality = 82
    ): bool {
        if (!$this->is_available() || !file_exists($source_path)) {
            return false;
        }

        $info = @getimagesize($source_path);
        if (!$info) {
            return false;
        }

        $source = $this->load_image($source_path);
        if (!$source) {
            return false;
        }

        // Use the real (orientation-corrected) pixel size
        $width  = imagesx($source);
        $height = imagesy($source);

        if ($width <= $max_width && $height <= $max_height) {
            imagedestroy($source);
            return true; // Dimensions within bounds
        }

        $ratio = min($max_width / $width, $max_height / $height);
        $new_width  = (int) max(1, round($width * $ratio));
        $new_height = (int) max(1, round($height * $ratio));

        $destination = imagecreatetruecolor($new_width, $new_height);
        if (!$destination) {
            imagedestroy($source);
            return false;
        }

        // Preserve PNG transparency
        if ($info[2] === IMAGETYPE_PNG || $info[2] === IMAGETYPE_WEBP) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
        }

        imagecopyresampled(
            $destination,
            $source,
            0, 0, 0, 0,
            $new_width,
            $new_height,
            $width,
            $height
        );

        $result = false;
        if ($info[2] === IMAGETYPE_JPEG) {
            $result = imagejpeg($destination, $target_path, $quality);
        } elseif ($info[2] === IMAGETYPE_PNG) {
            $png_quality = 9;
            $result = imagepng($destination, $target_path, $png_quality);
        } elseif ($info[2] === IMAGETYPE_WEBP && $this->supports_webp()) {
            $result = imagewebp($destination, $target_path, $quality);
        }

        imagedestroy($source);
        imagedestroy($destination);

        return (bool) $result;
    }
}
