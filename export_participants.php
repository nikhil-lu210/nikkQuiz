<?php

declare(strict_types=1);

/**
 * Download participant list as an Excel-compatible XML spreadsheet (.xls).
 * Opens in Microsoft Excel, LibreOffice, etc. (not plain CSV).
 */

session_start();

require_once __DIR__ . '/bootstrap.php';

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

$batchManager = new BatchManager();
$data = $batchManager->loadBatch($batchId);
if (!$data) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Batch not found.';
    exit;
}

$participants = $data['participants'] ?? [];

$xmlCell = static function (string $s): string {
    return '<Cell><Data ss:Type="String">' . htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Data></Cell>';
};

$rows = [];
$rows[] = '<Row>' . $xmlCell('Name') . $xmlCell('Email') . $xmlCell('Participant ID') . $xmlCell('PIN') . '</Row>';
foreach ($participants as $p) {
    $rows[] = '<Row>'
        . $xmlCell((string) ($p['name'] ?? ''))
        . $xmlCell((string) ($p['email'] ?? ''))
        . $xmlCell((string) ($p['id'] ?? ''))
        . $xmlCell((string) ($p['pin'] ?? ''))
        . '</Row>';
}

$table = '<Table>' . implode('', $rows) . '</Table>';

$workbook = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
    . '<?mso-application progid="Excel.Sheet"?>' . "\n"
    . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
    . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
    . 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
    . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" '
    . 'xmlns:html="http://www.w3.org/TR/REC-html40">'
    . '<Worksheet ss:Name="Participants">'
    . $table
    . '</Worksheet>'
    . '</Workbook>';

$safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $data['batch_info']['name'] ?? 'batch');
$safeName = trim($safeName, '_');
if ($safeName === '') {
    $safeName = 'batch';
}
$filename = $safeName . '_participants_' . date('Y-m-d_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

echo "\xEF\xBB\xBF";
echo $workbook;
