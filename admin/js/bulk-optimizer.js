/**
 * Super Optimizer - Bulk Queue & Library Scanner Controller
 *
 * Provides real-time library scanning and batch optimization.
 *
 * @package SuperOptimizer\Admin
 */

(function ($) {
    'use strict';

    if (typeof superOptimizerData === 'undefined') {
        return;
    }

    const config = superOptimizerData;

    const state = {
        queue: [],
        total: 0,
        currentIndex: 0,
        isRunning: false,
        isPaused: false,
        errorCount: 0
    };

    // DOM Controls
    const $btnScan           = $('#btn-scan-library');
    const $btnScanText       = $('#btn-scan-text');
    const $btnStart          = $('#btn-start-bulk');
    const $btnPause          = $('#btn-pause-bulk');
    const $btnResume         = $('#btn-resume-bulk');
    const $btnReset          = $('#btn-reset-index');
    const $btnClearLog       = $('#btn-clear-console');

    // Progress elements
    const $progressBar       = $('#bulk-progress-bar');
    const $progressPct       = $('#bulk-progress-pct');
    const $progressStatus    = $('#bulk-progress-status');
    const $progressCounts    = $('#bulk-progress-counts');
    const $activeItemBox     = $('#so-active-item');
    const $activeFilename    = $('#so-current-filename');
    const $activeSavings     = $('#so-current-savings');
    const $consoleStream     = $('#so-console-stream');

    // Headline elements
    const $scanHeadline      = $('#so-scan-headline');
    const $scanSubline       = $('#so-scan-subline');

    // Options
    const $chkForceReopt     = $('#bulk_force_reoptimize');
    const $chkWebpOnly       = $('#bulk_webp_only');
    const $selectPause       = $('#bulk_pause_seconds');

    // Telemetry displays
    const $statTimeSaved      = $('#stat-time-saved');
    const $statBytesSaved     = $('#stat-bytes-saved');
    const $statPercentage     = $('#stat-percentage');
    const $statOptimizedCount = $('#stat-optimized-count');
    const $statNextgenCount   = $('#stat-nextgen-count');

    function formatBytes(bytes) {
        if (bytes <= 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const base = Math.floor(Math.log(bytes) / Math.log(1024));
        const val = (bytes / Math.pow(1024, base)).toFixed(1);
        return val + ' ' + (units[base] || 'B');
    }

    function getTimeStamp() {
        const d = new Date();
        return d.toTimeString().split(' ')[0];
    }

    function appendLog(message, type) {
        type = type || 'info';
        const $line = $('<div class="so-feed-row so-' + type + '"></div>');
        $line.append('<span class="so-feed-ts">' + getTimeStamp() + '</span>');
        $line.append('<span class="so-feed-msg">' + message + '</span>');
        $consoleStream.append($line);
        $consoleStream.scrollTop($consoleStream[0].scrollHeight);
    }

    function updateProgressUI() {
        const processed = state.currentIndex;
        const total = state.total;
        const pct = total > 0 ? Math.round((processed / total) * 100) : 0;

        $progressBar.css('width', pct + '%');
        $progressPct.text(pct + '%');
        $progressCounts.text(processed + ' / ' + total + ' processed');
    }

    function updateTelemetry(stats) {
        if (!stats) return;

        if ($statTimeSaved.length && stats.time_saved_formatted) {
            $statTimeSaved.text(stats.time_saved_formatted);
        }
        if ($statBytesSaved.length) {
            $statBytesSaved.text(formatBytes(stats.total_bytes_saved));
        }
        if ($statPercentage.length) {
            $statPercentage.text('-' + stats.percentage_saved + '%');
        }
        if ($statOptimizedCount.length) {
            $statOptimizedCount.text(stats.optimized_attachments);
        }
        if ($statNextgenCount.length) {
            $statNextgenCount.text(stats.webp_count);
        }
    }

    /**
     * Scans Media Library on demand and updates UI summary.
     */
    function scanLibrary() {
        const forceReopt = $chkForceReopt.is(':checked') ? 1 : 0;

        $btnScan.prop('disabled', true);
        $btnScanText.text('Scanning...');
        appendLog('Scanning Media Library for images (Force Re-optimize: ' + (forceReopt ? 'Yes' : 'No') + ')...', 'info');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_scan',
                nonce: config.nonce,
                force_reoptimize: forceReopt
            },
            success: function (res) {
                $btnScan.prop('disabled', false);
                $btnScanText.text('Scan Library');

                if (!res.success || !res.data) {
                    appendLog('Library scan failed.', 'error');
                    return;
                }

                const data = res.data;
                appendLog(data.message, 'success');

                if ($scanHeadline.length) {
                    if (data.active_queue_count > 0) {
                        $scanHeadline.html('Found <strong>' + data.active_queue_count + '</strong> image attachments ready for optimization.');
                    } else {
                        $scanHeadline.html('All <strong>' + data.total_library + '</strong> media library images are currently optimized with WebP siblings.');
                    }
                }

                $progressCounts.text('0 / ' + data.active_queue_count + ' processed');
                $progressStatus.text('Scan complete: ' + data.active_queue_count + ' ready.');
                updateTelemetry(data.stats);
            },
            error: function () {
                $btnScan.prop('disabled', false);
                $btnScanText.text('Scan Library');
                appendLog('Server error connecting to scanner endpoint.', 'error');
            }
        });
    }

    /**
     * Starts bulk processing batch queue.
     */
    function startBulkProcess() {
        const forceReopt = $chkForceReopt.is(':checked') ? 1 : 0;
        const webpOnly   = $chkWebpOnly.is(':checked') ? 1 : 0;

        state.isRunning = true;
        state.isPaused = false;
        state.currentIndex = 0;
        state.errorCount = 0;

        $btnStart.hide();
        $btnPause.show();
        $btnResume.hide();
        $progressStatus.text(config.i18n.optimizing);
        appendLog('Initializing optimization batch (WebP Only: ' + (webpOnly ? 'Yes' : 'No') + ')...', 'info');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_get_queue',
                nonce: config.nonce,
                force_reoptimize: forceReopt
            },
            success: function (res) {
                if (!res.success || !res.data) {
                    appendLog('Failed to query Media Library queue.', 'error');
                    finishProcess(false);
                    return;
                }

                state.queue = res.data.ids || [];
                state.total = state.queue.length;

                if (state.total === 0) {
                    appendLog('All media images are already optimized.', 'success');
                    $progressStatus.text(config.i18n.completed);
                    updateProgressUI();
                    finishProcess(true);
                    return;
                }

                appendLog('Starting queue: ' + state.total + ' images to process.', 'info');
                updateProgressUI();
                $activeItemBox.show();
                processNextItem();
            },
            error: function () {
                appendLog('Server communication failure while loading queue.', 'error');
                finishProcess(false);
            }
        });
    }

    function processNextItem() {
        if (!state.isRunning) return;

        if (state.isPaused) {
            $progressStatus.text(config.i18n.paused);
            $btnPause.hide();
            $btnResume.show();
            appendLog('Optimization paused.', 'warn');
            return;
        }

        if (state.currentIndex >= state.queue.length) {
            appendLog('Optimization complete: all images processed successfully.', 'success');
            $progressStatus.text(config.i18n.completed);
            $activeItemBox.hide();
            finishProcess(true);
            return;
        }

        const attachmentId = state.queue[state.currentIndex];
        const webpOnly = $chkWebpOnly.is(':checked') ? 1 : 0;
        const pauseSeconds = parseInt($selectPause.val() || '0', 10);
        const pauseMs = Math.max(50, pauseSeconds * 1000);

        $activeFilename.text('#' + attachmentId);
        $activeSavings.text('Compressing...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_process_item',
                nonce: config.nonce,
                attachment_id: attachmentId,
                webp_only: webpOnly
            },
            success: function (res) {
                if (res.success && res.data) {
                    const data = res.data;
                    const savedFormatted = formatBytes(data.bytes_saved);
                    $activeFilename.text(data.filename);
                    $activeSavings.text('-' + data.compression_ratio + '% (' + savedFormatted + ')');

                    const feedText = '(' + (state.currentIndex + 1) + '/' + state.total + ') ' +
                        data.filename + ' -> Saved ' + savedFormatted + ' (-' + data.compression_ratio + '%)' +
                        (data.webp_count > 0 ? ' [WebP created]' : '');
                    appendLog(feedText, 'success');

                    updateTelemetry(data.stats);
                } else {
                    state.errorCount++;
                    const errMsg = res.data ? res.data.message : 'Error processing image';
                    appendLog('Skipped #' + attachmentId + ': ' + errMsg, 'warn');
                }

                state.currentIndex++;
                updateProgressUI();

                setTimeout(processNextItem, pauseMs);
            },
            error: function () {
                state.errorCount++;
                appendLog('Network timeout on #' + attachmentId + '. Moving forward...', 'error');
                state.currentIndex++;
                updateProgressUI();
                setTimeout(processNextItem, Math.max(200, pauseMs));
            }
        });
    }

    function pauseBulkProcess() {
        state.isPaused = true;
    }

    function resumeBulkProcess() {
        state.isPaused = false;
        $btnResume.hide();
        $btnPause.show();
        $progressStatus.text(config.i18n.optimizing);
        appendLog('Resuming optimization...', 'info');
        processNextItem();
    }

    function finishProcess(allDone) {
        state.isRunning = false;
        state.isPaused = false;
        $btnPause.hide();
        $btnResume.hide();
        $btnStart.show();
    }

    function resetIndex() {
        if (!window.confirm(config.i18n.confirmReset)) {
            return;
        }

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_reset',
                nonce: config.nonce
            },
            success: function (res) {
                if (res.success) {
                    appendLog('Optimization status records reset. All images marked pending.', 'warn');
                    updateTelemetry(res.data.stats);
                    state.currentIndex = 0;
                    state.total = 0;
                    updateProgressUI();
                    $progressStatus.text(config.i18n.ready);
                    if ($scanHeadline.length) {
                        $scanHeadline.html('All records reset. Click <strong>Scan Library</strong> or <strong>Start Optimizing</strong> to re-process.');
                    }
                }
            }
        });
    }

    // Attach listeners
    $btnScan.on('click', scanLibrary);
    $btnStart.on('click', startBulkProcess);
    $btnPause.on('click', pauseBulkProcess);
    $btnResume.on('click', resumeBulkProcess);
    $btnReset.on('click', resetIndex);
    $btnClearLog.on('click', function () {
        $consoleStream.empty();
    });

})(jQuery);
