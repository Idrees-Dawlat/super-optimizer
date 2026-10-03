/**
 * Super Optimizer - Interactive Admin Controller
 *
 * Lightweight, resilient controller for bulk optimization, scanning,
 * and settings interactions.
 *
 * @package SuperOptimizer\Admin
 */

(function ($) {
    'use strict';

    if (typeof superOptimizerData === 'undefined') {
        return;
    }

    const config = superOptimizerData;
    const PAGE_SIZE = 500;

    const state = {
        queue: [],
        total: 0,
        currentIndex: 0,
        isRunning: false,
        isPaused: false,
        errorCount: 0,
        savedThisRun: 0
    };

    // DOM Elements - Optimize Deck
    const $btnScan        = $('#btn-scan-library');
    const $btnScanText    = $('#btn-scan-text');
    const $btnStart       = $('#btn-start-bulk');
    const $btnPause       = $('#btn-pause-bulk');
    const $btnResume      = $('#btn-resume-bulk');
    const $spinnerBox     = $('#so-scan-spinner');
    const $deckIdle       = $('#so-deck-idle');
    const $deckRunning    = $('#so-deck-running');
    const $badge          = $('#so-state-badge');
    const $reoptTip       = $('#so-reopt-tip');

    // DOM Elements - Progress & Ticker
    const $progressBar    = $('#bulk-progress-bar');
    const $progressTrack  = $('#bulk-progress-track');
    const $progressPct    = $('#bulk-progress-pct');
    const $progressStatus = $('#bulk-progress-status');
    const $progressCounts = $('#bulk-progress-counts');
    const $activeFilename = $('#so-current-filename');
    const $activeSavings  = $('#so-current-savings');
    const $scanHeadline   = $('#so-scan-headline');
    const $scanSubline    = $('#so-scan-subline');

    // DOM Elements - Telemetry Cards
    const $statBytesSaved     = $('#stat-bytes-saved');
    const $statPercentage     = $('#stat-percentage');
    const $statOptimizedCount = $('#stat-optimized-count');
    const $statTotalCount     = $('#stat-total-count');
    const $statNextgenCount   = $('#stat-nextgen-count');

    // DOM Elements - Settings Page
    const $btnReset           = $('#btn-reset-index');
    const $tierCards          = $('.so-tier-card');

    function formatNumber(n) {
        return Number(n || 0).toLocaleString();
    }

    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const base = Math.min(units.length - 1, Math.floor(Math.log(bytes) / Math.log(1024)));
        return (bytes / Math.pow(1024, base)).toFixed(base === 0 ? 0 : 1) + ' ' + units[base];
    }

    function setBadge(kind, label) {
        if (!$badge.length) return;
        $badge.removeClass('is-ready is-running is-paused is-done is-error');
        $badge.addClass('is-' + kind);
        $badge.text(label);
    }

    function updateProgressUI() {
        const processed = state.currentIndex;
        const total = state.total;
        const pct = total > 0 ? Math.round((processed / total) * 100) : 0;

        $progressBar.css('width', pct + '%');
        $progressTrack.attr('aria-valuenow', pct);
        $progressPct.text(pct + '%');
        $progressCounts.text(formatNumber(processed) + ' / ' + formatNumber(total));
    }

    function updateTelemetry(stats) {
        if (!stats) return;

        if ($statBytesSaved.length) {
            $statBytesSaved.text(formatBytes(stats.total_bytes_saved));
        }
        if ($statPercentage.length) {
            $statPercentage.text('-' + stats.percentage_saved + '%');
        }
        if ($statOptimizedCount.length) {
            $statOptimizedCount.text(formatNumber(stats.optimized_attachments));
        }
        if ($statTotalCount.length && stats.total_library_images !== undefined) {
            $statTotalCount.text(formatNumber(stats.total_library_images));
        }
        if ($statNextgenCount.length) {
            $statNextgenCount.text(formatNumber(stats.webp_count));
        }
    }

    /**
     * Checks/scans the media library with animated spinner.
     */
    function scanLibrary() {
        const forceReopt = (config.options && config.options.force) ? 1 : 0;

        $btnScan.prop('disabled', true);
        if ($deckIdle.length) $deckIdle.hide();
        if ($deckRunning.length) $deckRunning.hide();
        if ($spinnerBox.length) $spinnerBox.show();

        setBadge('ready', 'Scanning…');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_scan',
                nonce: config.nonce,
                force_reoptimize: forceReopt
            }
        }).done(function (res) {
            if ($spinnerBox.length) $spinnerBox.hide();
            if ($deckIdle.length) $deckIdle.show();

            if (!res || !res.success || !res.data) {
                if ($scanHeadline.length) {
                    $scanHeadline.text('Unable to check media library.');
                }
                if ($scanSubline.length) {
                    $scanSubline.text('The server did not respond as expected. Please try again.');
                }
                setBadge('error', 'Error');
                return;
            }

            const data = res.data;
            const count = data.active_queue_count || 0;
            const total = data.total_library || 0;

            if (count > 0) {
                if ($scanHeadline.length) {
                    $scanHeadline.html('Found <strong>' + formatNumber(count) + '</strong> ' +
                        (count === 1 ? 'image' : 'images') + ' ready to optimize');
                }
                if ($scanSubline.length) {
                    $scanSubline.text('Click Start Optimizing to compress these images and create WebP versions. You can pause anytime.');
                }
                if ($btnStart.length) $btnStart.show();
                if ($btnScanText.length) $btnScanText.text('Check Library Again');
                if ($reoptTip.length) $reoptTip.hide();
                setBadge('ready', 'Ready');
            } else {
                if ($scanHeadline.length) {
                    $scanHeadline.html('All <strong>' + formatNumber(total) + '</strong> images in your library are currently optimized!');
                }
                if ($scanSubline.length) {
                    $scanSubline.text('There are no unoptimized images pending. New uploads are optimized automatically.');
                }
                if ($btnStart.length) $btnStart.hide();
                if ($btnScanText.length) $btnScanText.text('Check Library Again');
                if ($reoptTip.length) $reoptTip.show();
                setBadge('done', 'All Optimized');
            }

            updateTelemetry(data.stats);
        }).fail(function () {
            if ($spinnerBox.length) $spinnerBox.hide();
            if ($deckIdle.length) $deckIdle.show();
            if ($scanHeadline.length) {
                $scanHeadline.text('Connection error.');
            }
            if ($scanSubline.length) {
                $scanSubline.text('Could not reach the server endpoint. Please verify your connection.');
            }
            setBadge('error', 'Error');
        }).always(function () {
            $btnScan.prop('disabled', false);
        });
    }

    /**
     * Loads queue across multiple pages if library exceeds PAGE_SIZE.
     */
    function loadQueue(forceReopt, offset, acc) {
        return $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_get_queue',
                nonce: config.nonce,
                force_reoptimize: forceReopt,
                limit: PAGE_SIZE,
                offset: offset
            }
        }).then(function (res) {
            if (!res || !res.success || !res.data) {
                return $.Deferred().reject().promise();
            }
            const ids = res.data.ids || [];
            acc = acc.concat(ids);
            if (ids.length >= PAGE_SIZE) {
                return loadQueue(forceReopt, offset + PAGE_SIZE, acc);
            }
            return acc;
        });
    }

    /**
     * Starts bulk optimization batch.
     */
    function startBulkProcess() {
        const forceReopt = (config.options && config.options.force) ? 1 : 0;
        const webpOnly   = (config.options && config.options.webpOnly) ? 1 : 0;

        state.isRunning = true;
        state.isPaused = false;
        state.currentIndex = 0;
        state.errorCount = 0;
        state.savedThisRun = 0;

        if ($deckIdle.length) $deckIdle.hide();
        if ($deckRunning.length) $deckRunning.show();

        $btnPause.show().prop('disabled', true);
        $btnResume.hide();
        $progressStatus.text('Preparing queue…');
        setBadge('running', 'Optimizing');

        loadQueue(forceReopt, 0, []).done(function (ids) {
            state.queue = ids;
            state.total = ids.length;
            $btnPause.prop('disabled', false);

            if (state.total === 0) {
                completeRun();
                return;
            }

            $progressStatus.text('Optimizing media library…');
            updateProgressUI();
            processNextItem();
        }).fail(function () {
            $progressStatus.text('Failed to query library queue.');
            setBadge('error', 'Error');
            finishProcess();
        });
    }

    function processNextItem() {
        if (!state.isRunning) return;

        if (state.isPaused) {
            $progressStatus.text('Optimization paused.');
            $btnPause.hide();
            $btnResume.show();
            setBadge('paused', 'Paused');
            return;
        }

        if (state.currentIndex >= state.queue.length) {
            completeRun();
            return;
        }

        const attachmentId = state.queue[state.currentIndex];
        const webpOnly = (config.options && config.options.webpOnly) ? 1 : 0;
        const delaySeconds = (config.options && config.options.delay) ? config.options.delay : 0;
        const pauseMs = Math.max(50, delaySeconds * 1000);

        $activeFilename.text('#' + attachmentId);
        $activeSavings.text('Compressing…');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_process_item',
                nonce: config.nonce,
                attachment_id: attachmentId,
                webp_only: webpOnly
            }
        }).done(function (res) {
            if (res && res.success && res.data) {
                const data = res.data;
                const savedFormatted = formatBytes(data.bytes_saved);
                state.savedThisRun += data.bytes_saved || 0;

                $activeFilename.text(data.filename);
                if (data.bytes_saved > 0) {
                    $activeSavings.text('Saved ' + savedFormatted + ' (-' + data.compression_ratio + '%)');
                } else {
                    $activeSavings.text('Already optimal' + (data.webp_count > 0 ? ' (WebP created)' : ''));
                }

                updateTelemetry(data.stats);
            } else {
                state.errorCount++;
                const errMsg = res && res.data && res.data.message ? res.data.message : 'Skipped';
                $activeFilename.text('#' + attachmentId);
                $activeSavings.text(errMsg);
            }
        }).fail(function () {
            state.errorCount++;
            $activeFilename.text('#' + attachmentId);
            $activeSavings.text('Network timeout, continuing…');
        }).always(function () {
            state.currentIndex++;
            updateProgressUI();
            setTimeout(processNextItem, pauseMs);
        });
    }

    function completeRun() {
        state.isRunning = false;
        state.isPaused = false;

        setBadge('done', 'Complete');

        if ($deckRunning.length) $deckRunning.hide();
        if ($deckIdle.length) $deckIdle.show();

        const savedFmt = formatBytes(state.savedThisRun);
        const processedCount = state.total - state.errorCount;

        if ($scanHeadline.length) {
            $scanHeadline.html('Optimization Complete! Saved <strong>' + savedFmt + '</strong>');
        }
        if ($scanSubline.length) {
            $scanSubline.text('Successfully compressed ' + formatNumber(processedCount) + ' ' +
                (processedCount === 1 ? 'image' : 'images') +
                (state.errorCount > 0 ? ' (' + state.errorCount + ' skipped).' : '. All images are up to date!'));
        }

        if ($btnStart.length) $btnStart.hide();
        if ($btnScanText.length) $btnScanText.text('Check Library Again');
        if ($reoptTip.length) $reoptTip.show();
    }

    function finishProcess() {
        state.isRunning = false;
        state.isPaused = false;
        $btnPause.hide().prop('disabled', false);
        $btnResume.hide();
        if ($deckRunning.length) $deckRunning.hide();
        if ($deckIdle.length) $deckIdle.show();
    }

    function pauseBulkProcess() {
        state.isPaused = true;
        $btnPause.prop('disabled', true);
        $progressStatus.text('Pausing…');
    }

    function resumeBulkProcess() {
        state.isPaused = false;
        $btnResume.hide();
        $btnPause.show().prop('disabled', false);
        setBadge('running', 'Optimizing');
        $progressStatus.text('Resuming optimization…');
        processNextItem();
    }

    /**
     * Resets optimization database records from Settings page.
     */
    function resetIndex() {
        const confirmMsg = config.i18n && config.i18n.confirmReset
            ? config.i18n.confirmReset
            : 'Reset all optimization records?';

        if (!window.confirm(confirmMsg)) {
            return;
        }

        $btnReset.prop('disabled', true).text('Resetting…');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'super_optimizer_bulk_reset', nonce: config.nonce }
        }).done(function (res) {
            if (res && res.success) {
                alert('Optimization database index has been reset. All images are now marked as pending.');
                window.location.reload();
            } else {
                alert('Reset failed. Please try again.');
                $btnReset.prop('disabled', false).text('Reset Records');
            }
        }).fail(function () {
            alert('Server error while resetting database index.');
            $btnReset.prop('disabled', false).text('Reset Records');
        });
    }

    // Interactive tier card selection on Settings page
    $tierCards.on('click', function () {
        $tierCards.removeClass('is-selected');
        $(this).addClass('is-selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });

    // Warn if leaving mid-process
    window.addEventListener('beforeunload', function (e) {
        if (state.isRunning && !state.isPaused) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Event Bindings
    if ($btnScan.length) $btnScan.on('click', scanLibrary);
    if ($btnStart.length) $btnStart.on('click', startBulkProcess);
    if ($btnPause.length) $btnPause.on('click', pauseBulkProcess);
    if ($btnResume.length) $btnResume.on('click', resumeBulkProcess);
    if ($btnReset.length) $btnReset.on('click', resetIndex);

})(jQuery);
