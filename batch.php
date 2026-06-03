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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <style>
        .batch-page { font-family: 'DM Sans', system-ui, sans-serif; }
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
        .bp-btn-danger { background: #dc2626; color: #fff; border: 1px solid #dc2626; }
        .bp-btn-danger:hover { background: #b91c1c; }
        .bp-btn-ok { background: #059669; color: #fff; border: 1px solid #059669; }
        .bp-btn-ok:hover { background: #047857; }
        .bp-input {
            background: #fff; border: 1px solid #cbd5e1; color: #0f172a;
            border-radius: 0.5rem;
        }
        .bp-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12); }
        .bp-tab-active { color: #4f46e5; border-bottom: 2px solid #4f46e5; }
        .bp-tab-idle { color: #64748b; border-bottom: 2px solid transparent; }
        .bp-tab-idle:hover { color: #334155; }
        .bp-stat-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem;
            padding: 1rem 1.25rem; display: flex; gap: 1rem; align-items: flex-start;
        }
        .bp-stat-icon {
            width: 2.75rem; height: 2.75rem; border-radius: 0.5rem;
            background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .bp-modal-overlay { background: rgba(15, 23, 42, 0.45); }
        .bp-modal-card { background: #fff; border: 1px solid #e2e8f0; }
        .bp-badge { background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; font-size: 0.75rem; }
        .bp-pin { font-family: ui-monospace, monospace; color: #4338ca; }
        .bp-row-hover:hover { background: #fafafa; }
        .fade-in { animation: bpfi .35s ease forwards; }
        @keyframes bpfi { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .quiz-card { border-radius: 0.75rem; border: 1px solid #e2e8f0; border-left-width: 4px; border-left-color: #6366f1; background: #fff; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06); }
        .quiz-link-value { font-size: 0.8125rem; }
        .quiz-status-select { min-width: 7.5rem; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1rem; padding-right: 2rem; appearance: none; }
    </style>
</head>
<body class="batch-page min-h-screen">
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 space-y-2"></div>

    <!-- Teacher sign-in -->
    <div id="screenLogin" class="relative z-10 max-w-md mx-auto px-4 py-16">
        <div class="bp-surface rounded-xl p-8 fade-in shadow-sm">
            <div class="flex justify-center mb-6" aria-hidden="true">
                <svg class="w-32 h-20 text-slate-400" viewBox="0 0 200 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="16" y="28" width="168" height="64" rx="6" stroke="currentColor" stroke-width="1.25"/>
                    <circle cx="56" cy="52" r="8" stroke="#4f46e5" stroke-width="1.5"/>
                    <path d="M44 72h24" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                    <circle cx="100" cy="52" r="8" stroke="#4f46e5" stroke-width="1.5"/>
                    <path d="M88 72h24" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                    <circle cx="144" cy="52" r="8" stroke="#4f46e5" stroke-width="1.5"/>
                    <path d="M132 72h24" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                    <path d="M32 100h136" stroke="#e2e8f0" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>
            <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-1">Teacher sign-in</p>
            <h1 class="text-2xl font-bold bp-heading mb-1" id="loginBatchTitle">Batch</h1>
            <p class="bp-muted text-sm mb-6" id="loginTeacherLine"></p>
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="teacherPassword" required class="bp-input w-full px-4 py-2.5 text-sm" placeholder="Teacher password" autocomplete="current-password">
                </div>
                <div id="loginError" class="text-red-600 text-sm hidden"></div>
                <button type="submit" class="bp-btn bp-btn-primary w-full py-2.5">Enter batch</button>
            </form>
            <p class="mt-6 text-center"><a href="index" class="bp-muted text-sm hover:text-indigo-600">← All batches</a></p>
        </div>
    </div>

    <!-- Dashboard -->
    <div id="screenDash" class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 py-8 hidden">
        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-8 mb-10 fade-in">
            <div class="flex-1 min-w-0">
                <a href="index" class="text-sm bp-muted hover:text-indigo-600 mb-2 inline-block">← Batches</a>
                <h1 class="text-2xl sm:text-3xl font-bold bp-heading" id="dashTitle">…</h1>
                <p class="bp-muted text-sm mt-1" id="dashSub"></p>
                <button type="button" id="btnEditBatch" class="mt-3 bp-btn bp-btn-secondary px-4 py-2 text-sm">Edit batch info</button>
            </div>
            <div class="hidden sm:block flex-shrink-0 w-44" aria-hidden="true">
                <svg viewBox="0 0 200 120" class="w-full h-auto text-slate-300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 96c20-24 52-36 88-36s68 12 88 36" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                    <rect x="48" y="32" width="104" height="48" rx="4" stroke="#cbd5e1" stroke-width="1.25" fill="#fff"/>
                    <circle cx="76" cy="52" r="6" stroke="#4f46e5" stroke-width="1.25"/>
                    <circle cx="100" cy="52" r="6" stroke="#4f46e5" stroke-width="1.25"/>
                    <circle cx="124" cy="52" r="6" stroke="#4f46e5" stroke-width="1.25"/>
                    <path d="M64 72h72" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <!-- Summary stats -->
        <div id="dashStats" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 hidden">
            <div class="bp-stat-card shadow-sm">
                <div class="bp-stat-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium bp-muted uppercase tracking-wide">Participants</p>
                    <p class="text-2xl font-bold bp-heading tabular-nums" id="statParticipants">0</p>
                </div>
            </div>
            <div class="bp-stat-card shadow-sm">
                <div class="bp-stat-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium bp-muted uppercase tracking-wide">Quizzes</p>
                    <p class="text-2xl font-bold bp-heading tabular-nums" id="statQuizzes">0</p>
                </div>
            </div>
            <div class="bp-stat-card shadow-sm">
                <div class="bp-stat-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium bp-muted uppercase tracking-wide">Completed attempts</p>
                    <p class="text-2xl font-bold bp-heading tabular-nums" id="statFinished">0</p>
                </div>
            </div>
        </div>

        <div class="flex gap-6 sm:gap-8 border-b border-slate-200 mb-6 flex-wrap">
            <button type="button" class="tab-btn bp-tab-active pb-3 text-sm font-semibold bg-transparent" data-tab="participants">Participants</button>
            <button type="button" class="tab-btn bp-tab-idle pb-3 text-sm font-medium bg-transparent" data-tab="quizzes">Quizzes</button>
            <button type="button" class="tab-btn bp-tab-idle pb-3 text-sm font-medium bg-transparent" data-tab="stats">Statistics</button>
        </div>

        <div id="tab-participants" class="tab-panel">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-semibold bp-heading">Class roster</h2>
                    <p class="bp-muted text-sm mt-0.5">Each student gets a unique PIN for quiz links in this batch.</p>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <?php if ($exportBatchId !== '') : ?>
                    <a href="export_participants?batch_id=<?php echo rawurlencode($exportBatchId); ?>" class="bp-btn bp-btn-secondary px-4 py-2 text-sm inline-flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export
                    </a>
                    <?php endif; ?>
                    <button type="button" id="btnAssignParticipant" class="bp-btn bp-btn-primary px-5 py-2">Assign participants</button>
                </div>
            </div>
            <div id="participantList"></div>
            <div id="noParticipants" class="hidden text-center py-14 px-4 bp-surface rounded-xl border-dashed">
                <div class="max-w-xs mx-auto text-slate-400 mb-3" aria-hidden="true">
                    <svg viewBox="0 0 120 80" class="w-full h-auto mx-auto" fill="none"><circle cx="40" cy="28" r="10" stroke="currentColor" stroke-width="1.5"/><circle cx="80" cy="28" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M24 56h72" stroke="#e2e8f0" stroke-width="3" stroke-linecap="round"/></svg>
                </div>
                <p class="bp-muted text-sm">No participants yet. Add your first student above.</p>
            </div>
        </div>

        <div id="tab-quizzes" class="tab-panel hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <h2 class="text-lg font-semibold bp-heading">Quizzes</h2>
                <button type="button" id="btnAssignQuiz" class="bp-btn bp-btn-ok px-5 py-2 shrink-0">Assign new quiz</button>
            </div>
            <div id="quizList" class="space-y-3"></div>
            <div id="noQuizzes" class="hidden text-center py-14 px-4 bp-surface rounded-xl border-dashed">
                <p class="bp-muted text-sm">No quizzes yet. Upload a JSON question file to create one.</p>
            </div>
        </div>

        <div id="tab-stats" class="tab-panel hidden">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                <p class="bp-muted text-sm flex-1 max-w-2xl">Rows are ordered by <span class="text-slate-700 font-medium">rank</span> (higher average % first; ties by shorter total time on completed quizzes). Scroll sideways on small screens. <span class="text-slate-700 font-medium">Click a row</span> for detail.</p>
                <?php if ($exportBatchId !== '') : ?>
                <a href="export_batch_stats?batch_id=<?php echo rawurlencode($exportBatchId); ?>" class="bp-btn bp-btn-secondary px-4 py-2 text-sm whitespace-nowrap shrink-0">Export results (Excel)</a>
                <?php endif; ?>
            </div>
            <div id="statsRollups" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6"></div>
            <div id="statsMatrixWrap" class="bp-surface rounded-xl overflow-x-auto shadow-sm">
                <div id="statsMatrixInner" class="p-4 text-sm bp-muted">Loading…</div>
            </div>
        </div>

        <div class="mt-10 bp-surface rounded-xl p-6 border-red-200 bg-red-50/50">
            <h3 class="text-base font-semibold text-red-700 mb-1">Danger zone</h3>
            <p class="bp-muted text-sm mb-4">Delete this batch, all participants, and all quizzes.</p>
            <button type="button" id="btnDeleteBatch" class="bp-btn bp-btn-danger px-5 py-2 text-sm">Delete batch</button>
        </div>

        <div class="flex flex-wrap gap-2 justify-end mt-6">
            <a href="quizzes" class="bp-btn bp-btn-secondary px-4 py-2 text-sm">Student quiz list</a>
            <a href="my_stats" class="bp-btn bp-btn-secondary px-4 py-2 text-sm">Student: my stats</a>
            <button type="button" id="btnLogout" class="bp-btn bp-btn-secondary px-4 py-2 text-sm">Sign out</button>
        </div>
    </div>

    <!-- Modal: participant -->
    <div id="modalParticipant" class="fixed inset-0 z-50 hidden items-center justify-center bp-modal-overlay p-4">
        <div class="bp-modal-card rounded-xl w-full max-w-md p-8 relative fade-in shadow-lg">
            <button type="button" class="close-mod absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl leading-none" data-close="modalParticipant" aria-label="Close">×</button>
            <h3 class="text-lg font-bold bp-heading mb-4">Assign participant</h3>
            <form id="formParticipant">
                <label class="block text-sm font-medium text-slate-700 mb-1">Student name</label>
                <input type="text" name="name" required class="bp-input w-full px-4 py-2.5 text-sm mb-3" placeholder="Full name">
                <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="email" name="email" class="bp-input w-full px-4 py-2.5 text-sm mb-4" placeholder="student@example.com" autocomplete="email">
                <button type="submit" class="bp-btn bp-btn-primary w-full py-2.5">Add participant</button>
            </form>
        </div>
    </div>

    <!-- Modal: edit participant name -->
    <div id="modalEditParticipant" class="fixed inset-0 z-50 hidden items-center justify-center bp-modal-overlay p-4">
        <div class="bp-modal-card rounded-xl w-full max-w-md p-8 relative fade-in shadow-lg">
            <button type="button" class="close-mod absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl leading-none" data-close="modalEditParticipant" aria-label="Close">×</button>
            <h3 class="text-lg font-bold bp-heading mb-4">Edit student</h3>
            <form id="formEditParticipant">
                <input type="hidden" name="participant_id" id="editParticipantId" value="">
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input type="text" name="name" id="editParticipantName" required class="bp-input w-full px-4 py-2.5 text-sm mb-3" placeholder="Full name">
                <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="email" name="email" id="editParticipantEmail" class="bp-input w-full px-4 py-2.5 text-sm mb-4" placeholder="student@example.com" autocomplete="email">
                <button type="submit" class="bp-btn bp-btn-primary w-full py-2.5">Save</button>
            </form>
        </div>
    </div>

    <!-- Modal: batch settings -->
    <div id="modalBatch" class="fixed inset-0 z-50 hidden items-center justify-center bp-modal-overlay p-4">
        <div class="bp-modal-card rounded-xl w-full max-w-md p-8 relative fade-in shadow-lg">
            <button type="button" class="close-mod absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl leading-none" data-close="modalBatch" aria-label="Close">×</button>
            <h3 class="text-lg font-bold bp-heading mb-4">Edit batch info</h3>
            <form id="formBatch">
                <label class="block text-sm font-medium text-slate-700 mb-1">Batch name</label>
                <input type="text" name="name" id="batchEditName" required class="bp-input w-full px-4 py-2.5 text-sm mb-3">
                <label class="block text-sm font-medium text-slate-700 mb-1">Teacher name</label>
                <input type="text" name="teacher_name" id="batchEditTeacher" required class="bp-input w-full px-4 py-2.5 text-sm mb-3">
                <label class="block text-sm font-medium text-slate-700 mb-1">New teacher password <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="password" name="teacher_password" id="batchEditPassword" class="bp-input w-full px-4 py-2.5 text-sm mb-1" placeholder="Leave blank to keep current" autocomplete="new-password" minlength="4">
                <p class="text-xs bp-muted mb-4">At least 4 characters if you change it.</p>
                <button type="submit" class="bp-btn bp-btn-primary w-full py-2.5">Save batch info</button>
            </form>
        </div>
    </div>

    <!-- Modal: quiz -->
    <div id="modalQuiz" class="fixed inset-0 z-50 hidden items-center justify-center bp-modal-overlay p-4">
        <div class="bp-modal-card rounded-xl w-full max-w-lg p-8 relative fade-in max-h-[90vh] overflow-y-auto shadow-lg">
            <button type="button" class="close-mod absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl leading-none" data-close="modalQuiz" aria-label="Close">×</button>
            <h3 class="text-lg font-bold bp-heading mb-4">Assign new quiz</h3>
            <form id="formQuiz" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Quiz name</label>
                        <input type="text" name="name" required class="bp-input w-full px-4 py-2.5 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Time limit (min)</label>
                            <input type="number" name="time_limit" required min="1" class="bp-input w-full px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Questions to display</label>
                            <input type="number" name="total_display" required min="1" class="bp-input w-full px-4 py-2.5 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Quiz file (JSON)</label>
                        <input type="file" name="questions_file" accept=".json,application/json" required class="bp-input w-full px-3 py-2 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded file:border file:border-slate-200 file:bg-slate-50 file:text-xs file:text-slate-700">
                    </div>
                    <button type="submit" id="btnQuizSubmit" class="bp-btn bp-btn-ok w-full py-2.5">Create quiz</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const API = 'api/handler';
    const BATCH_ID = new URLSearchParams(window.location.search).get('id');
    let batchPayload = null;
    let batchStatsCache = null;

    function toast(msg, err) {
        const c = err ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-emerald-50 border border-emerald-200 text-emerald-900';
        const $t = $('<div class="px-4 py-3 rounded-lg text-sm shadow-md ' + c + '">' + $('<div>').text(msg).html() + '</div>');
        $('#toastContainer').append($t);
        setTimeout(() => $t.remove(), 4000);
    }

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }

    function showDash() {
        $('#screenLogin').addClass('hidden');
        $('#screenDash').removeClass('hidden');
        $('#dashStats').removeClass('hidden');
    }

    function renderAll() {
        if (!batchPayload) return;
        const bi = batchPayload.batch_info;
        $('#dashTitle').text(bi.name);
        $('#dashSub').text('Teacher: ' + (bi.teacher_name || '') + ' · ' + (bi.created_at || ''));

        const parts = batchPayload.participants || [];
        const quizzes = batchPayload.quizzes || [];
        let finishedSum = 0;
        quizzes.forEach(q => { finishedSum += Number(q.finished_count) || 0; });

        $('#statParticipants').text(parts.length);
        $('#statQuizzes').text(quizzes.length);
        $('#statFinished').text(finishedSum);

        if (parts.length === 0) {
            $('#participantList').html('');
            $('#noParticipants').removeClass('hidden');
        } else {
            $('#noParticipants').addClass('hidden');
            let h = '<div class="bp-surface rounded-xl overflow-hidden shadow-sm"><div class="overflow-x-auto"><table class="w-full text-sm min-w-[760px]"><thead><tr class="border-b border-slate-200 bg-slate-50 text-left">';
            h += '<th scope="col" class="py-3 px-4 font-semibold text-slate-600">ID</th>';
            h += '<th scope="col" class="py-3 px-4 font-semibold text-slate-600">Name</th>';
            h += '<th scope="col" class="py-3 px-4 font-semibold text-slate-600">Email</th>';
            h += '<th scope="col" class="py-3 px-4 font-semibold text-slate-600">PIN</th>';
            h += '<th scope="col" class="py-3 px-4 font-semibold text-slate-600 text-right w-32">Actions</th>';
            h += '</tr></thead><tbody>';
            parts.forEach(p => {
                h += `<tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/80 transition-colors" data-participant-id="${escapeHtml(p.id)}">`;
                h += `<td class="py-3 px-4 font-mono text-xs sm:text-sm text-slate-800 align-middle">${escapeHtml(p.id)}</td>`;
                h += `<td class="py-3 px-4 font-medium text-slate-900 align-middle">${escapeHtml(p.name)}</td>`;
                const em = (p.email || '').trim();
                h += `<td class="py-3 px-4 text-slate-600 align-middle">${em ? escapeHtml(em) : '<span class="bp-muted">—</span>'}</td>`;
                h += `<td class="py-3 px-4 align-middle"><div class="flex flex-wrap items-center gap-2">`;
                h += `<span class="pin-display font-mono text-slate-800 tracking-widest">••••••</span>`;
                h += `<button type="button" class="btn-view-pin text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline" data-pin="${escapeHtml(p.pin)}">View PIN</button>`;
                h += `</div></td>`;
                h += `<td class="py-3 px-4 text-right align-middle whitespace-nowrap">`;
                h += `<button type="button" class="btn-edit-participant inline-flex items-center justify-center p-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-indigo-600" data-id="${escapeHtml(p.id)}" title="Edit name" aria-label="Edit name">`;
                h += `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>`;
                h += `<button type="button" class="remove-p inline-flex items-center justify-center p-2 rounded-lg text-slate-600 hover:bg-red-50 hover:text-red-600 ml-1" data-id="${escapeHtml(p.id)}" title="Remove" aria-label="Remove student">`;
                h += `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>`;
                h += `</td></tr>`;
            });
            h += '</tbody></table></div></div>';
            $('#participantList').html(h);
        }

        if (quizzes.length === 0) {
            $('#quizList').html('');
            $('#noQuizzes').removeClass('hidden');
        } else {
            $('#noQuizzes').addClass('hidden');
            const base = window.location.origin + window.location.pathname.replace(/[^/]*$/, '');
            let h = '';
            quizzes.forEach(q => {
                const link = base + 'take_quiz?q=' + encodeURIComponent(q.public_slug);
                const st = (q.status || 'active') === 'active';
                const badgeActive = st
                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                    : 'bg-slate-100 text-slate-600 border-slate-200';
                const badgeLabel = st ? 'Active' : 'Hidden';
                h += `<article class="quiz-card p-0 overflow-hidden" data-qid="${q.id}">
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-5">
                            <div class="flex gap-4 min-w-0 flex-1">
                                <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600" aria-hidden="true">
                                    <svg class="w-8 h-8" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="8" y="10" width="48" height="44" rx="4" stroke="currentColor" stroke-width="2"/>
                                        <path d="M16 22h32M16 30h24M16 38h28" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
                                        <circle cx="44" cy="46" r="10" fill="#fff" stroke="currentColor" stroke-width="2"/>
                                        <path d="M40 46l3 3 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 gap-y-1 mb-3">
                                        <h3 class="text-lg font-semibold text-slate-900 leading-tight">${escapeHtml(q.name)}</h3>
                                        <span class="quiz-status-badge inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full border ${badgeActive}">${badgeLabel}</span>
                                    </div>
                                    <div class="rounded-xl border border-slate-200/90 bg-slate-50/80 overflow-hidden max-w-3xl">
                                        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-200/80">
                                            <div class="p-3 sm:p-4 flex flex-col gap-1 min-h-[4.5rem] justify-center">
                                                <div class="flex items-center gap-1.5 text-slate-500">
                                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span class="text-[10px] font-medium uppercase tracking-wide">Time limit</span>
                                                </div>
                                                <p class="text-lg font-semibold text-slate-900 tabular-nums">${q.time_limit}<span class="text-slate-500 font-medium text-sm"> min</span></p>
                                            </div>
                                            <div class="p-3 sm:p-4 flex flex-col gap-1 min-h-[4.5rem] justify-center">
                                                <div class="flex items-center gap-1.5 text-slate-500">
                                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                                    <span class="text-[10px] font-medium uppercase tracking-wide">Pool</span>
                                                </div>
                                                <p class="text-lg font-semibold text-slate-900 tabular-nums">${q.question_count}</p>
                                                <p class="text-[10px] text-slate-400 leading-tight">Questions available</p>
                                            </div>
                                            <div class="p-3 sm:p-4 flex flex-col gap-1 min-h-[4.5rem] justify-center">
                                                <div class="flex items-center gap-1.5 text-slate-500">
                                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"/></svg>
                                                    <span class="text-[10px] font-medium uppercase tracking-wide">Display</span>
                                                </div>
                                                <p class="text-lg font-semibold text-slate-900 tabular-nums">${Number(q.total_display_questions) || 0}</p>
                                                <p class="text-[10px] text-slate-400 leading-tight">Questions per attempt</p>
                                            </div>
                                            <div class="p-3 sm:p-4 flex flex-col gap-1 min-h-[4.5rem] justify-center">
                                                <div class="flex items-center gap-1.5 text-slate-500">
                                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span class="text-[10px] font-medium uppercase tracking-wide">Finished</span>
                                                </div>
                                                <p class="text-lg font-semibold text-slate-900 tabular-nums">${q.finished_count}</p>
                                                <p class="text-[10px] text-slate-400 leading-tight">Submissions</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 pt-5 border-t border-slate-100">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Share with students</p>
                            <p class="text-sm text-slate-600 mb-3">Copy this link into your LMS, chat, or email. Students only need the link and their PIN.</p>
                            <div class="flex flex-col sm:flex-row gap-2 sm:items-stretch">
                                <div class="flex-1 min-w-0 flex items-stretch rounded-lg border border-slate-200 bg-slate-50/80 focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-300">
                                    <span class="pl-3 flex items-center text-slate-400 shrink-0" aria-hidden="true">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    </span>
                                    <input type="text" readonly class="quiz-link-value flex-1 min-w-0 bg-transparent border-0 py-2.5 pr-3 text-slate-700 focus:ring-0 outline-none" value="${$('<div>').text(link).html()}">
                                </div>
                                <button type="button" class="bp-btn bp-btn-primary px-5 py-2.5 text-sm copy-link shrink-0 inline-flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    Copy link
                                </button>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm text-slate-600">Quiz visibility</span>
                                <select class="bp-input quiz-status-select py-2 px-3 text-sm rounded-lg q-status" data-id="${q.id}">
                                    <option value="active" ${st ? 'selected' : ''}>Active — listed for students</option>
                                    <option value="inactive" ${!st ? 'selected' : ''}>Hidden — link only</option>
                                </select>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                <a href="quiz_questions?batch_id=${encodeURIComponent(BATCH_ID)}&id=${encodeURIComponent(q.id)}" class="bp-btn bp-btn-secondary px-4 py-2 text-sm inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"/></svg>
                                    View questions
                                </a>
                                <a href="quiz?batch_id=${encodeURIComponent(BATCH_ID)}&id=${encodeURIComponent(q.id)}" class="bp-btn bp-btn-secondary px-4 py-2 text-sm inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    View results
                                </a>
                                <button type="button" class="inline-flex items-center gap-1.5 text-sm font-medium text-red-600 hover:text-red-800 hover:underline delete-q px-1 py-2" data-id="${q.id}">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete quiz
                                </button>
                            </div>
                        </div>
                    </div>
                </article>`;
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
                $('#dashStats').addClass('hidden');
                $('#screenLogin').removeClass('hidden');
            } else {
                toast(res.error || 'Error', true);
                setTimeout(() => location.href = 'index', 2000);
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
            rh += `<div class="bp-surface rounded-lg p-4 shadow-sm border-slate-200">
                <p class="font-semibold text-slate-900 text-sm truncate" title="${escapeHtml(q.name)}">${escapeHtml(q.name)}</p>
                <p class="text-xs bp-muted mt-1">${subs} · ${avg}</p>
                ${q.class_best_pct != null ? '<p class="text-xs text-slate-700 mt-1">Best: ' + q.class_best_pct + '%</p>' : ''}
            </div>`;
        });
        $('#statsRollups').html(rh || '<p class="text-slate-500 col-span-full text-sm">No quizzes yet.</p>');

        const rows = st.rows || [];
        if (rows.length === 0 || quizzes.length === 0) {
            $('#statsMatrixInner').html('<p class="p-6 text-center bp-muted">Add participants and quizzes to see the matrix.</p>');
            return;
        }

        let t = '<table class="w-full text-xs min-w-full border-collapse"><thead><tr class="border-b border-slate-200">';
        t += '<th class="text-center py-3 px-2 w-12 min-w-[2.75rem] sticky left-0 bg-white z-20 text-slate-600 font-semibold border-r border-slate-100">Rank</th>';
        t += '<th class="text-left py-3 px-2 min-w-[140px] sticky left-12 z-10 bg-white text-slate-600 font-semibold border-r border-slate-100 shadow-[4px_0_8px_-4px_rgba(15,23,42,0.08)]">Student</th>';
        quizzes.forEach(function(q) {
            t += '<th class="text-center py-3 px-2 text-slate-500 font-medium max-w-[100px]" title="' + escapeHtml(q.name) + '"><span class="line-clamp-2">' + escapeHtml(q.name) + '</span></th>';
        });
        t += '<th class="text-center py-3 px-2 text-indigo-600 font-semibold">Avg</th></tr></thead><tbody>';

        rows.forEach(function(row) {
            const rk = row.rank != null ? String(row.rank) : '—';
            t += '<tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors" tabindex="0" role="link" data-participant-id="' + escapeHtml(row.participant_id) + '">';
            t += '<td class="py-2 px-2 text-center tabular-nums text-slate-800 font-semibold sticky left-0 z-20 bg-white border-r border-slate-100">' + escapeHtml(rk) + '</td>';
            t += '<td class="py-2 px-2 sticky left-12 z-10 bg-white text-slate-900 font-medium border-r border-slate-100 shadow-[4px_0_8px_-4px_rgba(15,23,42,0.06)]">' + escapeHtml(row.participant_name) + '<br><span class="text-[10px] bp-muted font-mono">' + escapeHtml(row.participant_id) + '</span></td>';
            quizzes.forEach(function(q) {
                const c = row.cells[q.id];
                let cell = '—';
                let cls = 'text-slate-400';
                if (c && c.key === 'done') {
                    cell = c.label + '<br><span class="text-indigo-600 font-medium">' + c.percentage + '%</span>';
                    cls = 'text-slate-700';
                } else if (c && c.key === 'running') {
                    cell = '<span class="text-amber-700">In progress</span>';
                    cls = '';
                }
                t += '<td class="py-2 px-1 text-center ' + cls + ' align-top">' + cell + '</td>';
            });
            const ap = row.avg_percentage != null ? row.avg_percentage + '%' : '—';
            t += '<td class="py-2 px-2 text-center text-indigo-700 font-semibold">' + ap + '</td></tr>';
        });
        t += '</tbody></table>';
        $('#statsMatrixInner').html(t);
        $('#statsMatrixInner').off('click', 'tr[data-participant-id]').on('click', 'tr[data-participant-id]', function() {
            const pid = $(this).attr('data-participant-id');
            if (pid) {
                window.location.href = 'participant_detail?batch_id=' + encodeURIComponent(BATCH_ID) + '&participant_id=' + encodeURIComponent(pid);
            }
        });
        $('#statsMatrixInner').off('keydown', 'tr[data-participant-id]').on('keydown', 'tr[data-participant-id]', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const pid = $(this).attr('data-participant-id');
                if (pid) {
                    window.location.href = 'participant_detail?batch_id=' + encodeURIComponent(BATCH_ID) + '&participant_id=' + encodeURIComponent(pid);
                }
            }
        });
    }

    function loadBatchStats() {
        if (batchStatsCache) {
            renderBatchStats(batchStatsCache);
            return;
        }
        $('#statsMatrixInner').html('<p class="p-6 text-center bp-muted">Loading…</p>');
        $.post(API, { action: 'batch_stats', batch_id: BATCH_ID }, function(res) {
            if (res.success) {
                batchStatsCache = res.stats;
                renderBatchStats(batchStatsCache);
            } else {
                $('#statsMatrixInner').html('<p class="p-6 text-red-600">' + escapeHtml(res.error || 'Failed') + '</p>');
            }
        }, 'json');
    }

    $('.tab-btn').click(function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('bp-tab-active').addClass('bp-tab-idle');
        $(this).removeClass('bp-tab-idle').addClass('bp-tab-active');
        $('.tab-panel').addClass('hidden');
        $('#tab-' + tab).removeClass('hidden');
        if (tab === 'stats') loadBatchStats();
    });

    $('#btnEditBatch').click(function() {
        if (!batchPayload || !batchPayload.batch_info) return;
        const bi = batchPayload.batch_info;
        $('#batchEditName').val(bi.name || '');
        $('#batchEditTeacher').val(bi.teacher_name || '');
        $('#batchEditPassword').val('');
        $('#modalBatch').removeClass('hidden').addClass('flex');
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
    $('#modalParticipant, #modalQuiz, #modalEditParticipant, #modalBatch').click(function(e) {
        if (e.target === this) $(this).removeClass('flex').addClass('hidden');
    });

    $('#formBatch').submit(function(e) {
        e.preventDefault();
        $.post(API, {
            action: 'update_batch',
            batch_id: BATCH_ID,
            name: $('#batchEditName').val(),
            teacher_name: $('#batchEditTeacher').val(),
            teacher_password: $('#batchEditPassword').val()
        }, function(res) {
            if (res.success) {
                $('#modalBatch').removeClass('flex').addClass('hidden');
                toast('Batch info updated');
                loadBatch();
                document.title = $('#batchEditName').val() + ' — NikkQuiz';
                $('#loginBatchTitle').text($('#batchEditName').val());
                $('#loginTeacherLine').text('Teacher: ' + $('#batchEditTeacher').val());
            } else {
                toast(res.error, true);
            }
        }, 'json');
    });

    $('#formParticipant').submit(function(e) {
        e.preventDefault();
        const form = this;
        const nameInput = form.elements.namedItem('name');
        const emailInput = form.elements.namedItem('email');
        $.post(API, {
            action: 'add_batch_participant',
            batch_id: BATCH_ID,
            name: nameInput ? nameInput.value : '',
            email: emailInput ? emailInput.value : ''
        }, function(res) {
            if (res.success) {
                $('#modalParticipant').removeClass('flex').addClass('hidden');
                toast('Participant added — PIN: ' + res.participant.pin);
                loadBatch();
            } else toast(res.error, true);
        }, 'json');
    });

    $('#formEditParticipant').submit(function(e) {
        e.preventDefault();
        const id = $('#editParticipantId').val();
        const name = $('#editParticipantName').val().trim();
        const email = $('#editParticipantEmail').val().trim();
        if (!id || !name) return;
        $.post(API, {
            action: 'update_batch_participant_name',
            batch_id: BATCH_ID,
            participant_id: id,
            name: name,
            email: email
        }, function(res) {
            if (res.success) {
                $('#modalEditParticipant').removeClass('flex').addClass('hidden');
                toast('Student updated');
                loadBatch();
            } else {
                toast(res.error, true);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-view-pin', function() {
        const $btn = $(this);
        const pin = String($btn.data('pin') || '');
        const $disp = $btn.closest('tr').find('.pin-display');
        if ($btn.hasClass('is-open')) {
            $disp.text('••••••').removeClass('tracking-normal').addClass('tracking-widest');
            $btn.removeClass('is-open').text('View PIN');
        } else {
            $disp.text(pin || '—').removeClass('tracking-widest').addClass('tracking-normal');
            $btn.addClass('is-open').text('Hide PIN');
        }
    });

    $(document).on('click', '.btn-edit-participant', function() {
        const id = $(this).data('id');
        if (!batchPayload || !batchPayload.participants) return;
        const p = batchPayload.participants.find(x => x.id === id);
        if (!p) return;
        $('#editParticipantId').val(id);
        $('#editParticipantName').val(p.name);
        $('#editParticipantEmail').val(p.email || '');
        $('#modalEditParticipant').removeClass('hidden').addClass('flex');
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
        let name = id;
        if (batchPayload && batchPayload.participants) {
            const p = batchPayload.participants.find(x => x.id === id);
            if (p) name = p.name;
        }
        if (!confirm('Remove ' + name + '?')) return;
        $.post(API, { action: 'remove_batch_participant', batch_id: BATCH_ID, participant_id: id }, function(res) {
            if (res.success) { toast('Removed'); loadBatch(); }
            else toast(res.error, true);
        }, 'json');
    });

    $(document).on('click', '.copy-link', function() {
        const inp = $(this).closest('.quiz-card').find('input.quiz-link-value');
        if (inp.length) {
            inp[0].select();
            document.execCommand('copy');
            toast('Link copied');
        }
    });

    $(document).on('change', '.q-status', function() {
        const id = $(this).data('id');
        const status = $(this).val();
        const $card = $(this).closest('.quiz-card');
        const $badge = $card.find('.quiz-status-badge');
        $.post(API, { action: 'set_quiz_status', batch_id: BATCH_ID, quiz_id: id, status: status }, function(res) {
            if (res.success) {
                const active = status === 'active';
                $badge.text(active ? 'Active' : 'Hidden');
                $badge.removeClass('bg-emerald-50 text-emerald-800 border-emerald-200 bg-slate-100 text-slate-600 border-slate-200');
                $badge.addClass(active ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200');
                toast('Status saved');
            } else {
                toast(res.error, true);
            }
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
            if (res.success) location.href = 'index';
            else toast(res.error, true);
        }, 'json');
    });

    $(document).ready(function() {
        if (!BATCH_ID) {
            location.href = 'index';
            return;
        }
        $.post(API, { action: 'batch_meta', batch_id: BATCH_ID }, function(m) {
            if (!m.success) {
                toast('Batch not found', true);
                setTimeout(() => location.href = 'index', 2000);
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
