<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My quiz stats — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(12px); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa, #6d28d9, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-primary { background: linear-gradient(135deg, #7c3aed, #6d28d9); box-shadow: 0 4px 20px rgba(109,40,217,0.35); }
        .stat-tile { background: linear-gradient(145deg, rgba(124,58,237,0.12), rgba(236,72,153,0.06)); border: 1px solid rgba(124,58,237,0.2); }
        .input-field { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
        .input-field:focus { border-color: #7c3aed; outline: none; box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }
        .pin-input { width: 44px; height: 52px; text-align: center; font-size: 1.1rem; font-weight: 700; border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.12), transparent 70%);"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <a href="quizzes" class="text-sm text-gray-500 hover:text-gray-300">← Active quizzes</a>
            <button type="button" id="btnLogoutStudent" class="text-sm text-gray-500 hover:text-violet-300 hidden">Sign out</button>
        </div>

        <!-- PIN gate -->
        <div id="gate" class="max-w-md mx-auto">
            <div class="glass-card rounded-2xl p-8 text-center">
                <h1 class="text-2xl font-bold gradient-text mb-2">My quiz stats</h1>
                <p class="text-gray-400 text-sm mb-6">Enter the same 6-digit PIN your teacher gave you for this class.</p>
                <div class="flex justify-center gap-1.5 mb-4" id="pinBox"></div>
                <p id="pinErr" class="text-red-400 text-xs mb-4 hidden"></p>
                <button type="button" id="btnView" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm disabled:opacity-40" disabled>View my stats</button>
            </div>
        </div>

        <!-- Dashboard -->
        <div id="dash" class="hidden">
            <div class="mb-8">
                <p class="text-xs text-violet-400 uppercase tracking-wider mb-1">Your progress</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-white" id="studentName"></h1>
                <p class="text-gray-500 text-sm mt-1" id="batchLine"></p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8" id="summaryTiles"></div>

            <h2 class="text-lg font-semibold text-gray-200 mb-3">All quizzes in your batch</h2>
            <div class="glass-card rounded-xl overflow-hidden overflow-x-auto">
                <table class="w-full text-sm min-w-[600px]">
                    <thead>
                        <tr class="border-b border-gray-800 text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Quiz</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Score</th>
                            <th class="px-4 py-3 text-center">%</th>
                            <th class="px-4 py-3 text-right">Grade</th>
                        </tr>
                    </thead>
                    <tbody id="statsBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    const API = 'api/handler';

    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }

    function buildPinInputs() {
        let h = '';
        for (let i = 0; i < 6; i++) {
            h += '<input type="text" maxlength="1" class="pin-input input-field text-white" data-i="' + i + '" inputmode="numeric" autocomplete="one-time-code">';
        }
        $('#pinBox').html(h);
        const inputs = $('#pinBox input');
        inputs.on('input', function() {
            const v = $(this).val().replace(/\D/g, '').slice(0, 1);
            $(this).val(v);
            if (v && $(this).data('i') < 5) inputs.eq($(this).data('i') + 1).focus();
            checkPin();
        });
        inputs.on('keydown', function(e) {
            if (e.key === 'Backspace' && !$(this).val() && $(this).data('i') > 0) {
                inputs.eq($(this).data('i') - 1).focus();
            }
        });
        inputs.first().focus();
    }

    function getPin() {
        let p = '';
        $('#pinBox input').each(function() { p += $(this).val(); });
        return p;
    }

    function checkPin() {
        $('#btnView').prop('disabled', getPin().length !== 6);
    }

    function loadStats(pinPost) {
        const data = { action: 'student_stats' };
        if (pinPost) data.pin = pinPost;
        $.post(API, data, function(res) {
            if (!res.success) {
                $('#pinErr').removeClass('hidden').text(res.error || 'Could not load.');
                return;
            }
            $('#pinErr').addClass('hidden');
            $('#gate').addClass('hidden');
            $('#dash').removeClass('hidden');
            $('#btnLogoutStudent').removeClass('hidden');
            render(res.stats);
        }, 'json');
    }

    function render(s) {
        document.title = 'My stats — ' + (s.participant_name || 'NikkQuiz');
        $('#studentName').text(s.participant_name || 'Student');
        $('#batchLine').text(s.batch_name || '');

        const sum = s.summary;
        const tiles = [
            { label: 'Quizzes in batch', val: sum.quizzes_total, sub: 'assigned' },
            { label: 'Completed', val: sum.quizzes_completed, sub: 'finished attempts' },
            { label: 'Your average', val: sum.average_percentage != null ? sum.average_percentage + '%' : '—', sub: 'completed only' },
            { label: 'Your best', val: sum.best_percentage != null ? sum.best_percentage + '%' : '—', sub: 'single quiz' },
        ];
        let th = '';
        tiles.forEach(t => {
            th += '<div class="stat-tile rounded-xl p-4 text-center">';
            th += '<p class="text-2xl font-bold text-white">' + escapeHtml(String(t.val)) + '</p>';
            th += '<p class="text-xs text-gray-400 mt-1">' + escapeHtml(t.label) + '</p>';
            th += '<p class="text-[10px] text-gray-600 mt-0.5">' + escapeHtml(t.sub) + '</p></div>';
        });
        $('#summaryTiles').html(th);

        let rows = '';
        (s.quizzes || []).forEach(function(q) {
            let status = '';
            let score = '—';
            let pct = '—';
            let grade = '—';
            if (q.attempt_status === 'not_started') {
                status = '<span class="text-gray-500">Not started</span>';
            } else if (q.attempt_status === 'in_progress') {
                status = '<span class="text-amber-400">In progress</span>';
            } else if (q.attempt_status === 'finished') {
                status = '<span class="text-emerald-400">Completed</span>';
                score = (q.marks != null ? q.marks : 0) + '/' + (q.total || 0);
                pct = (q.percentage != null ? q.percentage : 0) + '%';
                grade = (q.emoji || '') + ' <span class="text-gray-400 text-xs">' + escapeHtml(q.grade || '') + '</span>';
            }
            rows += '<tr class="border-b border-gray-800/60 hover:bg-white/5">';
            rows += '<td class="px-4 py-3 font-medium text-white">' + escapeHtml(q.quiz_name) + '</td>';
            rows += '<td class="px-4 py-3">' + status + '</td>';
            rows += '<td class="px-4 py-3 text-center text-violet-300">' + score + '</td>';
            rows += '<td class="px-4 py-3 text-center">' + pct + '</td>';
            rows += '<td class="px-4 py-3 text-right">' + grade + '</td></tr>';
        });
        $('#statsBody').html(rows || '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No quizzes in this batch yet.</td></tr>');
    }

    $('#btnView').click(function() {
        const p = getPin();
        if (p.length !== 6) return;
        loadStats(p);
    });

    $('#btnLogoutStudent').click(function() {
        $.post(API, { action: 'logout_student_stats' }, function() {
            location.reload();
        }, 'json');
    });

    $(document).ready(function() {
        buildPinInputs();
        $.post(API, { action: 'student_stats' }, function(res) {
            if (res.success) {
                $('#gate').addClass('hidden');
                $('#dash').removeClass('hidden');
                $('#btnLogoutStudent').removeClass('hidden');
                render(res.stats);
            }
        }, 'json');
    });
    </script>
</body>
</html>
