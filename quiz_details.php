<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Details — NikkQuiz</title>
    <meta name="description" content="Manage quiz questions and participants.">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .gradient-text {
            background: linear-gradient(135deg, #a78bfa, #6d28d9, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(109, 40, 217, 0.35);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(109, 40, 217, 0.5); }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.1); }
        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
            transition: all 0.3s ease;
        }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(220, 38, 38, 0.45); }
        .btn-emerald {
            background: linear-gradient(135deg, #059669, #047857);
            box-shadow: 0 4px 20px rgba(5, 150, 105, 0.3);
            transition: all 0.3s ease;
        }
        .btn-emerald:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(5, 150, 105, 0.45); }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
            outline: none;
            background: rgba(255, 255, 255, 0.08);
        }
        .modal-overlay {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }
        .modal-card {
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .stat-badge {
            background: rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(124, 58, 237, 0.2);
        }
        .fade-in { animation: fadeIn 0.5s ease forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .status-pending { color: #fbbf24; background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.2); }
        .status-running { color: #60a5fa; background: rgba(96,165,250,0.12); border: 1px solid rgba(96,165,250,0.2); }
        .status-finished { color: #34d399; background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.2); }
        .tab-active {
            border-bottom: 2px solid #7c3aed;
            color: #c4b5fd;
        }
        .tab-inactive {
            border-bottom: 2px solid transparent;
            color: #6b7280;
        }
        .tab-inactive:hover { color: #9ca3af; }
        .copy-btn { transition: all 0.2s; }
        .copy-btn:hover { color: #a78bfa; }
        .toast {
            animation: toastIn 0.4s ease, toastOut 0.4s ease 2.5s forwards;
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes toastOut {
            from { opacity: 1; }
            to { opacity: 0; transform: translateY(-10px); }
        }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body class="min-h-screen text-white">

    <!-- Decorative -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.15), transparent 70%);"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(236,72,153,0.1), transparent 70%);"></div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 space-y-2"></div>

    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Back Button & Header -->
        <div class="flex items-center gap-4 mb-8 fade-in">
            <a href="index.php" class="btn-secondary px-3 py-2 rounded-xl text-sm flex items-center gap-1.5 text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back
            </a>
            <div>
                <h1 id="quizTitle" class="text-2xl sm:text-3xl font-bold gradient-text">Loading...</h1>
                <p class="text-gray-500 text-sm mt-0.5" id="quizMeta"></p>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 fade-in" style="animation-delay:0.1s;">
            <div class="glass-card rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Time Limit</p>
                <p class="text-2xl font-bold text-violet-300" id="statTime">—</p>
                <p class="text-xs text-gray-500">minutes</p>
            </div>
            <div class="glass-card rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Questions</p>
                <p class="text-2xl font-bold text-violet-300" id="statQuestions">—</p>
                <p class="text-xs text-gray-500">in pool</p>
            </div>
            <div class="glass-card rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Display</p>
                <p class="text-2xl font-bold text-violet-300" id="statDisplay">—</p>
                <p class="text-xs text-gray-500">per student</p>
            </div>
            <div class="glass-card rounded-xl p-4 text-center">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Participants</p>
                <p class="text-2xl font-bold text-violet-300" id="statParticipants">—</p>
                <p class="text-xs text-gray-500">registered</p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="flex gap-6 border-b border-gray-800 mb-6 fade-in" style="animation-delay:0.15s;">
            <button class="tab-btn tab-active pb-3 text-sm font-medium" data-tab="questions">
                📝 Questions
            </button>
            <button class="tab-btn tab-inactive pb-3 text-sm font-medium" data-tab="participants">
                👥 Participants
            </button>
            <button class="tab-btn tab-inactive pb-3 text-sm font-medium" data-tab="results">
                📊 Results
            </button>
        </div>

        <!-- Questions Tab -->
        <div id="tab-questions" class="tab-content fade-in">
            <div class="glass-card rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-200 mb-4">Upload Questions</h3>
                <p class="text-gray-400 text-sm mb-4">Upload a JSON file containing your question pool. Use the format below:</p>
                <div class="rounded-xl p-4 mb-4" style="background: rgba(124,58,237,0.08); border: 1px solid rgba(124,58,237,0.15);">
                    <pre class="text-xs text-violet-300"><code>[
  {
    "id": 1,
    "question": "What is the capital of France?",
    "options": ["Berlin", "Madrid", "Paris", "Rome"],
    "answer": 2
  },
  {
    "id": 2,
    "question": "Which planet is closest to the Sun?",
    "options": ["Venus", "Mercury", "Earth", "Mars"],
    "answer": 1
  }
]</code></pre>
                    <p class="text-xs text-gray-500 mt-2">Note: <code class="text-violet-400">answer</code> is a 0-based index of the correct option.</p>
                </div>
                <form id="uploadForm" class="flex flex-col sm:flex-row gap-3 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Select JSON File</label>
                        <input type="file" name="questions_file" accept=".json" required
                            class="input-field w-full px-4 py-2.5 rounded-xl text-white text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-violet-900 file:text-violet-300 file:cursor-pointer">
                    </div>
                    <button type="submit" class="btn-emerald text-white px-6 py-2.5 rounded-xl font-semibold text-sm whitespace-nowrap">
                        Upload Questions
                    </button>
                </form>
            </div>

            <div id="questionPreview" class="space-y-3"></div>
        </div>

        <!-- Participants Tab -->
        <div id="tab-participants" class="tab-content hidden">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-200">Registered Participants</h3>
                <button id="btnAddParticipant" class="btn-primary text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Add Participant
                </button>
            </div>
            <div id="participantList" class="space-y-3"></div>
            <div id="noParticipants" class="hidden text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl mb-4" style="background: rgba(124,58,237,0.1);">
                    <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="text-gray-400 text-sm">No participants added yet.</p>
            </div>
        </div>

        <!-- Results Tab -->
        <div id="tab-results" class="tab-content hidden">
            <div id="resultsList" class="space-y-3"></div>
            <div id="noResults" class="hidden text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl mb-4" style="background: rgba(124,58,237,0.1);">
                    <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <p class="text-gray-400 text-sm">No completed quizzes yet.</p>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="mt-12 glass-card rounded-2xl p-6 border-red-900 fade-in" style="animation-delay:0.3s; border-color: rgba(220,38,38,0.2);">
            <h3 class="text-lg font-semibold text-red-400 mb-2">Danger Zone</h3>
            <p class="text-gray-500 text-sm mb-4">Permanently delete this quiz and all its data. This action cannot be undone.</p>
            <button id="btnDeleteQuiz" class="btn-danger text-white px-6 py-2 rounded-xl font-semibold text-sm">
                Delete This Quiz
            </button>
        </div>
    </div>

    <!-- Add Participant Modal -->
    <div id="participantModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay p-4">
        <div class="modal-card rounded-2xl w-full max-w-lg p-8 relative fade-in">
            <button id="closeParticipantModal" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h2 class="text-2xl font-bold gradient-text mb-1">Add Participant</h2>
            <p class="text-gray-400 text-sm mb-6">Enter the participant's name. An ID, PIN, and unique link will be auto-generated.</p>

            <form id="addParticipantForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Full Name</label>
                    <input type="text" name="name" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="e.g., John Doe">
                </div>
                <button type="submit" id="btnSubmitParticipant" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm">
                    Add Participant
                </button>
            </form>

            <!-- Success Panel (shown after adding) -->
            <div id="participantSuccess" class="hidden mt-6 rounded-xl p-5" style="background: rgba(52,211,153,0.08); border: 1px solid rgba(52,211,153,0.2);">
                <h4 class="text-sm font-semibold text-emerald-400 mb-3">✅ Participant Added Successfully</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">ID:</span>
                        <span class="text-white font-mono font-bold" id="successId"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">PIN:</span>
                        <span class="text-white font-mono font-bold" id="successPin"></span>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-1">Quiz Link:</span>
                        <div class="flex gap-2">
                            <input type="text" readonly id="successLink" class="input-field flex-1 px-3 py-2 rounded-lg text-xs text-violet-300">
                            <button onclick="copyToClipboard($('#successLink').val())" class="btn-secondary px-3 py-2 rounded-lg text-xs text-gray-300">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const API = 'api/handler.php';
    const QUIZ_ID = new URLSearchParams(window.location.search).get('id');
    let quizData = null;

    // ─── Toast Notification ──────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const colors = {
            success: 'bg-emerald-900 border-emerald-700 text-emerald-200',
            error: 'bg-red-900 border-red-700 text-red-200',
            info: 'bg-violet-900 border-violet-700 text-violet-200',
        };
        const $toast = $(`<div class="toast ${colors[type]} border px-4 py-3 rounded-xl text-sm font-medium shadow-lg">${msg}</div>`);
        $('#toastContainer').append($toast);
        setTimeout(() => $toast.remove(), 3000);
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => showToast('Copied to clipboard!'));
    }

    // ─── Tab Switching ───────────────────────────────────────────────
    $('.tab-btn').click(function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('tab-active').addClass('tab-inactive');
        $(this).removeClass('tab-inactive').addClass('tab-active');
        $('.tab-content').addClass('hidden');
        $(`#tab-${tab}`).removeClass('hidden');
    });

    // ─── Load Quiz ───────────────────────────────────────────────────
    function loadQuiz() {
        $.post(API, { action: 'get_quiz', quiz_id: QUIZ_ID }, function(res) {
            if (!res.success) {
                if (res.needs_auth) {
                    window.location.href = 'index.php';
                } else {
                    showToast(res.error, 'error');
                }
                return;
            }
            quizData = res.quiz;
            renderQuizDetails();
        }, 'json');
    }

    function renderQuizDetails() {
        const q = quizData.quiz_info;
        document.title = q.name + ' — NikkQuiz';
        $('#quizTitle').text(q.name);
        $('#quizMeta').text('Created ' + q.created_at);
        $('#statTime').text(q.time_limit);
        $('#statQuestions').text(quizData.questions.length);
        $('#statDisplay').text(q.total_display_questions);
        $('#statParticipants').text(quizData.participants.length);

        renderQuestions();
        renderParticipants();
        renderResults();
    }

    // ─── Questions ───────────────────────────────────────────────────
    function renderQuestions() {
        if (quizData.questions.length === 0) {
            $('#questionPreview').html('<p class="text-gray-500 text-sm text-center py-6">No questions uploaded yet.</p>');
            return;
        }
        let html = `<p class="text-gray-400 text-sm mb-3">${quizData.questions.length} question(s) in pool</p>`;
        quizData.questions.forEach((q, i) => {
            const opts = q.options.map((o, oi) =>
                `<span class="inline-block px-2.5 py-1 rounded-lg text-xs mr-2 mb-1 ${oi === q.answer ? 'bg-emerald-900 text-emerald-300 border border-emerald-700' : 'bg-gray-800 text-gray-400'}">${o}${oi === q.answer ? ' ✓' : ''}</span>`
            ).join('');
            html += `
            <div class="glass-card rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <span class="stat-badge px-2 py-0.5 rounded-lg text-xs text-violet-300 font-mono">#${q.id}</span>
                    <div>
                        <p class="text-sm text-gray-200 mb-2">${escapeHtml(q.question)}</p>
                        <div>${opts}</div>
                    </div>
                </div>
            </div>`;
        });
        $('#questionPreview').html(html);
    }

    // ─── Participants ────────────────────────────────────────────────
    function renderParticipants() {
        const p = quizData.participants;
        if (p.length === 0) {
            $('#participantList').html('');
            $('#noParticipants').removeClass('hidden');
            return;
        }
        $('#noParticipants').addClass('hidden');

        const baseUrl = window.location.origin + window.location.pathname.replace('quiz_details.php', '') + 'take_quiz.php?uid=';

        let html = '';
        p.forEach(pt => {
            const link = baseUrl + pt.token;
            html += `
            <div class="glass-card rounded-xl p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-sm font-semibold text-white">${escapeHtml(pt.name)}</span>
                        <span class="stat-badge px-2 py-0.5 rounded-lg text-xs text-violet-300 font-mono">${escapeHtml(pt.id)}</span>
                        <span class="status-${pt.status} px-2 py-0.5 rounded-lg text-xs font-medium capitalize">${pt.status}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400">
                        <span>PIN: <code class="text-violet-300 font-mono">${pt.pin}</code></span>
                        <span class="hidden sm:inline">|</span>
                        <span class="truncate max-w-xs">
                            <a href="${link}" target="_blank" class="text-violet-400 hover:text-violet-300 transition-colors">${link.length > 60 ? link.substring(0, 60) + '...' : link}</a>
                        </span>
                        <button onclick="copyToClipboard('${link}')" class="copy-btn text-gray-500" title="Copy link">
                            <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                    ${pt.marks !== undefined && pt.status === 'finished' ? `<span class="text-xs text-emerald-400 mt-1 inline-block">Score: ${pt.marks}/${pt.assigned_questions ? pt.assigned_questions.length : '?'}</span>` : ''}
                </div>
                <button onclick="removeParticipant('${pt.token}', '${escapeHtml(pt.name)}')" class="text-red-500 hover:text-red-400 text-xs font-medium transition-colors whitespace-nowrap">
                    Remove
                </button>
            </div>`;
        });
        $('#participantList').html(html);
    }

    // ─── Results ─────────────────────────────────────────────────────
    function renderResults() {
        const finished = quizData.participants.filter(p => p.status === 'finished');
        if (finished.length === 0) {
            $('#resultsList').html('');
            $('#noResults').removeClass('hidden');
            return;
        }
        $('#noResults').addClass('hidden');

        // Sort by marks descending
        finished.sort((a, b) => b.marks - a.marks);

        let html = `<div class="glass-card rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">#</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Score</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">%</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Grade</th>
                    </tr>
                </thead>
                <tbody>`;

        finished.forEach((p, i) => {
            const total = p.assigned_questions ? p.assigned_questions.length : 0;
            const pct = total > 0 ? Math.round((p.marks / total) * 100) : 0;
            const { grade, emoji } = getGrade(pct);
            html += `
                <tr class="border-b border-gray-800 border-opacity-50 hover:bg-white hover:bg-opacity-5 transition-colors">
                    <td class="px-5 py-3 text-gray-500 font-mono">${i + 1}</td>
                    <td class="px-5 py-3 text-white font-medium">${escapeHtml(p.name)}</td>
                    <td class="px-5 py-3 text-gray-400 font-mono text-xs">${escapeHtml(p.id)}</td>
                    <td class="px-5 py-3 text-center text-violet-300 font-semibold">${p.marks}/${total}</td>
                    <td class="px-5 py-3 text-center text-gray-300">${pct}%</td>
                    <td class="px-5 py-3 text-center">${emoji} <span class="text-xs text-gray-400">${grade}</span></td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        $('#resultsList').html(html);
    }

    function getGrade(pct) {
        if (pct >= 80) return { grade: 'Excellent', emoji: '🤩' };
        if (pct >= 60) return { grade: 'Good', emoji: '🙂' };
        if (pct >= 40) return { grade: 'Average', emoji: '😐' };
        if (pct >= 20) return { grade: 'Poor', emoji: '☹️' };
        return { grade: 'Very Poor', emoji: '💀' };
    }

    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }

    // ─── Upload Questions ────────────────────────────────────────────
    $('#uploadForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'upload_questions');
        formData.append('quiz_id', QUIZ_ID);

        $.ajax({
            url: API,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast(`${res.count} question(s) uploaded successfully!`);
                    loadQuiz();
                    $('#uploadForm')[0].reset();
                } else {
                    showToast(res.error, 'error');
                }
            }
        });
    });

    // ─── Add Participant Modal ───────────────────────────────────────
    $('#btnAddParticipant').click(function() {
        $('#participantSuccess').addClass('hidden');
        $('#addParticipantForm')[0].reset();
        $('#addParticipantForm').show();
        $('#btnSubmitParticipant').show();
        $('#participantModal').removeClass('hidden').addClass('flex');
    });
    $('#closeParticipantModal').click(() => {
        $('#participantModal').removeClass('flex').addClass('hidden');
    });
    $('#participantModal').click(function(e) {
        if (e.target === this) $('#closeParticipantModal').click();
    });

    $('#addParticipantForm').submit(function(e) {
        e.preventDefault();
        const btn = $('#btnSubmitParticipant');
        btn.prop('disabled', true).text('Adding...');

        $.post(API, {
            action: 'add_participant',
            quiz_id: QUIZ_ID,
            name: this.name.value
        }, function(res) {
            btn.prop('disabled', false).text('Add Participant');
            if (res.success) {
                $('#successId').text(res.participant.id);
                $('#successPin').text(res.participant.pin);
                $('#successLink').val(res.participant.quiz_link);
                $('#participantSuccess').removeClass('hidden');
                showToast('Participant added!');
                loadQuiz();
            } else {
                showToast(res.error, 'error');
            }
        }, 'json');
    });

    // ─── Remove Participant ──────────────────────────────────────────
    function removeParticipant(token, name) {
        if (!confirm(`Remove "${name}" from this quiz? This cannot be undone.`)) return;
        $.post(API, { action: 'remove_participant', quiz_id: QUIZ_ID, token: token }, function(res) {
            if (res.success) {
                showToast('Participant removed.');
                loadQuiz();
            } else {
                showToast(res.error, 'error');
            }
        }, 'json');
    }

    // ─── Delete Quiz ─────────────────────────────────────────────────
    $('#btnDeleteQuiz').click(function() {
        if (!confirm('Are you sure you want to permanently delete this quiz? This cannot be undone.')) return;
        $.post(API, { action: 'delete_quiz', quiz_id: QUIZ_ID }, function(res) {
            if (res.success) {
                window.location.href = 'index.php';
            } else {
                showToast(res.error, 'error');
            }
        }, 'json');
    });

    // ─── Init ────────────────────────────────────────────────────────
    $(document).ready(function() {
        if (!QUIZ_ID) {
            window.location.href = 'index.php';
            return;
        }
        loadQuiz();
    });
    </script>
</body>
</html>
