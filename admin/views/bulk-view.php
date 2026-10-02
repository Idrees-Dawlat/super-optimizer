<?php
/**
 * Media -> Super Optimizer View
 *
 * Pixel-perfect implementation of the approved stillframe UI design.
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$time_saved      = $stats['time_saved_formatted'] ?? '0 ms';
$saved_bytes_fmt = \SuperOptimizer\Admin::format_bytes($stats['total_bytes_saved']);
$settings_url    = admin_url('options-general.php?page=super-optimizer-settings');
?>

<div class="wrap so-page-wrap">
    <!-- Top Header -->
    <header class="so-header-bar">
        <div class="so-header-left">
            <div class="so-logo-glyph">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
            </div>
            <h1 class="so-brand-title"><?php esc_html_e('Super Optimizer', 'super-optimizer'); ?></h1>
            <div class="so-driver-pill">
                <span><?php echo esc_html($diagnostics['active_engine_name']); ?></span>
                <?php if ($diagnostics['imagick_webp'] || $diagnostics['gd_webp']) : ?>
                    <span class="so-pill-sep">&bull;</span>
                    <span><?php esc_html_e('WebP Ready', 'super-optimizer'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="so-header-right">
            <a href="<?php echo esc_url($settings_url); ?>" class="button so-btn-nav">
                <?php esc_html_e('Settings', 'super-optimizer'); ?>
            </a>
        </div>
    </header>

    <!-- Main Scanner & Optimizer Card -->
    <div class="so-dashboard-card">
        <!-- Top Section: Media Library Scanner & Action Buttons -->
        <div class="so-scanner-row">
            <div class="so-scanner-info">
                <h2 class="so-scanner-title"><?php esc_html_e('Media Library Scanner', 'super-optimizer'); ?></h2>
                <p class="so-scanner-status" id="so-scan-headline">
                    <?php
                    if ($queue_count > 0) {
                        echo sprintf(
                            esc_html__('Found %1$s unoptimized image attachments ready for optimization', 'super-optimizer'),
                            '<strong>' . esc_html(number_format_i18n($queue_count)) . '</strong>'
                        );
                    } else {
                        echo sprintf(
                            esc_html__('All %1$s media library images are currently optimized with WebP siblings', 'super-optimizer'),
                            '<strong>' . esc_html(number_format_i18n($total_count)) . '</strong>'
                        );
                    }
                    ?>
                </p>
            </div>

            <div class="so-scanner-actions">
                <button type="button" class="button so-btn-secondary" id="btn-scan-library">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <span id="btn-scan-text"><?php esc_html_e('Scan Library', 'super-optimizer'); ?></span>
                </button>

                <button type="button" class="button button-primary so-btn-execute" id="btn-start-bulk">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    <span><?php esc_html_e('Start Optimizing', 'super-optimizer'); ?></span>
                </button>

                <button type="button" class="button so-btn-secondary" id="btn-pause-bulk" style="display: none;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
                    <span><?php esc_html_e('Pause', 'super-optimizer'); ?></span>
                </button>

                <button type="button" class="button button-primary so-btn-execute" id="btn-resume-bulk" style="display: none;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    <span><?php esc_html_e('Resume', 'super-optimizer'); ?></span>
                </button>
            </div>
        </div>

        <!-- Options Strip -->
        <div class="so-options-row">
            <div class="so-options-left">
                <span class="so-options-label"><?php esc_html_e('Options', 'super-optimizer'); ?></span>
                <label class="so-checkbox">
                    <input type="checkbox" id="bulk_force_reoptimize" name="bulk_force_reoptimize" value="1">
                    <span><?php esc_html_e('Force Re-optimize', 'super-optimizer'); ?></span>
                </label>
                <label class="so-checkbox">
                    <input type="checkbox" id="bulk_webp_only" name="bulk_webp_only" value="1">
                    <span><?php esc_html_e('WebP Only', 'super-optimizer'); ?></span>
                </label>
            </div>

            <div class="so-options-right">
                <div class="so-select-wrapper">
                    <select id="bulk_pause_seconds" name="bulk_pause_seconds" class="so-pause-dropdown">
                        <option value="0"><?php esc_html_e('Pause: 0s (Fastest)', 'super-optimizer'); ?></option>
                        <option value="1"><?php esc_html_e('Pause: 1s (Safe)', 'super-optimizer'); ?></option>
                        <option value="2"><?php esc_html_e('Pause: 2s (Low CPU)', 'super-optimizer'); ?></option>
                        <option value="3"><?php esc_html_e('Pause: 3s', 'super-optimizer'); ?></option>
                    </select>
                </div>
                <button type="button" class="button-link so-reset-link" id="btn-reset-index" title="<?php esc_attr_e('Reset optimization status in database', 'super-optimizer'); ?>">
                    <?php esc_html_e('Reset', 'super-optimizer'); ?>
                </button>
            </div>
        </div>

        <!-- Progress Bar & Active Ticker -->
        <div class="so-progress-section">
            <div class="so-progress-meta-row">
                <span class="so-progress-text">
                    <?php esc_html_e('Progress bar:', 'super-optimizer'); ?>
                    <strong id="bulk-progress-pct" class="so-pct-num">0%</strong>
                </span>
                <span class="so-progress-counts" id="bulk-progress-counts">
                    0 / <?php echo esc_html(number_format_i18n($queue_count)); ?> <?php esc_html_e('processed', 'super-optimizer'); ?>
                </span>
            </div>

            <div class="so-progress-track">
                <div class="so-progress-fill" id="bulk-progress-bar" style="width: 0%;"></div>
            </div>

            <div class="so-active-ticker" id="so-active-item" style="display: none;">
                <span class="so-ticker-file" id="so-current-filename">-</span>
                <span class="so-ticker-savings" id="so-current-savings"></span>
            </div>
        </div>

        <!-- 4-Column Numbered Telemetry Strip -->
        <div class="so-telemetry-grid">
            <div class="so-telemetry-cell">
                <div class="so-cell-header">
                    <span class="so-cell-num">1.</span>
                    <span class="so-cell-label"><?php esc_html_e('Est. Page Load Time Saved', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-value so-val-emerald" id="stat-time-saved"><?php echo esc_html($time_saved); ?></div>
            </div>

            <div class="so-telemetry-cell">
                <div class="so-cell-header">
                    <span class="so-cell-num">2.</span>
                    <span class="so-cell-label"><?php esc_html_e('Total Data Saved', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-value">
                    <span id="stat-bytes-saved"><?php echo esc_html($saved_bytes_fmt); ?></span>
                    <span class="so-val-ratio" id="stat-percentage">(-<?php echo esc_html($stats['percentage_saved']); ?>%)</span>
                </div>
            </div>

            <div class="so-telemetry-cell">
                <div class="so-cell-header">
                    <span class="so-cell-num">3.</span>
                    <span class="so-cell-label"><?php esc_html_e('Optimized Images', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-value">
                    <span id="stat-optimized-count"><?php echo esc_html($stats['optimized_attachments']); ?></span>
                    <span class="so-val-denom">/ <?php echo esc_html($total_count); ?></span>
                </div>
            </div>

            <div class="so-telemetry-cell">
                <div class="so-cell-header">
                    <span class="so-cell-num">4.</span>
                    <span class="so-cell-label"><?php esc_html_e('WebP Siblings Active', 'super-optimizer'); ?></span>
                </div>
                <div class="so-cell-value" id="stat-nextgen-count"><?php echo esc_html($stats['webp_count']); ?></div>
            </div>
        </div>

        <!-- Activity Log Console -->
        <div class="so-console-panel">
            <div class="so-console-bar">
                <span class="so-console-title"><?php esc_html_e('Execution Log', 'super-optimizer'); ?></span>
                <button type="button" class="so-console-clear" id="btn-clear-console"><?php esc_html_e('Clear', 'super-optimizer'); ?></button>
            </div>
            <div class="so-console-feed" id="so-console-stream">
                <div class="so-console-line">
                    <span class="so-log-time"><?php echo esc_html(current_time('H:i:s')); ?></span>
                    <span class="so-log-msg"><?php esc_html_e('Ready. Click "Scan Library" to inspect, or "Start Optimizing" to process.', 'super-optimizer'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
