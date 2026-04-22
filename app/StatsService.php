<?php

declare(strict_types=1);

class StatsService
{
    public function __construct(
        private QuizManager $quizManager,
        private BatchManager $batchManager
    ) {
    }

    /** Human-readable duration between two stored datetimes (e.g. "1m 08s"). */
    public static function formatAttemptDuration(?string $start, ?string $end): ?string
    {
        if ($start === null || $end === null || $start === '' || $end === '') {
            return null;
        }
        $t1 = strtotime($start);
        $t2 = strtotime($end);
        if ($t1 === false || $t2 === false) {
            return null;
        }
        $sec = max(0, $t2 - $t1);
        if ($sec < 60) {
            return $sec . 's';
        }
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        $s = $sec % 60;
        if ($h > 0) {
            return sprintf('%dh %dm %02ds', $h, $m, $s);
        }

        return sprintf('%dm %02ds', $m, $s);
    }

    /** Elapsed seconds for a finished attempt, or null if times are missing. */
    public static function attemptDurationSeconds(?string $start, ?string $end): ?int
    {
        if ($start === null || $end === null || $start === '' || $end === '') {
            return null;
        }
        $t1 = strtotime($start);
        $t2 = strtotime($end);
        if ($t1 === false || $t2 === false) {
            return null;
        }

        return max(0, $t2 - $t1);
    }

    /** @return array{grade: string, emoji: string} */
    public static function gradeFromPercent(int $pct): array
    {
        if ($pct >= 80) {
            return ['grade' => 'Excellent', 'emoji' => '🤩'];
        }
        if ($pct >= 60) {
            return ['grade' => 'Good', 'emoji' => '🙂'];
        }
        if ($pct >= 40) {
            return ['grade' => 'Average', 'emoji' => '😐'];
        }
        if ($pct >= 20) {
            return ['grade' => 'Poor', 'emoji' => '☹️'];
        }
        return ['grade' => 'Very Poor', 'emoji' => '💀'];
    }

    private function findAttempt(array $attempts, string $batchId, string $participantId): ?array
    {
        foreach ($attempts as $a) {
            if (($a['batch_id'] ?? '') === $batchId && ($a['participant_id'] ?? '') === $participantId) {
                return $a;
            }
        }
        return null;
    }

    /**
     * @return array|null Full stats for one participant in a batch
     */
    public function getParticipantStats(string $batchId, string $participantId): ?array
    {
        $batch = $this->batchManager->loadBatch($batchId);
        if (!$batch) {
            return null;
        }

        $participantName = '';
        foreach ($batch['participants'] ?? [] as $p) {
            if (($p['id'] ?? '') === $participantId) {
                $participantName = $p['name'] ?? '';
                break;
            }
        }

        $quizList = $this->quizManager->listQuizzesForBatch($batchId);
        $rows = [];
        $completed = 0;
        $inProgress = 0;
        $sumPct = 0;
        $bestPct = null;

        foreach ($quizList as $qmeta) {
            $data = $this->quizManager->loadQuiz($qmeta['id']);
            if (!$data) {
                continue;
            }
            $attempt = $this->findAttempt($data['attempts'] ?? [], $batchId, $participantId);
            if ($attempt === null) {
                $rows[] = [
                    'quiz_id' => $qmeta['id'],
                    'quiz_name' => $qmeta['name'],
                    'quiz_status' => $qmeta['status'] ?? 'active',
                    'attempt_status' => 'not_started',
                    'marks' => null,
                    'total' => null,
                    'percentage' => null,
                    'grade' => null,
                    'emoji' => null,
                    'started_at' => null,
                    'completed_at' => null,
                ];
                continue;
            }

            $st = $attempt['status'] ?? '';
            if ($st === 'finished') {
                $total = count($attempt['assigned_questions'] ?? []);
                $marks = (int)($attempt['marks'] ?? 0);
                $pct = $total > 0 ? (int)round(($marks / $total) * 100) : 0;
                $g = self::gradeFromPercent($pct);
                $completed++;
                $sumPct += $pct;
                $bestPct = $bestPct === null ? $pct : max($bestPct, $pct);
                $rows[] = [
                    'quiz_id' => $qmeta['id'],
                    'quiz_name' => $qmeta['name'],
                    'quiz_status' => $qmeta['status'] ?? 'active',
                    'attempt_status' => 'finished',
                    'marks' => $marks,
                    'total' => $total,
                    'percentage' => $pct,
                    'grade' => $g['grade'],
                    'emoji' => $g['emoji'],
                    'started_at' => $attempt['start_time'] ?? null,
                    'completed_at' => $attempt['end_time'] ?? null,
                ];
            } elseif ($st === 'running') {
                $inProgress++;
                $rows[] = [
                    'quiz_id' => $qmeta['id'],
                    'quiz_name' => $qmeta['name'],
                    'quiz_status' => $qmeta['status'] ?? 'active',
                    'attempt_status' => 'in_progress',
                    'marks' => null,
                    'total' => null,
                    'percentage' => null,
                    'grade' => null,
                    'emoji' => null,
                    'started_at' => $attempt['start_time'] ?? null,
                    'completed_at' => null,
                ];
            }
        }

        $avgPct = $completed > 0 ? (int)round($sumPct / $completed) : null;

        return [
            'batch_id' => $batchId,
            'batch_name' => $batch['batch_info']['name'] ?? '',
            'participant_id' => $participantId,
            'participant_name' => $participantName,
            'summary' => [
                'quizzes_total' => count($quizList),
                'quizzes_completed' => $completed,
                'quizzes_in_progress' => $inProgress,
                'quizzes_not_started' => max(0, count($quizList) - $completed - $inProgress),
                'average_percentage' => $avgPct,
                'best_percentage' => $bestPct,
            ],
            'quizzes' => $rows,
        ];
    }

    /**
     * Order by average % (higher first), tie-break by total time on finished quizzes (lower / faster first).
     * Assign competition ranks (e.g. 1, 1, 3). No completed quiz → rank null.
     *
     * @param list<array<string, mixed>> $matrix
     * @return list<array<string, mixed>>
     */
    private function assignBatchMatrixRanks(array $matrix): array
    {
        usort($matrix, static function (array $a, array $b): int {
            $avgA = $a['avg_percentage'] ?? null;
            $avgB = $b['avg_percentage'] ?? null;
            if ($avgA === null && $avgB === null) {
                return strcmp((string) ($a['participant_id'] ?? ''), (string) ($b['participant_id'] ?? ''));
            }
            if ($avgA === null) {
                return 1;
            }
            if ($avgB === null) {
                return -1;
            }
            if ($avgA !== $avgB) {
                return $avgB <=> $avgA;
            }
            $ta = (int) ($a['total_time_seconds'] ?? 0);
            $tb = (int) ($b['total_time_seconds'] ?? 0);
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }

            return strcmp((string) ($a['participant_id'] ?? ''), (string) ($b['participant_id'] ?? ''));
        });

        $n = count($matrix);
        for ($i = 0; $i < $n; $i++) {
            if ($matrix[$i]['avg_percentage'] === null) {
                $matrix[$i]['rank'] = null;
                continue;
            }
            if ($i > 0
                && $matrix[$i]['avg_percentage'] === $matrix[$i - 1]['avg_percentage']
                && (int) ($matrix[$i]['total_time_seconds'] ?? 0) === (int) ($matrix[$i - 1]['total_time_seconds'] ?? 0)
            ) {
                $matrix[$i]['rank'] = $matrix[$i - 1]['rank'];
            } else {
                $matrix[$i]['rank'] = $i + 1;
            }
        }

        return $matrix;
    }

    /**
     * Teacher: full matrix participants × quizzes + per-quiz rollups.
     */
    public function getBatchStats(string $batchId): ?array
    {
        $batch = $this->batchManager->loadBatch($batchId);
        if (!$batch) {
            return null;
        }

        $participants = $batch['participants'] ?? [];
        $quizList = $this->quizManager->listQuizzesForBatch($batchId);

        $matrix = [];
        foreach ($participants as $p) {
            $pid = $p['id'];
            $row = [
                'participant_id' => $pid,
                'participant_name' => $p['name'] ?? '',
                'cells' => [],
                'avg_percentage' => null,
            ];
            $sumPct = 0;
            $nDone = 0;
            $totalTimeSec = 0;

            foreach ($quizList as $qmeta) {
                $data = $this->quizManager->loadQuiz($qmeta['id']);
                $att = $data ? $this->findAttempt($data['attempts'] ?? [], $batchId, $pid) : null;

                if ($att === null) {
                    $row['cells'][$qmeta['id']] = [
                        'key' => 'empty',
                        'label' => '—',
                        'percentage' => null,
                        'marks' => null,
                        'total' => null,
                    ];
                    continue;
                }

                $st = $att['status'] ?? '';
                if ($st === 'finished') {
                    $total = count($att['assigned_questions'] ?? []);
                    $marks = (int)($att['marks'] ?? 0);
                    $pct = $total > 0 ? (int)round(($marks / $total) * 100) : 0;
                    $sumPct += $pct;
                    $nDone++;
                    $startT = $att['start_time'] ?? null;
                    $endT = $att['end_time'] ?? null;
                    if (is_string($startT) && $startT !== '' && is_string($endT) && $endT !== '') {
                        $t1 = strtotime($startT);
                        $t2 = strtotime($endT);
                        if ($t1 !== false && $t2 !== false) {
                            $totalTimeSec += max(0, $t2 - $t1);
                        }
                    }
                    $row['cells'][$qmeta['id']] = [
                        'key' => 'done',
                        'label' => $marks . '/' . $total,
                        'percentage' => $pct,
                        'marks' => $marks,
                        'total' => $total,
                    ];
                } elseif ($st === 'running') {
                    $row['cells'][$qmeta['id']] = [
                        'key' => 'running',
                        'label' => 'In progress',
                        'percentage' => null,
                        'marks' => null,
                        'total' => null,
                    ];
                } else {
                    $row['cells'][$qmeta['id']] = [
                        'key' => 'empty',
                        'label' => '—',
                        'percentage' => null,
                        'marks' => null,
                        'total' => null,
                    ];
                }
            }

            $row['avg_percentage'] = $nDone > 0 ? (int)round($sumPct / $nDone) : null;
            $row['total_time_seconds'] = $totalTimeSec;
            $matrix[] = $row;
        }

        $matrix = $this->assignBatchMatrixRanks($matrix);

        $quizRollups = [];
        foreach ($quizList as $qmeta) {
            $data = $this->quizManager->loadQuiz($qmeta['id']);
            $finished = [];
            if ($data) {
                foreach ($data['attempts'] ?? [] as $a) {
                    if (($a['status'] ?? '') !== 'finished') {
                        continue;
                    }
                    if (($a['batch_id'] ?? '') !== $batchId) {
                        continue;
                    }
                    $t = count($a['assigned_questions'] ?? []);
                    if ($t <= 0) {
                        continue;
                    }
                    $m = (int)($a['marks'] ?? 0);
                    $finished[] = (int)round(($m / $t) * 100);
                }
            }
            $n = count($finished);
            $quizRollups[] = [
                'id' => $qmeta['id'],
                'name' => $qmeta['name'],
                'status' => $qmeta['status'] ?? 'active',
                'submissions' => $n,
                'class_average_pct' => $n > 0 ? (int)round(array_sum($finished) / $n) : null,
                'class_best_pct' => $n > 0 ? max($finished) : null,
            ];
        }

        return [
            'batch_name' => $batch['batch_info']['name'] ?? '',
            'participant_count' => count($participants),
            'quiz_count' => count($quizList),
            'quizzes' => $quizList,
            'rows' => $matrix,
            'quiz_rollups' => $quizRollups,
        ];
    }

    /**
     * Richer per-quiz stats for teacher quiz page.
     */
    public function getQuizDetailStats(string $batchId, string $quizId): ?array
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data || ($data['quiz_info']['batch_id'] ?? '') !== $batchId) {
            return null;
        }

        $batch = $this->batchManager->loadBatch($batchId);
        if (!$batch) {
            return null;
        }

        $attempts = $data['attempts'] ?? [];

        $finishedPcts = [];
        $detailRows = [];

        foreach ($batch['participants'] as $p) {
            $pid = $p['id'];
            $att = $this->findAttempt($attempts, $batchId, $pid);
            if ($att === null) {
                $detailRows[] = [
                    'participant_id' => $pid,
                    'participant_name' => $p['name'] ?? '',
                    'status' => 'not_started',
                    'marks' => null,
                    'total' => null,
                    'percentage' => null,
                    'grade' => null,
                    'emoji' => null,
                    'started_at' => null,
                    'completed_at' => null,
                    'total_time' => null,
                    'total_time_seconds' => null,
                    'rank' => null,
                ];
                continue;
            }
            $st = $att['status'] ?? '';
            if ($st === 'running') {
                $detailRows[] = [
                    'participant_id' => $pid,
                    'participant_name' => $p['name'] ?? '',
                    'status' => 'in_progress',
                    'marks' => null,
                    'total' => null,
                    'percentage' => null,
                    'grade' => null,
                    'emoji' => null,
                    'started_at' => $att['start_time'] ?? null,
                    'completed_at' => null,
                    'total_time' => null,
                    'total_time_seconds' => null,
                    'rank' => null,
                ];
                continue;
            }
            if ($st === 'finished') {
                $total = count($att['assigned_questions'] ?? []);
                $marks = (int)($att['marks'] ?? 0);
                $pct = $total > 0 ? (int)round(($marks / $total) * 100) : 0;
                $g = self::gradeFromPercent($pct);
                $finishedPcts[] = $pct;
                $startT = $att['start_time'] ?? null;
                $endT = $att['end_time'] ?? null;
                $timeSec = self::attemptDurationSeconds($startT, $endT);
                $detailRows[] = [
                    'participant_id' => $pid,
                    'participant_name' => $p['name'] ?? '',
                    'status' => 'finished',
                    'marks' => $marks,
                    'total' => $total,
                    'percentage' => $pct,
                    'grade' => $g['grade'],
                    'emoji' => $g['emoji'],
                    'started_at' => $startT,
                    'completed_at' => $endT,
                    'total_time' => self::formatAttemptDuration($startT, $endT),
                    'total_time_seconds' => $timeSec,
                    'rank' => null,
                ];
            }
        }

        $finishedForRank = array_values(array_filter(
            $detailRows,
            static fn(array $r): bool => ($r['status'] ?? '') === 'finished',
        ));
        usort(
            $finishedForRank,
            static function (array $a, array $b): int {
                $ma = (int)($a['marks'] ?? 0);
                $mb = (int)($b['marks'] ?? 0);
                if ($ma !== $mb) {
                    return $mb <=> $ma;
                }
                $sa = $a['total_time_seconds'] ?? null;
                $sb = $b['total_time_seconds'] ?? null;
                if ($sa === null) {
                    $sa = PHP_INT_MAX;
                }
                if ($sb === null) {
                    $sb = PHP_INT_MAX;
                }
                if ($sa !== $sb) {
                    return $sa <=> $sb;
                }

                return strcmp((string)($a['participant_id'] ?? ''), (string)($b['participant_id'] ?? ''));
            },
        );
        $rankByPid = [];
        foreach (array_values($finishedForRank) as $i => $row) {
            $rankByPid[$row['participant_id']] = $i + 1;
        }
        foreach ($detailRows as $k => $row) {
            if (($row['status'] ?? '') === 'finished' && isset($rankByPid[$row['participant_id']])) {
                $detailRows[$k]['rank'] = $rankByPid[$row['participant_id']];
            }
        }

        $rankedRows = [];
        $unrankedRows = [];
        foreach ($detailRows as $row) {
            if (($row['status'] ?? '') === 'finished' && $row['rank'] !== null) {
                $rankedRows[] = $row;
            } else {
                $unrankedRows[] = $row;
            }
        }
        usort(
            $rankedRows,
            static function (array $a, array $b): int {
                return (int)($a['rank'] ?? 0) <=> (int)($b['rank'] ?? 0);
            },
        );
        $detailRows = array_merge($rankedRows, $unrankedRows);

        $nRoster = count($batch['participants'] ?? []);
        $nFinished = count($finishedPcts);
        $nRunning = count(array_filter($detailRows, fn($r) => $r['status'] === 'in_progress'));
        $avg = $nFinished > 0 ? (int)round(array_sum($finishedPcts) / $nFinished) : null;
        $best = $nFinished > 0 ? max($finishedPcts) : null;

        return [
            'roster_count' => $nRoster,
            'completed_count' => $nFinished,
            'not_started_count' => max(0, $nRoster - $nFinished - $nRunning),
            'in_progress_count' => $nRunning,
            'class_average_pct' => $avg,
            'class_best_pct' => $best,
            'participants' => $detailRows,
        ];
    }

    /**
     * Teacher: per-student drill-down — each quiz attempt with per-question correct/incorrect.
     */
    public function getTeacherParticipantDetail(string $batchId, string $participantId): ?array
    {
        $batch = $this->batchManager->loadBatch($batchId);
        if (!$batch) {
            return null;
        }

        $participantName = '';
        $found = false;
        foreach ($batch['participants'] ?? [] as $p) {
            if (($p['id'] ?? '') === $participantId) {
                $participantName = $p['name'] ?? '';
                $found = true;
                break;
            }
        }
        if (!$found) {
            return null;
        }

        $quizList = $this->quizManager->listQuizzesForBatch($batchId);
        $quizzesOut = [];
        $completedCount = 0;
        $sumPct = 0;

        foreach ($quizList as $qmeta) {
            $data = $this->quizManager->loadQuiz($qmeta['id']);
            if (!$data) {
                continue;
            }

            $att = $this->findAttempt($data['attempts'] ?? [], $batchId, $participantId);
            if ($att === null) {
                $quizzesOut[] = [
                    'quiz_id' => $qmeta['id'],
                    'quiz_name' => $qmeta['name'],
                    'quiz_status' => $qmeta['status'] ?? 'active',
                    'public_slug' => $qmeta['public_slug'] ?? '',
                    'attempt_status' => 'not_started',
                    'marks' => null,
                    'total' => null,
                    'percentage' => null,
                    'started_at' => null,
                    'completed_at' => null,
                    'questions' => [],
                ];
                continue;
            }

            $st = $att['status'] ?? '';
            if ($st === 'running') {
                $assigned = $att['assigned_questions'] ?? [];
                $quizzesOut[] = [
                    'quiz_id' => $qmeta['id'],
                    'quiz_name' => $qmeta['name'],
                    'quiz_status' => $qmeta['status'] ?? 'active',
                    'public_slug' => $qmeta['public_slug'] ?? '',
                    'attempt_status' => 'in_progress',
                    'marks' => null,
                    'total' => count($assigned),
                    'percentage' => null,
                    'started_at' => $att['start_time'] ?? null,
                    'completed_at' => null,
                    'questions' => $this->buildQuestionDetailsForAttempt($data, $att, false),
                ];
                continue;
            }

            if ($st === 'finished') {
                $total = count($att['assigned_questions'] ?? []);
                $marks = (int)($att['marks'] ?? 0);
                $pct = $total > 0 ? (int) round(($marks / $total) * 100) : 0;
                $completedCount++;
                $sumPct += $pct;
                $quizzesOut[] = [
                    'quiz_id' => $qmeta['id'],
                    'quiz_name' => $qmeta['name'],
                    'quiz_status' => $qmeta['status'] ?? 'active',
                    'public_slug' => $qmeta['public_slug'] ?? '',
                    'attempt_status' => 'finished',
                    'marks' => $marks,
                    'total' => $total,
                    'percentage' => $pct,
                    'started_at' => $att['start_time'] ?? null,
                    'completed_at' => $att['end_time'] ?? null,
                    'questions' => $this->buildQuestionDetailsForAttempt($data, $att, true),
                ];
            }
        }

        $avgPct = $completedCount > 0 ? (int) round($sumPct / $completedCount) : null;

        return [
            'batch_id' => $batchId,
            'batch_name' => $batch['batch_info']['name'] ?? '',
            'participant_id' => $participantId,
            'participant_name' => $participantName,
            'summary' => [
                'quizzes_in_batch' => count($quizList),
                'quizzes_completed' => $completedCount,
                'average_percentage_finished' => $avgPct,
            ],
            'quizzes' => $quizzesOut,
        ];
    }

    /**
     * Student: one quiz attempt with per-question correct/incorrect (same batch as session).
     *
     * @return array<string, mixed>|null
     */
    public function getStudentQuizDetail(string $batchId, string $participantId, string $quizId): ?array
    {
        $data = $this->quizManager->loadQuiz($quizId);
        if (!$data || ($data['quiz_info']['batch_id'] ?? '') !== $batchId) {
            return null;
        }

        $batch = $this->batchManager->loadBatch($batchId);
        if (!$batch) {
            return null;
        }

        $found = false;
        foreach ($batch['participants'] ?? [] as $p) {
            if (($p['id'] ?? '') === $participantId) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return null;
        }

        $qi = $data['quiz_info'];
        $att = $this->findAttempt($data['attempts'] ?? [], $batchId, $participantId);
        if ($att === null) {
            return [
                'quiz_id' => $quizId,
                'quiz_name' => $qi['name'] ?? '',
                'time_limit' => (int) ($qi['time_limit'] ?? 0),
                'attempt_status' => 'not_started',
                'marks' => null,
                'total' => null,
                'percentage' => null,
                'grade' => null,
                'emoji' => null,
                'started_at' => null,
                'completed_at' => null,
                'total_time' => null,
                'questions' => [],
            ];
        }

        $st = $att['status'] ?? '';
        if ($st === 'running') {
            $assigned = $att['assigned_questions'] ?? [];

            return [
                'quiz_id' => $quizId,
                'quiz_name' => $qi['name'] ?? '',
                'time_limit' => (int) ($qi['time_limit'] ?? 0),
                'attempt_status' => 'in_progress',
                'marks' => null,
                'total' => count($assigned),
                'percentage' => null,
                'grade' => null,
                'emoji' => null,
                'started_at' => $att['start_time'] ?? null,
                'completed_at' => null,
                'total_time' => null,
                'questions' => $this->buildQuestionDetailsForAttempt($data, $att, false),
            ];
        }

        if ($st === 'finished') {
            $total = count($att['assigned_questions'] ?? []);
            $marks = (int) ($att['marks'] ?? 0);
            $pct = $total > 0 ? (int) round(($marks / $total) * 100) : 0;
            $g = self::gradeFromPercent($pct);
            $startT = $att['start_time'] ?? null;
            $endT = $att['end_time'] ?? null;

            return [
                'quiz_id' => $quizId,
                'quiz_name' => $qi['name'] ?? '',
                'time_limit' => (int) ($qi['time_limit'] ?? 0),
                'attempt_status' => 'finished',
                'marks' => $marks,
                'total' => $total,
                'percentage' => $pct,
                'grade' => $g['grade'],
                'emoji' => $g['emoji'],
                'started_at' => $startT,
                'completed_at' => $endT,
                'total_time' => self::formatAttemptDuration($startT, $endT),
                'questions' => $this->buildQuestionDetailsForAttempt($data, $att, true),
            ];
        }

        return null;
    }

    /**
     * @param array{answers?: array, assigned_questions?: array} $att
     * @return list<array<string, mixed>>
     */
    private function buildQuestionDetailsForAttempt(array $quizData, array $att, bool $finished): array
    {
        $questions = $quizData['questions'] ?? [];
        $assigned = $att['assigned_questions'] ?? [];
        $byIndex = [];
        foreach ($att['answers'] ?? [] as $a) {
            $qi = (int) ($a['question_index'] ?? -1);
            if ($qi >= 0) {
                $byIndex[$qi] = (int) ($a['selected'] ?? 0);
            }
        }

        $out = [];
        $pos = 0;
        foreach ($assigned as $qIdx) {
            $pos++;
            $qIdx = (int) $qIdx;
            if (!isset($questions[$qIdx])) {
                continue;
            }
            $q = $questions[$qIdx];
            $opts = $q['options'] ?? [];
            if (!is_array($opts)) {
                $opts = [];
            }
            $correctIdx = (int) ($q['answer'] ?? 0);
            $hasAnswer = array_key_exists($qIdx, $byIndex);
            $selectedIdx = $hasAnswer ? $byIndex[$qIdx] : null;

            $correctLabel = $opts[$correctIdx] ?? '(n/a)';
            $selectedLabel = null;
            if ($selectedIdx !== null && isset($opts[$selectedIdx])) {
                $selectedLabel = $opts[$selectedIdx];
            } elseif ($selectedIdx !== null) {
                $selectedLabel = '(invalid option)';
            }

            $isCorrect = $finished && $hasAnswer && $selectedIdx === $correctIdx;

            $out[] = [
                'position' => $pos,
                'question_index' => $qIdx,
                'question_id' => $q['id'] ?? null,
                'question' => $q['question'] ?? '',
                'options' => $opts,
                'correct_index' => $correctIdx,
                'selected_index' => $selectedIdx,
                'correct_label' => is_string($correctLabel) ? $correctLabel : (string) $correctLabel,
                'selected_label' => $selectedLabel !== null ? (string) $selectedLabel : null,
                'answered' => $hasAnswer,
                'is_correct' => $isCorrect,
            ];
        }

        return $out;
    }

    /**
     * UTF-8 CSV (Excel-friendly). Caller should send BOM when downloading.
     *
     * @param array<string, mixed> $stats Output of getBatchStats()
     */
    public static function batchStatsToCsv(array $stats): string
    {
        $lines = [];
        $lines[] = self::csvEscapeRow(['Batch', $stats['batch_name'] ?? '']);
        $lines[] = self::csvEscapeRow(['Exported', date('Y-m-d H:i:s')]);
        $lines[] = '';
        $lines[] = self::csvEscapeRow(['Quiz summary']);
        $lines[] = self::csvEscapeRow(['Quiz', 'Submissions', 'Class average %', 'Best %']);
        foreach ($stats['quiz_rollups'] ?? [] as $r) {
            $lines[] = self::csvEscapeRow([
                $r['name'] ?? '',
                (string) ($r['submissions'] ?? ''),
                $r['class_average_pct'] !== null ? (string) $r['class_average_pct'] : '',
                $r['class_best_pct'] !== null ? (string) $r['class_best_pct'] : '',
            ]);
        }
        $lines[] = '';
        $lines[] = self::csvEscapeRow(['Participant results']);
        $quizzes = $stats['quizzes'] ?? [];
        $rows = $stats['rows'] ?? [];
        $header = ['Rank', 'Student', 'Participant ID'];
        foreach ($quizzes as $q) {
            $header[] = $q['name'] ?? '';
        }
        $header[] = 'Average %';
        $lines[] = self::csvEscapeRow($header);
        foreach ($rows as $row) {
            $out = [
                isset($row['rank']) && $row['rank'] !== null ? (string) $row['rank'] : '',
                $row['participant_name'] ?? '',
                $row['participant_id'] ?? '',
            ];
            foreach ($quizzes as $qmeta) {
                $cid = $qmeta['id'];
                $c = $row['cells'][$cid] ?? null;
                if ($c === null) {
                    $out[] = '';
                    continue;
                }
                $key = $c['key'] ?? '';
                if ($key === 'done') {
                    $lbl = $c['label'] ?? '';
                    $pct = $c['percentage'] ?? '';
                    $out[] = $lbl . ' (' . $pct . '%)';
                } elseif ($key === 'running') {
                    $out[] = 'In progress';
                } else {
                    $out[] = '';
                }
            }
            $out[] = isset($row['avg_percentage']) && $row['avg_percentage'] !== null ? (string) $row['avg_percentage'] : '';
            $lines[] = self::csvEscapeRow($out);
        }

        return implode("\r\n", $lines);
    }

    /**
     * @param list<string|int|float> $fields
     */
    private static function csvEscapeRow(array $fields): string
    {
        $esc = [];
        foreach ($fields as $f) {
            $s = (string) $f;
            if (strpbrk($s, ",\r\n\"") !== false) {
                $s = '"' . str_replace('"', '""', $s) . '"';
            }
            $esc[] = $s;
        }

        return implode(',', $esc);
    }
}
