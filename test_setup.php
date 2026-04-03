<?php
/**
 * Full test setup: Creates a quiz, uploads questions, adds a participant.
 * Run: php test_setup.php
 */
require_once __DIR__ . '/classes/QuizManager.php';
require_once __DIR__ . '/classes/Participant.php';

$qm = new QuizManager();
$pm = new Participant();

// Create quiz
echo "=== Creating Quiz ===\n";
$quiz = $qm->createQuiz('Science Quiz', 2, 3, 'test123');
$quizId = $quiz['quiz_info']['id'];
echo "Created: {$quiz['quiz_info']['name']} (ID: $quizId)\n";

// Upload questions
echo "\n=== Uploading Questions ===\n";
$questions = json_decode(file_get_contents(__DIR__ . '/sample_questions.json'), true);
if ($qm->uploadQuestions($quizId, $questions)) {
    echo "Uploaded " . count($questions) . " questions.\n";
} else {
    echo "FAILED to upload questions.\n";
}

// Add participants (name only — ID is auto-generated)
echo "\n=== Adding Participants ===\n";
$participants = ['Alice Johnson', 'Bob Smith', 'Carol Davis'];
foreach ($participants as $name) {
    $result = $pm->addParticipant($quizId, $name);
    if ($result) {
        echo "  ✓ {$name}\n";
        echo "    ID:    {$result['id']}\n";
        echo "    PIN:   {$result['pin']}\n";
        echo "    Link:  http://localhost/nikkQuiz/take_quiz.php?uid={$result['token']}\n\n";
    } else {
        echo "  ✗ FAILED to add {$name}\n";
    }
}

// Verify data integrity
echo "=== Verifying Quiz Data ===\n";
$data = $qm->loadQuiz($quizId);
echo "Quiz name:     {$data['quiz_info']['name']}\n";
echo "Time limit:    {$data['quiz_info']['time_limit']} min\n";
echo "Display Q's:   {$data['quiz_info']['total_display_questions']}\n";
echo "Questions:     " . count($data['questions']) . "\n";
echo "Participants:  " . count($data['participants']) . "\n";

// Verify password hashing
echo "\n=== Security Check ===\n";
echo "Password hash stored: " . (str_starts_with($data['quiz_info']['admin_password'], '$2y$') ? 'YES (bcrypt)' : 'NO - ERROR') . "\n";
echo "Password verify test: " . ($qm->verifyAdminPassword($quizId, 'test123') ? 'PASS' : 'FAIL') . "\n";
echo "Wrong password test:  " . (!$qm->verifyAdminPassword($quizId, 'wrong') ? 'PASS (correctly rejected)' : 'FAIL') . "\n";

echo "\n=== All Tests Complete ===\n";
echo "Admin URL: http://localhost/nikkQuiz/quiz_details.php?id=$quizId\n";
echo "Admin Password: test123\n";
