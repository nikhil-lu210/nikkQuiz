<?php
/**
 * Dev smoke test: batch + participants + quiz with questions.
 * Run: php test_setup.php
 */
require_once __DIR__ . '/classes/QuizManager.php';
require_once __DIR__ . '/classes/BatchManager.php';
require_once __DIR__ . '/classes/Participant.php';

$qm = new QuizManager();
$bm = new BatchManager();
$pm = new Participant();

echo "=== Batch ===\n";
$batch = $bm->createBatch('Demo batch', 'Demo Teacher', 'teacher123');
$bid = $batch['batch_info']['id'];
echo "Created $bid\n";

echo "=== Participants ===\n";
foreach (['Alice', 'Bob'] as $n) {
    $p = $pm->addParticipant($bid, $n);
    echo "$n → PIN {$p['pin']}\n";
}

echo "=== Quiz ===\n";
$qs = json_decode(file_get_contents(__DIR__ . '/sample_questions.json'), true);
$norm = $qm->validateAndNormalizeQuestions($qs);
$quiz = $qm->createQuizForBatch($bid, 'Sample quiz', 30, 5, $norm);
$slug = $quiz['quiz_info']['public_slug'];
echo "Quiz {$quiz['quiz_info']['id']} slug=$slug\n";

echo "\nBatch URL:  http://localhost/nikkQuiz/batch.php?id=$bid\n";
echo "Take quiz:  http://localhost/nikkQuiz/take_quiz.php?q=$slug\n";
echo "Password:   teacher123\n";
