<?php
require_once 'classes/QuizManager.php';
$qm = new QuizManager();
$data = json_decode(file_get_contents('nikkk.json'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Decode Error: " . json_last_error_msg() . "\n";
    exit;
}

$success = $qm->uploadQuestions('quiz_3b477df00c97', $data);
if ($success) {
    echo "SUCCESS\n";
} else {
    echo "FAILED\n";
    foreach ($data as $i => $q) {
        if (!isset($q['id'], $q['question'], $q['options'])) {
            echo "Failed at index $i: missing id/question/options\n";
        }
        if (!isset($q['answer']) && isset($q['correct'])) {
            $q['answer'] = max(0, (int)$q['correct'] - 1);
            unset($q['correct']);
        }
        if (!isset($q['answer'])) {
            echo "Failed at index $i: missing answer\n";
        }
    }
}
