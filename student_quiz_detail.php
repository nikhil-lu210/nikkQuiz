<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/bootstrap.php';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function option_letter(int $i): string
{
    return chr(65 + $i);
}

$quizId = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
if ($quizId === '') {
    header('Location: my_stats');
    exit;
}

$bid = (string) ($_SESSION['student_identity']['batch_id'] ?? '');
$pid = (string) ($_SESSION['student_identity']['participant_id'] ?? '');
if ($bid === '' || $pid === '') {
    $target = 'student_quiz_detail?id=' . rawurlencode($quizId);
    header('Location: my_stats?redirect=' . rawurlencode($target));
    exit;
}

$statsService = new StatsService(new QuizManager(), new BatchManager());
$detail = $statsService->getStudentQuizDetail($bid, $pid, $quizId);

if ($detail === null) {
    http_response_code(404);
    $pageTitle = 'Quiz not found';
    $notFound = true;
} else {
    $pageTitle = ($detail['quiz_name'] ?? 'Quiz') . ' — Your answers';
    $notFound = false;
}

$st = $notFound ? '' : (string) ($detail['attempt_status'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> — NikkQuiz</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/theme.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0f0f1a; }
        .glass-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(12px); }
        .gradient-text { background: linear-gradient(135deg, #a78bfa, #6d28d9, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .quiz-detail-opt-correct { background-color: rgba(34, 197, 94, 0.22) !important; border-color: #22c55e !important; }
        .quiz-detail-opt-wrong { background-color: rgba(239, 68, 68, 0.18) !important; border-color: #ef4444 !important; }
        .quiz-detail-opt-neutral { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.12); }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full" style="background: radial-gradient(circle, rgba(124,58,237,0.12), transparent 70%);"></div>
    </div>

    <div class="relative z-10 max-w-2xl mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="my_stats" class="text-sm text-gray-500 hover:text-gray-300">← My quiz stats</a>
        </div>

        <?php if ($notFound) : ?>
            <div class="glass-card rounded-2xl p-10 text-center">
                <h1 class="text-xl font-bold text-white mb-2">Quiz not found</h1>
                <p class="text-gray-400 text-sm mb-6">This quiz is not in your batch or you do not have access.</p>
                <a href="my_stats" class="text-violet-400 hover:text-violet-300 text-sm font-medium">Back to my stats</a>
            </div>
        <?php else : ?>
            <div class="mb-8">
                <p class="text-xs text-violet-400 uppercase tracking-wider mb-1">Your answers</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-white"><?php echo e((string) ($detail['quiz_name'] ?? 'Quiz')); ?></h1>
                <p class="text-gray-400 text-sm mt-2">
                    <?php
                    $parts = [];
                    if (!empty($detail['time_limit'])) {
                        $parts[] = 'Time limit: ' . (int) $detail['time_limit'] . ' min';
                    }
                    if ($st === 'finished') {
                        $parts[] = ((int) ($detail['marks'] ?? 0)) . '/' . ((int) ($detail['total'] ?? 0)) . ' correct';
                        $parts[] = ((int) ($detail['percentage'] ?? 0)) . '%';
                        $parts[] = trim(((string) ($detail['emoji'] ?? '')) . ' ' . ((string) ($detail['grade'] ?? '')));
                        if (!empty($detail['total_time'])) {
                            $parts[] = 'Duration: ' . (string) $detail['total_time'];
                        }
                    } elseif ($st === 'in_progress') {
                        $parts[] = 'In progress';
                    } else {
                        $parts[] = 'Not started yet — open the quiz link from your teacher to begin.';
                    }
                    echo e(implode(' · ', $parts));
                    ?>
                </p>
            </div>

            <div class="glass-card rounded-xl p-5 sm:p-6 space-y-8">
                <?php
                $questions = $detail['questions'] ?? [];
                if ($st === 'not_started') :
                    ?>
                    <p class="text-gray-400 text-center py-6">You have not started this quiz. Use the link your teacher shared.</p>
                <?php elseif (count($questions) === 0) : ?>
                    <p class="text-gray-400 text-center py-6">No question data to show.</p>
                <?php else : ?>
                    <?php foreach ($questions as $idx => $q) : ?>
                        <?php
                        $opts = is_array($q['options'] ?? null) ? $q['options'] : [];
                        $correctIdx = isset($q['correct_index']) ? (int) $q['correct_index'] : -1;
                        $selRaw = $q['selected_index'] ?? null;
                        $selIdx = $selRaw !== null && $selRaw !== '' ? (int) $selRaw : null;
                        $answered = !empty($q['answered']);
                        $isCorrect = !empty($q['is_correct']);
                        $pos = (int) ($q['position'] ?? ($idx + 1));
                        ?>
                        <div class="pb-8 border-b border-gray-800 last:border-0 last:pb-0">
                            <p class="text-xs text-violet-400 font-semibold mb-1">Question <?php echo $pos; ?></p>
                            <p class="text-white font-medium mb-3"><?php echo e((string) ($q['question'] ?? '')); ?></p>
                            <ul class="space-y-2">
                                <?php foreach ($opts as $oi => $opt) : ?>
                                    <?php
                                    $oi = (int) $oi;
                                    $rowCls = 'quiz-detail-opt-neutral rounded-lg border px-3 py-2';
                                    $aria = '';
                                    if ($correctIdx >= 0 && $oi === $correctIdx) {
                                        $rowCls = 'quiz-detail-opt-correct rounded-lg border px-3 py-2';
                                        $aria = ' aria-label="Correct answer"';
                                    } elseif ($answered && $selIdx !== null && $selIdx === $oi && $oi !== $correctIdx) {
                                        $rowCls = 'quiz-detail-opt-wrong rounded-lg border px-3 py-2';
                                        $aria = ' aria-label="Your answer"';
                                    }
                                    ?>
                                    <li class="<?php echo e($rowCls); ?>"<?php echo $aria; ?>>
                                        <span class="text-gray-500 mr-2"><?php echo e(option_letter($oi)); ?>.</span>
                                        <?php echo e(is_string($opt) ? $opt : (string) $opt); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($st === 'finished' && !$answered) : ?>
                                <p class="text-xs text-gray-500 mt-2">No answer recorded for this question.</p>
                            <?php endif; ?>
                            <?php if ($st === 'finished' && $answered) : ?>
                                <div class="mt-2">
                                    <?php if ($isCorrect) : ?>
                                        <span class="text-xs font-semibold text-emerald-400">Correct</span>
                                    <?php else : ?>
                                        <span class="text-xs font-semibold text-red-400">Wrong</span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($st === 'in_progress') : ?>
                                <div class="mt-2"><span class="text-xs text-amber-400">In progress</span></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
