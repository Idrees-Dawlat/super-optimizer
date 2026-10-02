<?php
/**
 * Main Admin View Template for Super Optimizer
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$tab = $current_tab ?? 'dashboard';
?>

<div class="wrap super-optimizer-wrap">
    <!-- Header Banner -->
    <header class="so-header">
        <div class="so-header-left">
            <div class="so-logo-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
            </div>
            <div>
                <h1 class="so-title"><?php esc_html_e('Super Optimizer', 'super-optimizer'); ?></h1>
                <p class="so-subtitle"><?php esc_html_e('High-Performance Local Image Compression & Next-Gen Delivery', 'super-optimizer'); ?></p>
            </div>
        </div>
        <div class="so-header-right">
            <div class="so-badge so-badge-engine">
                <span class="so-indicator so-indicator-online"></span>
                <span class="so-badge-label"><?php esc_html_e('Driver:', 'super-optimizer'); ?></span>
                <strong><?php echo esc_html($diagnostics['active_engine_name']); ?></strong>
            </div>
            <?php if ($diagnostics['imagick_webp'] || $diagnostics['gd_webp']) : ?>
                <div class="so-badge so-badge-feature">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>WebP Ready</span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($is_updated)) : ?>
        <div class="so-alert so-alert-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span><?php esc_html_e('Settings saved successfully.', 'super-optimizer'); ?></span>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <nav class="so-tabs">
        <a href="<?php echo esc_url(add_query_arg(['page' => 'super-optimizer', 'tab' => 'dashboard'], admin_url('admin.php'))); ?>"
           class="so-tab-btn <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            <?php esc_html_e('Dashboard & Bulk Optimizer', 'super-optimizer'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'super-optimizer', 'tab' => 'settings'], admin_url('admin.php'))); ?>"
           class="so-tab-btn <?php echo $tab === 'settings' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
            <?php esc_html_e('Optimization Settings', 'super-optimizer'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'super-optimizer', 'tab' => 'system'], admin_url('admin.php'))); ?>"
           class="so-tab-btn <?php echo $tab === 'system' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            <?php esc_html_e('System & Delivery', 'super-optimizer'); ?>
        </a>
    </nav>

    <!-- Tab 1: Dashboard & Bulk Optimizer -->
    <?php if ($tab === 'dashboard') : ?>
        <section class="so-tab-content">
            <!-- Metrics Grid -->
            <div class="so-metrics-grid">
                <div class="so-metric-card">
                    <div class="so-metric-icon so-icon-blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                    <div class="so-metric-data">
                        <span class="so-metric-label"><?php esc_html_e('Optimized Images', 'super-optimizer'); ?></span>
                        <div class="so-metric-value">
                            <span id="stat-optimized-count"><?php echo esc_html(number_format_i18n($stats['optimized_attachments'])); ?></span>
                            <span class="so-metric-sub">/ <?php echo esc_html(number_format_i18n($stats['total_library_images'])); ?></span>
                        </div>
                        <span class="so-metric-desc" id="stat-subsizes-desc">
                            <?php echo esc_html(sprintf(__('%s thumbnails optimized', 'super-optimizer'), number_format_i18n($stats['optimized_subsizes']))); ?>
                        </span>
                    </div>
                </div>

                <div class="so-metric-card">
                    <div class="so-metric-icon so-icon-emerald">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    </div>
                    <div class="so-metric-data">
                        <span class="so-metric-label"><?php esc_html_e('Disk Space Saved', 'super-optimizer'); ?></span>
                        <div class="so-metric-value" id="stat-bytes-saved">
                            <?php echo esc_html(\SuperOptimizer\Admin::format_bytes($stats['total_bytes_saved'])); ?>
                        </div>
                        <span class="so-metric-desc">
                            <?php echo esc_html(sprintf(__('from %s original total', 'super-optimizer'), \SuperOptimizer\Admin::format_bytes($stats['total_original_bytes']))); ?>
                        </span>
                    </div>
                </div>

                <div class="so-metric-card">
                    <div class="so-metric-icon so-icon-purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div class="so-metric-data">
                        <span class="so-metric-label"><?php esc_html_e('Average Reduction', 'super-optimizer'); ?></span>
                        <div class="so-metric-value">
                            <span id="stat-percentage"><?php echo esc_html($stats['percentage_saved']); ?></span>%
                        </div>
                        <span class="so-metric-desc"><?php esc_html_e('Net byte reduction ratio', 'super-optimizer'); ?></span>
                    </div>
                </div>

                <div class="so-metric-card">
                    <div class="so-metric-icon so-icon-amber">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    </div>
                    <div class="so-metric-data">
                        <span class="so-metric-label"><?php esc_html_e('Next-Gen Assets', 'super-optimizer'); ?></span>
                        <div class="so-metric-value" id="stat-nextgen-count">
                            <?php echo esc_html(number_format_i18n($stats['webp_count'] + $stats['avif_count'])); ?>
                        </div>
                        <span class="so-metric-desc">
                            <?php echo esc_html(sprintf(__('%d WebP, %d AVIF siblings', 'super-optimizer'), $stats['webp_count'], $stats['avif_count'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bulk Processing Station -->
            <div class="so-card so-bulk-station">
                <div class="so-card-header">
                    <div>
                        <h2 class="so-card-title"><?php esc_html_e('Bulk Media Optimization Engine', 'super-optimizer'); ?></h2>
                        <p class="so-card-desc"><?php esc_html_e('Process your existing media library with isolated batch execution to ensure zero host timeouts and 100% memory stability.', 'super-optimizer'); ?></p>
                    </div>
                    <div class="so-status-pill" id="bulk-status-pill" data-status="idle">
                        <span class="so-pill-dot"></span>
                        <span class="so-pill-text"><?php esc_html_e('Idle', 'super-optimizer'); ?></span>
                    </div>
                </div>

                <div class="so-card-body">
                    <!-- Progress Container -->
                    <div class="so-progress-wrapper">
                        <div class="so-progress-header">
                            <span class="so-progress-title" id="bulk-progress-status"><?php esc_html_e('Queue status: Ready', 'super-optimizer'); ?></span>
                            <span class="so-progress-pct" id="bulk-progress-pct">0%</span>
                        </div>
                        <div class="so-progress-track">
                            <div class="so-progress-fill" id="bulk-progress-bar" style="width: 0%;"></div>
                        </div>
                        <div class="so-progress-meta">
                            <span id="bulk-progress-counts">0 / 0 images</span>
                            <span id="bulk-eta-text"></span>
                        </div>
                    </div>

                    <!-- Current Item Display -->
                    <div class="so-active-item" id="so-active-item" style="display: none;">
                        <span class="so-active-label"><?php esc_html_e('Currently processing:', 'super-optimizer'); ?></span>
                        <code class="so-active-filename" id="so-current-filename">-</code>
                        <span class="so-item-savings" id="so-current-savings"></span>
                    </div>

                    <!-- Controls -->
                    <div class="so-controls-row">
                        <div class="so-btn-group">
                            <button type="button" class="so-btn so-btn-primary" id="btn-start-bulk">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                <span><?php esc_html_e('Start Bulk Optimization', 'super-optimizer'); ?></span>
                            </button>
                            <button type="button" class="so-btn so-btn-secondary" id="btn-pause-bulk" style="display: none;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                                <span><?php esc_html_e('Pause', 'super-optimizer'); ?></span>
                            </button>
                            <button type="button" class="so-btn so-btn-primary" id="btn-resume-bulk" style="display: none;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                <span><?php esc_html_e('Resume', 'super-optimizer'); ?></span>
                            </button>
                        </div>

                        <div class="so-btn-group-right">
                            <button type="button" class="so-btn so-btn-outline so-btn-danger" id="btn-reset-index">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                                <span><?php esc_html_e('Reset Optimization Index', 'super-optimizer'); ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Live Stream Console -->
                <div class="so-console-panel">
                    <div class="so-console-header">
                        <div class="so-console-title">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>
                            <span><?php esc_html_e('Execution Log', 'super-optimizer'); ?></span>
                        </div>
                        <button type="button" class="so-console-clear" id="btn-clear-console"><?php esc_html_e('Clear Log', 'super-optimizer'); ?></button>
                    </div>
                    <div class="so-console-body" id="so-console-stream">
                        <div class="so-console-line so-line-info">
                            <span class="so-line-time">[<?php echo esc_html(current_time('H:i:s')); ?>]</span>
                            <span class="so-line-msg"><?php esc_html_e('Super Optimizer initialized. System ready for processing.', 'super-optimizer'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Tab 2: Settings -->
    <?php if ($tab === 'settings') : ?>
        <section class="so-tab-content">
            <form method="post" action="" class="so-settings-form">
                <?php wp_nonce_field('super_optimizer_settings_nonce'); ?>
                <input type="hidden" name="super_optimizer_save_settings" value="1">
                <input type="hidden" name="current_tab" value="settings">

                <div class="so-cards-stack">
                    <!-- Compression Tuning -->
                    <div class="so-card">
                        <div class="so-card-header">
                            <div>
                                <h2 class="so-card-title"><?php esc_html_e('Lossy Compression Quality', 'super-optimizer'); ?></h2>
                                <p class="so-card-desc"><?php esc_html_e('Tuned quality settings balancing visual clarity and byte savings. 80-82% is industry standard lossy sweet spot.', 'super-optimizer'); ?></p>
                            </div>
                        </div>
                        <div class="so-card-body">
                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label for="quality_jpeg" class="so-setting-label"><?php esc_html_e('JPEG Compression Quality', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Recommended: 80% to 85%', 'super-optimizer'); ?></span>
                                </div>
                                <div class="so-slider-control">
                                    <input type="range" id="quality_jpeg" name="settings[quality_jpeg]" min="50" max="100" value="<?php echo esc_attr($settings['quality_jpeg']); ?>" class="so-range-input" oninput="this.nextElementSibling.value = this.value">
                                    <output class="so-slider-bubble"><?php echo esc_html($settings['quality_jpeg']); ?></output>
                                    <span class="so-unit">%</span>
                                </div>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label for="quality_png" class="so-setting-label"><?php esc_html_e('PNG Compression Quality', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Recommended: 80% to 85%', 'super-optimizer'); ?></span>
                                </div>
                                <div class="so-slider-control">
                                    <input type="range" id="quality_png" name="settings[quality_png]" min="50" max="100" value="<?php echo esc_attr($settings['quality_png']); ?>" class="so-range-input" oninput="this.nextElementSibling.value = this.value">
                                    <output class="so-slider-bubble"><?php echo esc_html($settings['quality_png']); ?></output>
                                    <span class="so-unit">%</span>
                                </div>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label for="quality_webp" class="so-setting-label"><?php esc_html_e('WebP Quality', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Recommended: 78% to 82%', 'super-optimizer'); ?></span>
                                </div>
                                <div class="so-slider-control">
                                    <input type="range" id="quality_webp" name="settings[quality_webp]" min="50" max="100" value="<?php echo esc_attr($settings['quality_webp']); ?>" class="so-range-input" oninput="this.nextElementSibling.value = this.value">
                                    <output class="so-slider-bubble"><?php echo esc_html($settings['quality_webp']); ?></output>
                                    <span class="so-unit">%</span>
                                </div>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label for="quality_avif" class="so-setting-label"><?php esc_html_e('AVIF Quality', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Recommended: 70% to 75% due to ultra-high efficiency', 'super-optimizer'); ?></span>
                                </div>
                                <div class="so-slider-control">
                                    <input type="range" id="quality_avif" name="settings[quality_avif]" min="50" max="100" value="<?php echo esc_attr($settings['quality_avif']); ?>" class="so-range-input" oninput="this.nextElementSibling.value = this.value">
                                    <output class="so-slider-bubble"><?php echo esc_html($settings['quality_avif']); ?></output>
                                    <span class="so-unit">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Next-Gen Formats & Delivery -->
                    <div class="so-card">
                        <div class="so-card-header">
                            <div>
                                <h2 class="so-card-title"><?php esc_html_e('Next-Generation Formats', 'super-optimizer'); ?></h2>
                                <p class="so-card-desc"><?php esc_html_e('Generate modern high-density formats alongside original images for instant frontend delivery.', 'super-optimizer'); ?></p>
                            </div>
                        </div>
                        <div class="so-card-body">
                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label class="so-setting-label"><?php esc_html_e('Generate WebP Copies', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Creates sibling .webp files for every original and thumbnail size. Supported by 97%+ of browsers.', 'super-optimizer'); ?></span>
                                </div>
                                <label class="so-toggle-switch">
                                    <input type="checkbox" name="settings[convert_webp]" value="1" <?php checked($settings['convert_webp'], 1); ?>>
                                    <span class="so-toggle-slider"></span>
                                </label>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label class="so-setting-label"><?php esc_html_e('Generate AVIF Copies', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Creates sibling .avif files. Next-generation format offering ~20% higher compression than WebP.', 'super-optimizer'); ?></span>
                                </div>
                                <label class="so-toggle-switch">
                                    <input type="checkbox" name="settings[convert_avif]" value="1" <?php checked($settings['convert_avif'], 1); ?>>
                                    <span class="so-toggle-slider"></span>
                                </label>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label for="serve_webp" class="so-setting-label"><?php esc_html_e('Frontend Delivery Mechanism', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('How next-gen formats are served to visitors.', 'super-optimizer'); ?></span>
                                </div>
                                <div class="so-select-wrap">
                                    <select name="settings[serve_webp]" id="serve_webp" class="so-select-input">
                                        <option value="picture" <?php selected($settings['serve_webp'], 'picture'); ?>><?php esc_html_e('HTML Picture Tags (Recommended, Zero Server Config)', 'super-optimizer'); ?></option>
                                        <option value="rewrite" <?php selected($settings['serve_webp'], 'rewrite'); ?>><?php esc_html_e('Server Rewrite Rules (.htaccess / Nginx)', 'super-optimizer'); ?></option>
                                        <option value="disabled" <?php selected($settings['serve_webp'], 'disabled'); ?>><?php esc_html_e('Disabled (Only generate files)', 'super-optimizer'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auto-Resize & Lifecycle -->
                    <div class="so-card">
                        <div class="so-card-header">
                            <div>
                                <h2 class="so-card-title"><?php esc_html_e('Auto-Resize & Upload Pipeline', 'super-optimizer'); ?></h2>
                                <p class="so-card-desc"><?php esc_html_e('Prevent camera uploads from wasting disk space and strip unnecessary bloat.', 'super-optimizer'); ?></p>
                            </div>
                        </div>
                        <div class="so-card-body">
                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label class="so-setting-label"><?php esc_html_e('Auto-Resize Large Uploads', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Automatically downscales massive raw phone/camera photos down to max dimensions before creating thumbnails.', 'super-optimizer'); ?></span>
                                </div>
                                <label class="so-toggle-switch">
                                    <input type="checkbox" name="settings[auto_resize]" value="1" <?php checked($settings['auto_resize'], 1); ?>>
                                    <span class="so-toggle-slider"></span>
                                </label>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label class="so-setting-label"><?php esc_html_e('Max Dimension Bounds (Pixels)', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Images wider or taller than these limits will be proportionally scaled down.', 'super-optimizer'); ?></span>
                                </div>
                                <div class="so-dimensions-inputs">
                                    <div class="so-input-unit-group">
                                        <label for="max_width">W:</label>
                                        <input type="number" id="max_width" name="settings[max_width]" value="<?php echo esc_attr($settings['max_width']); ?>" class="so-num-input" min="500" max="8000" step="10">
                                        <span>px</span>
                                    </div>
                                    <span class="so-dim-separator">&times;</span>
                                    <div class="so-input-unit-group">
                                        <label for="max_height">H:</label>
                                        <input type="number" id="max_height" name="settings[max_height]" value="<?php echo esc_attr($settings['max_height']); ?>" class="so-num-input" min="500" max="8000" step="10">
                                        <span>px</span>
                                    </div>
                                </div>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label class="so-setting-label"><?php esc_html_e('Strip EXIF & Device Metadata', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Safely eliminates camera metadata, GPS tags, and software logs. ICC color profiles are strictly preserved.', 'super-optimizer'); ?></span>
                                </div>
                                <label class="so-toggle-switch">
                                    <input type="checkbox" name="settings[strip_metadata]" value="1" <?php checked($settings['strip_metadata'], 1); ?>>
                                    <span class="so-toggle-slider"></span>
                                </label>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label class="so-setting-label"><?php esc_html_e('Auto-Optimize on Upload', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Hook into media upload lifecycle to compress new images and generate WebP automatically in the background.', 'super-optimizer'); ?></span>
                                </div>
                                <label class="so-toggle-switch">
                                    <input type="checkbox" name="settings[optimize_on_upload]" value="1" <?php checked($settings['optimize_on_upload'], 1); ?>>
                                    <span class="so-toggle-slider"></span>
                                </label>
                            </div>

                            <div class="so-setting-row">
                                <div class="so-setting-meta">
                                    <label for="selected_engine" class="so-setting-label"><?php esc_html_e('Graphics Engine Driver', 'super-optimizer'); ?></label>
                                    <span class="so-setting-hint"><?php esc_html_e('Auto mode selects Imagick when available for highest fidelity.', 'super-optimizer'); ?></span>
                                </div>
                                <div class="so-select-wrap">
                                    <select name="settings[selected_engine]" id="selected_engine" class="so-select-input">
                                        <option value="auto" <?php selected($settings['selected_engine'], 'auto'); ?>><?php esc_html_e('Auto Detect (Recommended)', 'super-optimizer'); ?></option>
                                        <option value="imagick" <?php selected($settings['selected_engine'], 'imagick'); ?> <?php disabled(!$diagnostics['imagick_available']); ?>><?php esc_html_e('Imagick (ImageMagick)', 'super-optimizer'); ?></option>
                                        <option value="gd" <?php selected($settings['selected_engine'], 'gd'); ?> <?php disabled(!$diagnostics['gd_available']); ?>><?php esc_html_e('GD Library', 'super-optimizer'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="so-form-actions">
                    <button type="submit" class="so-btn so-btn-primary so-btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span><?php esc_html_e('Save Configuration', 'super-optimizer'); ?></span>
                    </button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <!-- Tab 3: System & Delivery -->
    <?php if ($tab === 'system') : ?>
        <section class="so-tab-content">
            <div class="so-cards-stack">
                <!-- System Diagnostics -->
                <div class="so-card">
                    <div class="so-card-header">
                        <div>
                            <h2 class="so-card-title"><?php esc_html_e('Host Environment & Graphics Subsystem', 'super-optimizer'); ?></h2>
                            <p class="so-card-desc"><?php esc_html_e('Hardware and PHP runtime diagnostics for image processing capabilities.', 'super-optimizer'); ?></p>
                        </div>
                    </div>
                    <div class="so-card-body">
                        <table class="so-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Component', 'super-optimizer'); ?></th>
                                    <th><?php esc_html_e('Status / Version', 'super-optimizer'); ?></th>
                                    <th><?php esc_html_e('Capabilities', 'super-optimizer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e('PHP Version', 'super-optimizer'); ?></strong></td>
                                    <td><code><?php echo esc_html($diagnostics['php_version']); ?></code></td>
                                    <td><span class="so-pill-success"><?php esc_html_e('Compatible', 'super-optimizer'); ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Memory Limit', 'super-optimizer'); ?></strong></td>
                                    <td><code><?php echo esc_html($diagnostics['memory_limit']); ?></code></td>
                                    <td><span class="so-hint"><?php esc_html_e('Adequate for bulk batch processing', 'super-optimizer'); ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Max Execution Time', 'super-optimizer'); ?></strong></td>
                                    <td><code><?php echo esc_html($diagnostics['max_execution_time']); ?></code></td>
                                    <td><span class="so-hint"><?php esc_html_e('Per-item batching circumvents limits', 'super-optimizer'); ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Imagick Extension', 'super-optimizer'); ?></strong></td>
                                    <td><?php echo esc_html($diagnostics['imagick_version']); ?></td>
                                    <td>
                                        <?php if ($diagnostics['imagick_available']) : ?>
                                            <span class="so-tag"><?php echo $diagnostics['imagick_webp'] ? 'WebP Supported' : 'No WebP'; ?></span>
                                            <span class="so-tag"><?php echo $diagnostics['imagick_avif'] ? 'AVIF Supported' : 'No AVIF'; ?></span>
                                        <?php else : ?>
                                            <span class="so-pill-warning"><?php esc_html_e('Not Available', 'super-optimizer'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('GD Extension', 'super-optimizer'); ?></strong></td>
                                    <td><?php echo esc_html($diagnostics['gd_version']); ?></td>
                                    <td>
                                        <?php if ($diagnostics['gd_available']) : ?>
                                            <span class="so-tag"><?php echo $diagnostics['gd_webp'] ? 'WebP Supported' : 'No WebP'; ?></span>
                                            <span class="so-tag"><?php echo $diagnostics['gd_avif'] ? 'AVIF Supported' : 'No AVIF'; ?></span>
                                        <?php else : ?>
                                            <span class="so-pill-warning"><?php esc_html_e('Not Available', 'super-optimizer'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Relational Database Schema Health -->
                <div class="so-card">
                    <div class="so-card-header">
                        <div>
                            <h2 class="so-card-title"><?php esc_html_e('Relational Database Schema', 'super-optimizer'); ?></h2>
                            <p class="so-card-desc"><?php esc_html_e('Dedicated indexing tables isolating optimization state from core wp_posts/wp_postmeta.', 'super-optimizer'); ?></p>
                        </div>
                    </div>
                    <div class="so-card-body">
                        <table class="so-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Table Name', 'super-optimizer'); ?></th>
                                    <th><?php esc_html_e('Entity Role', 'super-optimizer'); ?></th>
                                    <th><?php esc_html_e('Indexed Keys', 'super-optimizer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php echo esc_html(\SuperOptimizer\Database\Schema::get_items_table()); ?></code></td>
                                    <td><?php esc_html_e('Master Attachment Entity (1:1 with media item)', 'super-optimizer'); ?></td>
                                    <td><code>uq_attachment_id, idx_status, idx_bytes_saved</code></td>
                                </tr>
                                <tr>
                                    <td><code><?php echo esc_html(\SuperOptimizer\Database\Schema::get_subsizes_table()); ?></code></td>
                                    <td><?php esc_html_e('Subsizes Entity (1:Many child crops & WebP paths)', 'super-optimizer'); ?></td>
                                    <td><code>idx_item_id, idx_attachment_size, idx_status</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- WebP Rewrite Rules Helpers -->
                <div class="so-card">
                    <div class="so-card-header">
                        <div>
                            <h2 class="so-card-title"><?php esc_html_e('Server Rewrite Configuration', 'super-optimizer'); ?></h2>
                            <p class="so-card-desc"><?php esc_html_e('If choosing Rewrite mode instead of Picture tags, add these directives to your web server config.', 'super-optimizer'); ?></p>
                        </div>
                    </div>
                    <div class="so-card-body">
                        <div class="so-code-section">
                            <span class="so-code-title">Apache / LiteSpeed (.htaccess)</span>
                            <pre class="so-code-block"><code><?php echo esc_html(\SuperOptimizer\Delivery::get_htaccess_rules()); ?></code></pre>
                        </div>

                        <div class="so-code-section" style="margin-top: 20px;">
                            <span class="so-code-title">Nginx (Server Block)</span>
                            <pre class="so-code-block"><code><?php echo esc_html(\SuperOptimizer\Delivery::get_nginx_rules()); ?></code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>
