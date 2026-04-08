<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';

$quizManager = new QuizManager();
$batchManager = new BatchManager();
$participant = new Participant();
$statsService = new StatsService($quizManager, $batchManager);

function teacher_session_key(string $batchId): string
{
    return 'teacher_batch_' . $batchId;
}

function require_teacher_batch(string $batchId): bool
{
    return !empty($_SESSION[teacher_session_key($batchId)]);
}

function json_public_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($scheme . '://' . $host . $base, '/');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$publicActions = [
    'site_login',
    'site_logout',
    'site_status',
    'list_active_quizzes',
    'get_quiz_public',
    'student_stats',
    'logout_student_stats',
    'verify_pin',
    'start_quiz',
    'submit_quiz',
];

if ($action !== '' && !in_array($action, $publicActions, true)) {
    if (!SiteAuth::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Site login required.', 'needs_site_auth' => true]);
        exit;
    }
}

try {
    switch ($action) {

        case 'site_status':
            echo json_encode([
                'success' => true,
                'configured' => SiteAuth::isConfigured(),
                'authenticated' => SiteAuth::isAuthenticated(),
            ]);
            break;

        case 'site_login':
            $password = $_POST['password'] ?? '';
            if (!SiteAuth::isConfigured()) {
                echo json_encode(['success' => false, 'error' => 'Site password is not configured. Copy config.local.php.example to config.local.php.']);
                exit;
            }
            if (!is_string($password) || $password === '' || !SiteAuth::verifyPassword($password)) {
                echo json_encode(['success' => false, 'error' => 'Invalid password.']);
                exit;
            }
            SiteAuth::login();
            echo json_encode(['success' => true]);
            break;

        case 'site_logout':
            SiteAuth::logout();
            echo json_encode(['success' => true]);
            break;

        case 'create_batch':
            $name = trim($_POST['name'] ?? '');
            $teacherName = trim($_POST['teacher_name'] ?? '');
            $teacherPassword = $_POST['teacher_password'] ?? '';
            if ($name === '' || $teacherName === '' || strlen($teacherPassword) < 4) {
                echo json_encode(['success' => false, 'error' => 'Batch name, teacher name, and a password (min 4 characters) are required.']);
                exit;
            }
            $batch = $batchManager->createBatch($name, $teacherName, $teacherPassword);
            $info = $batch['batch_info'];
            unset($info['teacher_password']);
            echo json_encode(['success' => true, 'batch' => $info]);
            break;

        case 'list_batches':
            echo json_encode(['success' => true, 'batches' => $batchManager->listBatches()]);
            break;

        case 'batch_meta':
            $batchId = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
            if ($batchId === '') {
                echo json_encode(['success' => false, 'error' => 'Batch ID required.']);
                exit;
            }
            $data = $batchManager->loadBatch($batchId);
            if (!$data) {
                echo json_encode(['success' => false, 'error' => 'Batch not found.']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'name' => $data['batch_info']['name'],
                'teacher_name' => $data['batch_info']['teacher_name'] ?? 'Teacher',
            ]);
            break;

        case 'login_batch':
            $batchId = $_POST['batch_id'] ?? '';
            $password = $_POST['teacher_password'] ?? '';
            if ($batchId === '' || $password === '') {
                echo json_encode(['success' => false, 'error' => 'Batch and password required.']);
                exit;
            }
            if (!$batchManager->verifyTeacherPassword($batchId, $password)) {
                echo json_encode(['success' => false, 'error' => 'Invalid password.']);
                exit;
            }
            $_SESSION[teacher_session_key($batchId)] = true;
            echo json_encode(['success' => true]);
            break;

        case 'logout_batch':
            $batchId = $_POST['batch_id'] ?? '';
            if ($batchId !== '') {
                unset($_SESSION[teacher_session_key($batchId)]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'get_batch':
            $batchId = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
            if ($batchId === '') {
                echo json_encode(['success' => false, 'error' => 'Batch ID required.']);
                exit;
            }
            if (!require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'needs_auth' => true, 'error' => 'Please sign in with the teacher password.']);
                exit;
            }
            $data = $batchManager->loadBatch($batchId);
            if (!$data) {
                echo json_encode(['success' => false, 'error' => 'Batch not found.']);
                exit;
            }
            unset($data['batch_info']['teacher_password']);
            $data['quizzes'] = $quizManager->listQuizzesForBatch($batchId);
            echo json_encode(['success' => true, 'batch' => $data]);
            break;

        case 'delete_batch':
            $batchId = $_POST['batch_id'] ?? '';
            if ($batchId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            $quizManager->deleteAllQuizzesForBatch($batchId);
            unset($_SESSION[teacher_session_key($batchId)]);
            if ($batchManager->deleteBatch($batchId)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete batch.']);
            }
            break;

        case 'add_batch_participant':
            $batchId = $_POST['batch_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            if ($batchId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Name is required.']);
                exit;
            }
            $p = $participant->addParticipant($batchId, $name);
            if ($p) {
                echo json_encode(['success' => true, 'participant' => $p]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Could not add participant.']);
            }
            break;

        case 'remove_batch_participant':
            $batchId = $_POST['batch_id'] ?? '';
            $participantId = $_POST['participant_id'] ?? '';
            if ($batchId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            if ($participant->removeParticipant($batchId, $participantId)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove.']);
            }
            break;

        case 'update_batch_participant_name':
            $batchId = $_POST['batch_id'] ?? '';
            $participantId = $_POST['participant_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            if ($batchId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            if ($participantId === '' || $name === '') {
                echo json_encode(['success' => false, 'error' => 'Participant and name are required.']);
                exit;
            }
            if ($participant->updateParticipantName($batchId, $participantId, $name)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Could not update name.']);
            }
            break;

        case 'create_quiz_in_batch':
            $batchId = $_POST['batch_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $timeLimit = (int)($_POST['time_limit'] ?? 0);
            $totalDisplay = (int)($_POST['total_display'] ?? 0);

            if ($batchId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            if ($name === '' || $timeLimit <= 0 || $totalDisplay <= 0) {
                echo json_encode(['success' => false, 'error' => 'Quiz name, time limit, and questions to display are required.']);
                exit;
            }
            if (!isset($_FILES['questions_file']) || $_FILES['questions_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'Please upload a valid JSON question file.']);
                exit;
            }
            $raw = file_get_contents($_FILES['questions_file']['tmp_name']);
            $questions = json_decode($raw, true);
            if (!is_array($questions) || $questions === []) {
                echo json_encode(['success' => false, 'error' => 'Invalid JSON: expected a non-empty array of questions.']);
                exit;
            }
            $normalized = $quizManager->validateAndNormalizeQuestions($questions);
            if ($normalized === null) {
                echo json_encode(['success' => false, 'error' => 'Question format invalid. Each item needs id, question, options, and answer (or correct).']);
                exit;
            }

            $quiz = $quizManager->createQuizForBatch($batchId, $name, $timeLimit, $totalDisplay, $normalized);
            $slug = $quiz['quiz_info']['public_slug'];
            $link = json_public_base_url() . '/take_quiz.php?q=' . rawurlencode($slug);
            $qi = $quiz['quiz_info'];
            unset($qi['batch_id']);
            echo json_encode([
                'success' => true,
                'quiz' => $qi,
                'quiz_link' => $link,
                'question_count' => count($normalized),
            ]);
            break;

        case 'list_quizzes_batch':
            $batchId = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
            if ($batchId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            echo json_encode(['success' => true, 'quizzes' => $quizManager->listQuizzesForBatch($batchId)]);
            break;

        case 'get_quiz_teacher':
            $batchId = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
            $quizId = $_POST['quiz_id'] ?? $_GET['quiz_id'] ?? '';
            if ($batchId === '' || $quizId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            $data = $quizManager->loadQuiz($quizId);
            if (!$data || ($data['quiz_info']['batch_id'] ?? '') !== $batchId) {
                echo json_encode(['success' => false, 'error' => 'Quiz not found.']);
                exit;
            }
            if (isset($data['quiz_info']['admin_password'])) {
                unset($data['quiz_info']['admin_password']);
            }
            echo json_encode(['success' => true, 'quiz' => $data]);
            break;

        case 'set_quiz_status':
            $batchId = $_POST['batch_id'] ?? '';
            $quizId = $_POST['quiz_id'] ?? '';
            $status = trim($_POST['status'] ?? '');
            if ($batchId === '' || $quizId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            $data = $quizManager->loadQuiz($quizId);
            if (!$data || ($data['quiz_info']['batch_id'] ?? '') !== $batchId) {
                echo json_encode(['success' => false, 'error' => 'Quiz not found.']);
                exit;
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                echo json_encode(['success' => false, 'error' => 'Invalid status.']);
                exit;
            }
            if ($quizManager->setQuizStatus($quizId, $status)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update.']);
            }
            break;

        case 'delete_quiz_teacher':
            $batchId = $_POST['batch_id'] ?? '';
            $quizId = $_POST['quiz_id'] ?? '';
            if ($batchId === '' || $quizId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            $data = $quizManager->loadQuiz($quizId);
            if (!$data || ($data['quiz_info']['batch_id'] ?? '') !== $batchId) {
                echo json_encode(['success' => false, 'error' => 'Quiz not found.']);
                exit;
            }
            if ($quizManager->deleteQuiz($quizId)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete quiz.']);
            }
            break;

        case 'student_stats':
            $pin = preg_replace('/\D/', '', $_POST['pin'] ?? '');
            if (strlen($pin) === 6) {
                $found = $participant->findParticipantByPin($pin);
                if (!$found) {
                    echo json_encode(['success' => false, 'error' => 'Invalid PIN.']);
                    exit;
                }
                $bid = $found['batch_id'];
                $pid = $found['participant']['id'];
                $_SESSION['student_identity'] = [
                    'batch_id' => $bid,
                    'participant_id' => $pid,
                    'participant_name' => $found['participant']['name'] ?? '',
                ];
            } elseif (!empty($_SESSION['student_identity']['batch_id']) && !empty($_SESSION['student_identity']['participant_id'])) {
                $bid = $_SESSION['student_identity']['batch_id'];
                $pid = $_SESSION['student_identity']['participant_id'];
            } else {
                echo json_encode(['success' => false, 'error' => 'PIN required', 'needs_pin' => true]);
                exit;
            }
            $stats = $statsService->getParticipantStats($bid, $pid);
            if ($stats === null) {
                echo json_encode(['success' => false, 'error' => 'Could not load stats.']);
                exit;
            }
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'logout_student_stats':
            unset($_SESSION['student_identity']);
            echo json_encode(['success' => true]);
            break;

        case 'batch_stats':
            $batchId = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
            if ($batchId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            $stats = $statsService->getBatchStats($batchId);
            if ($stats === null) {
                echo json_encode(['success' => false, 'error' => 'Batch not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'participant_detail_teacher':
            $batchId = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
            $participantId = $_POST['participant_id'] ?? $_GET['participant_id'] ?? '';
            if ($batchId === '' || $participantId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            $detail = $statsService->getTeacherParticipantDetail($batchId, $participantId);
            if ($detail === null) {
                echo json_encode(['success' => false, 'error' => 'Participant not found in this batch.']);
                exit;
            }
            echo json_encode(['success' => true, 'detail' => $detail]);
            break;

        case 'quiz_stats_teacher':
            $batchId = $_POST['batch_id'] ?? $_GET['batch_id'] ?? '';
            $quizId = $_POST['quiz_id'] ?? $_GET['quiz_id'] ?? '';
            if ($batchId === '' || $quizId === '' || !require_teacher_batch($batchId)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }
            $stats = $statsService->getQuizDetailStats($batchId, $quizId);
            if ($stats === null) {
                echo json_encode(['success' => false, 'error' => 'Quiz not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'list_active_quizzes':
            echo json_encode(['success' => true, 'quizzes' => $quizManager->listActiveQuizzesWithBatchNames()]);
            break;

        case 'get_quiz_public':
            $slug = trim($_POST['quiz_slug'] ?? $_GET['quiz_slug'] ?? '');
            if ($slug === '') {
                echo json_encode(['success' => false, 'error' => 'Invalid link.']);
                exit;
            }
            $data = $quizManager->loadQuizBySlug($slug);
            if (!$data) {
                echo json_encode(['success' => false, 'error' => 'Quiz not found.']);
                exit;
            }
            $qi = $data['quiz_info'];
            echo json_encode([
                'success' => true,
                'quiz' => [
                    'name' => $qi['name'],
                    'time_limit' => $qi['time_limit'],
                    'status' => $qi['status'] ?? 'inactive',
                    'question_count' => count($data['questions'] ?? []),
                ],
            ]);
            break;

        case 'verify_pin':
            $pin = $_POST['pin'] ?? '';
            $slug = trim($_POST['quiz_slug'] ?? '');
            $v = $participant->verifyPinForQuiz($pin, $slug);
            if (!empty($v['ok'])) {
                $_SESSION['take_quiz_id'] = $v['quiz_id'];
                $_SESSION['take_batch_id'] = $v['batch_id'];
                $_SESSION['take_participant_id'] = $v['participant_id'];
                $_SESSION['student_identity'] = [
                    'batch_id' => $v['batch_id'],
                    'participant_id' => $v['participant_id'],
                    'participant_name' => $v['participant_name'],
                ];
                echo json_encode(['success' => true, 'quiz_id' => $v['quiz_id']]);
                break;
            }
            $finished = ($v['reason'] ?? '') === 'finished';
            if ($finished && !empty($v['batch_id']) && !empty($v['participant_id'])) {
                $_SESSION['student_identity'] = [
                    'batch_id' => $v['batch_id'],
                    'participant_id' => $v['participant_id'],
                    'participant_name' => $v['participant_name'] ?? '',
                ];
            }
            echo json_encode([
                'success' => false,
                'error' => $v['message'] ?? 'Verification failed.',
                'reason' => $v['reason'] ?? 'unknown',
                'finished' => $finished,
            ]);
            break;

        case 'start_quiz':
            $quizId = $_SESSION['take_quiz_id'] ?? '';
            $batchId = $_SESSION['take_batch_id'] ?? '';
            $participantId = $_SESSION['take_participant_id'] ?? '';
            if ($quizId === '' || $batchId === '' || $participantId === '') {
                echo json_encode(['success' => false, 'error' => 'Session expired. Please enter your PIN again.']);
                exit;
            }
            $quizData = $participant->startQuiz($quizId, $batchId, $participantId);
            if ($quizData) {
                echo json_encode(['success' => true, 'data' => $quizData]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to start the quiz. It may be inactive or already completed.']);
            }
            break;

        case 'submit_quiz':
            $quizId = $_SESSION['take_quiz_id'] ?? '';
            $batchId = $_SESSION['take_batch_id'] ?? '';
            $participantId = $_SESSION['take_participant_id'] ?? '';
            if ($quizId === '' || $batchId === '' || $participantId === '') {
                echo json_encode(['success' => false, 'error' => 'Session expired.']);
                exit;
            }
            if ($participant->checkTimeExpired($quizId, $batchId, $participantId)) {
                // still grade submitted answers
            }
            $answersRaw = $_POST['answers'] ?? '[]';
            $answers = json_decode($answersRaw, true);
            if (!is_array($answers)) {
                $answers = [];
            }
            $results = $participant->submitQuiz($quizId, $batchId, $participantId, $answers);
            if ($results) {
                unset($_SESSION['take_quiz_id'], $_SESSION['take_batch_id'], $_SESSION['take_participant_id']);
                if (!empty($_SESSION['student_identity'])) {
                    $_SESSION['student_identity']['batch_id'] = $batchId;
                    $_SESSION['student_identity']['participant_id'] = $participantId;
                    if (!empty($results['name'])) {
                        $_SESSION['student_identity']['participant_name'] = $results['name'];
                    }
                }
                echo json_encode(['success' => true, 'results' => $results]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to submit.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
