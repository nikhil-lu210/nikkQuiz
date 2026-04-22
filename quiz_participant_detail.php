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
    <title>Student — quiz answers — NikkQuiz</title>
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
        .quiz-detail-opt-correct { background-color: rgba(34, 197, 94, 0.22) !important; border-color: #22c55e !important; }
        .quiz-detail-opt-wrong { background-color: rgba(239, 68, 68, 0.18) !important; border-color: #ef4444 !important; }
        .quiz-detail-opt-neutral { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.12); }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="max-w-2xl mx-auto px-4 py-8">
        <a href="#" id="backLink" class="text-sm text-gray-500 hover:text-gray-300 mb-6 inline-block">← Back to quiz</a>

        <div id="needAuth" class="hidden text-center py-16">
            <p class="text-gray-400 mb-4" id="needAuthText">Sign in to the batch first.</p>
            <a href="#" id="batchLink" class="text-violet-400 underline">Open batch</a>
        </div>

        <div id="mainContent" class="hidden">
            <p class="text-xs text-violet-400 uppercase tracking-wider mb-1" id="batchLine"></p>
            <h1 class="text-2xl sm:text-3xl font-bold gradient-text" id="titleLine">…</h1>
            <p class="text-gray-500 text-sm mt-1" id="studentLine"></p>
            <p class="text-gray-400 text-sm mt-2" id="metaLine"></p>
            <div class="glass-card rounded-xl p-5 sm:p-6 mt-6" id="questionBlock"></div>
        </div>
    </div>

    <script>
    const API = 'api/handler';
    const params = new URLSearchParams(window.location.search);
    const BATCH_ID = params.get('batch_id');
    const QUIZ_ID = params.get('quiz_id');
    const PARTICIPANT_ID = params.get('participant_id');

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }
    function optionLetter(i) {
        return String.fromCharCode(65 + i);
    }
    function renderQuestions(d) {
        const st = d.attempt_status;
        const questions = d.questions || [];
        if (st === 'not_started') {
            return '<p class="text-gray-400 text-center py-6">This student has not started this quiz yet.</p>';
        }
        if (questions.length === 0) {
            return '<p class="text-gray-400 text-center py-6">No question data to show.</p>';
        }
        let h = '<div class="space-y-8">';
        questions.forEach(function(q, idx) {
            const opts = Array.isArray(q.options) ? q.options : [];
            const correctIdx = typeof q.correct_index === 'number' ? q.correct_index : -1;
            const selRaw = q.selected_index;
            const selIdx = (selRaw !== null && selRaw !== undefined && selRaw !== '') ? parseInt(selRaw, 10) : null;
            const answered = !!q.answered;
            const isCorrect = !!q.is_correct;
            const pos = q.position != null ? q.position : (idx + 1);
            h += '<div class="pb-8 border-b border-gray-800 last:border-0 last:pb-0">';
            h += '<p class="text-xs text-violet-400 font-semibold mb-1">Question ' + escapeHtml(String(pos)) + '</p>';
            h += '<p class="text-white font-medium mb-3">' + escapeHtml(q.question || '') + '</p>';
            h += '<ul class="space-y-2">';
            opts.forEach(function(opt, oi) {
                let rowCls = 'quiz-detail-opt-neutral rounded-lg border px-3 py-2';
                let aria = '';
                if (correctIdx >= 0 && oi === correctIdx) {
                    rowCls = 'quiz-detail-opt-correct rounded-lg border px-3 py-2';
                    aria = ' aria-label="Correct answer"';
                } else if (answered && selIdx !== null && !isNaN(selIdx) && oi === selIdx && oi !== correctIdx) {
                    rowCls = 'quiz-detail-opt-wrong rounded-lg border px-3 py-2';
                    aria = ' aria-label="Selected answer"';
                }
                h += '<li class="' + rowCls + '"' + aria + '><span class="text-gray-500 mr-2">' + escapeHtml(optionLetter(oi)) + '.</span> ';
                h += escapeHtml(String(opt)) + '</li>';
            });
            h += '</ul>';
            if (st === 'finished' && !answered) {
                h += '<p class="text-xs text-gray-500 mt-2">No answer recorded for this question.</p>';
            }
            if (st === 'finished' && answered) {
                h += '<div class="mt-2">';
                h += isCorrect
                    ? '<span class="text-xs font-semibold text-emerald-400">Correct</span>'
                    : '<span class="text-xs font-semibold text-red-400">Wrong</span>';
                h += '</div>';
            } else if (st === 'in_progress') {
                h += '<div class="mt-2"><span class="text-xs text-amber-400">In progress</span></div>';
            }
            h += '</div>';
        });
        h += '</div>';
        return h;
    }
    function loadPage() {
        if (!BATCH_ID || !QUIZ_ID || !PARTICIPANT_ID) {
            $('#needAuth').removeClass('hidden');
            $('#needAuthText').text('Missing batch, quiz, or student.');
            return;
        }
        const back = 'quiz?batch_id=' + encodeURIComponent(BATCH_ID) + '&id=' + encodeURIComponent(QUIZ_ID);
        $('#backLink').attr('href', back);

        $.post(API, {
            action: 'quiz_participant_detail_teacher',
            batch_id: BATCH_ID,
            quiz_id: QUIZ_ID,
            participant_id: PARTICIPANT_ID
        }, function(res) {
            if (!res.success) {
                $('#needAuth').removeClass('hidden');
                $('#needAuthText').text(res.error || 'Could not load.');
                $('#batchLink').attr('href', 'batch?id=' + encodeURIComponent(BATCH_ID));
                return;
            }
            const d = res.detail;
            const qn = d.quiz_name || 'Quiz';
            document.title = (d.participant_name || 'Student') + ' — ' + qn;
            $('#batchLine').text(d.batch_name || '');
            $('#titleLine').text(qn);
            $('#studentLine').html(escapeHtml(d.participant_name || '') + ' <span class="text-gray-600 font-mono text-xs">' + escapeHtml(d.participant_id || '') + '</span>');
            const st = d.attempt_status;
            const parts = [];
            if (d.time_limit) {
                parts.push('Time limit: ' + d.time_limit + ' min');
            }
            if (st === 'finished') {
                parts.push((d.marks != null ? d.marks : 0) + '/' + (d.total || 0) + ' correct');
                if (d.percentage != null) {
                    parts.push(d.percentage + '%');
                }
                if (d.grade) {
                    parts.push((d.emoji || '') + ' ' + d.grade);
                }
                if (d.total_time) {
                    parts.push('Duration: ' + d.total_time);
                }
            } else if (st === 'in_progress') {
                parts.push('In progress');
            } else {
                parts.push('Not started');
            }
            $('#metaLine').text(parts.join(' · '));
            $('#questionBlock').html(renderQuestions(d));
            $('#mainContent').removeClass('hidden');
        }, 'json').fail(function() {
            $('#needAuth').removeClass('hidden');
            $('#needAuthText').text('Request failed.');
            $('#batchLink').attr('href', 'batch?id=' + encodeURIComponent(BATCH_ID));
        });
    }
    $(document).ready(loadPage);
    </script>
</body>
</html>
