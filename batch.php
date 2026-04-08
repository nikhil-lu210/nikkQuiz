<?php
session_start();
require_once __DIR__ . '/bootstrap.php';
SiteAuth::requirePage();
$exportBatchId = isset($_GET['id']) ? (string) $_GET['id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(24px); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa, #6d28d9, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-primary { background: linear-gradient(135deg, #7c3aed, #6d28d9); box-shadow: 0 4px 20px rgba(109,40,217,0.35); }
        .btn-primary:hover { transform: translateY(-1px); }
        .btn-secondary { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); }
        .btn-emerald { background: linear-gradient(135deg, #059669, #047857); }
        .btn-danger { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        .input-field { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
        .input-field:focus { border-color: #7c3aed; outline: none; box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }
        .modal-overlay { background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); }
        .modal-card { background: #1a1a2e; border: 1px solid rgba(255,255,255,0.08); }
        .stat-badge { background: rgba(124,58,237,0.15); border: 1px solid rgba(124,58,237,0.2); }
        .tab-active { border-bottom: 2px solid #7c3aed; color: #c4b5fd; }
        .tab-inactive { border-bottom: 2px solid transparent; color: #6b7280; }
        .fade-in { animation: fi 0.4s ease forwards; }
        @keyframes fi { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(236,72,153,0.08), transparent 70%);"></div>
    </div>

    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 space-y-2"></div>

    <!-- Login screen (visible until session OK) -->
    <div id="screenLogin" class="relative z-10 max-w-md mx-auto px-4 py-16">
        <div class="glass-card rounded-2xl p-8 fade-in">
            <p class="text-xs text-violet-400 uppercase tracking-wider mb-2">Teacher sign-in</p>
            <h1 class="text-2xl font-bold gradient-text mb-1" id="loginBatchTitle">Batch</h1>
            <p class="text-gray-500 text-sm mb-6" id="loginTeacherLine"></p>
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-300 mb-1">Password</label>
                    <input type="password" id="teacherPassword" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="Teacher password" autocomplete="current-password">
                </div>
                <div id="loginError" class="text-red-400 text-sm hidden"></div>
                <button type="submit" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm">Enter batch</button>
            </form>
            <p class="mt-6 text-center"><a href="index.php" class="text-gray-500 hover:text-gray-300 text-sm">← All batches</a></p>
        </div>
    </div>

    <!-- Dashboard -->
    <div id="screenDash" class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 py-8 hidden">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8 fade-in">
            <div>
                <a href="index.php" class="text-sm text-gray-500 hover:text-gray-300 mb-2 inline-block">← Batches</a>
                <h1 class="text-2xl sm:text-3xl font-bold gradient-text" id="dashTitle">…</h1>
                <p class="text-gray-500 text-sm mt-1" id="dashSub"></p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="quizzes.php" class="btn-secondary px-4 py-2 rounded-xl text-sm text-gray-300">Student quiz list</a>
                <a href="my_stats.php" class="btn-secondary px-4 py-2 rounded-xl text-sm text-gray-300">Student: my stats</a>
                <button type="button" id="btnLogout" class="btn-secondary px-4 py-2 rounded-xl text-sm text-gray-300">Sign out</button>
            </div>
        </div>

        <div class="flex gap-4 sm:gap-6 border-b border-gray-800 mb-6 flex-wrap">
            <button type="button" class="tab-btn tab-active pb-3 text-sm font-medium" data-tab="participants">Participants</button>
            <button type="button" class="tab-btn tab-inactive pb-3 text-sm font-medium" data-tab="quizzes">Quizzes</button>
            <button type="button" class="tab-btn tab-inactive pb-3 text-sm font-medium" data-tab="stats">Statistics</button>
        </div>

        <div id="tab-participants" class="tab-panel">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-200">Class roster</h2>
                <button type="button" id="btnAssignParticipant" class="btn-primary text-white px-5 py-2 rounded-xl text-sm font-semibold">Assign participants</button>
            </div>
            <p class="text-gray-500 text-sm mb-4">Each student gets a unique PIN to use on any quiz link you share for this batch.</p>
            <div id="participantList" class="space-y-3"></div>
            <div id="noParticipants" class="hidden text-center py-12 text-gray-500 text-sm border border-dashed border-gray-700 rounded-xl">No participants yet.</div>
        </div>

        <div id="tab-quizzes" class="tab-panel hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-200">Quizzes</h2>
                <button type="button" id="btnAssignQuiz" class="btn-emerald text-white px-5 py-2 rounded-xl text-sm font-semibold">Assign new quiz</button>
            </div>
            <div id="quizList" class="space-y-4"></div>
            <div id="noQuizzes" class="hidden text-center py-12 text-gray-500 text-sm border border-dashed border-gray-700 rounded-xl">No quizzes yet. Upload a JSON question file to create one.</div>
        </div>

        <div id="tab-stats" class="tab-panel hidden">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                <p class="text-gray-500 text-sm flex-1">Overview of every participant across all quizzes in this batch. Scroll horizontally on small screens. <span class="text-violet-400/90">Click a student row</span> for question-by-question results (correct / incorrect).</p>
                <?php if ($exportBatchId !== '') : ?>
                <a href="export_batch_stats.php?batch_id=<?php echo rawurlencode($exportBatchId); ?>" class="btn-secondary px-4 py-2 rounded-xl text-sm text-gray-200 whitespace-nowrap shrink-0 text-center">Export results (Excel)</a>
                <?php endif; ?>
            </div>
            <div id="statsRollups" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6"></div>
            <div id="statsMatrixWrap" class="glass-card rounded-xl overflow-x-auto">
                <div id="statsMatrixInner" class="p-2 text-sm text-gray-400">Loading…</div>
            </div>
        </div>

        <div class="mt-12 glass-card rounded-2xl p-6 border border-red-900/40">
            <h3 class="text-lg font-semibold text-red-400 mb-2">Danger zone</h3>
            <p class="text-gray-500 text-sm mb-4">Delete this batch, all participants, and all quizzes in this batch.</p>
            <button type="button" id="btnDeleteBatch" class="btn-danger text-white px-5 py-2 rounded-xl text-sm font-semibold">Delete batch</button>
        </div>
    </div>

    <!-- Modal: participant -->
    <div id="modalParticipant" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay p-4">
        <div class="modal-card rounded-2xl w-full max-w-md p-8 relative fade-in">
            <button type="button" class="close-mod absolute top-4 right-4 text-gray-500 hover:text-white" data-close="modalParticipant">✕</button>
            <h3 class="text-xl font-bold gradient-text mb-4">Assign participant</h3>
            <form id="formParticipant">
                <label class="block text-sm text-gray-300 mb-1">Student name</label>
                <input type="text" name="name" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm mb-4" placeholder="Full name">
                <button type="submit" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm">Add participant</button>
            </form>
        </div>
    </div>

    <!-- Modal: quiz -->
    <div id="modalQuiz" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay p-4">
        <div class="modal-card rounded-2xl w-full max-w-lg p-8 relative fade-in max-h-[90vh] overflow-y-auto">
            <button type="button" class="close-mod absolute top-4 right-4 text-gray-500 hover:text-white" data-close="modalQuiz">✕</button>
            <h3 class="text-xl font-bold gradient-text mb-4">Assign new quiz</h3>
            <form id="formQuiz" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Quiz name</label>
                        <input type="text" name="name" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">Time limit (min)</label>
                            <input type="number" name="time_limit" required min="1" class="input-field w-full px-4 py-3 rounded-xl text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">Questions to display</label>
                            <input type="number" name="total_display" required min="1" class="input-field w-full px-4 py-3 rounded-xl text-white text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Quiz file (JSON)</label>
                        <input type="file" name="questions_file" accept=".json,application/json" required class="input-field w-full px-4 py-2 rounded-xl text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-violet-900 file:text-violet-300">
                    </div>
                    <button type="submit" id="btnQuizSubmit" class="btn-emerald w-full text-white py-3 rounded-xl font-semibold text-sm">Create quiz</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const API = 'api/handler.php';
    const BATCH_ID = new URLSearchParams(window.location.search).get('id');
    let batchPayload = null;
    let batchStatsCache = null;

    function toast(msg, err) {
        const c = err ? 'bg-red-900 border-red-700 text-red-200' : 'bg-emerald-900 border-emerald-700 text-emerald-200';
        const $t = $('<div class="border px-4 py-3 rounded-xl text-sm ' + c + '">' + $('<div>').text(msg).html() + '</div>');
        $('#toastContainer').append($t);
        setTimeout(() => $t.remove(), 4000);
    }

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }

    function showDash() {
        $('#screenLogin').addClass('hidden');
        $('#screenDash').removeClass('hidden');
    }

    function renderAll() {
        if (!batchPayload) return;
        const bi = batchPayload.batch_info;
        $('#dashTitle').text(bi.name);
        $('#dashSub').text('Teacher: ' + (bi.teacher_name || '') + ' · ' + (bi.created_at || ''));

        const parts = batchPayload.participants || [];
        if (parts.length === 0) {
            $('#participantList').html('');
            $('#noParticipants').removeClass('hidden');
        } else {
            $('#noParticipants').addClass('hidden');
            let h = '';
            parts.forEach(p => {
                h += `<div class="glass-card rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="font-semibold text-white">${escapeHtml(p.name)}</span>
                        <span class="stat-badge px-2 py-0.5 rounded-lg text-xs font-mono ml-2">${escapeHtml(p.id)}</span>
                        <p class="text-xs text-gray-400 mt-1">PIN: <code class="text-violet-300 font-mono">${escapeHtml(p.pin)}</code></p>
                    </div>
                    <button type="button" class="text-red-400 hover:text-red-300 text-sm remove-p" data-id="${p.id}" data-name="${escapeHtml(p.name)}">Remove</button>
                </div>`;
            });
            $('#participantList').html(h);
        }

        const quizzes = batchPayload.quizzes || [];
        if (quizzes.length === 0) {
            $('#quizList').html('');
            $('#noQuizzes').removeClass('hidden');
        } else {
            $('#noQuizzes').addClass('hidden');
            const base = window.location.origin + window.location.pathname.replace(/[^/]*$/, '');
            let h = '';
            quizzes.forEach(q => {
                const link = base + 'take_quiz.php?q=' + encodeURIComponent(q.public_slug);
                const st = (q.status || 'active') === 'active';
                h += `<div class="glass-card rounded-xl p-5" data-qid="${q.id}">
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-white mb-2">${escapeHtml(q.name)}</h3>
                            <div class="flex flex-wrap gap-2 text-xs mb-3">
                                <span class="stat-badge px-2 py-1 rounded-lg text-violet-300">⏱ ${q.time_limit} min</span>
                                <span class="stat-badge px-2 py-1 rounded-lg text-violet-300">📝 ${q.question_count} in pool</span>
                                <span class="stat-badge px-2 py-1 rounded-lg text-violet-300">✓ ${q.finished_count} completed</span>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                <input type="text" readonly class="input-field flex-1 px-3 py-2 rounded-lg text-xs text-violet-300 font-mono truncate" value="${link}">
                                <button type="button" class="btn-secondary px-3 py-2 rounded-lg text-xs copy-link">Copy link</button>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 shrink-0">
                            <select class="input-field px-3 py-2 rounded-lg text-sm q-status" data-id="${q.id}">
                                <option value="active" ${st ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${!st ? 'selected' : ''}>Inactive</option>
                            </select>
                            <a href="quiz.php?batch_id=${encodeURIComponent(BATCH_ID)}&id=${encodeURIComponent(q.id)}" class="btn-secondary text-center px-3 py-2 rounded-lg text-xs text-gray-300">Results & questions</a>
                            <button type="button" class="text-red-400 text-xs text-left delete-q" data-id="${q.id}">Delete quiz</button>
                        </div>
                    </div>
                </div>`;
            });
            $('#quizList').html(h);
        }
    }

    function loadBatch() {
        $.post(API, { action: 'get_batch', batch_id: BATCH_ID }, function(res) {
            if (res.success) {
                batchPayload = res.batch;
                batchStatsCache = null;
                renderAll();
                showDash();
            } else if (res.needs_auth) {
                $('#screenDash').addClass('hidden');
                $('#screenLogin').removeClass('hidden');
            } else {
                toast(res.error || 'Error', true);
                setTimeout(() => location.href = 'index.php', 2000);
            }
        }, 'json');
    }

    $('#loginForm').submit(function(e) {
        e.preventDefault();
        $('#loginError').addClass('hidden');
        $.post(API, {
            action: 'login_batch',
            batch_id: BATCH_ID,
            teacher_password: $('#teacherPassword').val()
        }, function(res) {
            if (res.success) {
                loadBatch();
            } else {
                $('#loginError').removeClass('hidden').text(res.error || 'Login failed');
            }
        }, 'json');
    });

    $('#btnLogout').click(function() {
        $.post(API, { action: 'logout_batch', batch_id: BATCH_ID }, function() {
            location.reload();
        }, 'json');
    });

    function renderBatchStats(st) {
        const quizzes = st.quizzes || [];
        const roll = st.quiz_rollups || [];
        let rh = '';
        roll.forEach(function(q) {
            const avg = q.class_average_pct != null ? q.class_average_pct + '% avg' : 'No scores yet';
            const subs = q.submissions + ' submitted';
            rh += `<div class="glass-card rounded-xl p-4 border border-violet-500/20">
                <p class="font-semibold text-white text-sm truncate" title="${escapeHtml(q.name)}">${escapeHtml(q.name)}</p>
                <p class="text-xs text-gray-500 mt-1">${subs} · ${avg}</p>
                ${q.class_best_pct != null ? '<p class="text-xs text-violet-300 mt-1">Best: ' + q.class_best_pct + '%</p>' : ''}
            </div>`;
        });
        $('#statsRollups').html(rh || '<p class="text-gray-500 col-span-full text-sm">No quizzes yet.</p>');

        const rows = st.rows || [];
        if (rows.length === 0 || quizzes.length === 0) {
            $('#statsMatrixInner').html('<p class="p-6 text-center text-gray-500">Add participants and quizzes to see the matrix.</p>');
            return;
        }

        let t = '<table class="w-full text-xs min-w-full border-collapse"><thead><tr class="border-b border-gray-800">';
        t += '<th class="text-left py-3 px-2 sticky left-0 bg-[#1a1a2e] z-10 text-gray-400 font-semibold">Student</th>';
        quizzes.forEach(function(q) {
            t += '<th class="text-center py-3 px-2 text-gray-400 font-normal max-w-[100px]" title="' + escapeHtml(q.name) + '"><span class="line-clamp-2">' + escapeHtml(q.name) + '</span></th>';
        });
        t += '<th class="text-center py-3 px-2 text-violet-300 font-semibold">Avg</th></tr></thead><tbody>';

        rows.forEach(function(row) {
            t += '<tr class="border-b border-gray-800/50 hover:bg-violet-500/10 cursor-pointer transition-colors" tabindex="0" role="link" data-participant-id="' + escapeHtml(row.participant_id) + '" title="View this student\'s detailed stats">';
            t += '<td class="py-2 px-2 sticky left-0 bg-[#14141f] text-white font-medium">' + escapeHtml(row.participant_name) + '<br><span class="text-[10px] text-gray-500 font-mono">' + escapeHtml(row.participant_id) + '</span></td>';
            quizzes.forEach(function(q) {
                const c = row.cells[q.id];
                let cell = '—';
                let cls = 'text-gray-600';
                if (c && c.key === 'done') {
                    cell = c.label + '<br><span class="text-violet-300">' + c.percentage + '%</span>';
                    cls = 'text-gray-300';
                } else if (c && c.key === 'running') {
                    cell = '<span class="text-amber-400">In progress</span>';
                    cls = '';
                }
                t += '<td class="py-2 px-1 text-center ' + cls + ' align-top">' + cell + '</td>';
            });
            const ap = row.avg_percentage != null ? row.avg_percentage + '%' : '—';
            t += '<td class="py-2 px-2 text-center text-violet-300 font-semibold">' + ap + '</td></tr>';
        });
        t += '</tbody></table>';
        $('#statsMatrixInner').html(t);
        $('#statsMatrixInner').off('click', 'tr[data-participant-id]').on('click', 'tr[data-participant-id]', function() {
            const pid = $(this).attr('data-participant-id');
            if (pid) {
                window.location.href = 'participant_detail.php?batch_id=' + encodeURIComponent(BATCH_ID) + '&participant_id=' + encodeURIComponent(pid);
            }
        });
        $('#statsMatrixInner').off('keydown', 'tr[data-participant-id]').on('keydown', 'tr[data-participant-id]', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const pid = $(this).attr('data-participant-id');
                if (pid) {
                    window.location.href = 'participant_detail.php?batch_id=' + encodeURIComponent(BATCH_ID) + '&participant_id=' + encodeURIComponent(pid);
                }
            }
        });
    }

    function loadBatchStats() {
        if (batchStatsCache) {
            renderBatchStats(batchStatsCache);
            return;
        }
        $('#statsMatrixInner').html('<p class="p-6 text-center text-gray-500">Loading…</p>');
        $.post(API, { action: 'batch_stats', batch_id: BATCH_ID }, function(res) {
            if (res.success) {
                batchStatsCache = res.stats;
                renderBatchStats(batchStatsCache);
            } else {
                $('#statsMatrixInner').html('<p class="p-6 text-red-400">' + escapeHtml(res.error || 'Failed') + '</p>');
            }
        }, 'json');
    }

    $('.tab-btn').click(function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('tab-active').addClass('tab-inactive');
        $(this).removeClass('tab-inactive').addClass('tab-active');
        $('.tab-panel').addClass('hidden');
        $('#tab-' + tab).removeClass('hidden');
        if (tab === 'stats') loadBatchStats();
    });

    $('#btnAssignParticipant').click(() => {
        $('#formParticipant')[0].reset();
        $('#modalParticipant').removeClass('hidden').addClass('flex');
    });
    $('#btnAssignQuiz').click(() => {
        $('#formQuiz')[0].reset();
        $('#modalQuiz').removeClass('hidden').addClass('flex');
    });
    $('.close-mod').click(function() {
        $('#' + $(this).data('close')).removeClass('flex').addClass('hidden');
    });
    $('.modal-overlay').parent().filter('#modalParticipant, #modalQuiz').each(function() {
        // click outside modal card
    });
    $('#modalParticipant, #modalQuiz').click(function(e) {
        if (e.target === this) $(this).removeClass('flex').addClass('hidden');
    });

    $('#formParticipant').submit(function(e) {
        e.preventDefault();
        $.post(API, {
            action: 'add_batch_participant',
            batch_id: BATCH_ID,
            name: this.name.value
        }, function(res) {
            if (res.success) {
                $('#modalParticipant').removeClass('flex').addClass('hidden');
                toast('Participant added — PIN: ' + res.participant.pin);
                loadBatch();
            } else toast(res.error, true);
        }, 'json');
    });

    $('#formQuiz').submit(function(e) {
        e.preventDefault();
        const btn = $('#btnQuizSubmit');
        btn.prop('disabled', true).text('Creating...');
        const fd = new FormData(this);
        fd.append('action', 'create_quiz_in_batch');
        fd.append('batch_id', BATCH_ID);
        $.ajax({
            url: API,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).text('Create quiz');
                if (res.success) {
                    $('#modalQuiz').removeClass('flex').addClass('hidden');
                    toast('Quiz created. Link copied.');
                    if (navigator.clipboard && res.quiz_link) {
                        navigator.clipboard.writeText(res.quiz_link);
                    }
                    loadBatch();
                } else {
                    toast(res.error, true);
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Create quiz');
                toast('Upload failed', true);
            }
        });
    });

    $(document).on('click', '.remove-p', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        if (!confirm('Remove ' + name + '?')) return;
        $.post(API, { action: 'remove_batch_participant', batch_id: BATCH_ID, participant_id: id }, function(res) {
            if (res.success) { toast('Removed'); loadBatch(); }
            else toast(res.error, true);
        }, 'json');
    });

    $(document).on('click', '.copy-link', function() {
        const inp = $(this).closest('.glass-card').find('input[readonly]');
        if (inp.length) {
            inp[0].select();
            document.execCommand('copy');
            toast('Link copied');
        }
    });

    $(document).on('change', '.q-status', function() {
        const id = $(this).data('id');
        const status = $(this).val();
        $.post(API, { action: 'set_quiz_status', batch_id: BATCH_ID, quiz_id: id, status: status }, function(res) {
            if (res.success) toast('Status saved');
            else toast(res.error, true);
        }, 'json');
    });

    $(document).on('click', '.delete-q', function() {
        const id = $(this).data('id');
        if (!confirm('Delete this quiz permanently?')) return;
        $.post(API, { action: 'delete_quiz_teacher', batch_id: BATCH_ID, quiz_id: id }, function(res) {
            if (res.success) { toast('Quiz deleted'); loadBatch(); }
            else toast(res.error, true);
        }, 'json');
    });

    $('#btnDeleteBatch').click(function() {
        if (!confirm('Delete this entire batch and all quizzes?')) return;
        $.post(API, { action: 'delete_batch', batch_id: BATCH_ID }, function(res) {
            if (res.success) location.href = 'index.php';
            else toast(res.error, true);
        }, 'json');
    });

    $(document).ready(function() {
        if (!BATCH_ID) {
            location.href = 'index.php';
            return;
        }
        $.post(API, { action: 'batch_meta', batch_id: BATCH_ID }, function(m) {
            if (!m.success) {
                toast('Batch not found', true);
                setTimeout(() => location.href = 'index.php', 2000);
                return;
            }
            document.title = m.name + ' — NikkQuiz';
            $('#loginBatchTitle').text(m.name);
            $('#loginTeacherLine').text('Teacher: ' + m.teacher_name);
            loadBatch();
        }, 'json');
    });
    </script>
</body>
</html>
