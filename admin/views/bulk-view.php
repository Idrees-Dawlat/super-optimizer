<?php
/**
 * Media -> Super Optimizer View
 *
 * Clean, modern, high-precision image optimization console with scanning and bulk processing.
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
    <!-- Clean, Non-intrusive Header -->
    <header class="so-header-clean">
        <div class="so-header-left">
            <h1 class="so-plugin-title"><?php esc_html_e('Super Optimizer', 'super-optimizer'); ?></h1>
            <div class="so-engine-pill">
                <span class="so-engine-dot"></span>
                <span><?php echo esc_html($diagnostics['active_engine_name']); ?></span>
            </div>
            <?php if ($diagnostics['imagick_webp'] || $diagnostics['gd_webp']) : ?>
                <span class="so-pill-tag"><?php esc_html_e('WebP Ready', 'super-optimizer'); ?></span>
            <?php endif; ?>
        </div>
        <div class="so-header-right">
            <a href="<?php echo esc_url($settings_url); ?>" class="button so-btn-settings">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span><?php esc_html_e('Settings', 'super-optimizer'); ?></span>
            </a>
        </div>
    </header>

    <!-- Main Clean Container -->
    <div class="so-main-card">
        <!-- Scanner & Execution Control Deck -->
        <div class="so-deck-section">
            <div class="so-scan-status-box">
                <div class="so-scan-text-wrap">
                    <span class="so-scan-headline" id="so-scan-headline">
                        <?php
                        if ($queue_count > 0) {
                            echo sprintf(
                                esc_html__('Found %1$s unoptimized image attachments ready to process.', 'super-optimizer'),
                                '<strong id="so-queue-count">' . esc_html(number_format_i18n($queue_count)) . '</strong>'
                            );
                        } else {
                            echo sprintf(
                                esc_html__('All %1$s media library images are currently optimized with WebP siblings.', 'super-optimizer'),
                                '<strong>' . esc_html(number_format_i18n($total_count)) . '</strong>'
                            );
                        }
                        ?>
                    </span>
                    <span class="so-scan-subline" id="so-scan-subline">
                        <?php
                        echo sprintf(
                            esc_html__('Library contains %1$s uploads with up to %2$s thumbnails each. Scaled to %3$s &times; %4$s max.', 'super-optimizer'),
                            esc_html(number_format_i18n($total_count)),
                            esc_html($max_thumbs),
                            esc_html($settings['max_width']),
                            esc_html($settings['max_height'])
                        );
                        ?>
                    </span>
                </div>

                <div class="so-button-row">
                    <!-- Scanner Button -->
                    <button type="button" class="button button-secondary so-btn-scan" id="btn-scan-library">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span id="btn-scan-text"><?php esc_html_e('Scan Library', 'super-optimizer'); ?></span>
                    </button>

                    <!-- Start Optimizer Button -->
                    <button type="button" class="button button-primary so-btn-action" id="btn-start-bulk">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        <span><?php esc_html_e('Start Optimizing', 'super-optimizer'); ?></span>
                    </button>

                    <button type="button" class="button button-secondary" id="btn-pause-bulk" style="display: none;">
                        <?php esc_html_e('Pause', 'super-optimizer'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="btn-resume-bulk" style="display: none;">
                        <?php esc_html_e('Resume', 'super-optimizer'); ?>
                    </button>
                </div>
            </div>

            <!-- Execution Options Bar -->
            <div class="so-options-strip">
                <label class="so-check-label">
                    <input type="checkbox" id="bulk_force_reoptimize" name="bulk_force_reoptimize" value="1">
                    <span><?php esc_html_e('Force Re-optimize (Override existing)', 'super-optimizer'); ?></span>
                </label>

                <label class="so-check-label">
                    <input type="checkbox" id="bulk_webp_only" name="bulk_webp_only" value="1">
                    <span><?php esc_html_e('WebP Only (Skip JPG/PNG compression)', 'super-optimizer'); ?></span>
                </label>

                <div class="so-pause-control">
                    <label for="bulk_pause_seconds"><?php esc_html_e('Pause between images:', 'super-optimizer'); ?></label>
                    <select id="bulk_pause_seconds" name="bulk_pause_seconds" class="so-pause-select">
                        <option value="0">0s (Fastest)</option>
                        <option value="1">1s (Safe)</option>
                        <option value="2">2s (Low CPU)</option>
                        <option value="3">3s</option>
                    </select>
                </div>

                <div class="so-reset-wrap">
                    <button type="button" class="button-link so-reset-btn" id="btn-reset-index">
                        <?php esc_html_e('Reset Index', 'super-optimizer'); ?>
                    </button>
                </div>
            </div>

            <!-- Progress Meter -->
            <div class="so-meter-box">
                <div class="so-meter-header">
                    <span class="so-meter-title" id="bulk-progress-status"><?php esc_html_e('Ready to optimize.', 'super-optimizer'); ?></span>
                    <span class="so-meter-percentage" id="bulk-progress-pct">0%</span>
                </div>
                <div class="so-meter-track">
                    <div class="so-meter-fill" id="bulk-progress-bar" style="width: 0%;"></div>
                </div>
                <div class="so-meter-footer">
                    <span id="bulk-progress-counts">0 / <?php echo esc_html(number_format_i18n($queue_count)); ?> <?php esc_html_e('processed', 'super-optimizer'); ?></span>
                    <div class="so-active-ticker" id="so-active-item" style="display: none;">
                        <span id="so-current-filename">-</span>
                        <span class="so-ticker-badge" id="so-current-savings"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integrated Telemetry Strip (Load Time, Data Saved, Images, WebP) -->
        <div class="so-telemetry-strip">
            <div class="so-telemetry-col">
                <span class="so-telemetry-label"><?php esc_html_e('Est. Page Load Time Saved', 'super-optimizer'); ?></span>
                <span class="so-telemetry-val so-val-emerald" id="stat-time-saved"><?php echo esc_html($time_saved); ?></span>
                <span class="so-telemetry-sub"><?php esc_html_e('Speedup on mobile & 4G', 'super-optimizer'); ?></span>
            </div>

            <div class="so-telemetry-col">
                <span class="so-telemetry-label"><?php esc_html_e('Total Data Saved', 'super-optimizer'); ?></span>
                <div class="so-val-row">
                    <span class="so-telemetry-val" id="stat-bytes-saved"><?php echo esc_html($saved_bytes_fmt); ?></span>
                    <span class="so-badge-ratio" id="stat-percentage">-<?php echo esc_html($stats['percentage_saved']); ?>%</span>
                </div>
                <span class="so-telemetry-sub"><?php esc_html_e('Bandwidth & disk reduction', 'super-optimizer'); ?></span>
            </div>

            <div class="so-telemetry-col">
                <span class="so-telemetry-label"><?php esc_html_e('Optimized Images', 'super-optimizer'); ?></span>
                <span class="so-telemetry-val"><span id="stat-optimized-count"><?php echo esc_html($stats['optimized_attachments']); ?></span> / <?php echo esc_html($total_count); ?></span>
                <span class="so-telemetry-sub"><?php esc_html_e('Media attachments', 'super-optimizer'); ?></span>
            </div>

            <div class="so-telemetry-col">
                <span class="so-telemetry-label"><?php esc_html_e('WebP Siblings Active', 'super-optimizer'); ?></span>
                <span class="so-telemetry-val" id="stat-nextgen-count"><?php echo esc_html($stats['webp_count']); ?></span>
                <span class="so-telemetry-sub"><?php esc_html_e('Next-gen format copies', 'super-optimizer'); ?></span>
            </div>
        </div>

        <!-- Clean Activity Feed -->
        <div class="so-feed-card">
            <div class="so-feed-header">
                <span class="so-feed-title"><?php esc_html_e('Activity Log', 'super-optimizer'); ?></span>
                <button type="button" class="so-feed-clear-btn" id="btn-clear-console"><?php esc_html_e('Clear Log', 'super-optimizer'); ?></button>
            </div>
            <div class="so-feed-stream" id="so-console-stream">
                <div class="so-feed-row">
                    <span class="so-feed-ts"><?php echo esc_html(current_time('H:i:s')); ?></span>
                    <span class="so-feed-msg"><?php esc_html_e('System ready. Click "Scan Library" to inspect, or "Start Optimizing" to process files.', 'super-optimizer'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
