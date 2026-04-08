<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/bootstrap.php';

if (SiteAuth::isConfigured() && SiteAuth::isAuthenticated()) {
    $r = isset($_GET['redirect']) ? rawurldecode((string) $_GET['redirect']) : '';
    header('Location: ' . SiteAuth::safeRedirectFromQuery($r));
    exit;
}

$redirectField = SiteAuth::safeRedirectFromQuery(isset($_GET['redirect']) ? rawurldecode((string) $_GET['redirect']) : '');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && SiteAuth::isConfigured()) {
    $pw = $_POST['password'] ?? '';
    $redir = $_POST['redirect'] ?? '';
    if (is_string($redir) && $redir !== '') {
        $redirectField = SiteAuth::safeRedirectFromQuery($redir);
    }
    if (!is_string($pw) || $pw === '' || !SiteAuth::verifyPassword($pw)) {
        $error = 'Invalid password.';
    } else {
        SiteAuth::login();
        header('Location: ' . $redirectField);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site login — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa, #6d28d9); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn-primary { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
        .input-field { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
        .input-field:focus { border-color: #7c3aed; outline: none; box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }
    </style>
</head>
<body class="min-h-screen text-white flex flex-col items-center justify-center px-4">
    <div class="w-full max-w-md">
        <h1 class="text-3xl font-extrabold text-center mb-2"><span class="gradient-text">NikkQuiz</span></h1>
        <p class="text-gray-400 text-sm text-center mb-8">Owner login — teacher and admin pages only. Students use quiz links and PINs; they do not need this password.</p>

        <?php if (!SiteAuth::isConfigured()) : ?>
            <div class="glass-card rounded-2xl p-6 text-gray-300 text-sm leading-relaxed">
                <p class="font-semibold text-white mb-2">Configure the site password first</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-400">
                    <li>Copy <code class="text-violet-300">config.local.php.example</code> to <code class="text-violet-300">config.local.php</code>.</li>
                    <li>Set <code class="text-violet-300">site_password</code> (or <code class="text-violet-300">site_password_hash</code>) in that file.</li>
                    <li>Reload this page. Never commit <code class="text-violet-300">config.local.php</code>.</li>
                </ol>
                <p class="mt-4"><a href="quizzes.php" class="text-violet-400 hover:text-violet-300">Student: active quizzes</a></p>
            </div>
        <?php else : ?>
            <div class="glass-card rounded-2xl p-8">
                <?php if ($error !== '') : ?>
                    <p class="text-red-400 text-sm mb-4"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <form method="post" action="login.php" class="space-y-4">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectField, ENT_QUOTES, 'UTF-8'); ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Site password</label>
                        <input type="password" name="password" required autocomplete="current-password" class="input-field w-full px-4 py-3 rounded-xl text-white text-sm" placeholder="Your owner password">
                    </div>
                    <button type="submit" class="btn-primary w-full text-white py-3 rounded-xl font-semibold text-sm">Sign in</button>
                </form>
                <p class="mt-6 text-center"><a href="quizzes.php" class="text-violet-400 hover:text-violet-300 text-sm">Student: active quizzes</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
