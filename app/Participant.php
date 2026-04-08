<?php

declare(strict_types=1);

class Participant
{
    private QuizManager $quizManager;
    private BatchManager $batchManager;
    private \PDO $pdo;

    public function __construct()
    {
        $this->quizManager = new QuizManager();
        $this->batchManager = new BatchManager();
        $this->pdo = Database::pdo();
    }

    private function generateUniquePin(): string
    {
        do {
            $pin = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while ($this->batchManager->isPinTaken($pin));

        return $pin;
    }

    private function generateParticipantId(string $batchId): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $stmt = $this->pdo->prepare('SELECT 1 FROM participants WHERE batch_id = ? AND id = ? LIMIT 1');
        do {
            $id = 'STU-';
            for ($i = 0; $i < 5; $i++) {
                $id .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $stmt->execute([$batchId, $id]);
            $exists = (bool) $stmt->fetchColumn();
        } while ($exists);

        return $id;
    }

    public function addParticipant(string $batchId, string $name): ?array
    {
        $data = $this->batchManager->loadBatch($batchId);
        if (!$data) {
            return null;
        }

        $participantId = $this->generateParticipantId($batchId);
        $pin = $this->generateUniquePin();

        $participant = [
            'id' => $participantId,
            'name' => trim($name),
            'pin' => $pin,
        ];

        $data['participants'][] = $participant;

        $this->batchManager->saveBatch($batchId, $data);

        return $participant;
    }

    public function removeParticipant(string $batchId, string $participantId): bool
    {
        $data = $this->batchManager->loadBatch($batchId);
        if (!$data) {
            return false;
        }
        $data['participants'] = array_values(array_filter(
            $data['participants'],
            fn($p) => ($p['id'] ?? '') !== $participantId
        ));

        return $this->batchManager->saveBatch($batchId, $data);
    }

    public function updateParticipantName(string $batchId, string $participantId, string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }
        $data = $this->batchManager->loadBatch($batchId);
        if (!$data) {
            return false;
        }
        $found = false;
        foreach ($data['participants'] as &$p) {
            if (($p['id'] ?? '') === $participantId) {
                $p['name'] = $name;
                $found = true;
                break;
            }
        }
        unset($p);

        return $found && $this->batchManager->saveBatch($batchId, $data);
    }

    public function findParticipantByPin(string $pin): ?array
    {
        if (!preg_match('/^\d{6}$/', $pin)) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.pin, p.batch_id, b.name AS batch_name
             FROM participants p
             INNER JOIN batches b ON b.id = p.batch_id
             WHERE p.pin = ? LIMIT 1'
        );
        $stmt->execute([$pin]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'batch_id' => $row['batch_id'],
            'batch_name' => $row['batch_name'],
            'participant' => [
                'id' => $row['id'],
                'name' => $row['name'],
                'pin' => $row['pin'],
            ],
        ];
    }

    private function findAttemptIndex(array $attempts, string $batchId, string $participantId): ?int
    {
        foreach ($attempts as $i => $a) {
            if (($a['batch_id'] ?? '') === $batchId && ($a['participant_id'] ?? '') === $participantId) {
                return $i;
            }
        }

        return null;
    }

    public function startQuiz(string $quizId, string $batchId, string $participantId): ?array
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data) {
            return null;
        }

        if (($data['quiz_info']['batch_id'] ?? '') !== $batchId) {
            return null;
        }

        if (($data['quiz_info']['status'] ?? '') !== 'active') {
            return null;
        }

        $batch = $this->batchManager->loadBatch($batchId);
        if (!$batch) {
            return null;
        }

        $participantRow = null;
        foreach ($batch['participants'] as $p) {
            if (($p['id'] ?? '') === $participantId) {
                $participantRow = $p;
                break;
            }
        }
        if (!$participantRow) {
            return null;
        }

        if (!isset($data['attempts'])) {
            $data['attempts'] = [];
        }

        $idx = $this->findAttemptIndex($data['attempts'], $batchId, $participantId);

        if ($idx !== null) {
            $att = &$data['attempts'][$idx];
            if (($att['status'] ?? '') === 'finished') {
                return null;
            }
            if (($att['status'] ?? '') === 'running') {
                $this->quizManager->saveQuiz($quizId, $data);

                return $this->buildStartResponse($data, $att, $participantRow['name']);
            }
        }

        $totalQuestions = count($data['questions']);
        $displayCount = min($data['quiz_info']['total_display_questions'], $totalQuestions);

        $assigned = [];
        if ($totalQuestions > 0 && $displayCount > 0) {
            $indices = array_rand($data['questions'], $displayCount);
            if (!is_array($indices)) {
                $indices = [$indices];
            }
            shuffle($indices);
            $assigned = array_values($indices);
        }

        $attempt = [
            'batch_id' => $batchId,
            'participant_id' => $participantId,
            'participant_name' => $participantRow['name'],
            'status' => 'running',
            'assigned_questions' => $assigned,
            'answers' => [],
            'marks' => 0,
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => null,
        ];

        if ($idx !== null) {
            $data['attempts'][$idx] = $attempt;
        } else {
            $data['attempts'][] = $attempt;
        }

        $this->quizManager->saveQuiz($quizId, $data);

        return $this->buildStartResponse($data, $attempt, $participantRow['name']);
    }

    private function buildStartResponse(array $quizData, array $attempt, string $participantName): array
    {
        $questions = [];
        foreach ($attempt['assigned_questions'] as $idx) {
            if (isset($quizData['questions'][$idx])) {
                $q = $quizData['questions'][$idx];
                $questions[] = [
                    'id' => $q['id'],
                    'question' => $q['question'],
                    'options' => $q['options'],
                    'index' => $idx,
                ];
            }
        }

        $startTime = strtotime($attempt['start_time']);
        $endTime = $startTime + ($quizData['quiz_info']['time_limit'] * 60);
        $remainingSeconds = max(0, $endTime - time());

        return [
            'questions' => $questions,
            'time_limit' => $quizData['quiz_info']['time_limit'],
            'remaining_seconds' => $remainingSeconds,
            'quiz_name' => $quizData['quiz_info']['name'],
            'participant_name' => $participantName,
            'status' => $attempt['status'],
        ];
    }

    public function submitQuiz(string $quizId, string $batchId, string $participantId, array $answers): ?array
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data || empty($data['attempts'])) {
            return null;
        }

        $idx = $this->findAttemptIndex($data['attempts'], $batchId, $participantId);
        if ($idx === null) {
            return null;
        }

        $att = &$data['attempts'][$idx];

        if (($att['status'] ?? '') === 'finished') {
            return $this->getResults($att, $data);
        }

        $att['status'] = 'finished';
        $att['end_time'] = date('Y-m-d H:i:s');
        $att['answers'] = $answers;

        $correct = 0;
        $total = count($att['assigned_questions']);

        foreach ($answers as $ans) {
            $qIndex = (int) $ans['question_index'];
            $selectedOption = (int) $ans['selected'];
            if (isset($data['questions'][$qIndex])) {
                if ($data['questions'][$qIndex]['answer'] === $selectedOption) {
                    $correct++;
                }
            }
        }

        $att['marks'] = $correct;
        $this->quizManager->saveQuiz($quizId, $data);

        return $this->getResults($att, $data);
    }

    private function getResults(array $attempt, array $quizData): array
    {
        $total = count($attempt['assigned_questions']);
        $marks = (int) ($attempt['marks'] ?? 0);
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
            'name' => $attempt['participant_name'] ?? '',
            'id' => $attempt['participant_id'] ?? '',
            'marks' => $marks,
            'total' => $total,
            'percentage' => $percentage,
            'grade' => $grade,
            'emoji' => $emoji,
            'quiz_name' => $quizData['quiz_info']['name'],
            'start_time' => $attempt['start_time'] ?? null,
            'end_time' => $attempt['end_time'] ?? null,
        ];
    }

    public function checkTimeExpired(string $quizId, string $batchId, string $participantId): bool
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data || empty($data['attempts'])) {
            return true;
        }
        $idx = $this->findAttemptIndex($data['attempts'], $batchId, $participantId);
        if ($idx === null) {
            return true;
        }
        $att = $data['attempts'][$idx];
        if (($att['status'] ?? '') !== 'running') {
            return false;
        }
        $startTime = strtotime($att['start_time']);
        $endTime = $startTime + ($data['quiz_info']['time_limit'] * 60);

        return time() > $endTime;
    }

    /**
     * PIN must belong to a participant in the same batch as the quiz.
     */
    public function verifyPinForQuiz(string $pin, string $publicSlug): array
    {
        $publicSlug = trim($publicSlug);
        if ($publicSlug === '') {
            return ['ok' => false, 'reason' => 'invalid_slug', 'message' => 'Invalid quiz link.'];
        }

        $quizData = $this->quizManager->loadQuizBySlug($publicSlug);
        if (!$quizData) {
            return ['ok' => false, 'reason' => 'invalid_slug', 'message' => 'Invalid quiz link.'];
        }

        $quizBatchId = $quizData['quiz_info']['batch_id'] ?? '';
        if ($quizBatchId === '') {
            return ['ok' => false, 'reason' => 'invalid_slug', 'message' => 'This quiz is not configured.'];
        }

        if (($quizData['quiz_info']['status'] ?? '') !== 'active') {
            return ['ok' => false, 'reason' => 'inactive', 'message' => 'This quiz is not accepting participants right now.'];
        }

        $found = $this->findParticipantByPin($pin);
        if (!$found) {
            return ['ok' => false, 'reason' => 'invalid_pin', 'message' => 'Invalid PIN. Please try again.'];
        }

        if ($found['batch_id'] !== $quizBatchId) {
            return ['ok' => false, 'reason' => 'wrong_batch', 'message' => 'This PIN is not registered for this class batch.'];
        }

        $quizId = $quizData['quiz_info']['id'];
        $batchId = $found['batch_id'];
        $participantId = $found['participant']['id'];

        if (!isset($quizData['attempts'])) {
            $quizData['attempts'] = [];
        }

        $idx = $this->findAttemptIndex($quizData['attempts'], $batchId, $participantId);
        if ($idx !== null && ($quizData['attempts'][$idx]['status'] ?? '') === 'finished') {
            return [
                'ok' => false,
                'reason' => 'finished',
                'message' => 'You have already completed this quiz.',
                'batch_id' => $batchId,
                'participant_id' => $participantId,
                'participant_name' => $found['participant']['name'] ?? '',
            ];
        }

        return [
            'ok' => true,
            'quiz_id' => $quizId,
            'batch_id' => $batchId,
            'participant_id' => $participantId,
            'participant_name' => $found['participant']['name'],
        ];
    }
}
