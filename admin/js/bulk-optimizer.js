/**
 * Super Optimizer - Bulk Queue Runner
 *
 * Browser-managed atomic batch queue runner with live telemetry.
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
    const $statusPill     = $('#bulk-status-pill');
    const $activeItemBox  = $('#so-active-item');
    const $activeFilename = $('#so-current-filename');
    const $activeSavings  = $('#so-current-savings');
    const $consoleStream  = $('#so-console-stream');

    // Stat card DOM elements
    const $statOptimizedCount = $('#stat-optimized-count');
    const $statSubsizesDesc   = $('#stat-subsizes-desc');
    const $statBytesSaved     = $('#stat-bytes-saved');
    const $statPercentage     = $('#stat-percentage');
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
        return '[' + d.toTimeString().split(' ')[0] + ']';
    }

    function appendLog(message, type) {
        type = type || 'info';
        const $line = $('<div class="so-console-line so-line-' + type + '"></div>');
        $line.append('<span class="so-line-time">' + getTimeStamp() + '</span>');
        $line.append('<span class="so-line-msg">' + message + '</span>');
        $consoleStream.append($line);
        $consoleStream.scrollTop($consoleStream[0].scrollHeight);
    }

    function updatePill(statusText, statusCode) {
        $statusPill.attr('data-status', statusCode);
        $statusPill.find('.so-pill-text').text(statusText);
    }

    function updateProgressUI() {
        const processed = state.currentIndex;
        const total = state.total;
        const pct = total > 0 ? Math.round((processed / total) * 100) : 0;

        $progressBar.css('width', pct + '%');
        $progressPct.text(pct + '%');
        $progressCounts.text(processed + ' / ' + total + ' images');
    }

    function updateStatCards(stats) {
        if (!stats) return;
        if ($statOptimizedCount.length) $statOptimizedCount.text(Number(stats.optimized_attachments).toLocaleString());
        if ($statSubsizesDesc.length) $statSubsizesDesc.text(Number(stats.optimized_subsizes).toLocaleString() + ' thumbnails optimized');
        if ($statBytesSaved.length) $statBytesSaved.text(formatBytes(stats.total_bytes_saved));
        if ($statPercentage.length) $statPercentage.text(stats.percentage_saved);
        if ($statNextgenCount.length) $statNextgenCount.text(Number(stats.webp_count + stats.avif_count).toLocaleString());
    }

    function startBulkProcess() {
        state.isRunning = true;
        state.isPaused = false;
        state.currentIndex = 0;
        state.errorCount = 0;

        $btnStart.hide();
        $btnPause.show();
        $btnResume.hide();
        updatePill('Running', 'running');
        $progressStatus.text(config.i18n.optimizing);
        appendLog('Scanning WordPress media library for unoptimized assets...', 'info');

        // Fetch queue
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
                    appendLog('Failed to retrieve media library queue.', 'error');
                    finishProcess(false);
                    return;
                }

                state.queue = res.data.ids || [];
                state.total = state.queue.length;

                if (state.total === 0) {
                    appendLog('All image attachments are already fully optimized.', 'success');
                    $progressStatus.text(config.i18n.completed);
                    updateProgressUI();
                    finishProcess(true);
                    return;
                }

                appendLog('Queue initialized: ' + state.total + ' images pending optimization.', 'info');
                updateProgressUI();
                $activeItemBox.slideDown(200);
                processNextItem();
            },
            error: function () {
                appendLog('Server error connecting to queue endpoint.', 'error');
                finishProcess(false);
            }
        });
    }

    function processNextItem() {
        if (!state.isRunning) return;

        if (state.isPaused) {
            updatePill('Paused', 'idle');
            $progressStatus.text(config.i18n.paused);
            $btnPause.hide();
            $btnResume.show();
            appendLog('Bulk runner paused by user.', 'warn');
            return;
        }

        if (state.currentIndex >= state.queue.length) {
            appendLog('Bulk optimization completed successfully.', 'success');
            $progressStatus.text(config.i18n.completed);
            $activeItemBox.slideUp(200);
            finishProcess(true);
            return;
        }

        const attachmentId = state.queue[state.currentIndex];
        $activeFilename.text('Attachment #' + attachmentId);
        $activeSavings.text('Optimizing...');

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
                    $activeSavings.text('-' + data.compression_ratio + '% (' + savedFormatted + ' saved)');

                    const logMsg = '(' + (state.currentIndex + 1) + '/' + state.total + ') ' +
                        data.filename + ' -> Saved ' + savedFormatted + ' (-' + data.compression_ratio + '%)' +
                        (data.webp_count > 0 ? ' [+' + data.webp_count + ' WebP]' : '');
                    appendLog(logMsg, 'success');

                    updateStatCards(data.stats);
                } else {
                    state.errorCount++;
                    const errMsg = res.data ? res.data.message : 'Unknown optimization error';
                    appendLog('Error on attachment #' + attachmentId + ': ' + errMsg, 'warn');
                }

                state.currentIndex++;
                updateProgressUI();

                // Small breath to avoid thread locking
                setTimeout(processNextItem, 60);
            },
            error: function (xhr) {
                state.errorCount++;
                appendLog('Network or memory error on item #' + attachmentId + '. Continuing queue...', 'error');
                state.currentIndex++;
                updateProgressUI();
                setTimeout(processNextItem, 150);
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
        updatePill('Running', 'running');
        $progressStatus.text(config.i18n.optimizing);
        appendLog('Resuming optimization queue...', 'info');
        processNextItem();
    }

    function finishProcess(allDone) {
        state.isRunning = false;
        state.isPaused = false;
        $btnPause.hide();
        $btnResume.hide();
        $btnStart.show();
        updatePill(allDone ? 'Completed' : 'Idle', allDone ? 'completed' : 'idle');
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
                    appendLog('Optimization index wiped. All attachments marked pending.', 'warn');
                    updateStatCards(res.data.stats);
                    state.currentIndex = 0;
                    state.total = 0;
                    updateProgressUI();
                    $progressStatus.text(config.i18n.ready);
                }
            }
        });
    }

    // Bind Event Listeners
    $btnStart.on('click', startBulkProcess);
    $btnPause.on('click', pauseBulkProcess);
    $btnResume.on('click', resumeBulkProcess);
    $btnReset.on('click', resetIndex);
    $btnClearLog.on('click', function () {
        $consoleStream.empty();
    });

})(jQuery);
