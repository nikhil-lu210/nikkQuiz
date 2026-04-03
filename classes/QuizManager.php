<?php

class QuizManager
{
    private string $dataDir;

    public function __construct()
    {
        $this->dataDir = __DIR__ . '/../data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    /**
     * Generate a unique quiz ID
     */
    private function generateQuizId(): string
    {
        return 'quiz_' . bin2hex(random_bytes(6));
    }

    /**
     * Get the file path for a quiz
     */
    private function getQuizFilePath(string $quizId): string
    {
        return $this->dataDir . '/' . $quizId . '.json';
    }

    /**
     * Create a new quiz
     */
    public function createQuiz(string $name, int $timeLimit, int $totalDisplay, string $adminPassword): array
    {
        $quizId = $this->generateQuizId();
        $data = [
            'quiz_info' => [
                'id' => $quizId,
                'name' => $name,
                'time_limit' => $timeLimit,
                'total_display_questions' => $totalDisplay,
                'admin_password' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ],
            'questions' => [],
            'participants' => [],
        ];

        $this->saveQuiz($quizId, $data);

        return $data;
    }

    /**
     * Save quiz data to JSON file
     */
    public function saveQuiz(string $quizId, array $data): bool
    {
        $filePath = $this->getQuizFilePath($quizId);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($filePath, $json, LOCK_EX) !== false;
    }

    /**
     * Load quiz data from JSON file
     */
    public function loadQuiz(string $quizId): ?array
    {
        $filePath = $this->getQuizFilePath($quizId);
        if (!file_exists($filePath)) {
            return null;
        }
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    }

    /**
     * List all quizzes
     */
    public function listQuizzes(): array
    {
        $quizzes = [];
        $files = glob($this->dataDir . '/quiz_*.json');
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['quiz_info'])) {
                $quizzes[] = [
                    'id' => $data['quiz_info']['id'],
                    'name' => $data['quiz_info']['name'],
                    'time_limit' => $data['quiz_info']['time_limit'],
                    'total_display_questions' => $data['quiz_info']['total_display_questions'],
                    'created_at' => $data['quiz_info']['created_at'],
                    'question_count' => count($data['questions']),
                    'participant_count' => count($data['participants']),
                ];
            }
        }
        // Sort by created_at desc
        usort($quizzes, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return $quizzes;
    }

    /**
     * Verify admin password for a quiz
     */
    public function verifyAdminPassword(string $quizId, string $password): bool
    {
        $data = $this->loadQuiz($quizId);
        if (!$data) return false;
        return password_verify($password, $data['quiz_info']['admin_password']);
    }

    /**
     * Upload questions to a quiz
     */
    public function uploadQuestions(string $quizId, array $questions): bool
    {
        $data = $this->loadQuiz($quizId);
        if (!$data) return false;

        // Validate question format
        foreach ($questions as &$q) {
            if (!isset($q['id'], $q['question'], $q['options'])) {
                return false;
            }
            
            // Handle alternatives for the answer key (e.g., 'correct' uses 1-based index)
            if (!isset($q['answer']) && isset($q['correct'])) {
                $q['answer'] = max(0, (int)$q['correct'] - 1);
                unset($q['correct']);
            }
            
            if (!isset($q['answer'])) {
                return false;
            }
            $q['answer'] = (int)$q['answer'];
        }

        $data['questions'] = $questions;
        return $this->saveQuiz($quizId, $data);
    }

    /**
     * Delete a quiz
     */
    public function deleteQuiz(string $quizId): bool
    {
        $filePath = $this->getQuizFilePath($quizId);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}
