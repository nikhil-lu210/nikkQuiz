<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NikkQuiz — Admin Dashboard</title>
    <meta name="description" content="Create and manage quizzes with a lightweight file-based system.">
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
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(139, 92, 246, 0.3);
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
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(109, 40, 217, 0.5);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(220, 38, 38, 0.45);
        }
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
        .pulse-dot {
            animation: pulse-anim 2s ease-in-out infinite;
        }
        @keyframes pulse-anim {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        .fade-in { animation: fadeIn 0.5s ease forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .quiz-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .quiz-card:hover { transform: translateY(-4px); }
        .glow-ring {
            position: absolute; inset: -1px; border-radius: inherit;
            background: linear-gradient(135deg, rgba(124,58,237,0.3), transparent, rgba(236,72,153,0.3));
            z-index: -1; opacity: 0; transition: opacity 0.3s;
        }
        .quiz-card:hover .glow-ring { opacity: 1; }
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.04) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="min-h-screen text-white">

    <!-- Decorative Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.15), transparent 70%);"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(236,72,153,0.1), transparent 70%);"></div>
    </div>

    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <header class="text-center mb-12 fade-in">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-medium tracking-wide mb-6" style="background: rgba(124,58,237,0.15); border: 1px solid rgba(124,58,237,0.25); color: #c4b5fd;">
                <span class="w-1.5 h-1.5 rounded-full bg-violet-400 pulse-dot"></span>
                QUIZ MANAGEMENT SYSTEM
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold mb-3">
                <span class="gradient-text">NikkQuiz</span>
            </h1>
            <p class="text-gray-400 text-lg max-w-md mx-auto">Create, manage, and deploy quizzes in seconds. Lightweight & file-based.</p>
        </header>

        <!-- Action Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-8 fade-in" style="animation-delay: 0.1s;">
            <h2 class="text-xl font-semibold text-gray-200">Your Quizzes</h2>
            <button id="btnCreateQuiz" class="btn-primary text-white px-6 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Quiz
            </button>
        </div>

        <!-- Quiz List -->
        <div id="quizList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Skeleton Loaders -->
            <div class="glass-card rounded-2xl p-6 h-52 skeleton"></div>
            <div class="glass-card rounded-2xl p-6 h-52 skeleton" style="animation-delay:0.15s;"></div>
            <div class="glass-card rounded-2xl p-6 h-52 skeleton" style="animation-delay:0.3s;"></div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden text-center py-20 fade-in">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl mb-6" style="background: rgba(124,58,237,0.1);">
                <svg class="w-10 h-10 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-300 mb-2">No quizzes yet</h3>
            <p class="text-gray-500 mb-6">Create your first quiz to get started.</p>
            <button onclick="$('#btnCreateQuiz').click()" class="btn-primary text-white px-6 py-2.5 rounded-xl font-semibold text-sm">
                Create Your First Quiz
            </button>
        </div>
    </div>

    <!-- Create Quiz Modal -->
    <div id="createModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay p-4">
        <div class="modal-card rounded-2xl w-full max-w-lg p-8 relative fade-in">
            <button id="closeCreateModal" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h2 class="text-2xl font-bold gradient-text mb-1">Create New Quiz</h2>
            <p class="text-gray-400 text-sm mb-6">Fill in the details below to create a new quiz.</p>

            <form id="createQuizForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Quiz Name</label>
                    <input type="text" name="name" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="e.g., Midterm Exam">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Time Limit (minutes)</label>
                        <input type="number" name="time_limit" required min="1" class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="30">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Questions to Display</label>
                        <input type="number" name="total_display" required min="1" class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="10">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Admin Password</label>
                    <input type="password" name="admin_password" required minlength="4" class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="Choose a secure password">
                    <p class="text-xs text-gray-500 mt-1">You'll need this to manage the quiz later.</p>
                </div>
                <button type="submit" id="btnSubmitCreate" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm mt-2">
                    Create Quiz
                </button>
            </form>
        </div>
    </div>

    <!-- Admin Auth Modal -->
    <div id="authModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay p-4">
        <div class="modal-card rounded-2xl w-full max-w-md p-8 relative fade-in">
            <button id="closeAuthModal" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl mb-4" style="background: rgba(124,58,237,0.15);">
                    <svg class="w-7 h-7 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h2 class="text-xl font-bold text-white">Enter Admin Password</h2>
                <p class="text-gray-400 text-sm mt-1" id="authQuizName"></p>
            </div>
            <form id="authForm" class="space-y-4">
                <input type="hidden" name="quiz_id" id="authQuizId">
                <input type="password" name="admin_password" id="authPassword" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm text-center tracking-widest" placeholder="••••••••">
                <div id="authError" class="text-red-400 text-xs text-center hidden"></div>
                <button type="submit" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm">
                    Unlock
                </button>
            </form>
        </div>
    </div>

    <script>
    const API = 'api/handler.php';

    // ─── Load Quizzes ────────────────────────────────────────────────
    function loadQuizzes() {
        $.post(API, { action: 'list_quizzes' }, function(res) {
            if (res.success) {
                if (res.quizzes.length === 0) {
                    $('#quizList').html('');
                    $('#emptyState').removeClass('hidden');
                } else {
                    $('#emptyState').addClass('hidden');
                    renderQuizzes(res.quizzes);
                }
            }
        }, 'json');
    }

    function renderQuizzes(quizzes) {
        let html = '';
        quizzes.forEach((q, i) => {
            html += `
            <div class="quiz-card glass-card rounded-2xl p-6 relative cursor-pointer fade-in" style="animation-delay:${i * 0.08}s" data-quiz-id="${q.id}" data-quiz-name="${escapeHtml(q.name)}">
                <div class="glow-ring rounded-2xl"></div>
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(236,72,153,0.2));">
                        <svg class="w-5 h-5 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <span class="text-xs text-gray-500">${q.created_at}</span>
                </div>
                <h3 class="text-lg font-semibold text-white mb-3 truncate">${escapeHtml(q.name)}</h3>
                <div class="flex flex-wrap gap-2">
                    <span class="stat-badge px-2.5 py-1 rounded-lg text-xs text-violet-300 font-medium">⏱ ${q.time_limit} min</span>
                    <span class="stat-badge px-2.5 py-1 rounded-lg text-xs text-violet-300 font-medium">📝 ${q.question_count} Q</span>
                    <span class="stat-badge px-2.5 py-1 rounded-lg text-xs text-violet-300 font-medium">👥 ${q.participant_count}</span>
                </div>
            </div>`;
        });
        $('#quizList').html(html);

        // Delegated click handler — safe from quote-escaping issues
        $('#quizList').off('click', '.quiz-card').on('click', '.quiz-card', function() {
            const id = $(this).data('quiz-id');
            const name = $(this).data('quiz-name');
            openQuiz(id, name);
        });
    }

    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }

    // ─── Create Quiz Modal ───────────────────────────────────────────
    $('#btnCreateQuiz').click(() => {
        $('#createModal').removeClass('hidden').addClass('flex');
    });
    $('#closeCreateModal').click(() => {
        $('#createModal').removeClass('flex').addClass('hidden');
    });
    $('#createModal').click(function(e) {
        if (e.target === this) $('#closeCreateModal').click();
    });

    $('#createQuizForm').submit(function(e) {
        e.preventDefault();
        const btn = $('#btnSubmitCreate');
        btn.prop('disabled', true).text('Creating...');
        $.post(API, {
            action: 'create_quiz',
            name: this.name.value,
            time_limit: this.time_limit.value,
            total_display: this.total_display.value,
            admin_password: this.admin_password.value
        }, function(res) {
            btn.prop('disabled', false).text('Create Quiz');
            if (res.success) {
                $('#closeCreateModal').click();
                $('#createQuizForm')[0].reset();
                loadQuizzes();
            } else {
                alert(res.error);
            }
        }, 'json');
    });

    // ─── Open Quiz (Auth) ────────────────────────────────────────────
    function openQuiz(id, name) {
        // Try without password first (session might exist)
        $.post(API, { action: 'get_quiz', quiz_id: id }, function(res) {
            if (res.success) {
                window.location.href = 'quiz_details.php?id=' + id;
            } else if (res.needs_auth) {
                $('#authQuizId').val(id);
                $('#authQuizName').text(name);
                $('#authPassword').val('');
                $('#authError').addClass('hidden');
                $('#authModal').removeClass('hidden').addClass('flex');
                setTimeout(() => $('#authPassword').focus(), 200);
            }
        }, 'json');
    }

    // Auth Modal
    $('#closeAuthModal').click(() => {
        $('#authModal').removeClass('flex').addClass('hidden');
    });
    $('#authModal').click(function(e) {
        if (e.target === this) $('#closeAuthModal').click();
    });

    $('#authForm').submit(function(e) {
        e.preventDefault();
        const id = $('#authQuizId').val();
        const pw = $('#authPassword').val();
        $.post(API, { action: 'get_quiz', quiz_id: id, admin_password: pw }, function(res) {
            if (res.success) {
                $('#closeAuthModal').click();
                window.location.href = 'quiz_details.php?id=' + id;
            } else {
                $('#authError').removeClass('hidden').text(res.error);
            }
        }, 'json');
    });

    // ─── Init ────────────────────────────────────────────────────────
    $(document).ready(loadQuizzes);
    </script>
</body>
</html>
