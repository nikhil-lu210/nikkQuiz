<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/SiteAuth.php';
require_once __DIR__ . '/classes/QuizManager.php';
require_once __DIR__ . '/classes/BatchManager.php';
require_once __DIR__ . '/classes/StatsService.php';

if (!SiteAuth::isAuthenticated()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Site login required.';
    exit;
}

$batchId = isset($_GET['batch_id']) ? trim((string) $_GET['batch_id']) : '';
if ($batchId === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Missing batch_id.';
    exit;
}

$sessionKey = 'teacher_batch_' . $batchId;
if (empty($_SESSION[$sessionKey])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Sign in to this batch first (teacher password).';
    exit;
}

$statsService = new StatsService(new QuizManager(), new BatchManager());
$stats = $statsService->getBatchStats($batchId);
if ($stats === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Batch not found.';
    exit;
}

$csv = StatsService::batchStatsToCsv($stats);
$safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $stats['batch_name'] ?? 'batch');
$safeName = trim($safeName, '_');
if ($safeName === '') {
    $safeName = 'batch';
}
$filename = $safeName . '_results_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

echo "\xEF\xBB\xBF";
echo $csv;
