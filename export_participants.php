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
$data = $batchManager->loadBatch($batchId, false);
if (!$data) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Batch not found.';
    exit;
}

$pdo = Database::pdo();
$stmt = $pdo->prepare(
    'SELECT id, name, pin, COALESCE(email, \'\') AS email
     FROM participants
     WHERE batch_id = ?
     ORDER BY id'
);
$stmt->execute([$batchId]);
$participants = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$rowCount = count($participants) + 1;

$xmlCell = static function (int $columnIndex, string $value): string {
    return '<Cell ss:Index="' . $columnIndex . '"><Data ss:Type="String">'
        . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        . '</Data></Cell>';
};

$xmlRow = static function (array $values) use ($xmlCell): string {
    $cells = '';
    foreach (array_values($values) as $i => $value) {
        $cells .= $xmlCell($i + 1, (string) $value);
    }

    return '<Row>' . $cells . '</Row>';
};

$rows = [];
$rows[] = $xmlRow(['Name', 'Email', 'Participant ID', 'PIN']);
foreach ($participants as $p) {
    $rows[] = $xmlRow([
        (string) ($p['name'] ?? ''),
        (string) ($p['email'] ?? ''),
        (string) ($p['id'] ?? ''),
        (string) ($p['pin'] ?? ''),
    ]);
}

$table = '<Table ss:ExpandedColumnCount="4" ss:ExpandedRowCount="' . $rowCount . '">'
    . '<Column ss:Index="1" ss:Width="140"/>'
    . '<Column ss:Index="2" ss:Width="200"/>'
    . '<Column ss:Index="3" ss:Width="100"/>'
    . '<Column ss:Index="4" ss:Width="80"/>'
    . implode('', $rows)
    . '</Table>';

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
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo "\xEF\xBB\xBF";
echo $workbook;
