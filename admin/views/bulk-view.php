<?php
/**
 * Media -> Bulk Optimize View
 *
 * Implements the clean, high-performance bulk optimization interface.
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$time_saved = $stats['time_saved_formatted'] ?? '0 ms';
$saved_bytes_fmt = \SuperOptimizer\Admin::format_bytes($stats['total_bytes_saved']);
$settings_url = admin_url('options-general.php?page=super-optimizer-settings');
?>

<div class="wrap so-page-wrap">
    <!-- Top Header Banner -->
    <div class="so-header-strip">
        <div class="so-header-brand">
            <span class="so-brand-name">Super Optimizer</span>
            <span class="so-engine-badge"><?php echo esc_html($diagnostics['active_engine_name']); ?></span>
            <?php if ($diagnostics['imagick_webp'] || $diagnostics['gd_webp']) : ?>
                <span class="so-webp-badge">WebP Ready</span>
            <?php endif; ?>
        </div>
        <div class="so-header-actions">
            <a href="<?php echo esc_url($settings_url); ?>" class="button button-secondary">
                <?php esc_html_e('Optimization Settings', 'super-optimizer'); ?>
            </a>
        </div>
    </div>

    <!-- Main Bulk Workspace -->
    <div class="so-bulk-container">
        <!-- Notification Text -->
        <p class="so-bulk-info-text">
            <?php
            echo sprintf(
                esc_html__('%1$s uploaded items in the Media Library have been selected with up to %2$s image files per upload. All images in the Media Library will be scaled to %3$s &times; %4$s.', 'super-optimizer'),
                '<strong id="so-queue-count">' . esc_html(number_format_i18n($queue_count)) . '</strong>',
                '<strong>' . esc_html($max_thumbs) . '</strong>',
                esc_html($settings['max_width']),
                esc_html($settings['max_height'])
            );
            ?>
        </p>

        <div class="so-bulk-columns">
            <!-- Left Column: Primary Action & Live Telemetry -->
            <div class="so-bulk-left">
                <div class="so-action-panel">
                    <div class="so-btn-group">
                        <button type="button" class="button button-primary button-hero so-btn-main" id="btn-start-bulk">
                            <?php esc_html_e('Start optimizing', 'super-optimizer'); ?>
                        </button>
                        <button type="button" class="button button-secondary button-hero" id="btn-pause-bulk" style="display: none;">
                            <?php esc_html_e('Pause', 'super-optimizer'); ?>
                        </button>
                        <button type="button" class="button button-primary button-hero" id="btn-resume-bulk" style="display: none;">
                            <?php esc_html_e('Resume', 'super-optimizer'); ?>
                        </button>
                    </div>

                    <!-- Progress Bar & Status -->
                    <div class="so-progress-box">
                        <div class="so-progress-meta">
                            <span class="so-progress-title" id="bulk-progress-status"><?php esc_html_e('Ready to start optimization.', 'super-optimizer'); ?></span>
                            <span class="so-progress-pct" id="bulk-progress-pct">0%</span>
                        </div>
                        <div class="so-progress-bar-track">
                            <div class="so-progress-bar-fill" id="bulk-progress-bar" style="width: 0%;"></div>
                        </div>
                        <div class="so-progress-counts">
                            <span id="bulk-progress-counts">0 / <?php echo esc_html(number_format_i18n($queue_count)); ?> <?php esc_html_e('images', 'super-optimizer'); ?></span>
                            <div class="so-active-ticker" id="so-active-item" style="display: none;">
                                <span id="so-current-filename">-</span>
                                <span class="so-ticker-pill" id="so-current-savings"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Telemetry Stats -->
                    <div class="so-metrics-inline">
                        <div class="so-metric-item">
                            <span class="so-metric-k"><?php esc_html_e('Est. Page Load Time Saved:', 'super-optimizer'); ?></span>
                            <strong class="so-metric-v so-metric-green" id="stat-time-saved"><?php echo esc_html($time_saved); ?></strong>
                        </div>
                        <div class="so-metric-item">
                            <span class="so-metric-k"><?php esc_html_e('Total Data Saved:', 'super-optimizer'); ?></span>
                            <strong class="so-metric-v" id="stat-bytes-saved"><?php echo esc_html($saved_bytes_fmt); ?></strong>
                            <span class="so-metric-pct" id="stat-percentage">(<?php echo esc_html($stats['percentage_saved']); ?>%)</span>
                        </div>
                        <div class="so-metric-item">
                            <span class="so-metric-k"><?php esc_html_e('Optimized Images:', 'super-optimizer'); ?></span>
                            <strong class="so-metric-v"><span id="stat-optimized-count"><?php echo esc_html($stats['optimized_attachments']); ?></span> / <?php echo esc_html($total_count); ?></strong>
                        </div>
                        <div class="so-metric-item">
                            <span class="so-metric-k"><?php esc_html_e('WebP Siblings:', 'super-optimizer'); ?></span>
                            <strong class="so-metric-v" id="stat-nextgen-count"><?php echo esc_html($stats['webp_count']); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Activity Log Feed -->
                <div class="so-log-deck">
                    <div class="so-log-deck-header">
                        <span><?php esc_html_e('Live Activity Log', 'super-optimizer'); ?></span>
                        <button type="button" class="so-log-clear" id="btn-clear-console"><?php esc_html_e('Clear', 'super-optimizer'); ?></button>
                    </div>
                    <div class="so-log-feed" id="so-console-stream">
                        <div class="so-log-line">
                            <span class="so-log-time"><?php echo esc_html(current_time('H:i:s')); ?></span>
                            <span class="so-log-msg"><?php esc_html_e('System ready. Click "Start optimizing" to compress images and generate WebP.', 'super-optimizer'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Optimization Execution Controls -->
            <div class="so-bulk-right">
                <div class="so-options-box">
                    <div class="so-option-item">
                        <label class="so-checkbox-label">
                            <input type="checkbox" id="bulk_background_mode" name="bulk_background_mode" value="1">
                            <strong><?php esc_html_e('Background Mode', 'super-optimizer'); ?></strong>
                        </label>
                        <p class="so-option-desc">
                            <?php esc_html_e('Optimize images in background without keeping this page open.', 'super-optimizer'); ?>
                        </p>
                    </div>

                    <div class="so-option-item">
                        <label class="so-checkbox-label">
                            <input type="checkbox" id="bulk_force_reoptimize" name="bulk_force_reoptimize" value="1">
                            <strong><?php esc_html_e('Force Re-optimize', 'super-optimizer'); ?></strong>
                        </label>
                        <p class="so-option-desc">
                            <?php esc_html_e('Previously optimized images will be skipped by default, check this box before scanning to override.', 'super-optimizer'); ?>
                        </p>
                    </div>

                    <div class="so-option-item">
                        <label class="so-checkbox-label">
                            <input type="checkbox" id="bulk_webp_only" name="bulk_webp_only" value="1">
                            <strong><?php esc_html_e('WebP Only', 'super-optimizer'); ?></strong>
                        </label>
                        <p class="so-option-desc">
                            <?php esc_html_e('Skip compression and only attempt WebP conversion.', 'super-optimizer'); ?>
                        </p>
                    </div>

                    <div class="so-option-item so-pause-option">
                        <div class="so-pause-header">
                            <label for="bulk_pause_seconds">
                                <strong><?php esc_html_e('Pause between images', 'super-optimizer'); ?></strong>
                            </label>
                            <div class="so-pause-input-wrap">
                                <input type="number" id="bulk_pause_seconds" name="bulk_pause_seconds" value="0" min="0" max="5" class="small-text">
                                <span><?php esc_html_e('in seconds', 'super-optimizer'); ?></span>
                            </div>
                        </div>
                        <input type="range" id="bulk_pause_slider" min="0" max="5" value="0" class="so-slider" oninput="document.getElementById('bulk_pause_seconds').value = this.value">
                    </div>

                    <div class="so-reset-box">
                        <button type="button" class="button-link so-reset-link" id="btn-reset-index">
                            <?php esc_html_e('Reset optimization status records', 'super-optimizer'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
