<?php
/**
 * Super Optimizer - Unified Cockpit Admin View
 *
 * Single-page high-density dashboard engineered for speed, clarity, and control.
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$time_saved = $stats['time_saved_formatted'] ?? '0 ms';
$saved_bytes_fmt = \SuperOptimizer\Admin::format_bytes($stats['total_bytes_saved']);
$orig_bytes_fmt  = \SuperOptimizer\Admin::format_bytes($stats['total_original_bytes']);
?>

<div class="wrap so-workbench">
    <?php if (!empty($is_updated)) : ?>
        <div class="so-toast so-toast-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span><?php esc_html_e('Optimization settings updated successfully.', 'super-optimizer'); ?></span>
        </div>
    <?php endif; ?>

    <!-- Master Unified Container -->
    <div class="so-canvas">
        <!-- Top Utility Bar -->
        <header class="so-topbar">
            <div class="so-brand">
                <div class="so-brand-mark">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 3v18"></path>
                        <path d="m4.93 4.93 14.14 14.14"></path>
                    </svg>
                </div>
                <div class="so-brand-text">
                    <div class="so-brand-row">
                        <span class="so-brand-title">Super Optimizer</span>
                        <span class="so-brand-version">v<?php echo esc_html(SUPER_OPTIMIZER_VERSION); ?></span>
                    </div>
                    <span class="so-brand-caption"><?php esc_html_e('High-Performance Local Image Engine', 'super-optimizer'); ?></span>
                </div>
            </div>

            <div class="so-system-status">
                <div class="so-chip">
                    <span class="so-chip-dot so-chip-dot-active"></span>
                    <span class="so-chip-label"><?php echo esc_html($diagnostics['active_engine_name']); ?></span>
                </div>
                <?php if ($diagnostics['imagick_webp'] || $diagnostics['gd_webp']) : ?>
                    <div class="so-chip so-chip-accent">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>WebP Enabled</span>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Primary Telemetry Strip: 4 Unified Columns with Zero Pastel Squares -->
        <section class="so-telemetry">
            <!-- 1. TIME SAVED METRIC -->
            <div class="so-telemetry-cell so-highlight-cell">
                <div class="so-cell-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span><?php esc_html_e('Est. Page Load Time Saved', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-main">
                    <span class="so-num-highlight" id="stat-time-saved"><?php echo esc_html($time_saved); ?></span>
                </div>
                <div class="so-cell-sub">
                    <span id="stat-time-desc"><?php esc_html_e('Faster delivery on 4G / Mobile', 'super-optimizer'); ?></span>
                </div>
            </div>

            <!-- 2. STORAGE / BANDWIDTH SAVED -->
            <div class="so-telemetry-cell">
                <div class="so-cell-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span><?php esc_html_e('Disk & Bandwidth Saved', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-main">
                    <span class="so-num" id="stat-bytes-saved"><?php echo esc_html($saved_bytes_fmt); ?></span>
                    <span class="so-metric-badge" id="stat-percentage-badge">-<span id="stat-percentage"><?php echo esc_html($stats['percentage_saved']); ?></span>%</span>
                </div>
                <div class="so-cell-sub">
                    <span><?php echo esc_html(sprintf(__('from %s original payload', 'super-optimizer'), $orig_bytes_fmt)); ?></span>
                </div>
            </div>

            <!-- 3. MEDIA ATTACHMENTS OPTIMIZED -->
            <div class="so-telemetry-cell">
                <div class="so-cell-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <span><?php esc_html_e('Media Library Progress', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-main">
                    <span class="so-num" id="stat-optimized-count"><?php echo esc_html(number_format_i18n($stats['optimized_attachments'])); ?></span>
                    <span class="so-num-total">/ <?php echo esc_html(number_format_i18n($stats['total_library_images'])); ?></span>
                </div>
                <div class="so-cell-sub">
                    <span id="stat-subsizes-desc"><?php echo esc_html(sprintf(__('%s thumbnails converted', 'super-optimizer'), number_format_i18n($stats['optimized_subsizes']))); ?></span>
                </div>
            </div>

            <!-- 4. NEXT-GEN WEBP SIBLINGS -->
            <div class="so-telemetry-cell">
                <div class="so-cell-header">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    <span><?php esc_html_e('Next-Gen Assets', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-main">
                    <span class="so-num" id="stat-nextgen-count"><?php echo esc_html(number_format_i18n($stats['webp_count'] + $stats['avif_count'])); ?></span>
                </div>
                <div class="so-cell-sub">
                    <span><?php echo esc_html(sprintf(__('%d WebP, %d AVIF siblings', 'super-optimizer'), $stats['webp_count'], $stats['avif_count'])); ?></span>
                </div>
            </div>
        </section>

        <!-- Section 1: Optimization Action Deck -->
        <section class="so-deck">
            <div class="so-deck-main">
                <div class="so-deck-info">
                    <h2 class="so-deck-title"><?php esc_html_e('Bulk Engine & Media Queue', 'super-optimizer'); ?></h2>
                    <p class="so-deck-subtitle"><?php esc_html_e('Processes files one-by-one via asynchronous AJAX to eliminate server timeouts and memory limits.', 'super-optimizer'); ?></p>
                </div>

                <div class="so-actions-row">
                    <button type="button" class="so-btn so-btn-execute" id="btn-start-bulk">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        <span><?php esc_html_e('Start Bulk Optimization', 'super-optimizer'); ?></span>
                    </button>
                    <button type="button" class="so-btn so-btn-neutral" id="btn-pause-bulk" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                        <span><?php esc_html_e('Pause', 'super-optimizer'); ?></span>
                    </button>
                    <button type="button" class="so-btn so-btn-execute" id="btn-resume-bulk" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        <span><?php esc_html_e('Resume', 'super-optimizer'); ?></span>
                    </button>
                    <button type="button" class="so-btn so-btn-ghost" id="btn-reset-index">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                        <span><?php esc_html_e('Re-index Library', 'super-optimizer'); ?></span>
                    </button>
                </div>
            </div>

            <!-- Progress Deck -->
            <div class="so-meter-panel">
                <div class="so-meter-meta">
                    <span class="so-meter-status" id="bulk-progress-status"><?php esc_html_e('Queue ready.', 'super-optimizer'); ?></span>
                    <span class="so-meter-pct" id="bulk-progress-pct">0%</span>
                </div>
                <div class="so-meter-track">
                    <div class="so-meter-fill" id="bulk-progress-bar" style="width: 0%;"></div>
                </div>
                <div class="so-meter-footer">
                    <span class="so-meter-counts" id="bulk-progress-counts">0 / 0 images</span>
                    <div class="so-active-ticker" id="so-active-item" style="display: none;">
                        <span class="so-ticker-label"><?php esc_html_e('Working on:', 'super-optimizer'); ?></span>
                        <span class="so-ticker-file" id="so-current-filename">-</span>
                        <span class="so-ticker-savings" id="so-current-savings"></span>
                    </div>
                </div>
            </div>

            <!-- Integrated Terminal Log -->
            <div class="so-terminal">
                <div class="so-terminal-bar">
                    <div class="so-terminal-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>
                        <span><?php esc_html_e('Activity Log', 'super-optimizer'); ?></span>
                    </div>
                    <button type="button" class="so-terminal-clear" id="btn-clear-console"><?php esc_html_e('Clear', 'super-optimizer'); ?></button>
                </div>
                <div class="so-terminal-feed" id="so-console-stream">
                    <div class="so-feed-item">
                        <span class="so-feed-time"><?php echo esc_html(current_time('H:i:s')); ?></span>
                        <span class="so-feed-text"><?php esc_html_e('Ready. Click "Start Bulk Optimization" to begin lossless & WebP generation.', 'super-optimizer'); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: In-Page Configuration Form (Everything on One Page) -->
        <section class="so-settings-deck">
            <form method="post" action="">
                <?php wp_nonce_field('super_optimizer_settings_nonce'); ?>
                <input type="hidden" name="super_optimizer_save_settings" value="1">
                <input type="hidden" name="current_tab" value="dashboard">

                <div class="so-section-header">
                    <div>
                        <h3 class="so-section-title"><?php esc_html_e('Compression & Delivery Parameters', 'super-optimizer'); ?></h3>
                        <p class="so-section-desc"><?php esc_html_e('Fine-tune visual lossy compression, next-gen sibling creation, and automatic camera downscaling.', 'super-optimizer'); ?></p>
                    </div>
                    <button type="submit" class="so-btn so-btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span><?php esc_html_e('Save Changes', 'super-optimizer'); ?></span>
                    </button>
                </div>

                <div class="so-columns-grid">
                    <!-- Column A: Compression Quality -->
                    <div class="so-column-card">
                        <div class="so-column-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line></svg>
                            <span><?php esc_html_e('Lossy Quality Sliders', 'super-optimizer'); ?></span>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label for="quality_jpeg" class="so-field-label"><?php esc_html_e('JPEG Quality', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('4:2:0 subsampling enabled', 'super-optimizer'); ?></span>
                            </div>
                            <div class="so-range-wrap">
                                <input type="range" id="quality_jpeg" name="settings[quality_jpeg]" min="50" max="100" value="<?php echo esc_attr($settings['quality_jpeg']); ?>" class="so-slider" oninput="this.nextElementSibling.value = this.value + '%'">
                                <output class="so-slider-val"><?php echo esc_html($settings['quality_jpeg']); ?>%</output>
                            </div>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label for="quality_png" class="so-field-label"><?php esc_html_e('PNG Quality', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('Deflate filter optimization', 'super-optimizer'); ?></span>
                            </div>
                            <div class="so-range-wrap">
                                <input type="range" id="quality_png" name="settings[quality_png]" min="50" max="100" value="<?php echo esc_attr($settings['quality_png']); ?>" class="so-slider" oninput="this.nextElementSibling.value = this.value + '%'">
                                <output class="so-slider-val"><?php echo esc_html($settings['quality_png']); ?>%</output>
                            </div>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label for="quality_webp" class="so-field-label"><?php esc_html_e('WebP Quality', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('Optimal sweet-spot: 80%', 'super-optimizer'); ?></span>
                            </div>
                            <div class="so-range-wrap">
                                <input type="range" id="quality_webp" name="settings[quality_webp]" min="50" max="100" value="<?php echo esc_attr($settings['quality_webp']); ?>" class="so-slider" oninput="this.nextElementSibling.value = this.value + '%'">
                                <output class="so-slider-val"><?php echo esc_html($settings['quality_webp']); ?>%</output>
                            </div>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label class="so-field-label"><?php esc_html_e('Strip Camera Metadata', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('Strips GPS/EXIF while retaining color profiles', 'super-optimizer'); ?></span>
                            </div>
                            <label class="so-switch">
                                <input type="checkbox" name="settings[strip_metadata]" value="1" <?php checked($settings['strip_metadata'], 1); ?>>
                                <span class="so-switch-track"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Column B: Formats, Downscaling & Delivery -->
                    <div class="so-column-card">
                        <div class="so-column-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                            <span><?php esc_html_e('Next-Gen Formats & Dimensions', 'super-optimizer'); ?></span>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label class="so-field-label"><?php esc_html_e('WebP Sibling Generation', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('Generates sibling files for all thumbnail dimensions', 'super-optimizer'); ?></span>
                            </div>
                            <label class="so-switch">
                                <input type="checkbox" name="settings[convert_webp]" value="1" <?php checked($settings['convert_webp'], 1); ?>>
                                <span class="so-switch-track"></span>
                            </label>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label class="so-field-label"><?php esc_html_e('Auto-Downscale Large Uploads', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('Resizes raw camera uploads prior to cropping', 'super-optimizer'); ?></span>
                            </div>
                            <div class="so-dimension-group">
                                <label class="so-switch">
                                    <input type="checkbox" name="settings[auto_resize]" value="1" <?php checked($settings['auto_resize'], 1); ?>>
                                    <span class="so-switch-track"></span>
                                </label>
                                <div class="so-dim-inputs">
                                    <input type="number" name="settings[max_width]" value="<?php echo esc_attr($settings['max_width']); ?>" class="so-dim-field" min="600" max="6000" step="50" title="Max Width">
                                    <span>&times;</span>
                                    <input type="number" name="settings[max_height]" value="<?php echo esc_attr($settings['max_height']); ?>" class="so-dim-field" min="600" max="6000" step="50" title="Max Height">
                                    <span class="so-dim-unit">px</span>
                                </div>
                            </div>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label class="so-field-label"><?php esc_html_e('Auto-Optimize on Upload', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('Hooks into media library to optimize new uploads seamlessly', 'super-optimizer'); ?></span>
                            </div>
                            <label class="so-switch">
                                <input type="checkbox" name="settings[optimize_on_upload]" value="1" <?php checked($settings['optimize_on_upload'], 1); ?>>
                                <span class="so-switch-track"></span>
                            </label>
                        </div>

                        <div class="so-field-row">
                            <div class="so-field-info">
                                <label for="serve_webp" class="so-field-label"><?php esc_html_e('Delivery Mechanism', 'super-optimizer'); ?></label>
                                <span class="so-field-hint"><?php esc_html_e('How visitors receive WebP files', 'super-optimizer'); ?></span>
                            </div>
                            <select name="settings[serve_webp]" id="serve_webp" class="so-select">
                                <option value="picture" <?php selected($settings['serve_webp'], 'picture'); ?>><?php esc_html_e('Picture Tags (Zero Server Config)', 'super-optimizer'); ?></option>
                                <option value="rewrite" <?php selected($settings['serve_webp'], 'rewrite'); ?>><?php esc_html_e('Rewrite Rules (Nginx / .htaccess)', 'super-optimizer'); ?></option>
                                <option value="disabled" <?php selected($settings['serve_webp'], 'disabled'); ?>><?php esc_html_e('Files Only (Custom Theme Handling)', 'super-optimizer'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <!-- Section 3: Relational Database & System Health (Collapsible Footer Shelf) -->
        <footer class="so-system-shelf">
            <details class="so-details">
                <summary class="so-summary">
                    <div class="so-summary-left">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                        <span><?php esc_html_e('Relational Database & Host Environment Health', 'super-optimizer'); ?></span>
                    </div>
                    <span class="so-summary-toggle"><?php esc_html_e('Inspect Specs', 'super-optimizer'); ?></span>
                </summary>

                <div class="so-details-content">
                    <div class="so-specs-grid">
                        <div class="so-spec-item">
                            <span class="so-spec-label"><?php esc_html_e('Master Items Table:', 'super-optimizer'); ?></span>
                            <code><?php echo esc_html(\SuperOptimizer\Database\Schema::get_items_table()); ?></code>
                        </div>
                        <div class="so-spec-item">
                            <span class="so-spec-label"><?php esc_html_e('Subsizes 1:N Table:', 'super-optimizer'); ?></span>
                            <code><?php echo esc_html(\SuperOptimizer\Database\Schema::get_subsizes_table()); ?></code>
                        </div>
                        <div class="so-spec-item">
                            <span class="so-spec-label"><?php esc_html_e('PHP Environment:', 'super-optimizer'); ?></span>
                            <code>PHP <?php echo esc_html($diagnostics['php_version']); ?> (Memory: <?php echo esc_html($diagnostics['memory_limit']); ?>)</code>
                        </div>
                        <div class="so-spec-item">
                            <span class="so-spec-label"><?php esc_html_e('Graphics Driver:', 'super-optimizer'); ?></span>
                            <code><?php echo esc_html($diagnostics['active_engine_name']); ?></code>
                        </div>
                    </div>
                </div>
            </details>
        </footer>
    </div>
</div>
