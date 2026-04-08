<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active quizzes — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa, #6d28d9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-primary { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
        .stat-badge { background: rgba(124,58,237,0.15); border: 1px solid rgba(124,58,237,0.2); }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="max-w-3xl mx-auto px-4 py-10">
        <header class="text-center mb-10">
            <h1 class="text-3xl font-extrabold mb-2"><span class="gradient-text">Active quizzes</span></h1>
            <p class="text-gray-400 text-sm max-w-md mx-auto">Open a quiz and enter your class PIN. Only quizzes your teacher marked active are listed.</p>
            <p class="mt-4 flex flex-wrap justify-center gap-4">
                <a href="my_stats" class="text-violet-400 hover:text-violet-300 text-sm font-medium">My quiz stats</a>
                <a href="index" class="text-gray-500 hover:text-gray-300 text-sm">Teacher home</a>
            </p>
        </header>
        <div id="list" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 h-20 animate-pulse bg-white/5"></div>
        </div>
        <div id="empty" class="hidden text-center py-12 text-gray-500 text-sm">No active quizzes.</div>
    </div>
    <script>
    const API = 'api/handler';
    function escapeHtml(s) {
        return $('<div>').text(s).html();
    }
    $(function() {
        $.get(API, { action: 'list_active_quizzes' }, function(res) {
            if (!res.success) return;
            if (!res.quizzes.length) {
                $('#list').html('');
                $('#empty').removeClass('hidden');
                return;
            }
            const base = window.location.pathname.replace(/[^/]*$/, '');
            let h = '';
            res.quizzes.forEach(function(q) {
                const href = base + 'take_quiz?q=' + encodeURIComponent(q.public_slug);
                const bn = q.batch_name ? '<span class="text-xs text-gray-500 block mb-1">' + escapeHtml(q.batch_name) + '</span>' : '';
                h += '<a href="' + href + '" class="block glass-card rounded-2xl p-6 hover:border-violet-500/30 transition-colors">';
                h += bn + '<div class="flex justify-between items-center gap-4">';
                h += '<div><h2 class="text-lg font-semibold text-white">' + escapeHtml(q.name) + '</h2>';
                h += '<div class="flex gap-2 mt-2"><span class="stat-badge px-2 py-1 rounded-lg text-xs text-violet-300">⏱ ' + q.time_limit + ' min</span>';
                h += '<span class="stat-badge px-2 py-1 rounded-lg text-xs text-violet-300">📝 ' + q.question_count + ' Q</span></div></div>';
                h += '<span class="btn-primary text-white px-4 py-2 rounded-xl text-sm font-semibold shrink-0">Open</span></div></a>';
            });
            $('#list').html(h);
        }, 'json');
    });
    </script>
</body>
</html>
