<?php

class BatchManager
{
    private string $dataDir;

    public function __construct()
    {
        $this->dataDir = __DIR__ . '/../data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    private function generateBatchId(): string
    {
        return 'batch_' . bin2hex(random_bytes(6));
    }

    public function getBatchFilePath(string $batchId): string
    {
        return $this->dataDir . '/' . $batchId . '.json';
    }

    /**
     * Legacy batches: add teacher fields and default password "changeme".
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
        $data = [
            'batch_info' => [
                'id' => $batchId,
                'name' => trim($name),
                'teacher_name' => trim($teacherName),
                'teacher_password' => password_hash($teacherPassword, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ],
            'participants' => [],
        ];
        $this->saveBatch($batchId, $data);
        return $data;
    }

    public function saveBatch(string $batchId, array $data): bool
    {
        $path = $this->getBatchFilePath($batchId);
        return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }

    public function loadBatch(string $batchId, bool $migrate = true): ?array
    {
        $path = $this->getBatchFilePath($batchId);
        if (!file_exists($path)) {
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        if ($data === null) {
            return null;
        }
        if ($migrate && $this->migrateBatchData($data)) {
            $this->saveBatch($batchId, $data);
        }
        return $data;
    }

    public function verifyTeacherPassword(string $batchId, string $password): bool
    {
        $data = $this->loadBatch($batchId);
        if (!$data) {
            return false;
        }
        return password_verify($password, $data['batch_info']['teacher_password']);
    }

    public function listBatches(): array
    {
        $out = [];
        foreach (glob($this->dataDir . '/batch_*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || !isset($data['batch_info'])) {
                continue;
            }
            $this->migrateBatchData($data);
            $id = $data['batch_info']['id'];
            $this->saveBatch($id, $data);
            $out[] = [
                'id' => $id,
                'name' => $data['batch_info']['name'],
                'teacher_name' => $data['batch_info']['teacher_name'] ?? 'Teacher',
                'created_at' => $data['batch_info']['created_at'],
                'participant_count' => count($data['participants'] ?? []),
            ];
        }
        usort($out, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        return $out;
    }

    public function deleteBatch(string $batchId): bool
    {
        $path = $this->getBatchFilePath($batchId);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    public function isPinTaken(string $pin): bool
    {
        foreach (glob($this->dataDir . '/batch_*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (empty($data['participants'])) {
                continue;
            }
            foreach ($data['participants'] as $p) {
                if (($p['pin'] ?? '') === $pin) {
                    return true;
                }
            }
        }
        return false;
    }
}
