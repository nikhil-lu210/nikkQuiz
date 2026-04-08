<?php

declare(strict_types=1);

class BatchManager
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    private function generateBatchId(): string
    {
        return 'batch_' . bin2hex(random_bytes(6));
    }

    /**
     * Legacy batches: add teacher fields and default password "changeme".
     *
     * @param array<string, mixed> $data
     */
    public function migrateBatchData(array &$data): bool
    {
        $changed = false;
        if (!isset($data['batch_info'])) {
            return false;
        }
        $b = &$data['batch_info'];
        if (!isset($b['teacher_name']) || $b['teacher_name'] === '') {
            $b['teacher_name'] = 'Teacher';
            $changed = true;
        }
        if (!isset($b['teacher_password']) || $b['teacher_password'] === '') {
            $b['teacher_password'] = password_hash('changeme', PASSWORD_DEFAULT);
            $changed = true;
        }
        if (!isset($data['participants']) || !is_array($data['participants'])) {
            $data['participants'] = [];
            $changed = true;
        }

        return $changed;
    }

    public function createBatch(string $name, string $teacherName, string $teacherPassword): array
    {
        $batchId = $this->generateBatchId();
        $created = date('Y-m-d H:i:s');
        $hash = password_hash($teacherPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            'INSERT INTO batches (id, name, teacher_name, teacher_password, created_at) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([
            $batchId,
            trim($name),
            trim($teacherName),
            $hash,
            $created,
        ]);

        return [
            'batch_info' => [
                'id' => $batchId,
                'name' => trim($name),
                'teacher_name' => trim($teacherName),
                'teacher_password' => $hash,
                'created_at' => $created,
            ],
            'participants' => [],
        ];
    }

    public function saveBatch(string $batchId, array $data): bool
    {
        if (!isset($data['batch_info'])) {
            return false;
        }
        $bi = $data['batch_info'];
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                'UPDATE batches SET name = ?, teacher_name = ?, teacher_password = ? WHERE id = ?'
            );
            $stmt->execute([
                $bi['name'] ?? '',
                $bi['teacher_name'] ?? 'Teacher',
                $bi['teacher_password'] ?? '',
                $batchId,
            ]);
            $this->pdo->prepare('DELETE FROM participants WHERE batch_id = ?')->execute([$batchId]);
            $ins = $this->pdo->prepare(
                'INSERT INTO participants (id, batch_id, name, pin) VALUES (?,?,?,?)'
            );
            foreach ($data['participants'] ?? [] as $p) {
                $ins->execute([
                    $p['id'] ?? '',
                    $batchId,
                    $p['name'] ?? '',
                    $p['pin'] ?? '',
                ]);
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

    public function loadBatch(string $batchId, bool $migrate = true): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, teacher_name, teacher_password, created_at FROM batches WHERE id = ?');
        $stmt->execute([$batchId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $data = [
            'batch_info' => [
                'id' => $row['id'],
                'name' => $row['name'],
                'teacher_name' => $row['teacher_name'],
                'teacher_password' => $row['teacher_password'],
                'created_at' => $row['created_at'],
            ],
            'participants' => [],
        ];
        $pstmt = $this->pdo->prepare('SELECT id, name, pin FROM participants WHERE batch_id = ? ORDER BY id');
        $pstmt->execute([$batchId]);
        while ($p = $pstmt->fetch(\PDO::FETCH_ASSOC)) {
            $data['participants'][] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'pin' => $p['pin'],
            ];
        }
        if ($migrate && $this->migrateBatchData($data)) {
            $this->saveBatch($batchId, $data);
        }

        return $data;
    }

    public function verifyTeacherPassword(string $batchId, string $password): bool
    {
        $data = $this->loadBatch($batchId, false);
        if (!$data) {
            return false;
        }

        return password_verify($password, $data['batch_info']['teacher_password']);
    }

    public function listBatches(): array
    {
        $sql = 'SELECT b.id, b.name, b.teacher_name, b.created_at,
            (SELECT COUNT(*) FROM participants p WHERE p.batch_id = b.id) AS participant_count
            FROM batches b ORDER BY b.created_at DESC';
        $stmt = $this->pdo->query($sql);
        $out = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'teacher_name' => $row['teacher_name'] ?? 'Teacher',
                'created_at' => $row['created_at'],
                'participant_count' => (int) $row['participant_count'],
            ];
        }

        return $out;
    }

    public function deleteBatch(string $batchId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM batches WHERE id = ?');

        return $stmt->execute([$batchId]) && $stmt->rowCount() > 0;
    }

    public function isPinTaken(string $pin): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM participants WHERE pin = ? LIMIT 1');
        $stmt->execute([$pin]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * One-time import from legacy JSON snapshot (e.g. migration script).
     *
     * @param array<string, mixed> $data Same shape as loadBatch() returns
     */
    public function importBatchSnapshot(array $data): bool
    {
        $this->migrateBatchData($data);
        $bi = $data['batch_info'] ?? null;
        if (!is_array($bi) || empty($bi['id'])) {
            return false;
        }
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare(
                'INSERT INTO batches (id, name, teacher_name, teacher_password, created_at) VALUES (?,?,?,?,?)'
            )->execute([
                $bi['id'],
                $bi['name'] ?? '',
                $bi['teacher_name'] ?? 'Teacher',
                $bi['teacher_password'] ?? password_hash('changeme', PASSWORD_DEFAULT),
                $bi['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $ins = $this->pdo->prepare(
                'INSERT INTO participants (id, batch_id, name, pin) VALUES (?,?,?,?)'
            );
            foreach ($data['participants'] ?? [] as $p) {
                $ins->execute([
                    $p['id'] ?? '',
                    $bi['id'],
                    $p['name'] ?? '',
                    $p['pin'] ?? '',
                ]);
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
}
