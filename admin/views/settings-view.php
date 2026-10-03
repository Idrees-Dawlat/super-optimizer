<?php
/**
 * Settings -> Super Optimizer (Settings tab)
 *
 * Professional configuration with curated compression tiers, delivery options,
 * and advanced database tools.
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$so_active_tab = 'settings';
$bulk_url      = admin_url('upload.php?page=super-optimizer-bulk');
$current_level = $settings['compression_level'] ?? 'balanced';

/**
 * Helper to render a clean toggle switch.
 */
$so_switch = static function (string $name, $checked): void {
    printf(
        '<label class="so-switch"><input type="checkbox" name="settings[%1$s]" value="1"%2$s><span class="so-switch-track"></span></label>',
        esc_attr($name),
        checked(!empty($checked), true, false)
    );
};
?>

<div class="wrap so-app">
    <?php require SUPER_OPTIMIZER_PATH . 'admin/views/partials/header.php'; ?>

    <?php if (!empty($is_updated)) : ?>
        <div class="so-notice is-success" role="status"><?php esc_html_e('Settings saved successfully.', 'super-optimizer'); ?></div>
    <?php endif; ?>

    <div class="so-page-title">
        <h1><?php esc_html_e('Settings & Optimization Rules', 'super-optimizer'); ?></h1>
        <p>
            <?php esc_html_e('Configure image quality, next-gen WebP delivery, and automated processing. To optimize existing images, visit', 'super-optimizer'); ?>
            <a href="<?php echo esc_url($bulk_url); ?>"><?php esc_html_e('Bulk Optimizer &rarr;', 'super-optimizer'); ?></a>
        </p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('super_optimizer_settings_nonce'); ?>
        <input type="hidden" name="super_optimizer_save_settings" value="1">
        <input type="hidden" name="redirect_page" value="super-optimizer-settings">

        <!-- 1. Curated Compression Quality Tiers (No Raw Dangerous Sliders) -->
        <section class="so-card" aria-labelledby="so-set-compression">
            <div class="so-card-head">
                <div>
                    <span class="so-eyebrow"><?php esc_html_e('Visual Quality & Compression', 'super-optimizer'); ?></span>
                    <h2 id="so-set-compression"><?php esc_html_e('Compression Mode', 'super-optimizer'); ?></h2>
                    <p><?php esc_html_e('Select a quality tier. All modes use smart chroma preservation to strictly prevent pixelation, blur, or compression artifacts.', 'super-optimizer'); ?></p>
                </div>
            </div>

            <div class="so-tier-grid" role="radiogroup" aria-label="<?php esc_attr_e('Compression quality tier', 'super-optimizer'); ?>">
                <!-- Tier: Balanced -->
                <label class="so-tier-card<?php echo $current_level === 'balanced' ? ' is-selected' : ''; ?>">
                    <input type="radio" name="settings[compression_level]" value="balanced" <?php checked($current_level, 'balanced'); ?>>
                    <div class="so-tier-header">
                        <span class="so-tier-title"><?php esc_html_e('Balanced', 'super-optimizer'); ?></span>
                        <span class="so-tier-badge"><?php esc_html_e('Recommended', 'super-optimizer'); ?></span>
                    </div>
                    <p class="so-tier-desc"><?php esc_html_e('The sweet spot for websites. Visually indistinguishable from original images with optimal file reduction.', 'super-optimizer'); ?></p>
                    <div class="so-tier-spec">
                        <span>JPEG 85% &bull; WebP 82% &bull; Lossless PNG</span>
                    </div>
                </label>

                <!-- Tier: Maximum Quality -->
                <label class="so-tier-card<?php echo $current_level === 'safe' ? ' is-selected' : ''; ?>">
                    <input type="radio" name="settings[compression_level]" value="safe" <?php checked($current_level, 'safe'); ?>>
                    <div class="so-tier-header">
                        <span class="so-tier-title"><?php esc_html_e('Maximum Quality', 'super-optimizer'); ?></span>
                    </div>
                    <p class="so-tier-desc"><?php esc_html_e('Conservative optimization keeping maximum color depth. Best for photography portfolios, fine art, and retina displays.', 'super-optimizer'); ?></p>
                    <div class="so-tier-spec">
                        <span>JPEG 90% &bull; WebP 88% &bull; Lossless PNG</span>
                    </div>
                </label>

                <!-- Tier: Maximum Compression -->
                <label class="so-tier-card<?php echo $current_level === 'smaller' ? ' is-selected' : ''; ?>">
                    <input type="radio" name="settings[compression_level]" value="smaller" <?php checked($current_level, 'smaller'); ?>>
                    <div class="so-tier-header">
                        <span class="so-tier-title"><?php esc_html_e('Maximum Compression', 'super-optimizer'); ?></span>
                    </div>
                    <p class="so-tier-desc"><?php esc_html_e('Highest file savings while strictly maintaining a guarded quality floor to prevent visible degradation.', 'super-optimizer'); ?></p>
                    <div class="so-tier-spec">
                        <span>JPEG 80% &bull; WebP 76% &bull; Lossless PNG</span>
                    </div>
                </label>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <label class="so-row-label" for="selected_engine"><?php esc_html_e('Graphics Engine', 'super-optimizer'); ?></label>
                    <span class="so-row-desc"><?php esc_html_e('Server library used to compress images. Automatic selects the best available engine.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control">
                    <select id="selected_engine" name="settings[selected_engine]">
                        <option value="auto" <?php selected($settings['selected_engine'] ?? 'auto', 'auto'); ?>><?php esc_html_e('Automatic (Recommended)', 'super-optimizer'); ?></option>
                        <option value="imagick" <?php selected($settings['selected_engine'] ?? 'auto', 'imagick'); ?>><?php esc_html_e('ImageMagick (Imagick)', 'super-optimizer'); ?></option>
                        <option value="gd" <?php selected($settings['selected_engine'] ?? 'auto', 'gd'); ?>><?php esc_html_e('GD Library', 'super-optimizer'); ?></option>
                    </select>
                </div>
            </div>
        </section>

        <!-- 2. Dimensions & Metadata -->
        <section class="so-card" aria-labelledby="so-set-uploads">
            <div class="so-card-head">
                <div>
                    <span class="so-eyebrow"><?php esc_html_e('Automation & Sizing', 'super-optimizer'); ?></span>
                    <h2 id="so-set-uploads"><?php esc_html_e('Upload Processing', 'super-optimizer'); ?></h2>
                    <p><?php esc_html_e('Automated actions executed when new images are uploaded to the Media Library.', 'super-optimizer'); ?></p>
                </div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <span class="so-row-label"><?php esc_html_e('Auto-Optimize on Upload', 'super-optimizer'); ?></span>
                    <span class="so-row-desc"><?php esc_html_e('Automatically compress newly uploaded images and create WebP versions in the background.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control"><?php $so_switch('optimize_on_upload', $settings['optimize_on_upload'] ?? 1); ?></div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <span class="so-row-label"><?php esc_html_e('Remove Camera Metadata (EXIF/GPS)', 'super-optimizer'); ?></span>
                    <span class="so-row-desc"><?php esc_html_e('Strips bulky camera data, GPS tags, and comments. Essential ICC color profiles are always preserved.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control"><?php $so_switch('remove_metadata', $settings['remove_metadata']); ?></div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <span class="so-row-label"><?php esc_html_e('Auto-Downscale Large Uploads', 'super-optimizer'); ?></span>
                    <span class="so-row-desc"><?php esc_html_e('Safely resizes oversized camera uploads prior to thumbnail generation.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control">
                    <label class="so-check-inline">
                        <input type="checkbox" name="settings[auto_resize]" value="1" <?php checked(!empty($settings['auto_resize'])); ?>>
                        <span><?php esc_html_e('Enable resizing to:', 'super-optimizer'); ?></span>
                    </label>
                    <input type="number" id="max_width" name="settings[max_width]" value="<?php echo esc_attr($settings['max_width']); ?>" min="800" max="6000" step="10" aria-label="<?php esc_attr_e('Maximum width in pixels', 'super-optimizer'); ?>">
                    <span>&times;</span>
                    <input type="number" id="max_height" name="settings[max_height]" value="<?php echo esc_attr($settings['max_height']); ?>" min="800" max="6000" step="10" aria-label="<?php esc_attr_e('Maximum height in pixels', 'super-optimizer'); ?>">
                    <span><?php esc_html_e('px', 'super-optimizer'); ?></span>
                </div>
            </div>
        </section>

        <!-- 3. WebP Delivery -->
        <section class="so-card" aria-labelledby="so-set-webp">
            <div class="so-card-head">
                <div>
                    <span class="so-eyebrow"><?php esc_html_e('Next-Generation Formats', 'super-optimizer'); ?></span>
                    <h2 id="so-set-webp"><?php esc_html_e('WebP Delivery', 'super-optimizer'); ?></h2>
                    <p><?php esc_html_e('Deliver lightweight WebP images to supported browsers while keeping fallback originals for older devices.', 'super-optimizer'); ?></p>
                </div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <span class="so-row-label"><?php esc_html_e('Generate WebP Images', 'super-optimizer'); ?></span>
                    <span class="so-row-desc"><?php esc_html_e('Create WebP siblings when compressing images. WebP files are only kept if they are genuinely smaller than originals.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control"><?php $so_switch('convert_webp', $settings['convert_webp']); ?></div>
            </div>

            <div class="so-radio-list" role="radiogroup" aria-label="<?php esc_attr_e('How WebP files are delivered', 'super-optimizer'); ?>">
                <span class="so-row-label"><?php esc_html_e('WebP Serving Mechanism', 'super-optimizer'); ?></span>
                <label class="so-radio">
                    <input type="radio" name="settings[webp_delivery_method]" value="picture" <?php checked($settings['webp_delivery_method'], 'picture'); ?>>
                    <div>
                        <strong><?php esc_html_e('Picture Tags (Zero-config HTML rewriting)', 'super-optimizer'); ?></strong>
                        <span><?php esc_html_e('Recommended. Replaces <img> with <picture> tags automatically. Works on all web servers without .htaccess or Nginx edits.', 'super-optimizer'); ?></span>
                    </div>
                </label>
                <label class="so-radio">
                    <input type="radio" name="settings[webp_delivery_method]" value="rewrite" <?php checked($settings['webp_delivery_method'], 'rewrite'); ?>>
                    <div>
                        <strong><?php esc_html_e('Server Rewrite Rules (.htaccess / Nginx)', 'super-optimizer'); ?></strong>
                        <span><?php esc_html_e('Swaps image requests with WebP directly at the server level without altering HTML markup.', 'super-optimizer'); ?></span>
                    </div>
                </label>
                <label class="so-radio">
                    <input type="radio" name="settings[webp_delivery_method]" value="disabled" <?php checked($settings['webp_delivery_method'], 'disabled'); ?>>
                    <div>
                        <strong><?php esc_html_e('Disabled (Files Only)', 'super-optimizer'); ?></strong>
                        <span><?php esc_html_e('Generate WebP files on disk without serving them automatically (for custom theme templates or CDNs).', 'super-optimizer'); ?></span>
                    </div>
                </label>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <label class="so-row-label" for="webp_exclusions"><?php esc_html_e('WebP Delivery Exclusions', 'super-optimizer'); ?></label>
                    <span class="so-row-desc"><?php esc_html_e('One exclusion per line. Matches image URLs, classes, or entire URLs with page:/checkout/.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control is-block">
                    <textarea id="webp_exclusions" name="settings[webp_exclusions]" rows="3"><?php echo esc_textarea($settings['webp_exclusions']); ?></textarea>
                </div>
            </div>
        </section>

        <!-- 4. Native Lazy Loading -->
        <section class="so-card" aria-labelledby="so-set-lazy">
            <div class="so-card-head">
                <div>
                    <span class="so-eyebrow"><?php esc_html_e('Performance & Core Web Vitals', 'super-optimizer'); ?></span>
                    <h2 id="so-set-lazy"><?php esc_html_e('Native Lazy Loading', 'super-optimizer'); ?></h2>
                    <p><?php esc_html_e('Defer off-screen images until visitors scroll near them, boosting page load speeds and initial render.', 'super-optimizer'); ?></p>
                </div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <span class="so-row-label"><?php esc_html_e('Enable Native Lazy Loading', 'super-optimizer'); ?></span>
                    <span class="so-row-desc"><?php esc_html_e('Applies browser-native loading="lazy" decoding="async" to images.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control"><?php $so_switch('lazy_load', $settings['lazy_load']); ?></div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <label class="so-row-label" for="lazy_load_above_fold"><?php esc_html_e('Above-the-Fold Exemption (LCP)', 'super-optimizer'); ?></label>
                    <span class="so-row-desc"><?php esc_html_e('Number of leading images on a page to load immediately for maximum Core Web Vitals LCP score.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control">
                    <input type="number" id="lazy_load_above_fold" name="settings[lazy_load_above_fold]" value="<?php echo esc_attr($settings['lazy_load_above_fold']); ?>" min="0" max="20">
                    <span><?php esc_html_e('images', 'super-optimizer'); ?></span>
                </div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <label class="so-row-label" for="lazy_load_exclusions"><?php esc_html_e('Lazy Loading Exclusions', 'super-optimizer'); ?></label>
                    <span class="so-row-desc"><?php esc_html_e('One exclusion per line. Matches classes (e.g. hero-logo), filenames, or page:/path/.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control is-block">
                    <textarea id="lazy_load_exclusions" name="settings[lazy_load_exclusions]" rows="3"><?php echo esc_textarea($settings['lazy_load_exclusions']); ?></textarea>
                </div>
            </div>
        </section>

        <!-- 5. Bulk Runner Options & Maintenance (Moved here from optimize page) -->
        <section class="so-card" aria-labelledby="so-set-bulk-options">
            <div class="so-card-head">
                <div>
                    <span class="so-eyebrow"><?php esc_html_e('Bulk Engine & Maintenance', 'super-optimizer'); ?></span>
                    <h2 id="so-set-bulk-options"><?php esc_html_e('Bulk Optimization Preferences', 'super-optimizer'); ?></h2>
                    <p><?php esc_html_e('Defaults used when running the Bulk Optimizer across your media library.', 'super-optimizer'); ?></p>
                </div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <span class="so-row-label"><?php esc_html_e('Force Re-optimize All Images', 'super-optimizer'); ?></span>
                    <span class="so-row-desc"><?php esc_html_e('When enabled, the Bulk Optimizer will re-scan and re-compress images even if they were previously optimized.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control"><?php $so_switch('bulk_force_reoptimize', $settings['bulk_force_reoptimize']); ?></div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <span class="so-row-label"><?php esc_html_e('WebP Only Mode', 'super-optimizer'); ?></span>
                    <span class="so-row-desc"><?php esc_html_e('Leave original image files untouched on disk and only generate next-generation WebP copies.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control"><?php $so_switch('bulk_webp_only', $settings['bulk_webp_only']); ?></div>
            </div>

            <div class="so-row">
                <div class="so-row-info">
                    <label class="so-row-label" for="bulk_pause_seconds"><?php esc_html_e('Server Pause Between Images', 'super-optimizer'); ?></label>
                    <span class="so-row-desc"><?php esc_html_e('Adds a brief pause between each optimized image to keep server CPU and memory usage very low.', 'super-optimizer'); ?></span>
                </div>
                <div class="so-row-control">
                    <select id="bulk_pause_seconds" name="settings[bulk_pause_seconds]">
                        <option value="0" <?php selected((int) ($settings['bulk_pause_seconds'] ?? 0), 0); ?>><?php esc_html_e('None (Fastest)', 'super-optimizer'); ?></option>
                        <option value="1" <?php selected((int) ($settings['bulk_pause_seconds'] ?? 0), 1); ?>><?php esc_html_e('1 second (Recommended for shared hosting)', 'super-optimizer'); ?></option>
                        <option value="2" <?php selected((int) ($settings['bulk_pause_seconds'] ?? 0), 2); ?>><?php esc_html_e('2 seconds (Ultra-light on CPU)', 'super-optimizer'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Danger Zone: Reset Index -->
            <div class="so-danger-box">
                <div class="so-danger-info">
                    <h3><?php esc_html_e('Reset Optimization Records', 'super-optimizer'); ?></h3>
                    <p><?php esc_html_e('Resets the plugin database index so all images are marked as unoptimized. Your image files are never deleted.', 'super-optimizer'); ?></p>
                </div>
                <button type="button" class="so-btn so-btn-danger" id="btn-reset-index">
                    <?php esc_html_e('Reset Records', 'super-optimizer'); ?>
                </button>
            </div>
        </section>

        <!-- 6. System Information -->
        <section class="so-card" aria-labelledby="so-set-system">
            <div class="so-card-head">
                <div>
                    <span class="so-eyebrow"><?php esc_html_e('Diagnostics', 'super-optimizer'); ?></span>
                    <h2 id="so-set-system"><?php esc_html_e('Host & Environment', 'super-optimizer'); ?></h2>
                    <p><?php esc_html_e('System specifications for technical support and performance verification.', 'super-optimizer'); ?></p>
                </div>
            </div>
            <dl class="so-specs">
                <div><dt><?php esc_html_e('Active Engine', 'super-optimizer'); ?></dt><dd><?php echo esc_html($diagnostics['active_engine_name']); ?></dd></div>
                <div><dt><?php esc_html_e('PHP Version', 'super-optimizer'); ?></dt><dd>PHP <?php echo esc_html($diagnostics['php_version']); ?></dd></div>
                <div><dt><?php esc_html_e('Memory Limit', 'super-optimizer'); ?></dt><dd><?php echo esc_html($diagnostics['memory_limit']); ?></dd></div>
                <div><dt><?php esc_html_e('WebP Support', 'super-optimizer'); ?></dt><dd><?php echo ($diagnostics['imagick_webp'] || $diagnostics['gd_webp']) ? esc_html__('Active', 'super-optimizer') : esc_html__('Not Available', 'super-optimizer'); ?></dd></div>
                <div><dt><?php esc_html_e('Items Table', 'super-optimizer'); ?></dt><dd><?php echo esc_html(\SuperOptimizer\Database\Schema::get_items_table()); ?></dd></div>
                <div><dt><?php esc_html_e('Subsizes Table', 'super-optimizer'); ?></dt><dd><?php echo esc_html(\SuperOptimizer\Database\Schema::get_subsizes_table()); ?></dd></div>
            </dl>
        </section>

        <!-- Sticky Save Bar -->
        <div class="so-savebar">
            <p><?php esc_html_e('Unsaved changes will not take effect until you save.', 'super-optimizer'); ?></p>
            <button type="submit" class="so-btn so-btn-primary"><?php esc_html_e('Save Changes', 'super-optimizer'); ?></button>
        </div>
    </form>
</div>
