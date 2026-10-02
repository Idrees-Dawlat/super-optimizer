<?php
/**
 * Imagick Image Processing Engine
 *
 * @package SuperOptimizer\Engines
 */

namespace SuperOptimizer\Engines;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * High-performance ImageMagick driver.
 */
class ImagickEngine implements EngineInterface
{
    /**
     * Engine name.
     */
    public function get_name(): string
    {
        return 'Imagick (ImageMagick)';
    }

    /**
     * Checks if Imagick extension is loaded and functional.
     */
    public function is_available(): bool
    {
        return extension_loaded('imagick') && class_exists('\Imagick');
    }

    /**
     * Checks if MIME type is supported by Imagick.
     */
    public function supports_mime_type(string $mime): bool
    {
        $supported = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
        ];

        if ($this->supports_avif()) {
            $supported[] = 'image/avif';
        }

        return in_array(strtolower($mime), $supported, true);
    }

    /**
     * Verifies if Imagick has WebP coder compiled.
     */
    public function supports_webp(): bool
    {
        if (!$this->is_available()) {
            return false;
        }

        try {
            $formats = \Imagick::queryFormats('WEBP');
            return !empty($formats);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Verifies if Imagick has AVIF coder compiled.
     */
    public function supports_avif(): bool
    {
        if (!$this->is_available()) {
            return false;
        }

        try {
            $formats = \Imagick::queryFormats('AVIF');
            return !empty($formats);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Compresses image using high-grade lossy / lossless techniques.
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

        try {
            $image = new \Imagick($source_path);

            // Handle multi-frame/animated images safely by selecting first frame
            if ($image->getNumberImages() > 1) {
                $image = $image->coalesceImages();
            }

            // Stripping bloated metadata while preserving essential ICC profiles
            if ($strip_metadata) {
                $icc_profile = $image->getImageProfile('icc');
                $image->stripImage();
                if (!empty($icc_profile)) {
                    $image->profileImage('icc', $icc_profile);
                }
            }

            // Format-specific tuning
            $format = strtoupper($image->getImageFormat());
            if ($format === 'JPEG' || $format === 'JPG') {
                $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($quality);
                $image->setInterlaceScheme(\Imagick::INTERLACE_PLANE); // Progressive JPEG
            } elseif ($format === 'PNG') {
                // Optimal PNG compression: deflate level 9, adaptive filtering
                $image->setOption('png:compression-level', '9');
                $image->setOption('png:compression-filter', '5');
                $image->setOption('png:compression-strategy', '1');
            } elseif ($format === 'WEBP') {
                $image->setImageCompressionQuality($quality);
                $image->setOption('webp:method', '6'); // Max compression quality effort
            }

            $success = $image->writeImage($target_path);
            $image->clear();
            $image->destroy();

            return (bool) $success;
        } catch (\Throwable $e) {
            error_log('Super Optimizer [Imagick Error]: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Converts source image to WebP format.
     */
    public function convert_to_webp(string $source_path, string $target_path, int $quality = 80): bool
    {
        if (!$this->supports_webp() || !file_exists($source_path)) {
            return false;
        }

        try {
            $image = new \Imagick($source_path);
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality($quality);
            $image->setOption('webp:method', '6');
            $image->setOption('webp:auto-filter', 'true');

            $success = $image->writeImage($target_path);
            $image->clear();
            $image->destroy();

            return (bool) $success;
        } catch (\Throwable $e) {
            error_log('Super Optimizer [Imagick WebP Error]: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Converts source image to AVIF format.
     */
    public function convert_to_avif(string $source_path, string $target_path, int $quality = 75): bool
    {
        if (!$this->supports_avif() || !file_exists($source_path)) {
            return false;
        }

        try {
            $image = new \Imagick($source_path);
            $image->setImageFormat('avif');
            $image->setImageCompressionQuality($quality);

            $success = $image->writeImage($target_path);
            $image->clear();
            $image->destroy();

            return (bool) $success;
        } catch (\Throwable $e) {
            error_log('Super Optimizer [Imagick AVIF Error]: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Downscales an image preserving aspect ratio with Lanczos resampling.
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

        try {
            $image = new \Imagick($source_path);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            if ($width <= $max_width && $height <= $max_height) {
                $image->clear();
                $image->destroy();
                return true; // No resizing needed
            }

            // Calculate scaled bounds
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = (int) max(1, round($width * $ratio));
            $new_height = (int) max(1, round($height * $ratio));

            $image->resizeImage($new_width, $new_height, \Imagick::FILTER_LANCZOS, 1);
            $image->setImageCompressionQuality($quality);

            $success = $image->writeImage($target_path);
            $image->clear();
            $image->destroy();

            return (bool) $success;
        } catch (\Throwable $e) {
            error_log('Super Optimizer [Imagick Resize Error]: ' . $e->getMessage());
            return false;
        }
    }
}
