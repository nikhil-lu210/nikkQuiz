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

    private function generateQuizId(): string
    {
        return 'quiz_' . bin2hex(random_bytes(6));
    }

    private function generatePublicSlug(): string
    {
        return bin2hex(random_bytes(8));
    }

    public function getQuizFilePath(string $quizId): string
    {
        return $this->dataDir . '/' . $quizId . '.json';
    }

    public function isSlugTaken(string $slug, ?string $exceptQuizId = null): bool
    {
        foreach (glob($this->dataDir . '/quiz_*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || empty($data['quiz_info']['public_slug'])) {
                continue;
            }
            if ($exceptQuizId !== null && ($data['quiz_info']['id'] ?? '') === $exceptQuizId) {
                continue;
            }
            if ($data['quiz_info']['public_slug'] === $slug) {
                return true;
            }
        }
        return false;
    }

    private function ensureUniqueSlug(): string
    {
        do {
            $slug = $this->generatePublicSlug();
        } while ($this->isSlugTaken($slug));
        return $slug;
    }

    public function migrateQuizData(array &$data): bool
    {
        $changed = false;
        if (!isset($data['attempts']) || !is_array($data['attempts'])) {
            $data['attempts'] = [];
            $changed = true;
        }
        $qi = &$data['quiz_info'];
        if (!isset($qi['status'])) {
            $qi['status'] = 'active';
            $changed = true;
        }
        if (!isset($qi['public_slug']) || $qi['public_slug'] === '') {
            $qi['public_slug'] = $this->ensureUniqueSlug();
            $changed = true;
        }
        if (!isset($data['participants'])) {
            $data['participants'] = [];
        }
        if (!isset($data['questions']) || !is_array($data['questions'])) {
            $data['questions'] = [];
            $changed = true;
        }
        foreach ($data['questions'] as &$q) {
            if (!isset($q['options']) || !is_array($q['options'])) {
                continue;
            }
            $q['options'] = array_values(array_map(static function ($o) {
                if (is_array($o) || is_object($o)) {
                    return json_encode($o, JSON_UNESCAPED_UNICODE);
                }

                return (string) $o;
            }, $q['options']));
            $n = count($q['options']);
            if ($n < 1) {
                continue;
            }
            $a = isset($q['answer']) ? (int) $q['answer'] : null;
            if ($a !== null && $a >= 0 && $a < $n) {
                continue;
            }
            if (isset($q['correct'])) {
                $c = (int) $q['correct'];
                if ($c >= 1 && $c <= $n) {
                    $q['answer'] = $c - 1;
                    $changed = true;
                } elseif ($c >= 0 && $c < $n) {
                    $q['answer'] = $c;
                    $changed = true;
                }
            }
        }
        unset($q);

        return $changed;
    }

    /**
     * Create quiz under a batch with questions inline (no per-quiz admin password).
     */
    public function createQuizForBatch(string $batchId, string $name, int $timeLimit, int $totalDisplay, array $questions): array
    {
        $quizId = $this->generateQuizId();
        $data = [
            'quiz_info' => [
                'id' => $quizId,
                'batch_id' => $batchId,
                'name' => trim($name),
                'time_limit' => $timeLimit,
                'total_display_questions' => $totalDisplay,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'active',
                'public_slug' => $this->ensureUniqueSlug(),
            ],
            'questions' => $questions,
            'attempts' => [],
        ];
        $this->saveQuiz($quizId, $data);
        return $data;
    }

    public function saveQuiz(string $quizId, array $data): bool
    {
        $path = $this->getQuizFilePath($quizId);
        return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }

    public function loadQuiz(string $quizId, bool $migrate = true): ?array
    {
        $path = $this->getQuizFilePath($quizId);
        if (!file_exists($path)) {
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        if ($migrate && $data !== null && $this->migrateQuizData($data)) {
            $this->saveQuiz($quizId, $data);
        }
        return $data;
    }

    public function loadQuizBySlug(string $publicSlug): ?array
    {
        foreach (glob($this->dataDir . '/quiz_*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data) {
                continue;
            }
            $changed = $this->migrateQuizData($data);
            if ($changed) {
                $this->saveQuiz($data['quiz_info']['id'], $data);
            }
            if (($data['quiz_info']['public_slug'] ?? '') === $publicSlug) {
                return $data;
            }
        }
        return null;
    }

    public function listQuizzesForBatch(string $batchId): array
    {
        $out = [];
        foreach (glob($this->dataDir . '/quiz_*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || !isset($data['quiz_info'])) {
                continue;
            }
            $this->migrateQuizData($data);
            $id = $data['quiz_info']['id'];
            $this->saveQuiz($id, $data);

            if (($data['quiz_info']['batch_id'] ?? '') !== $batchId) {
                continue;
            }

            $attempts = $data['attempts'] ?? [];
            $finished = count(array_filter($attempts, fn($a) => ($a['status'] ?? '') === 'finished'));

            $out[] = [
                'id' => $data['quiz_info']['id'],
                'name' => $data['quiz_info']['name'],
                'time_limit' => $data['quiz_info']['time_limit'],
                'total_display_questions' => $data['quiz_info']['total_display_questions'],
                'created_at' => $data['quiz_info']['created_at'],
                'status' => $data['quiz_info']['status'] ?? 'active',
                'public_slug' => $data['quiz_info']['public_slug'] ?? '',
                'question_count' => count($data['questions'] ?? []),
                'finished_count' => $finished,
            ];
        }
        usort($out, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return $out;
    }

    /** Active quizzes across all batches (for student catalog). */
    public function listActiveQuizzesWithBatchNames(): array
    {
        require_once __DIR__ . '/BatchManager.php';
        $bm = new BatchManager();
        $nameCache = [];
        $out = [];

        foreach (glob($this->dataDir . '/quiz_*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || !isset($data['quiz_info'])) {
                continue;
            }
            $this->migrateQuizData($data);
            $this->saveQuiz($data['quiz_info']['id'], $data);

            if (($data['quiz_info']['status'] ?? '') !== 'active') {
                continue;
            }
            $bid = $data['quiz_info']['batch_id'] ?? '';
            if ($bid === '') {
                continue;
            }

            if (!array_key_exists($bid, $nameCache)) {
                $b = $bm->loadBatch($bid);
                $nameCache[$bid] = $b['batch_info']['name'] ?? '';
            }

            $out[] = [
                'id' => $data['quiz_info']['id'],
                'name' => $data['quiz_info']['name'],
                'time_limit' => $data['quiz_info']['time_limit'],
                'public_slug' => $data['quiz_info']['public_slug'] ?? '',
                'question_count' => count($data['questions'] ?? []),
                'batch_name' => $nameCache[$bid],
            ];
        }
        usort($out, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $out;
    }

    public function setQuizStatus(string $quizId, string $status): bool
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return false;
        }
        $data = $this->loadQuiz($quizId);
        if (!$data) {
            return false;
        }
        $data['quiz_info']['status'] = $status;
        return $this->saveQuiz($quizId, $data);
    }

    public function deleteQuiz(string $quizId): bool
    {
        $path = $this->getQuizFilePath($quizId);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    public function deleteAllQuizzesForBatch(string $batchId): void
    {
        foreach (glob($this->dataDir . '/quiz_*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && ($data['quiz_info']['batch_id'] ?? '') === $batchId) {
                unlink($file);
            }
        }
    }

    public function validateAndNormalizeQuestions(array $questions): ?array
    {
        $out = [];
        foreach ($questions as $q) {
            if (!isset($q['id'], $q['question'], $q['options'])) {
                return null;
            }
            if (!is_array($q['options'])) {
                return null;
            }
            $opts = array_values(array_map(static function ($o) {
                if (is_array($o) || is_object($o)) {
                    return json_encode($o, JSON_UNESCAPED_UNICODE);
                }

                return (string) $o;
            }, $q['options']));
            $optCount = count($opts);
            if ($optCount < 2) {
                return null;
            }

            if (array_key_exists('answer', $q)) {
                $ans = (int) $q['answer'];
            } elseif (isset($q['correct'])) {
                $c = (int) $q['correct'];
                // Prefer 1-based (1 = first choice) when value is in 1..N; otherwise allow 0-based (0..N-1).
                if ($c >= 1 && $c <= $optCount) {
                    $ans = $c - 1;
                } elseif ($c >= 0 && $c < $optCount) {
                    $ans = $c;
                } else {
                    return null;
                }
            } else {
                return null;
            }

            if ($ans < 0 || $ans >= $optCount) {
                return null;
            }

            $q['options'] = $opts;
            $q['answer'] = $ans;
            $out[] = $q;
        }

        return $out;
    }
}
