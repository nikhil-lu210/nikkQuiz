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
    <title>Quiz questions — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <style>
        .qp-page { font-family: 'DM Sans', system-ui, sans-serif; }
        .bp-surface { background: #fff; border: 1px solid #e2e8f0; }
        .bp-muted { color: #64748b; }
        .bp-heading { color: #0f172a; letter-spacing: -0.02em; }
        .bp-btn {
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem;
            transition: background .15s, border-color .15s, color .15s;
        }
        .bp-btn-primary { background: #4f46e5; color: #fff; border: 1px solid #4f46e5; }
        .bp-btn-primary:hover { background: #4338ca; border-color: #4338ca; }
        .bp-btn-secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .bp-btn-secondary:hover { background: #f8fafc; border-color: #94a3b8; }
        .bp-btn-danger { background: #fff; color: #dc2626; border: 1px solid #fecaca; }
        .bp-btn-danger:hover { background: #fef2f2; }
        .bp-btn-danger:disabled { opacity: 0.45; cursor: not-allowed; }
        .bp-btn-ok { background: #059669; color: #fff; border: 1px solid #059669; }
        .bp-btn-ok:hover { background: #047857; border-color: #047857; }
        .bp-input {
            background: #fff; border: 1px solid #cbd5e1; color: #0f172a;
            border-radius: 0.5rem;
        }
        .bp-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12); }
        .bp-modal-overlay { background: rgba(15, 23, 42, 0.45); }
        .bp-modal-card { background: #fff; border: 1px solid #e2e8f0; }
        .opt-correct { border-color: #059669 !important; background: #ecfdf5; }
        /* MCQ list: Tailwind v2 CDN has no `emerald-*`; use explicit green-200 so correct row always shows */
        .quiz-option--correct {
            background-color: #bbf7d0 !important;
            border-color: #16a34a !important;
            color: #0f172a !important;
            font-weight: 600;
        }
        .quiz-option--neutral {
            background-color: #fff;
            border-color: #e2e8f0;
            color: #334155;
        }
        .stat-pool-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
            padding: 0.4rem 0.75rem;
            border-radius: 9999px;
            border-width: 1px;
            transition: transform 0.12s, box-shadow 0.12s, background 0.12s, border-color 0.12s;
        }
        .stat-pool-btn:not(:disabled):hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        }
        .stat-pool-btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }
        .stat-pool-btn--att {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .stat-pool-btn--att:not(:disabled):hover { background: #e2e8f0; border-color: #94a3b8; }
        .stat-pool-btn--ok {
            background: #ecfdf5;
            border-color: #6ee7b7;
            color: #047857;
        }
        .stat-pool-btn--ok:not(:disabled):hover { background: #d1fae5; border-color: #34d399; }
        .stat-pool-btn--bad {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #be123c;
        }
        .stat-pool-btn--bad:not(:disabled):hover { background: #ffe4e6; border-color: #fb7185; }
        .opt-sel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.75rem;
            padding: 0.35rem 0.6rem;
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.2;
            border-radius: 0.375rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #4f46e5;
            flex-shrink: 0;
            align-self: center;
            margin: 0.35rem 0.5rem 0.35rem 0;
            transition: transform 0.12s, box-shadow 0.12s, background 0.12s, border-color 0.12s;
        }
        .opt-sel-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 1px 6px rgba(15, 23, 42, 0.1);
            background: #eef2ff;
            border-color: #a5b4fc;
        }
        .quiz-option--correct .opt-sel-btn {
            background: #f0fdf4;
            border-color: #6ee7b0;
            color: #047857;
        }
        .quiz-option--correct .opt-sel-btn:hover {
            background: #dcfce7;
            border-color: #34d399;
        }
    </style>
</head>
<body class="qp-page min-h-screen bg-slate-50">
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 space-y-2"></div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
        <a href="batch?id=" id="backLink" class="text-sm text-slate-500 hover:text-indigo-600 mb-6 inline-block">← Back to batch</a>

        <div id="needAuth" class="hidden bp-surface rounded-xl p-10 text-center shadow-sm">
            <p class="bp-muted mb-4">Sign in to this batch first to manage questions.</p>
            <a href="#" id="batchLink" class="text-indigo-600 font-medium hover:underline">Open batch</a>
        </div>

        <div id="mainContent" class="hidden">
            <div class="mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold bp-heading" id="quizTitle">…</h1>
                    <p class="bp-muted text-sm mt-1" id="quizMeta"></p>
                    <p id="deleteHint" class="hidden text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-4 max-w-2xl"></p>
                </div>
                <button type="button" id="btnAddQuestion" class="bp-btn bp-btn-ok px-5 py-2.5 text-sm shrink-0 self-start">Add question</button>
            </div>

            <div id="questionList" class="space-y-4"></div>
        </div>
    </div>

    <div id="modalPoolStat" class="fixed inset-0 z-50 hidden items-center justify-center bp-modal-overlay p-4 overflow-y-auto" aria-hidden="true">
        <div class="bp-modal-card rounded-xl w-full max-w-md p-6 sm:p-7 relative my-8 shadow-lg">
            <button type="button" class="close-pool-stat absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl leading-none" aria-label="Close">×</button>
            <h3 class="text-lg font-bold bp-heading pr-8 mb-1" id="modalPoolStatTitle">Participants</h3>
            <p class="text-xs text-slate-500 mb-4" id="modalPoolStatSub"></p>
            <ul class="max-h-72 overflow-y-auto space-y-2 text-sm" id="modalPoolStatList"></ul>
        </div>
    </div>

    <div id="modalOptionStat" class="fixed inset-0 z-50 hidden items-center justify-center bp-modal-overlay p-4 overflow-y-auto" aria-hidden="true">
        <div class="bp-modal-card rounded-xl w-full max-w-md p-6 sm:p-7 relative my-8 shadow-lg">
            <button type="button" class="close-option-stat absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl leading-none" aria-label="Close">×</button>
            <h3 class="text-lg font-bold bp-heading pr-8 mb-1" id="modalOptionStatTitle">Participants</h3>
            <p class="text-xs text-slate-500 mb-4" id="modalOptionStatSub"></p>
            <ul class="max-h-72 overflow-y-auto space-y-2 text-sm" id="modalOptionStatList"></ul>
        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center bp-modal-overlay p-4 overflow-y-auto">
        <div class="bp-modal-card rounded-xl w-full max-w-lg p-6 sm:p-8 relative my-8 shadow-lg">
            <button type="button" class="close-mod absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl leading-none" data-close="modalEdit" aria-label="Close">×</button>
            <h3 class="text-lg font-bold bp-heading mb-4" id="editModalTitle">Edit question</h3>
            <form id="formEdit">
                <input type="hidden" id="modalMode" value="edit">
                <input type="hidden" name="pool_index" id="editPoolIndex" value="">
                <input type="hidden" name="qid" id="editQuestionId" value="">
                <label class="block text-sm font-medium text-slate-700 mb-1">Question</label>
                <textarea name="question" id="editQuestionText" required rows="3" class="bp-input w-full px-4 py-2.5 text-sm mb-4"></textarea>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-700">Options</span>
                    <button type="button" id="btnAddOption" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">+ Add option</button>
                </div>
                <div id="optionsContainer" class="space-y-2 mb-4"></div>
                <p class="text-xs bp-muted mb-2">Correct answer</p>
                <div id="correctRadios" class="flex flex-wrap gap-3 mb-6"></div>
                <button type="submit" id="editModalSubmitBtn" class="bp-btn bp-btn-primary w-full py-2.5">Save question</button>
            </form>
        </div>
    </div>

    <script>
    const API = 'api/handler';
    const params = new URLSearchParams(window.location.search);
    const BATCH_ID = params.get('batch_id');
    const QUIZ_ID = params.get('id');
    let quizPayload = null;
    let poolStats = null; // { per_question: [{ attempts, correct, wrong, *_participants }, ...] }

    function toast(msg, err) {
        const c = err ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-emerald-50 border border-emerald-200 text-emerald-900';
        const $t = $('<div class="px-4 py-3 rounded-lg text-sm shadow-md ' + c + '">' + $('<div>').text(msg).html() + '</div>');
        $('#toastContainer').append($t);
        setTimeout(() => $t.remove(), 4000);
    }

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }

    function optionLetter(i) {
        return String.fromCharCode(65 + i);
    }

    function canDeleteQuestion(poolSize, displayLimit) {
        return poolSize > displayLimit;
    }

    function renderOptionsEditor(opts, answerIndex) {
        const list = (opts && opts.length) ? opts.slice() : ['', ''];
        while (list.length < 2) list.push('');
        let h = '';
        list.forEach(function(o, i) {
            h += '<div class="flex gap-2 items-center opt-row" data-i="' + i + '">';
            h += '<span class="text-xs font-semibold text-slate-500 w-6 shrink-0">' + optionLetter(i) + '</span>';
            h += '<input type="text" class="opt-input bp-input flex-1 px-3 py-2 text-sm" value="">';
            h += '</div>';
        });
        $('#optionsContainer').html(h);
        $('#optionsContainer .opt-row').each(function(i) {
            $(this).find('.opt-input').val(list[i] || '');
        });
        rebuildCorrectRadios(answerIndex);
    }

    function currentOptionValues() {
        const opts = [];
        $('#optionsContainer .opt-input').each(function() {
            const t = $(this).val().trim();
            if (t !== '') opts.push(t);
        });
        return opts;
    }

    function rebuildCorrectRadios(preferredIndex) {
        const opts = currentOptionValues();
        let h = '';
        if (opts.length < 2) {
            $('#correctRadios').html('<span class="text-xs text-amber-700">Enter at least two options.</span>');
            return;
        }
        let checked = preferredIndex;
        if (checked < 0 || checked >= opts.length) checked = 0;
        opts.forEach(function(_, i) {
            const id = 'cr' + i;
            h += '<label class="inline-flex items-center gap-2 text-sm cursor-pointer">';
            h += '<input type="radio" name="correctIdx" id="' + id + '" value="' + i + '"' + (i === checked ? ' checked' : '') + '>';
            h += '<span>' + optionLetter(i) + '</span></label>';
        });
        $('#correctRadios').html(h);
    }

    function openAddModal() {
        $('#modalMode').val('add');
        $('#editModalTitle').text('Add question');
        $('#editModalSubmitBtn').text('Add question').removeClass('bp-btn-primary').addClass('bp-btn-ok');
        $('#editPoolIndex').val('');
        $('#editQuestionId').val('');
        $('#editQuestionText').val('');
        renderOptionsEditor([], 0);
        $('#modalEdit').removeClass('hidden').addClass('flex');
    }

    function openPoolStatModal(poolIndex, kind) {
        closeOptionStatModal();
        const pq = (poolStats && poolStats.per_question) ? (poolStats.per_question[poolIndex] || {}) : {};
        const qn = poolIndex + 1;
        let key = 'attempt_participants';
        let title = 'Who attempted this question';
        let sub = 'Finished attempts in this batch that included this item.';
        if (kind === 'correct') {
            key = 'correct_participants';
            title = 'Correct';
            sub = 'Students who selected the right answer.';
        } else if (kind === 'wrong') {
            key = 'wrong_participants';
            title = 'Wrong or no answer';
            sub = 'Wrong option chosen, or no response recorded.';
        }
        const list = pq[key] || [];
        $('#modalPoolStatTitle').text('Question ' + qn + ' — ' + title);
        $('#modalPoolStatSub').text(sub);
        if (list.length === 0) {
            $('#modalPoolStatList').html('<li class="text-slate-500 text-center py-4">No students in this list.</li>');
        } else {
            let li = '';
            list.forEach(function(p) {
                li += '<li class="bp-surface rounded-lg px-3 py-2.5 border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">';
                li += '<span class="text-slate-900 font-medium">' + escapeHtml(p.participant_name || '—') + '</span>';
                li += '<span class="text-xs text-slate-500 font-mono">' + escapeHtml(p.participant_id || '') + '</span>';
                li += '</li>';
            });
            $('#modalPoolStatList').html(li);
        }
        $('#modalPoolStat').removeClass('hidden').addClass('flex').attr('aria-hidden', 'false');
    }

    function closeOptionStatModal() {
        $('#modalOptionStat').addClass('hidden').removeClass('flex').attr('aria-hidden', 'true');
    }

    function openOptionStatModal(poolIndex, optionIndex) {
        const pq = (poolStats && poolStats.per_question) ? (poolStats.per_question[poolIndex] || {}) : {};
        const perOpt = pq.per_option || [];
        const bucket = perOpt[optionIndex] || { count: 0, participants: [] };
        const list = bucket.participants || [];
        const qn = poolIndex + 1;
        const q = (quizPayload && quizPayload.questions) ? quizPayload.questions[poolIndex] : null;
        const optLetter = optionLetter(optionIndex);
        const rawOpt = (q && q.options && q.options[optionIndex]) != null ? String(q.options[optionIndex]) : '';
        const shortOpt = rawOpt.length > 90 ? rawOpt.slice(0, 87) + '…' : rawOpt;
        closePoolStatModal();
        $('#modalOptionStatTitle').text('Question ' + qn + ' — option ' + optLetter);
        $('#modalOptionStatSub').text(
            (shortOpt ? '“' + shortOpt + '”' : 'Students who chose this option on a finished attempt (no answer is not included).')
        );
        if (list.length === 0) {
            $('#modalOptionStatList').html('<li class="text-slate-500 text-center py-4">No students chose this option.</li>');
        } else {
            let li = '';
            list.forEach(function(p) {
                li += '<li class="bp-surface rounded-lg px-3 py-2.5 border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">';
                li += '<span class="text-slate-900 font-medium">' + escapeHtml(p.participant_name || '—') + '</span>';
                li += '<span class="text-xs text-slate-500 font-mono">' + escapeHtml(p.participant_id || '') + '</span>';
                li += '</li>';
            });
            $('#modalOptionStatList').html(li);
        }
        $('#modalOptionStat').removeClass('hidden').addClass('flex').attr('aria-hidden', 'false');
    }

    function openEditModal(poolIndex) {
        if (!quizPayload || !quizPayload.questions) return;
        const q = quizPayload.questions[poolIndex];
        if (!q) return;
        $('#modalMode').val('edit');
        $('#editModalTitle').text('Edit question');
        $('#editModalSubmitBtn').text('Save question').removeClass('bp-btn-ok').addClass('bp-btn-primary');
        $('#editPoolIndex').val(String(poolIndex));
        $('#editQuestionId').val(String(q.id));
        $('#editQuestionText').val(q.question || '');
        const opts = q.options || [];
        const ans = parseInt(q.answer, 10);
        renderOptionsEditor(opts, isNaN(ans) ? 0 : ans);
        $('#modalEdit').removeClass('hidden').addClass('flex');
    }

    function renderList() {
        if (!quizPayload) return;
        const qi = quizPayload.quiz_info;
        const questions = quizPayload.questions || [];
        const displayLimit = Number(qi.total_display_questions) || 0;
        const poolSize = questions.length;
        const allowDelete = canDeleteQuestion(poolSize, displayLimit);

        $('#quizTitle').text(qi.name);
        document.title = qi.name + ' — Questions';
        $('#quizMeta').text(
            'Time limit: ' + qi.time_limit + ' min · Pool: ' + poolSize + ' · Display per attempt: ' + displayLimit
        );
        $('#backLink').attr('href', 'batch?id=' + encodeURIComponent(BATCH_ID));

        if (!allowDelete) {
            $('#deleteHint').removeClass('hidden').text(
                'Delete is disabled while the pool has only enough questions to meet the per-attempt display limit (' + displayLimit + '). The pool must stay at least that large.'
            );
        } else {
            $('#deleteHint').addClass('hidden').text('');
        }

        const pq = (poolStats && poolStats.per_question) ? poolStats.per_question : [];
        let h = '';
        questions.forEach(function(q, poolIndex) {
            const s = pq[poolIndex] || {};
            const nAtt = Number(s.attempts) || 0;
            const nOk = Number(s.correct) || 0;
            const nBad = Number(s.wrong) || 0;
            const statsHtml = '<div class="flex flex-wrap gap-2 mt-2 mb-1">' +
                '<button type="button" class="stat-pool-btn stat-pool-btn--att" data-pool="' + poolIndex + '" data-kind="attempts" title="Open list: who had this question on a finished attempt">' +
                '<span class="tabular-nums text-base">' + nAtt + '</span> ' + (nAtt === 1 ? 'attempt' : 'attempts') + '</button>' +
                '<button type="button" class="stat-pool-btn stat-pool-btn--ok" data-pool="' + poolIndex + '" data-kind="correct" title="Open list: correct answer">' +
                '<span class="tabular-nums text-base">' + nOk + '</span> correct</button>' +
                '<button type="button" class="stat-pool-btn stat-pool-btn--bad" data-pool="' + poolIndex + '" data-kind="wrong" title="Open list: wrong or no answer">' +
                '<span class="tabular-nums text-base">' + nBad + '</span> wrong</button></div>';
            const opts = q.options || [];
            const perOpt = s.per_option || [];
            let optsHtml = '<ul class="mt-3 space-y-2">';
            opts.forEach(function(o, oi) {
                const ans = parseInt(q.answer, 10);
                const correct = !isNaN(ans) && ans === oi;
                const rowCls = correct ? 'quiz-option--correct' : 'quiz-option--neutral';
                const ob = perOpt[oi] || { count: 0 };
                const oc = Number(ob.count) || 0;
                optsHtml += '<li class="flex items-stretch gap-0 rounded-lg border overflow-hidden text-sm ' + rowCls + '"' + (correct ? ' aria-label="Correct answer"' : '') + '>';
                optsHtml += '<div class="min-w-0 flex-1 flex items-start gap-2 px-3 py-2.5">';
                optsHtml += '<span class="text-gray-500 mr-0 shrink-0 w-5">' + optionLetter(oi) + '.</span>';
                optsHtml += '<span class="text-slate-800 leading-relaxed">' + escapeHtml(String(o)) + '</span>';
                optsHtml += '</div>';
                optsHtml += '<button type="button" class="opt-sel-btn" data-pool="' + poolIndex + '" data-option="' + oi + '" title="Open list: students who chose this option">' + oc + '</button>';
                optsHtml += '</li>';
            });
            optsHtml += '</ul>';

            h += '<article class="bp-surface rounded-xl p-5 shadow-sm border-l-4 border-indigo-400">';
            h += '<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">';
            h += '<div class="min-w-0 flex-1">';
            h += '<p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-1">Question ' + (poolIndex + 1) + ' · id ' + escapeHtml(String(q.id)) + '</p>';
            h += statsHtml;
            h += '<p class="text-slate-900 font-medium leading-relaxed">' + escapeHtml(q.question || '') + '</p>';
            h += optsHtml;
            h += '</div>';
            h += '<div class="flex flex-wrap gap-2 shrink-0">';
            h += '<button type="button" class="bp-btn bp-btn-secondary px-3 py-2 text-sm btn-edit-q" data-pool="' + poolIndex + '">Edit</button>';
            h += '<button type="button" class="bp-btn bp-btn-danger px-3 py-2 text-sm btn-del-q" data-pool="' + poolIndex + '"' + (allowDelete ? '' : ' disabled title="Pool must stay at least as large as the display limit"') + '>Delete</button>';
            h += '</div></div></article>';
        });
        $('#questionList').html(h || '<p class="bp-muted text-sm">No questions in this quiz.</p>');
    }

    function loadQuiz() {
        poolStats = null;
        $.when(
            $.post(API, { action: 'get_quiz_teacher', batch_id: BATCH_ID, quiz_id: QUIZ_ID }),
            $.post(API, { action: 'quiz_question_pool_stats_teacher', batch_id: BATCH_ID, quiz_id: QUIZ_ID })
        ).done(function(a, b) {
            const resQ = a[0];
            const resP = b[0];
            if (!resQ.success) {
                $('#needAuth').removeClass('hidden');
                $('#batchLink').attr('href', 'batch?id=' + encodeURIComponent(BATCH_ID));
                return;
            }
            quizPayload = resQ.quiz;
            if (resP.success && resP.pool_stats) {
                poolStats = resP.pool_stats;
            }
            $('#mainContent').removeClass('hidden');
            renderList();
        });
    }

    function closePoolStatModal() {
        $('#modalPoolStat').addClass('hidden').removeClass('flex').attr('aria-hidden', 'true');
    }

    $(document).on('click', '.stat-pool-btn', function() {
        if ($(this).prop('disabled')) return;
        const pool = parseInt($(this).data('pool'), 10);
        const kind = $(this).data('kind');
        if (isNaN(pool) || !kind) return;
        openPoolStatModal(pool, kind);
    });
    $(document).on('click', '.close-pool-stat', function() { closePoolStatModal(); });
    $('#modalPoolStat').on('click', function(e) { if (e.target === this) closePoolStatModal(); });

    $(document).on('click', '.opt-sel-btn', function(e) {
        e.stopPropagation();
        const pool = parseInt($(this).data('pool'), 10);
        const opt = parseInt($(this).data('option'), 10);
        if (isNaN(pool) || isNaN(opt)) return;
        openOptionStatModal(pool, opt);
    });
    $(document).on('click', '.close-option-stat', function() { closeOptionStatModal(); });
    $('#modalOptionStat').on('click', function(e) { if (e.target === this) closeOptionStatModal(); });

    $('#btnAddQuestion').click(function() {
        openAddModal();
    });

    $(document).on('click', '.btn-edit-q', function() {
        openEditModal(parseInt($(this).data('pool'), 10));
    });

    $(document).on('click', '.btn-del-q', function() {
        const poolIndex = parseInt($(this).data('pool'), 10);
        if ($(this).prop('disabled')) return;
        if (!confirm('Remove this question from the pool? Existing attempts will be updated to match new question indices.')) return;
        $.post(API, {
            action: 'delete_quiz_question',
            batch_id: BATCH_ID,
            quiz_id: QUIZ_ID,
            pool_index: poolIndex
        }, function(res) {
            if (res.success) {
                toast('Question removed');
                loadQuiz();
            } else {
                toast(res.error || 'Delete failed', true);
            }
        }, 'json');
    });

    $('#btnAddOption').click(function() {
        const n = $('#optionsContainer .opt-row').length;
        if (n >= 12) {
            toast('Maximum 12 options', true);
            return;
        }
        const row = $('<div class="flex gap-2 items-center opt-row" data-i="' + n + '">' +
            '<span class="text-xs font-semibold text-slate-500 w-6 shrink-0">' + optionLetter(n) + '</span>' +
            '<input type="text" class="opt-input bp-input flex-1 px-3 py-2 text-sm" value="">' +
            '</div>');
        $('#optionsContainer').append(row);
        rebuildCorrectRadios(parseInt($('input[name=correctIdx]:checked').val(), 10) || 0);
    });

    $(document).on('input', '#optionsContainer .opt-input', function() {
        const cur = parseInt($('input[name=correctIdx]:checked').val(), 10);
        rebuildCorrectRadios(isNaN(cur) ? 0 : cur);
    });

    $('#formEdit').submit(function(e) {
        e.preventDefault();
        const mode = $('#modalMode').val();
        const opts = currentOptionValues();
        if (opts.length < 2) {
            toast('Enter at least two non-empty options.', true);
            return;
        }
        const cr = $('input[name=correctIdx]:checked');
        if (!cr.length) {
            toast('Select the correct answer.', true);
            return;
        }
        const answer = parseInt(cr.val(), 10);
        if (answer < 0 || answer >= opts.length) {
            toast('Invalid correct answer.', true);
            return;
        }
        const qtext = $('#editQuestionText').val().trim();
        if (!qtext) {
            toast('Question text is required.', true);
            return;
        }
        if (mode === 'add') {
            const payload = {
                question: qtext,
                options: opts,
                answer: answer
            };
            $.post(API, {
                action: 'add_quiz_question',
                batch_id: BATCH_ID,
                quiz_id: QUIZ_ID,
                question: JSON.stringify(payload)
            }, function(res) {
                if (res.success) {
                    $('#modalEdit').removeClass('flex').addClass('hidden');
                    toast('Question added');
                    loadQuiz();
                } else {
                    toast(res.error || 'Could not add question', true);
                }
            }, 'json');
            return;
        }
        const poolIndex = parseInt($('#editPoolIndex').val(), 10);
        const payload = {
            id: parseInt($('#editQuestionId').val(), 10) || 0,
            question: qtext,
            options: opts,
            answer: answer
        };
        $.post(API, {
            action: 'update_quiz_question',
            batch_id: BATCH_ID,
            quiz_id: QUIZ_ID,
            pool_index: poolIndex,
            question: JSON.stringify(payload)
        }, function(res) {
            if (res.success) {
                $('#modalEdit').removeClass('flex').addClass('hidden');
                toast('Question saved');
                loadQuiz();
            } else {
                toast(res.error || 'Save failed', true);
            }
        }, 'json');
    });

    $('.close-mod').click(function() {
        $('#' + $(this).data('close')).removeClass('flex').addClass('hidden');
    });
    $('#modalEdit').click(function(e) {
        if (e.target === this) $(this).removeClass('flex').addClass('hidden');
    });

    $(document).ready(function() {
        if (!BATCH_ID || !QUIZ_ID) {
            window.location.href = 'index';
            return;
        }
        loadQuiz();
    });
    </script>
</body>
</html>
