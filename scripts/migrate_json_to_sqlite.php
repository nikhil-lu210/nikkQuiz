<?php

/**
 * One-time migration: import data/*.json (batch_*.json, quiz_*.json) into SQLite.
 *
 * Usage (CLI): php scripts/migrate_json_to_sqlite.php
 *
 * Refuses to run if the batches table already has rows (to avoid duplicates).
 * Override: delete or rename data/nikkquiz.sqlite first.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$pdo = Database::pdo();
$n = (int) $pdo->query('SELECT COUNT(*) FROM batches')->fetchColumn();
if ($n > 0) {
    fwrite(STDERR, "Refusing to migrate: `batches` table is not empty. Backup/remove the SQLite file first if you really want to re-import.\n");
    exit(1);
}

$dataDir = dirname(__DIR__) . '/data';
$bm = new BatchManager();
$qm = new QuizManager();

$batchFiles = glob($dataDir . '/batch_*.json') ?: [];
foreach ($batchFiles as $file) {
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data) || empty($data['batch_info']['id'])) {
        echo "Skip invalid batch file: $file\n";
        continue;
    }
    if (!$bm->importBatchSnapshot($data)) {
        fwrite(STDERR, "Failed batch import: $file\n");
        exit(1);
    }
    echo "Imported batch: {$data['batch_info']['id']}\n";
}

$quizFiles = glob($dataDir . '/quiz_*.json') ?: [];
foreach ($quizFiles as $file) {
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data) || empty($data['quiz_info']['id'])) {
        echo "Skip invalid quiz file: $file\n";
        continue;
    }
    if (!$qm->importQuizSnapshot($data)) {
        fwrite(STDERR, "Failed quiz import: $file\n");
        exit(1);
    }
    echo "Imported quiz: {$data['quiz_info']['id']}\n";
}

echo "Migration finished.\n";
