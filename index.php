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
    <title>NikkQuiz — Batches</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
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
        .btn-primary:hover { transform: translateY(-2px); }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .input-field:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
            outline: none;
        }
        .modal-overlay { background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px); }
        .modal-card { background: #1a1a2e; border: 1px solid rgba(255, 255, 255, 0.08); }
        .stat-badge {
            background: rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(124, 58, 237, 0.2);
        }
        .fade-in { animation: fadeIn 0.5s ease forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .batch-card { transition: transform 0.3s ease; cursor: pointer; }
        .batch-card:hover { transform: translateY(-4px); }
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.04) 75%);
            background-size: 200% 100%;
            animation: sk 1.5s infinite;
        }
        @keyframes sk {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.15), transparent 70%);"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <header class="text-center mb-10 fade-in">
            <h1 class="text-4xl font-extrabold mb-2"><span class="gradient-text">NikkQuiz</span></h1>
            <p class="text-gray-400 text-sm max-w-lg mx-auto">Create a batch for your class, then add participants and quizzes. Teachers sign in with their batch password.</p>
            <p class="mt-4 flex flex-wrap justify-center gap-4 items-center">
                <a href="quizzes" class="text-violet-400 hover:text-violet-300 text-sm underline underline-offset-2">Student: browse active quizzes</a>
                <a href="logout_site" class="text-gray-500 hover:text-gray-300 text-sm">Sign out site</a>
            </p>
        </header>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-8">
            <h2 class="text-xl font-semibold text-gray-200">Student batches</h2>
            <button type="button" id="btnCreateBatch" class="btn-primary text-white px-6 py-2.5 rounded-xl font-semibold text-sm">Create batch</button>
        </div>

        <div id="batchList" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="glass-card rounded-2xl p-6 h-36 skeleton"></div>
            <div class="glass-card rounded-2xl p-6 h-36 skeleton"></div>
        </div>

        <div id="emptyState" class="hidden text-center py-16 border border-dashed border-gray-700 rounded-2xl">
            <p class="text-gray-500 mb-4">No batches yet. Create one to get started.</p>
        </div>
    </div>

    <div id="createModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay p-4">
        <div class="modal-card rounded-2xl w-full max-w-lg p-8 relative fade-in max-h-[90vh] overflow-y-auto">
            <button type="button" id="closeCreateModal" class="absolute top-4 right-4 text-gray-500 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <h2 class="text-2xl font-bold gradient-text mb-1">New student batch</h2>
            <p class="text-gray-400 text-sm mb-6">You will use the teacher password to open this batch and manage participants and quizzes.</p>
            <form id="createBatchForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Batch name</label>
                    <input type="text" name="name" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="e.g., Spring 2026 — Economics">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Teacher name</label>
                    <input type="text" name="teacher_name" required class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="Your name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Teacher password</label>
                    <input type="password" name="teacher_password" required minlength="4" class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="Min 4 characters">
                </div>
                <button type="submit" id="btnSubmitBatch" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm">Create batch</button>
            </form>
        </div>
    </div>

    <script>
    const API = 'api/handler';

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }

    function loadBatches() {
        $.post(API, { action: 'list_batches' }, function(res) {
            if (!res.success) return;
            if (res.batches.length === 0) {
                $('#batchList').html('');
                $('#emptyState').removeClass('hidden');
                return;
            }
            $('#emptyState').addClass('hidden');
            let html = '';
            res.batches.forEach(function(b, i) {
                html += `
                <div class="batch-card glass-card rounded-2xl p-6 fade-in" style="animation-delay:${i * 0.05}s" data-id="${b.id}">
                    <h3 class="text-lg font-semibold text-white mb-2 truncate">${escapeHtml(b.name)}</h3>
                    <p class="text-sm text-gray-400 mb-3">Teacher: ${escapeHtml(b.teacher_name)}</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="stat-badge px-2.5 py-1 rounded-lg text-xs text-violet-300">${b.participant_count} participants</span>
                        <span class="text-xs text-gray-500">${b.created_at}</span>
                    </div>
                </div>`;
            });
            $('#batchList').html(html);
            $('#batchList').off('click', '.batch-card').on('click', '.batch-card', function() {
                window.location.href = 'batch?id=' + $(this).data('id');
            });
        }, 'json');
    }

    $('#btnCreateBatch').click(function() {
        $('#createModal').removeClass('hidden').addClass('flex');
    });
    $('#closeCreateModal').click(function() {
        $('#createModal').removeClass('flex').addClass('hidden');
    });
    $('#createModal').click(function(e) {
        if (e.target === this) $('#closeCreateModal').click();
    });

    $('#createBatchForm').submit(function(e) {
        e.preventDefault();
        const btn = $('#btnSubmitBatch');
        const pw = this.teacher_password.value;
        btn.prop('disabled', true).text('Creating...');
        $.post(API, {
            action: 'create_batch',
            name: this.name.value,
            teacher_name: this.teacher_name.value,
            teacher_password: pw
        }, function(res) {
            if (!res.success) {
                btn.prop('disabled', false).text('Create batch');
                alert(res.error);
                return;
            }
            const batchId = res.batch.id;
            $.post(API, { action: 'login_batch', batch_id: batchId, teacher_password: pw }, function() {
                $('#closeCreateModal').click();
                $('#createBatchForm')[0].reset();
                window.location.href = 'batch?id=' + batchId;
            }, 'json').fail(function() {
                btn.prop('disabled', false).text('Create batch');
                window.location.href = 'batch?id=' + batchId;
            });
        }, 'json');
    });

    $(document).ready(loadBatches);
    </script>
</body>
</html>
