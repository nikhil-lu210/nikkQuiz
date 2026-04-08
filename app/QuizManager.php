<?php

declare(strict_types=1);

class QuizManager
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    private function generateQuizId(): string
    {
        return 'quiz_' . bin2hex(random_bytes(6));
    }

    private function generatePublicSlug(): string
    {
        return bin2hex(random_bytes(8));
    }

    public function isSlugTaken(string $slug, ?string $exceptQuizId = null): bool
    {
        if ($exceptQuizId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM quizzes WHERE public_slug = ? AND id != ? LIMIT 1'
            );
            $stmt->execute([$slug, $exceptQuizId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM quizzes WHERE public_slug = ? LIMIT 1');
            $stmt->execute([$slug]);
        }

        return (bool) $stmt->fetchColumn();
    }

    private function ensureUniqueSlug(): string
    {
        do {
            $slug = $this->generatePublicSlug();
        } while ($this->isSlugTaken($slug));

        return $slug;
    }

    /**
     * @param array<string, mixed> $data
     */
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

    public function createQuizForBatch(string $batchId, string $name, int $timeLimit, int $totalDisplay, array $questions): array
    {
        $quizId = $this->generateQuizId();
        $slug = $this->ensureUniqueSlug();
        $created = date('Y-m-d H:i:s');
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO quizzes (id, batch_id, name, time_limit, total_display_questions, public_slug, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $quizId,
                $batchId,
                trim($name),
                $timeLimit,
                $totalDisplay,
                $slug,
                'active',
                $created,
            ]);
            $this->insertQuestions($quizId, $questions);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return [
            'quiz_info' => [
                'id' => $quizId,
                'batch_id' => $batchId,
                'name' => trim($name),
                'time_limit' => $timeLimit,
                'total_display_questions' => $totalDisplay,
                'created_at' => $created,
                'status' => 'active',
                'public_slug' => $slug,
            ],
            'questions' => $questions,
            'attempts' => [],
        ];
    }

    /**
     * @param list<array<string, mixed>> $questions
     */
    private function insertQuestions(string $quizId, array $questions): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO questions (quiz_id, pool_index, q_ref_id, question_text, options_json, answer_index)
             VALUES (?,?,?,?,?,?)'
        );
        foreach (array_values($questions) as $poolIndex => $q) {
            $opts = $q['options'] ?? [];
            $stmt->execute([
                $quizId,
                $poolIndex,
                isset($q['id']) ? (int) $q['id'] : null,
                (string) ($q['question'] ?? ''),
                json_encode($opts, JSON_UNESCAPED_UNICODE),
                (int) ($q['answer'] ?? 0),
            ]);
        }
    }

    public function saveQuiz(string $quizId, array $data): bool
    {
        try {
            $this->pdo->beginTransaction();
            $qi = $data['quiz_info'] ?? [];
            $stmt = $this->pdo->prepare(
                'UPDATE quizzes SET name = ?, time_limit = ?, total_display_questions = ?, public_slug = ?, status = ? WHERE id = ?'
            );
            $stmt->execute([
                $qi['name'] ?? '',
                (int) ($qi['time_limit'] ?? 0),
                (int) ($qi['total_display_questions'] ?? 0),
                $qi['public_slug'] ?? '',
                $qi['status'] ?? 'active',
                $quizId,
            ]);
            $this->pdo->prepare('DELETE FROM questions WHERE quiz_id = ?')->execute([$quizId]);
            $this->pdo->prepare('DELETE FROM attempts WHERE quiz_id = ?')->execute([$quizId]);
            foreach (array_values($data['questions'] ?? []) as $poolIndex => $q) {
                $this->pdo->prepare(
                    'INSERT INTO questions (quiz_id, pool_index, q_ref_id, question_text, options_json, answer_index)
                     VALUES (?,?,?,?,?,?)'
                )->execute([
                    $quizId,
                    $poolIndex,
                    isset($q['id']) ? (int) $q['id'] : null,
                    (string) ($q['question'] ?? ''),
                    json_encode($q['options'] ?? [], JSON_UNESCAPED_UNICODE),
                    (int) ($q['answer'] ?? 0),
                ]);
            }
            foreach ($data['attempts'] ?? [] as $att) {
                $this->insertAttemptRow($quizId, $att);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $att
     */
    private function insertAttemptRow(string $quizId, array $att): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attempts (quiz_id, batch_id, participant_id, participant_name, status, start_time, end_time, marks)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $quizId,
            $att['batch_id'] ?? '',
            $att['participant_id'] ?? '',
            $att['participant_name'] ?? '',
            $att['status'] ?? 'running',
            $att['start_time'] ?? null,
            $att['end_time'] ?? null,
            (int) ($att['marks'] ?? 0),
        ]);
        $attemptId = (int) $this->pdo->lastInsertId();
        $sort = 0;
        foreach ($att['assigned_questions'] ?? [] as $poolIndex) {
            $this->pdo->prepare(
                'INSERT INTO attempt_assigned (attempt_id, sort_order, pool_index) VALUES (?,?,?)'
            )->execute([$attemptId, $sort++, (int) $poolIndex]);
        }
        foreach ($att['answers'] ?? [] as $ans) {
            $this->pdo->prepare(
                'INSERT INTO attempt_answers (attempt_id, pool_index, selected_index) VALUES (?,?,?)'
            )->execute([
                $attemptId,
                (int) ($ans['question_index'] ?? 0),
                (int) ($ans['selected'] ?? 0),
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loadQuiz(string $quizId, bool $migrate = true): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM quizzes WHERE id = ?');
        $stmt->execute([$quizId]);
        $qrow = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$qrow) {
            return null;
        }
        $data = [
            'quiz_info' => [
                'id' => $qrow['id'],
                'batch_id' => $qrow['batch_id'],
                'name' => $qrow['name'],
                'time_limit' => (int) $qrow['time_limit'],
                'total_display_questions' => (int) $qrow['total_display_questions'],
                'created_at' => $qrow['created_at'],
                'status' => $qrow['status'],
                'public_slug' => $qrow['public_slug'],
            ],
            'questions' => $this->loadQuestionsForQuiz($quizId),
            'attempts' => $this->loadAttemptsForQuiz($quizId),
            'participants' => [],
        ];
        if ($migrate && $this->migrateQuizData($data)) {
            $this->saveQuiz($quizId, $data);
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadQuestionsForQuiz(string $quizId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pool_index, q_ref_id, question_text, options_json, answer_index FROM questions WHERE quiz_id = ? ORDER BY pool_index'
        );
        $stmt->execute([$quizId]);
        $questions = [];
        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $pi = (int) $r['pool_index'];
            $questions[$pi] = [
                'id' => $r['q_ref_id'] !== null ? (int) $r['q_ref_id'] : $pi,
                'question' => $r['question_text'],
                'options' => json_decode($r['options_json'], true) ?: [],
                'answer' => (int) $r['answer_index'],
            ];
        }
        ksort($questions, SORT_NUMERIC);

        return $questions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadAttemptsForQuiz(string $quizId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, batch_id, participant_id, participant_name, status, start_time, end_time, marks FROM attempts WHERE quiz_id = ?'
        );
        $stmt->execute([$quizId]);
        $out = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $aid = (int) $row['id'];
            $assigned = [];
            $as = $this->pdo->prepare(
                'SELECT pool_index FROM attempt_assigned WHERE attempt_id = ? ORDER BY sort_order'
            );
            $as->execute([$aid]);
            while ($x = $as->fetch(\PDO::FETCH_ASSOC)) {
                $assigned[] = (int) $x['pool_index'];
            }
            $answers = [];
            $ans = $this->pdo->prepare(
                'SELECT pool_index, selected_index FROM attempt_answers WHERE attempt_id = ? ORDER BY pool_index'
            );
            $ans->execute([$aid]);
            while ($x = $ans->fetch(\PDO::FETCH_ASSOC)) {
                $answers[] = [
                    'question_index' => (int) $x['pool_index'],
                    'selected' => (int) $x['selected_index'],
                ];
            }
            $out[] = [
                'batch_id' => $row['batch_id'],
                'participant_id' => $row['participant_id'],
                'participant_name' => $row['participant_name'],
                'status' => $row['status'],
                'assigned_questions' => $assigned,
                'answers' => $answers,
                'marks' => (int) $row['marks'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
            ];
        }

        return $out;
    }

    public function loadQuizBySlug(string $publicSlug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM quizzes WHERE public_slug = ? LIMIT 1');
        $stmt->execute([$publicSlug]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            return null;
        }

        return $this->loadQuiz((string) $id, true);
    }

    public function listQuizzesForBatch(string $batchId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT q.id, q.name, q.time_limit, q.total_display_questions, q.created_at, q.status, q.public_slug,
                (SELECT COUNT(*) FROM questions x WHERE x.quiz_id = q.id) AS question_count,
                (SELECT COUNT(*) FROM attempts a WHERE a.quiz_id = q.id AND a.status = \'finished\') AS finished_count
             FROM quizzes q WHERE q.batch_id = ? ORDER BY q.created_at DESC'
        );
        $stmt->execute([$batchId]);
        $out = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'time_limit' => (int) $row['time_limit'],
                'total_display_questions' => (int) $row['total_display_questions'],
                'created_at' => $row['created_at'],
                'status' => $row['status'] ?? 'active',
                'public_slug' => $row['public_slug'] ?? '',
                'question_count' => (int) $row['question_count'],
                'finished_count' => (int) $row['finished_count'],
            ];
        }

        return $out;
    }

    /** Active quizzes across all batches (for student catalog). */
    public function listActiveQuizzesWithBatchNames(): array
    {
        $bm = new BatchManager();
        $stmt = $this->pdo->query(
            'SELECT q.id, q.name, q.time_limit, q.public_slug, q.batch_id,
                (SELECT COUNT(*) FROM questions x WHERE x.quiz_id = q.id) AS question_count
             FROM quizzes q WHERE q.status = \'active\' ORDER BY q.name'
        );
        $out = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $bid = $row['batch_id'];
            $b = $bm->loadBatch($bid, false);
            $out[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'time_limit' => (int) $row['time_limit'],
                'public_slug' => $row['public_slug'] ?? '',
                'question_count' => (int) $row['question_count'],
                'batch_name' => $b['batch_info']['name'] ?? '',
            ];
        }

        return $out;
    }

    public function setQuizStatus(string $quizId, string $status): bool
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return false;
        }
        $stmt = $this->pdo->prepare('UPDATE quizzes SET status = ? WHERE id = ?');

        return $stmt->execute([$status, $quizId]);
    }

    /**
     * One-time import from legacy JSON snapshot (e.g. migration script).
     *
     * @param array<string, mixed> $data Same shape as loadQuiz() returns
     */
    public function importQuizSnapshot(array $data): bool
    {
        $qi = $data['quiz_info'] ?? null;
        if (!is_array($qi) || empty($qi['id'])) {
            return false;
        }
        $this->migrateQuizData($data);
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                'INSERT INTO quizzes (id, batch_id, name, time_limit, total_display_questions, public_slug, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $qi['id'],
                $qi['batch_id'] ?? '',
                $qi['name'] ?? '',
                (int) ($qi['time_limit'] ?? 0),
                (int) ($qi['total_display_questions'] ?? 0),
                $qi['public_slug'] ?? $this->ensureUniqueSlug(),
                $qi['status'] ?? 'active',
                $qi['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $qs = $data['questions'] ?? [];
            ksort($qs, SORT_NUMERIC);
            $this->insertQuestions($qi['id'], array_values($qs));
            foreach ($data['attempts'] ?? [] as $att) {
                $this->insertAttemptRow($qi['id'], $att);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }

        return true;
    }

    public function deleteQuiz(string $quizId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM quizzes WHERE id = ?');

        return $stmt->execute([$quizId]) && $stmt->rowCount() > 0;
    }

    public function deleteAllQuizzesForBatch(string $batchId): void
    {
        $this->pdo->prepare('DELETE FROM quizzes WHERE batch_id = ?')->execute([$batchId]);
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

    /**
     * @param array<string, mixed> $question Single question (id, question, options, answer or correct)
     * @return true|string true on success, error message on failure
     */
    public function updateQuizQuestionAtPoolIndex(string $quizId, int $poolIndex, array $question): bool|string
    {
        $data = $this->loadQuiz($quizId, false);
        if (!$data) {
            return 'Quiz not found.';
        }
        $questions = array_values($data['questions'] ?? []);
        if ($poolIndex < 0 || $poolIndex >= count($questions)) {
            return 'Invalid question index.';
        }
        $normalized = $this->validateAndNormalizeQuestions([$question]);
        if ($normalized === null) {
            return 'Invalid question: need text, at least two options, and a valid correct answer.';
        }
        $questions[$poolIndex] = $normalized[0];
        $data['questions'] = $questions;

        return $this->saveQuiz($quizId, $data) ? true : 'Could not save.';
    }

    /**
     * Appends one MCQ to the pool. Assigns the next available question `id` (ref id).
     *
     * @param array<string, mixed> $question question text, options, answer (or correct); id optional
     * @return true|string true on success, error message on failure
     */
    public function addQuizQuestionToPool(string $quizId, array $question): bool|string
    {
        $data = $this->loadQuiz($quizId, false);
        if (!$data) {
            return 'Quiz not found.';
        }
        $questions = array_values($data['questions'] ?? []);
        $question['id'] = $this->nextQuestionRefId($questions);
        $normalized = $this->validateAndNormalizeQuestions([$question]);
        if ($normalized === null) {
            return 'Invalid question: need text, at least two options, and a valid correct answer.';
        }
        $questions[] = $normalized[0];
        $data['questions'] = $questions;

        return $this->saveQuiz($quizId, $data) ? true : 'Could not save.';
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function nextQuestionRefId(array $questions): int
    {
        $max = 0;
        foreach ($questions as $q) {
            $id = isset($q['id']) ? (int) $q['id'] : 0;
            if ($id > $max) {
                $max = $id;
            }
        }

        return $max + 1;
    }

    /**
     * Deletes one pool question and reindexes attempts that reference pool indices.
     *
     * @return true|string true on success, error message on failure
     */
    public function deleteQuizQuestionAtPoolIndex(string $quizId, int $poolIndex): bool|string
    {
        $data = $this->loadQuiz($quizId, false);
        if (!$data) {
            return 'Quiz not found.';
        }
        $totalDisplay = (int) ($data['quiz_info']['total_display_questions'] ?? 0);
        $questions = array_values($data['questions'] ?? []);
        $n = count($questions);
        if ($poolIndex < 0 || $poolIndex >= $n) {
            return 'Invalid question index.';
        }
        if ($n - 1 < $totalDisplay) {
            return 'Cannot delete: the pool must stay at least as large as the “questions per attempt” limit (' . $totalDisplay . ').';
        }
        array_splice($questions, $poolIndex, 1);
        $data['questions'] = array_values($questions);
        $data['attempts'] = self::remapAttemptsAfterPoolDelete($data['attempts'] ?? [], $poolIndex);

        return $this->saveQuiz($quizId, $data) ? true : 'Could not save.';
    }

    /**
     * @param list<array<string, mixed>> $attempts
     * @return list<array<string, mixed>>
     */
    private static function remapAttemptsAfterPoolDelete(array $attempts, int $deletedIndex): array
    {
        $out = [];
        foreach ($attempts as $att) {
            $assigned = [];
            foreach ($att['assigned_questions'] ?? [] as $pi) {
                $pi = (int) $pi;
                if ($pi === $deletedIndex) {
                    continue;
                }
                $assigned[] = $pi > $deletedIndex ? $pi - 1 : $pi;
            }
            $assigned = array_values(array_unique($assigned));
            sort($assigned, SORT_NUMERIC);
            $answers = [];
            foreach ($att['answers'] ?? [] as $ans) {
                $qi = (int) ($ans['question_index'] ?? 0);
                if ($qi === $deletedIndex) {
                    continue;
                }
                $answers[] = [
                    'question_index' => $qi > $deletedIndex ? $qi - 1 : $qi,
                    'selected' => (int) ($ans['selected'] ?? 0),
                ];
            }
            $att['assigned_questions'] = $assigned;
            $att['answers'] = $answers;
            $out[] = $att;
        }

        return $out;
    }
}
