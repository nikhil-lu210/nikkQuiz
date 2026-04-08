<?php
session_start();
require_once __DIR__ . '/includes/SiteAuth.php';
SiteAuth::requirePage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa, #6d28d9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-secondary { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); }
        .stat-tile { background: linear-gradient(145deg, rgba(124,58,237,0.12), rgba(236,72,153,0.05)); border: 1px solid rgba(124,58,237,0.2); }
        .stat-badge { background: rgba(124,58,237,0.15); border: 1px solid rgba(124,58,237,0.2); }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="max-w-5xl mx-auto px-4 py-8">
        <a href="batch.php?id=" id="backLink" class="text-sm text-gray-500 hover:text-gray-300 mb-6 inline-block">← Back to batch</a>

        <div id="needAuth" class="hidden text-center py-16">
            <p class="text-gray-400 mb-4">Sign in to the batch first.</p>
            <a href="#" id="batchLink" class="text-violet-400 underline">Open batch</a>
        </div>

        <div id="mainContent" class="hidden">
            <div class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold gradient-text mb-2" id="quizTitle">…</h1>
                <p class="text-gray-500 text-sm" id="quizMeta"></p>
            </div>

            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Class performance</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8" id="summaryCards"></div>

            <h2 class="text-lg font-semibold text-gray-200 mb-3">Participants</h2>
            <div class="glass-card rounded-xl overflow-x-auto mb-10">
                <table class="w-full text-sm min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-800 text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Score</th>
                            <th class="px-4 py-3 text-center">%</th>
                            <th class="px-4 py-3 text-right">Grade</th>
                            <th class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">Start</th>
                            <th class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">End</th>
                            <th class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">Duration</th>
                        </tr>
                    </thead>
                    <tbody id="participantRows"></tbody>
                </table>
            </div>

            <h2 class="text-lg font-semibold text-gray-200 mb-3">Question pool</h2>
            <div id="questionList" class="space-y-3"></div>
        </div>
    </div>

    <script>
    const API = 'api/handler.php';
    const params = new URLSearchParams(window.location.search);
    const BATCH_ID = params.get('batch_id');
    const QUIZ_ID = params.get('id');

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }

    function loadPage() {
        $.when(
            $.post(API, { action: 'get_quiz_teacher', batch_id: BATCH_ID, quiz_id: QUIZ_ID }),
            $.post(API, { action: 'quiz_stats_teacher', batch_id: BATCH_ID, quiz_id: QUIZ_ID })
        ).done(function(a, b) {
            const resQ = a[0];
            const resS = b[0];
            if (!resQ.success) {
                $('#needAuth').removeClass('hidden');
                $('#batchLink').attr('href', 'batch.php?id=' + encodeURIComponent(BATCH_ID));
                return;
            }
            const q = resQ.quiz;
            const qi = q.quiz_info;
            document.title = qi.name + ' — NikkQuiz';
            $('#quizTitle').text(qi.name);
            $('#quizMeta').text('Time limit: ' + qi.time_limit + ' min · Up to ' + qi.total_display_questions + ' questions shown per attempt · ' + (qi.status === 'active' ? 'Active' : 'Inactive'));
            $('#backLink').attr('href', 'batch.php?id=' + encodeURIComponent(BATCH_ID));

            if (resS.success && resS.stats) {
                const s = resS.stats;
                const cards = [
                    { k: 'Roster', v: s.roster_count, d: 'students in batch' },
                    { k: 'Completed', v: s.completed_count, d: 'submitted attempts' },
                    { k: 'Class average', v: s.class_average_pct != null ? s.class_average_pct + '%' : '—', d: 'finished only' },
                    { k: 'Best score', v: s.class_best_pct != null ? s.class_best_pct + '%' : '—', d: 'single attempt' },
                ];
                let ch = '';
                cards.forEach(function(c) {
                    ch += '<div class="stat-tile rounded-xl p-4 text-center">';
                    ch += '<p class="text-2xl font-bold text-white">' + escapeHtml(String(c.v)) + '</p>';
                    ch += '<p class="text-xs text-gray-400 mt-1">' + escapeHtml(c.k) + '</p>';
                    ch += '<p class="text-[10px] text-gray-600">' + escapeHtml(c.d) + '</p></div>';
                });
                $('#summaryCards').html(ch);

                let pr = '';
                (s.participants || []).forEach(function(p) {
                    let st = '';
                    let score = '—';
                    let pct = '—';
                    let gr = '—';
                    let tStart = '—';
                    let tEnd = '—';
                    let tDur = '—';
                    if (p.status === 'not_started') {
                        st = '<span class="text-gray-500">Not started</span>';
                    } else if (p.status === 'in_progress') {
                        st = '<span class="text-amber-400">In progress</span>';
                        tStart = escapeHtml(p.started_at || '—');
                    } else if (p.status === 'finished') {
                        st = '<span class="text-emerald-400">Completed</span>';
                        score = (p.marks != null ? p.marks : 0) + '/' + (p.total || 0);
                        pct = (p.percentage != null ? p.percentage : 0) + '%';
                        gr = (p.emoji || '') + ' <span class="text-gray-500 text-xs">' + escapeHtml(p.grade || '') + '</span>';
                        tStart = escapeHtml(p.started_at || '—');
                        tEnd = escapeHtml(p.completed_at || '—');
                        tDur = p.total_time != null && p.total_time !== '' ? escapeHtml(p.total_time) : '—';
                    }
                    pr += '<tr class="border-b border-gray-800/50 hover:bg-white/5">';
                    pr += '<td class="px-4 py-3"><span class="text-white font-medium">' + escapeHtml(p.participant_name) + '</span><br><span class="text-[10px] text-gray-500 font-mono">' + escapeHtml(p.participant_id) + '</span></td>';
                    pr += '<td class="px-4 py-3">' + st + '</td>';
                    pr += '<td class="px-4 py-3 text-center text-violet-300">' + score + '</td>';
                    pr += '<td class="px-4 py-3 text-center">' + pct + '</td>';
                    pr += '<td class="px-4 py-3 text-right">' + gr + '</td>';
                    pr += '<td class="px-4 py-3 text-right text-xs text-gray-500 whitespace-nowrap">' + tStart + '</td>';
                    pr += '<td class="px-4 py-3 text-right text-xs text-gray-500 whitespace-nowrap">' + tEnd + '</td>';
                    pr += '<td class="px-4 py-3 text-right text-xs text-violet-300/90 font-medium whitespace-nowrap">' + tDur + '</td></tr>';
                });
                $('#participantRows').html(pr || '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No roster data.</td></tr>');
            }

            let qh = '';
            (q.questions || []).forEach(function(item) {
                qh += '<div class="glass-card rounded-xl p-4 text-sm"><span class="stat-badge px-2 py-0.5 rounded text-xs font-mono mr-2">#' + escapeHtml(String(item.id)) + '</span>';
                qh += '<p class="text-gray-200 mt-2">' + escapeHtml(item.question) + '</p></div>';
            });
            $('#questionList').html(qh || '<p class="text-gray-500 text-sm">No questions.</p>');

            $('#mainContent').removeClass('hidden');
        });
    }

    $(document).ready(function() {
        if (!BATCH_ID || !QUIZ_ID) {
            window.location.href = 'index.php';
            return;
        }
        loadPage();
    });
    </script>
</body>
</html>
