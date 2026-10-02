/**
 * Super Optimizer - Bulk Queue Runner & Real-Time Telemetry
 *
 * Single-loop asynchronous queue runner updating integrated telemetry.
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

    // DOM Elements
    const $btnStart       = $('#btn-start-bulk');
    const $btnPause       = $('#btn-pause-bulk');
    const $btnResume      = $('#btn-resume-bulk');
    const $btnReset       = $('#btn-reset-index');
    const $btnClearLog    = $('#btn-clear-console');
    const $progressBar    = $('#bulk-progress-bar');
    const $progressPct    = $('#bulk-progress-pct');
    const $progressStatus = $('#bulk-progress-status');
    const $progressCounts = $('#bulk-progress-counts');
    const $activeItemBox  = $('#so-active-item');
    const $activeFilename = $('#so-current-filename');
    const $activeSavings  = $('#so-current-savings');
    const $consoleStream  = $('#so-console-stream');

    // Telemetry DOM elements
    const $statTimeSaved      = $('#stat-time-saved');
    const $statBytesSaved     = $('#stat-bytes-saved');
    const $statPercentage     = $('#stat-percentage');
    const $statOptimizedCount = $('#stat-optimized-count');
    const $statSubsizesDesc   = $('#stat-subsizes-desc');
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

    function appendFeed(message, type) {
        type = type || 'info';
        const $line = $('<div class="so-feed-item so-' + type + '"></div>');
        $line.append('<span class="so-feed-time">' + getTimeStamp() + '</span>');
        $line.append('<span class="so-feed-text">' + message + '</span>');
        $consoleStream.append($line);
        $consoleStream.scrollTop($consoleStream[0].scrollHeight);
    }

    function updateProgressUI() {
        const processed = state.currentIndex;
        const total = state.total;
        const pct = total > 0 ? Math.round((processed / total) * 100) : 0;

        $progressBar.css('width', pct + '%');
        $progressPct.text(pct + '%');
        $progressCounts.text(processed + ' / ' + total + ' images');
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
            $statPercentage.text(stats.percentage_saved);
        }
        if ($statOptimizedCount.length) {
            $statOptimizedCount.text(Number(stats.optimized_attachments).toLocaleString());
        }
        if ($statSubsizesDesc.length) {
            $statSubsizesDesc.text(Number(stats.optimized_subsizes).toLocaleString() + ' thumbnails converted');
        }
        if ($statNextgenCount.length) {
            $statNextgenCount.text(Number(stats.webp_count + stats.avif_count).toLocaleString());
        }
    }

    function startBulkProcess() {
        state.isRunning = true;
        state.isPaused = false;
        state.currentIndex = 0;
        state.errorCount = 0;

        $btnStart.hide();
        $btnPause.show();
        $btnResume.hide();
        $progressStatus.text(config.i18n.optimizing);
        appendFeed('Scanning media attachments for pending optimizations...', 'info');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_get_queue',
                nonce: config.nonce
            },
            success: function (res) {
                if (!res.success || !res.data) {
                    appendFeed('Failed to query attachment queue.', 'error');
                    finishProcess(false);
                    return;
                }

                state.queue = res.data.ids || [];
                state.total = state.queue.length;

                if (state.total === 0) {
                    appendFeed('All media attachments are currently up to date.', 'success');
                    $progressStatus.text(config.i18n.completed);
                    updateProgressUI();
                    finishProcess(true);
                    return;
                }

                appendFeed('Found ' + state.total + ' images requiring processing.', 'info');
                updateProgressUI();
                $activeItemBox.fadeIn(150);
                processNextItem();
            },
            error: function () {
                appendFeed('Server error connecting to queue endpoint.', 'error');
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
            appendFeed('Optimization paused.', 'warn');
            return;
        }

        if (state.currentIndex >= state.queue.length) {
            appendFeed('Batch run complete: all library assets optimized.', 'success');
            $progressStatus.text(config.i18n.completed);
            $activeItemBox.fadeOut(150);
            finishProcess(true);
            return;
        }

        const attachmentId = state.queue[state.currentIndex];
        $activeFilename.text('#' + attachmentId);
        $activeSavings.text('Compressing...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'super_optimizer_bulk_process_item',
                nonce: config.nonce,
                attachment_id: attachmentId
            },
            success: function (res) {
                if (res.success && res.data) {
                    const data = res.data;
                    const savedFormatted = formatBytes(data.bytes_saved);
                    $activeFilename.text(data.filename);
                    $activeSavings.text('-' + data.compression_ratio + '% (' + savedFormatted + ')');

                    const feedText = '(' + (state.currentIndex + 1) + '/' + state.total + ') ' +
                        data.filename + ' -> Saved ' + savedFormatted + ' (-' + data.compression_ratio + '%)' +
                        (data.webp_count > 0 ? ' [WebP generated]' : '');
                    appendFeed(feedText, 'success');

                    updateTelemetry(data.stats);
                } else {
                    state.errorCount++;
                    const errMsg = res.data ? res.data.message : 'Processing error';
                    appendFeed('Skipped #' + attachmentId + ': ' + errMsg, 'warn');
                }

                state.currentIndex++;
                updateProgressUI();

                // Non-blocking yield for browser paint
                setTimeout(processNextItem, 50);
            },
            error: function () {
                state.errorCount++;
                appendFeed('Network timeout on #' + attachmentId + '. Moving to next asset...', 'error');
                state.currentIndex++;
                updateProgressUI();
                setTimeout(processNextItem, 120);
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
        appendFeed('Resuming optimization batch...', 'info');
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
                    appendFeed('Optimization index wiped. All media attachments set to pending.', 'warn');
                    updateTelemetry(res.data.stats);
                    state.currentIndex = 0;
                    state.total = 0;
                    updateProgressUI();
                    $progressStatus.text(config.i18n.ready);
                }
            }
        });
    }

    // Attach listeners
    $btnStart.on('click', startBulkProcess);
    $btnPause.on('click', pauseBulkProcess);
    $btnResume.on('click', resumeBulkProcess);
    $btnReset.on('click', resetIndex);
    $btnClearLog.on('click', function () {
        $consoleStream.empty();
    });

})(jQuery);
