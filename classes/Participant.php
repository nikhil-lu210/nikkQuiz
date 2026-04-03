<?php

require_once __DIR__ . '/QuizManager.php';

class Participant
{
    private QuizManager $quizManager;

    public function __construct()
    {
        $this->quizManager = new QuizManager();
    }

    /**
     * Generate a unique token for participant URL
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a random 6-digit PIN
     */
    private function generatePin(): string
    {
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a short unique participant ID (e.g., STU-A3F7K)
     */
    private function generateParticipantId(array $existingParticipants): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No ambiguous chars (0/O, 1/I)
        do {
            $id = 'STU-';
            for ($i = 0; $i < 5; $i++) {
                $id .= $chars[random_int(0, strlen($chars) - 1)];
            }
            // Ensure uniqueness
            $exists = false;
            foreach ($existingParticipants as $p) {
                if ($p['id'] === $id) {
                    $exists = true;
                    break;
                }
            }
        } while ($exists);

        return $id;
    }

    /**
     * Add a participant to a quiz
     */
    public function addParticipant(string $quizId, string $name): ?array
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data) return null;

        $participantId = $this->generateParticipantId($data['participants']);

        $token = $this->generateToken();
        $pin = $this->generatePin();

        $participant = [
            'id' => $participantId,
            'name' => $name,
            'token' => $token,
            'pin' => $pin,
            'marks' => 0,
            'start_time' => null,
            'end_time' => null,
            'status' => 'pending',
            'assigned_questions' => [],
            'answers' => [],
        ];

        $data['participants'][] = $participant;
        $this->quizManager->saveQuiz($quizId, $data);

        return $participant;
    }

    /**
     * Find participant by token across all quizzes
     */
    public function findByToken(string $token): ?array
    {
        $dataDir = __DIR__ . '/../data';
        $files = glob($dataDir . '/quiz_*.json');

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data) continue;

            foreach ($data['participants'] as $p) {
                if ($p['token'] === $token) {
                    return [
                        'quiz_id' => $data['quiz_info']['id'],
                        'quiz_data' => $data,
                        'participant' => $p,
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Verify participant PIN
     */
    public function verifyPin(string $token, string $pin): ?array
    {
        $result = $this->findByToken($token);
        if (!$result) return null;

        if ($result['participant']['pin'] !== $pin) {
            return null;
        }

        return $result;
    }

    /**
     * Start the quiz for a participant
     */
    public function startQuiz(string $quizId, string $token): ?array
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data) return null;

        foreach ($data['participants'] as &$p) {
            if ($p['token'] === $token) {
                if ($p['status'] === 'finished') {
                    return null; // Already finished
                }

                if ($p['status'] === 'pending') {
                    $p['status'] = 'running';
                    $p['start_time'] = date('Y-m-d H:i:s');

                    // Assign random questions
                    $totalQuestions = count($data['questions']);
                    $displayCount = min($data['quiz_info']['total_display_questions'], $totalQuestions);

                    if ($totalQuestions > 0 && $displayCount > 0) {
                        $indices = array_rand($data['questions'], $displayCount);
                        if (!is_array($indices)) $indices = [$indices];
                        shuffle($indices); // Randomize order too
                        $p['assigned_questions'] = array_values($indices);
                    }
                }

                $this->quizManager->saveQuiz($quizId, $data);

                // Build question set for the participant
                $questions = [];
                foreach ($p['assigned_questions'] as $idx) {
                    if (isset($data['questions'][$idx])) {
                        $q = $data['questions'][$idx];
                        $questions[] = [
                            'id' => $q['id'],
                            'question' => $q['question'],
                            'options' => $q['options'],
                            'index' => $idx,
                        ];
                    }
                }

                $startTime = strtotime($p['start_time']);
                $endTime = $startTime + ($data['quiz_info']['time_limit'] * 60);
                $remainingSeconds = max(0, $endTime - time());

                return [
                    'questions' => $questions,
                    'time_limit' => $data['quiz_info']['time_limit'],
                    'remaining_seconds' => $remainingSeconds,
                    'quiz_name' => $data['quiz_info']['name'],
                    'participant_name' => $p['name'],
                    'status' => $p['status'],
                ];
            }
        }
        return null;
    }

    /**
     * Submit quiz answers
     */
    public function submitQuiz(string $quizId, string $token, array $answers): ?array
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data) return null;

        foreach ($data['participants'] as &$p) {
            if ($p['token'] === $token) {
                if ($p['status'] === 'finished') {
                    // Return existing results
                    return $this->getResults($p, $data);
                }

                $p['status'] = 'finished';
                $p['end_time'] = date('Y-m-d H:i:s');
                $p['answers'] = $answers;

                // Calculate score
                $correct = 0;
                $total = count($p['assigned_questions']);

                foreach ($answers as $ans) {
                    $qIndex = (int)$ans['question_index'];
                    $selectedOption = (int)$ans['selected'];

                    if (isset($data['questions'][$qIndex])) {
                        if ($data['questions'][$qIndex]['answer'] === $selectedOption) {
                            $correct++;
                        }
                    }
                }

                $p['marks'] = $correct;
                $this->quizManager->saveQuiz($quizId, $data);

                return $this->getResults($p, $data);
            }
        }
        return null;
    }

    /**
     * Get formatted results
     */
    private function getResults(array $participant, array $quizData): array
    {
        $total = count($participant['assigned_questions']);
        $marks = $participant['marks'];
        $percentage = $total > 0 ? round(($marks / $total) * 100) : 0;

        if ($percentage >= 80) {
            $grade = 'Excellent';
            $emoji = '🤩';
        } elseif ($percentage >= 60) {
            $grade = 'Good';
            $emoji = '🙂';
        } elseif ($percentage >= 40) {
            $grade = 'Average';
            $emoji = '😐';
        } elseif ($percentage >= 20) {
            $grade = 'Poor';
            $emoji = '☹️';
        } else {
            $grade = 'Very Poor';
            $emoji = '💀';
        }

        return [
            'name' => $participant['name'],
            'id' => $participant['id'],
            'marks' => $marks,
            'total' => $total,
            'percentage' => $percentage,
            'grade' => $grade,
            'emoji' => $emoji,
            'quiz_name' => $quizData['quiz_info']['name'],
            'start_time' => $participant['start_time'],
            'end_time' => $participant['end_time'],
        ];
    }

    /**
     * Remove a participant from a quiz
     */
    public function removeParticipant(string $quizId, string $token): bool
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data) return false;

        $data['participants'] = array_values(array_filter(
            $data['participants'],
            fn($p) => $p['token'] !== $token
        ));

        return $this->quizManager->saveQuiz($quizId, $data);
    }

    /**
     * Check if time has expired for a running participant
     */
    public function checkTimeExpired(string $quizId, string $token): bool
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data) return true;

        foreach ($data['participants'] as $p) {
            if ($p['token'] === $token && $p['status'] === 'running') {
                $startTime = strtotime($p['start_time']);
                $endTime = $startTime + ($data['quiz_info']['time_limit'] * 60);
                return time() > $endTime;
            }
        }
        return true;
    }
}
