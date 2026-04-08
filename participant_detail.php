<?php
session_start();
require_once __DIR__ . '/bootstrap.php';
SiteAuth::requirePage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student detail — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa, #6d28d9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-secondary { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); }
        .stat-badge { background: rgba(124,58,237,0.15); border: 1px solid rgba(124,58,237,0.2); }
        .q-correct { border-left: 3px solid #34d399; }
        .q-wrong { border-left: 3px solid #f87171; }
        .q-pending { border-left: 3px solid #94a3b8; }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <a href="#" id="backLink" class="text-sm text-gray-500 hover:text-gray-300 mb-6 inline-block">← Back to batch</a>

        <div id="needAuth" class="hidden text-center py-16">
            <p class="text-gray-400 mb-4">Sign in to the batch first.</p>
            <a href="#" id="batchLink" class="text-violet-400 underline">Open batch</a>
        </div>

        <div id="mainContent" class="hidden">
            <div class="mb-8">
                <p class="text-xs text-violet-400 uppercase tracking-wider mb-1" id="batchLine"></p>
                <h1 class="text-2xl sm:text-3xl font-bold gradient-text" id="studentTitle">…</h1>
                <p class="text-gray-500 text-sm mt-1 font-mono" id="studentId"></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6" id="summaryCards"></div>

            <div id="quizSections" class="space-y-8"></div>
        </div>
    </div>

    <script>
    const API = 'api/handler';
    const params = new URLSearchParams(window.location.search);
    const BATCH_ID = params.get('batch_id');
    const PARTICIPANT_ID = params.get('participant_id');

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }

    function loadPage() {
        if (!BATCH_ID || !PARTICIPANT_ID) {
            $('#needAuth').removeClass('hidden');
            $('#needAuth p').text('Missing batch or participant.');
            return;
        }
        $('#backLink').attr('href', 'batch?id=' + encodeURIComponent(BATCH_ID));

        $.post(API, {
            action: 'participant_detail_teacher',
            batch_id: BATCH_ID,
            participant_id: PARTICIPANT_ID
        }, function(res) {
            if (!res.success) {
                $('#needAuth').removeClass('hidden');
                $('#needAuth p').text(res.error || 'Could not load.');
                $('#batchLink').attr('href', 'batch?id=' + encodeURIComponent(BATCH_ID));
                return;
            }

            const d = res.detail;
            document.title = (d.participant_name || 'Student') + ' — NikkQuiz';
            $('#batchLine').text(d.batch_name || '');
            $('#studentTitle').text(d.participant_name || 'Student');
            $('#studentId').text(d.participant_id || '');

            const s = d.summary || {};
            const cards = [
                { k: 'Quizzes in batch', v: s.quizzes_in_batch ?? '—', d: '' },
                { k: 'Completed', v: s.quizzes_completed ?? '—', d: 'submitted attempts' },
                { k: 'Avg (finished)', v: s.average_percentage_finished != null ? s.average_percentage_finished + '%' : '—', d: 'across quizzes' }
            ];
            let ch = '';
            cards.forEach(c => {
                ch += '<div class="glass-card rounded-xl p-4 text-center">';
                ch += '<p class="text-2xl font-bold text-violet-300">' + escapeHtml(String(c.v)) + '</p>';
                ch += '<p class="text-xs text-gray-500 mt-1">' + escapeHtml(c.k) + '</p>';
                if (c.d) ch += '<p class="text-[10px] text-gray-600">(' + escapeHtml(c.d) + ')</p>';
                ch += '</div>';
            });
            $('#summaryCards').html(ch);

            const quizzes = d.quizzes || [];
            let qh = '';
            quizzes.forEach(function(q) {
                const st = q.attempt_status;
                let head = '<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">';
                head += '<div><h2 class="text-lg font-semibold text-white">' + escapeHtml(q.quiz_name) + '</h2>';
                head += '<p class="text-xs text-gray-500 mt-1">';
                if (q.public_slug) {
                    head += '<span class="stat-badge px-2 py-0.5 rounded text-[10px] mr-2">Slug: ' + escapeHtml(q.public_slug) + '</span>';
                }
                head += '</p></div>';
                head += '<div class="text-right text-sm">';
                if (st === 'finished') {
                    head += '<span class="text-violet-300 font-semibold">' + q.marks + '/' + q.total + '</span>';
                    head += ' <span class="text-gray-400">(' + q.percentage + '%)</span>';
                } else if (st === 'in_progress') {
                    head += '<span class="text-amber-400">In progress</span>';
                    if (q.total) head += ' <span class="text-gray-500">(' + q.total + ' questions)</span>';
                } else {
                    head += '<span class="text-gray-500">Not started</span>';
                }
                head += '</div></div>';

                if (q.started_at) {
                    head += '<p class="text-xs text-gray-600 mb-3">Started: ' + escapeHtml(q.started_at) + (q.completed_at ? ' · Finished: ' + escapeHtml(q.completed_at) : '') + '</p>';
                }

                qh += '<section class="glass-card rounded-2xl p-5 sm:p-6">' + head;

                if (st === 'not_started') {
                    qh += '<p class="text-gray-500 text-sm">No attempt for this quiz yet.</p>';
                    qh += '</section>';
                    return;
                }

                const qs = q.questions || [];
                if (qs.length === 0) {
                    qh += '<p class="text-gray-500 text-sm">No question data.</p>';
                    qh += '</section>';
                    return;
                }

                qs.forEach(function(item, i) {
                    let cls = 'q-pending';
                    let badge = '<span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300">Pending</span>';
                    if (st === 'finished') {
                        if (item.answered) {
                            if (item.is_correct) {
                                cls = 'q-correct';
                                badge = '<span class="text-xs px-2 py-0.5 rounded bg-emerald-900/50 text-emerald-300 border border-emerald-700/50">Correct</span>';
                            } else {
                                cls = 'q-wrong';
                                badge = '<span class="text-xs px-2 py-0.5 rounded bg-red-900/40 text-red-300 border border-red-700/50">Incorrect</span>';
                            }
                        } else {
                            badge = '<span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300">No answer</span>';
                        }
                    } else {
                        badge = '<span class="text-xs px-2 py-0.5 rounded bg-amber-900/40 text-amber-300">In progress</span>';
                    }

                    qh += '<div class="glass-card rounded-xl p-4 mb-3 last:mb-0 ' + cls + ' pl-4">';
                    qh += '<div class="flex flex-wrap items-center justify-between gap-2 mb-2">';
                    qh += '<span class="text-xs text-gray-500">Question ' + item.position + '</span>';
                    qh += badge + '</div>';
                    qh += '<p class="text-sm text-gray-200 mb-3 leading-relaxed">' + escapeHtml(item.question) + '</p>';
                    qh += '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">';
                    qh += '<div><span class="text-gray-500">Their answer:</span> ';
                    qh += '<span class="text-white">' + (item.selected_label != null ? escapeHtml(item.selected_label) : '<span class="text-gray-500">—</span>') + '</span></div>';
                    qh += '<div><span class="text-gray-500">Correct:</span> ';
                    qh += '<span class="text-emerald-300/90">' + escapeHtml(item.correct_label) + '</span></div>';
                    qh += '</div></div>';
                });

                qh += '</section>';
            });

            if (quizzes.length === 0) {
                qh = '<p class="text-gray-500">No quizzes in this batch.</p>';
            }
            $('#quizSections').html(qh);

            $('#mainContent').removeClass('hidden');
        }, 'json').fail(function() {
            $('#needAuth').removeClass('hidden');
            $('#needAuth p').text('Request failed.');
            $('#batchLink').attr('href', 'batch?id=' + encodeURIComponent(BATCH_ID));
        });
    }

    loadPage();
    </script>
</body>
</html>
