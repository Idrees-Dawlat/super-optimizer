<?php
/**
 * Media -> Super Optimizer (Optimize tab)
 *
 * Streamlined, professional bulk optimizer inspired by EWWW Image Optimizer.
 *
 * @package SuperOptimizer\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$so_active_tab   = 'optimize';
$saved_bytes_fmt = \SuperOptimizer\Admin::format_bytes((int) $stats['total_bytes_saved']);
$orig_bytes_fmt  = \SuperOptimizer\Admin::format_bytes((int) $stats['total_original_bytes']);
$settings_url    = admin_url('options-general.php?page=super-optimizer-settings');
$has_queue       = ($queue_count > 0);
?>

<div class="wrap so-app">
    <?php require SUPER_OPTIMIZER_PATH . 'admin/views/partials/header.php'; ?>

    <?php if (!empty($is_updated)) : ?>
        <div class="so-notice is-success" role="status"><?php esc_html_e('Settings saved.', 'super-optimizer'); ?></div>
    <?php endif; ?>

    <div class="so-page-title">
        <h1><?php esc_html_e('Bulk Image Optimizer', 'super-optimizer'); ?></h1>
        <p><?php esc_html_e('Compress images across your WordPress media library and generate WebP versions locally on your server.', 'super-optimizer'); ?></p>
    </div>

    <!-- Main Optimization Control Deck -->
    <section class="so-card so-optimizer-deck" aria-labelledby="so-optimizer-heading">
        <div class="so-card-head">
            <div>
                <span class="so-eyebrow"><?php esc_html_e('Media Library Queue', 'super-optimizer'); ?></span>
                <h2 id="so-optimizer-heading"><?php esc_html_e('Optimization Status', 'super-optimizer'); ?></h2>
            </div>
            <span class="so-badge<?php echo $has_queue ? ' is-ready' : ' is-done'; ?>" id="so-state-badge" role="status">
                <?php echo $has_queue ? esc_html__('Ready', 'super-optimizer') : esc_html__('All Optimized', 'super-optimizer'); ?>
            </span>
        </div>

        <!-- 1. Scanner Loading State (shown while checking) -->
        <div class="so-scanner-loading" id="so-scan-spinner" style="display: none;">
            <div class="so-spinner" aria-hidden="true"></div>
            <p class="so-spinner-text"><?php esc_html_e('Checking media library for unoptimized images…', 'super-optimizer'); ?></p>
        </div>

        <!-- 2. Idle State: Ready to run or Already optimized -->
        <div class="so-deck-body" id="so-deck-idle">
            <div class="so-status-content">
                <div class="so-status-text">
                    <p class="so-status-headline" id="so-scan-headline">
                        <?php
                        if ($has_queue) {
                            echo wp_kses(
                                sprintf(
                                    /* translators: %s: number of unoptimized images */
                                    _n('Found %s image ready to optimize', 'Found %s images ready to optimize', $queue_count, 'super-optimizer'),
                                    '<strong>' . esc_html(number_format_i18n($queue_count)) . '</strong>'
                                ),
                                ['strong' => []]
                            );
                        } else {
                            echo wp_kses(
                                sprintf(
                                    /* translators: %s: total library images */
                                    __('All %s images in your library are currently optimized!', 'super-optimizer'),
                                    '<strong>' . esc_html(number_format_i18n($total_count)) . '</strong>'
                                ),
                                ['strong' => []]
                            );
                        }
                        ?>
                    </p>
                    <p class="so-status-subline" id="so-scan-subline">
                        <?php
                        if ($has_queue) {
                            esc_html_e('Click Start Optimizing to compress these images and generate WebP siblings. You can pause at any time.', 'super-optimizer');
                        } else {
                            esc_html_e('There are no unoptimized images pending. New uploads are optimized automatically.', 'super-optimizer');
                        }
                        ?>
                    </p>
                </div>

                <div class="so-status-actions" id="so-idle-actions">
                    <button type="button" class="so-btn so-btn-primary" id="btn-start-bulk" style="<?php echo $has_queue ? '' : 'display: none;'; ?>">
                        <span><?php esc_html_e('Start Optimizing', 'super-optimizer'); ?></span>
                    </button>
                    <button type="button" class="so-btn" id="btn-scan-library">
                        <span id="btn-scan-text"><?php echo $has_queue ? esc_html__('Check Library Again', 'super-optimizer') : esc_html__('Check Library', 'super-optimizer'); ?></span>
                    </button>
                </div>
            </div>

            <?php if (!$has_queue) : ?>
                <div class="so-idle-tip" id="so-reopt-tip">
                    <p>
                        <?php esc_html_e('Want to force re-compress existing images or change quality?', 'super-optimizer'); ?>
                        <a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Configure in Settings &rarr;', 'super-optimizer'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Active Running State: Progress Bar & Current Ticker -->
        <div class="so-deck-running" id="so-deck-running" style="display: none;">
            <div class="so-progress-meta">
                <span class="so-progress-status" id="bulk-progress-status"><?php esc_html_e('Optimizing…', 'super-optimizer'); ?></span>
                <span class="so-progress-numbers">
                    <span id="bulk-progress-counts">0 / 0</span>
                    <span class="so-progress-divider">&bull;</span>
                    <strong id="bulk-progress-pct">0%</strong>
                </span>
            </div>

            <div class="so-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="bulk-progress-track">
                <div class="so-progress-fill" id="bulk-progress-bar"></div>
            </div>

            <div class="so-active-ticker" id="so-active-item">
                <span class="so-ticker-label"><?php esc_html_e('Optimizing:', 'super-optimizer'); ?></span>
                <span class="so-ticker-filename" id="so-current-filename">-</span>
                <span class="so-ticker-savings" id="so-current-savings"></span>
            </div>

            <div class="so-running-actions">
                <button type="button" class="so-btn" id="btn-pause-bulk">
                    <span><?php esc_html_e('Pause', 'super-optimizer'); ?></span>
                </button>
                <button type="button" class="so-btn so-btn-primary" id="btn-resume-bulk" style="display: none;">
                    <span><?php esc_html_e('Resume', 'super-optimizer'); ?></span>
                </button>
            </div>
        </div>
    </section>

    <!-- Results Telemetry (100% Genuine, Measured Savings Only) -->
    <section class="so-card" aria-labelledby="so-results-heading">
        <div class="so-card-head">
            <div>
                <span class="so-eyebrow"><?php esc_html_e('Measured Performance', 'super-optimizer'); ?></span>
                <h2 id="so-results-heading"><?php esc_html_e('Total Space Saved', 'super-optimizer'); ?></h2>
            </div>
        </div>
        <div class="so-stats so-stats-clean">
            <div class="so-stat">
                <div class="so-stat-label"><?php esc_html_e('Disk & Bandwidth Saved', 'super-optimizer'); ?></div>
                <div class="so-stat-value">
                    <span id="stat-bytes-saved"><?php echo esc_html($saved_bytes_fmt); ?></span>
                    <small id="stat-percentage-wrap"><span id="stat-percentage">-<?php echo esc_html($stats['percentage_saved']); ?>%</span></small>
                </div>
                <div class="so-stat-sub">
                    <?php
                    /* translators: %s: formatted original size */
                    echo esc_html(sprintf(__('Reduced from %s original payload', 'super-optimizer'), $orig_bytes_fmt));
                    ?>
                </div>
            </div>

            <div class="so-stat">
                <div class="so-stat-label"><?php esc_html_e('Media Library Coverage', 'super-optimizer'); ?></div>
                <div class="so-stat-value">
                    <span id="stat-optimized-count"><?php echo esc_html(number_format_i18n($stats['optimized_attachments'])); ?></span>
                    <small style="color:var(--so-muted)">/ <span id="stat-total-count"><?php echo esc_html(number_format_i18n($total_count)); ?></span></small>
                </div>
                <div class="so-stat-sub"><?php esc_html_e('Image attachments optimized', 'super-optimizer'); ?></div>
            </div>

            <div class="so-stat">
                <div class="so-stat-label"><?php esc_html_e('WebP Siblings Active', 'super-optimizer'); ?></div>
                <div class="so-stat-value" id="stat-nextgen-count"><?php echo esc_html(number_format_i18n($stats['webp_count'])); ?></div>
                <div class="so-stat-sub"><?php esc_html_e('Next-gen files served to browsers', 'super-optimizer'); ?></div>
            </div>
        </div>
    </section>

    <div class="so-footer-links">
        <p>
            <?php esc_html_e('Compression tiers, WebP delivery, and database maintenance are managed in', 'super-optimizer'); ?>
            <a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Settings', 'super-optimizer'); ?></a>.
        </p>
    </div>
</div>
