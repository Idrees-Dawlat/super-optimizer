<?php
/**
 * Settings -> Super Optimizer View
 *
 * Implements clean WordPress native form-table layout matching user's requested specification.
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$bulk_url = admin_url('upload.php?page=super-optimizer-bulk');
?>

<div class="wrap so-page-wrap">
    <div class="so-header-strip">
        <div class="so-header-brand">
            <span class="so-brand-name"><?php esc_html_e('Super Optimizer Settings', 'super-optimizer'); ?></span>
            <span class="so-engine-badge"><?php echo esc_html($diagnostics['active_engine_name']); ?></span>
        </div>
        <div class="so-header-actions">
            <a href="<?php echo esc_url($bulk_url); ?>" class="button button-primary">
                <?php esc_html_e('Bulk Optimize', 'super-optimizer'); ?>
            </a>
        </div>
    </div>

    <?php if (!empty($is_updated)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'super-optimizer'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('super_optimizer_settings_nonce'); ?>
        <input type="hidden" name="super_optimizer_save_settings" value="1">
        <input type="hidden" name="redirect_page" value="super-optimizer-settings">

        <!-- Section 1: Dimensions & Metadata -->
        <h2 class="title"><?php esc_html_e('Image Sizing & Metadata', 'super-optimizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Remove Metadata', 'super-optimizer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="settings[remove_metadata]" value="1" <?php checked($settings['remove_metadata'], 1); ?>>
                            <?php esc_html_e('This will remove ALL metadata: EXIF, comments, and camera tags. Essential ICC color profiles are strictly preserved.', 'super-optimizer'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Max Image Dimensions', 'super-optimizer'); ?></th>
                    <td>
                        <label for="max_width"><?php esc_html_e('Max Width', 'super-optimizer'); ?></label>
                        <input type="number" id="max_width" name="settings[max_width]" value="<?php echo esc_attr($settings['max_width']); ?>" class="small-text" min="400" max="6000" step="10">
                        &nbsp;&nbsp;
                        <label for="max_height"><?php esc_html_e('Max Height', 'super-optimizer'); ?></label>
                        <input type="number" id="max_height" name="settings[max_height]" value="<?php echo esc_attr($settings['max_height']); ?>" class="small-text" min="400" max="6000" step="10">
                        <span><?php esc_html_e('in pixels', 'super-optimizer'); ?></span>
                        <p class="description">
                            <?php esc_html_e('Limit full-size images to these dimensions. Overrides default WordPress threshold of 2560px. Use the Bulk Optimizer for existing uploads.', 'super-optimizer'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Auto-Optimize on Upload', 'super-optimizer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="settings[optimize_on_upload]" value="1" <?php checked($settings['optimize_on_upload'] ?? 1, 1); ?>>
                            <?php esc_html_e('Automatically compress newly uploaded images and create WebP siblings in the background.', 'super-optimizer'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Section 2: Lazy Load -->
        <h2 class="title"><?php esc_html_e('Native Lazy Loading', 'super-optimizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Lazy Load', 'super-optimizer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="settings[lazy_load]" value="1" <?php checked($settings['lazy_load'], 1); ?>>
                            <?php esc_html_e('Improves actual and perceived loading time as images will be loaded only as they enter the viewport.', 'super-optimizer'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Above the Fold', 'super-optimizer'); ?></th>
                    <td>
                        <input type="number" id="lazy_load_above_fold" name="settings[lazy_load_above_fold]" value="<?php echo esc_attr($settings['lazy_load_above_fold']); ?>" class="small-text" min="0" max="10">
                        <p class="description">
                            <?php esc_html_e('Skip this many images from lazy loading so that above the fold images load immediately for superior Core Web Vitals LCP score.', 'super-optimizer'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Lazy Load Exclusions', 'super-optimizer'); ?></th>
                    <td>
                        <textarea name="settings[lazy_load_exclusions]" rows="4" cols="50" class="large-text code"><?php echo esc_textarea($settings['lazy_load_exclusions']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('One exclusion per line, no wildcards (*) needed. Use any string matching desired elements or page:/path/ syntax.', 'super-optimizer'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Section 3: WebP Conversion & Delivery -->
        <h2 class="title"><?php esc_html_e('Next-Gen WebP Conversion & Delivery', 'super-optimizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('WebP Conversion', 'super-optimizer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="settings[convert_webp]" value="1" <?php checked($settings['convert_webp'], 1); ?>>
                            <?php esc_html_e('Convert your images to the next generation format for supported browsers, while retaining originals for other browsers.', 'super-optimizer'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('WebP images will be generated automatically for new uploads. Use the Bulk Optimizer for existing uploads.', 'super-optimizer'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('WebP Delivery Method', 'super-optimizer'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="settings[webp_delivery_method]" value="picture" <?php checked($settings['webp_delivery_method'], 'picture'); ?>>
                                <strong><?php esc_html_e('Picture WebP Rewriting', 'super-optimizer'); ?></strong>
                                <span class="description"><?php esc_html_e('— Zero-config HTML rewriting replacing <img> with <picture> tags.', 'super-optimizer'); ?></span>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="settings[webp_delivery_method]" value="rewrite" <?php checked($settings['webp_delivery_method'], 'rewrite'); ?>>
                                <strong><?php esc_html_e('Server Rewrite Rules (.htaccess / Nginx)', 'super-optimizer'); ?></strong>
                                <span class="description"><?php esc_html_e('— Native server redirect serving WebP transparently without HTML alteration.', 'super-optimizer'); ?></span>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="settings[webp_delivery_method]" value="disabled" <?php checked($settings['webp_delivery_method'], 'disabled'); ?>>
                                <strong><?php esc_html_e('Disabled', 'super-optimizer'); ?></strong>
                                <span class="description"><?php esc_html_e('— Only generate sibling files on disk.', 'super-optimizer'); ?></span>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('WebP Exclusions', 'super-optimizer'); ?></th>
                    <td>
                        <textarea name="settings[webp_exclusions]" rows="4" cols="50" class="large-text code"><?php echo esc_textarea($settings['webp_exclusions']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('One exclusion per line. Matches image URLs, element attributes, or pages using page:/xyz/ syntax.', 'super-optimizer'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Section 4: Compression Tuning -->
        <h2 class="title"><?php esc_html_e('Compression Quality Levels', 'super-optimizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="quality_jpeg"><?php esc_html_e('JPG Optimization Quality', 'super-optimizer'); ?></label></th>
                    <td>
                        <input type="range" id="quality_jpeg" name="settings[quality_jpeg]" min="50" max="100" value="<?php echo esc_attr($settings['quality_jpeg']); ?>" class="so-slider" oninput="this.nextElementSibling.value = this.value + '%'">
                        <output class="so-slider-val"><?php echo esc_html($settings['quality_jpeg']); ?>%</output>
                        <p class="description"><?php esc_html_e('Visual lossy compression with 4:2:0 chroma subsampling. Recommended: 80% to 85%.', 'super-optimizer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="quality_png"><?php esc_html_e('PNG Optimization Quality', 'super-optimizer'); ?></label></th>
                    <td>
                        <input type="range" id="quality_png" name="settings[quality_png]" min="50" max="100" value="<?php echo esc_attr($settings['quality_png']); ?>" class="so-slider" oninput="this.nextElementSibling.value = this.value + '%'">
                        <output class="so-slider-val"><?php echo esc_html($settings['quality_png']); ?>%</output>
                        <p class="description"><?php esc_html_e('High-efficiency deflate compression. Recommended: 80% to 85%.', 'super-optimizer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="quality_webp"><?php esc_html_e('WebP Optimization Quality', 'super-optimizer'); ?></label></th>
                    <td>
                        <input type="range" id="quality_webp" name="settings[quality_webp]" min="50" max="100" value="<?php echo esc_attr($settings['quality_webp']); ?>" class="so-slider" oninput="this.nextElementSibling.value = this.value + '%'">
                        <output class="so-slider-val"><?php echo esc_html($settings['quality_webp']); ?>%</output>
                        <p class="description"><?php esc_html_e('Next-gen WebP quality sweet spot. Recommended: 80%.', 'super-optimizer'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Save Changes', 'super-optimizer')); ?>
    </form>
</div>
