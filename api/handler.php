<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/QuizManager.php';
require_once __DIR__ . '/../classes/Participant.php';

$quizManager = new QuizManager();
$participant = new Participant();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ─── Admin: Create Quiz ─────────────────────────────────────────
        case 'create_quiz':
            $name = trim($_POST['name'] ?? '');
            $timeLimit = (int)($_POST['time_limit'] ?? 0);
            $totalDisplay = (int)($_POST['total_display'] ?? 0);
            $adminPassword = $_POST['admin_password'] ?? '';

            if (empty($name) || $timeLimit <= 0 || $totalDisplay <= 0 || empty($adminPassword)) {
                echo json_encode(['success' => false, 'error' => 'All fields are required and must be valid.']);
                exit;
            }

            $quiz = $quizManager->createQuiz($name, $timeLimit, $totalDisplay, $adminPassword);
            echo json_encode(['success' => true, 'quiz' => $quiz['quiz_info']]);
            break;

        // ─── Admin: List Quizzes ────────────────────────────────────────
        case 'list_quizzes':
            $quizzes = $quizManager->listQuizzes();
            echo json_encode(['success' => true, 'quizzes' => $quizzes]);
            break;

        // ─── Admin: Get Quiz Details ────────────────────────────────────
        case 'get_quiz':
            $quizId = $_POST['quiz_id'] ?? $_GET['quiz_id'] ?? '';
            $password = $_POST['admin_password'] ?? $_GET['admin_password'] ?? '';

            if (empty($quizId)) {
                echo json_encode(['success' => false, 'error' => 'Quiz ID required.']);
                exit;
            }

            // Check session first
            if (!empty($_SESSION['admin_quiz_' . $quizId])) {
                $data = $quizManager->loadQuiz($quizId);
                if ($data) {
                    unset($data['quiz_info']['admin_password']);
                    echo json_encode(['success' => true, 'quiz' => $data]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Quiz not found.']);
                }
                break;
            }

            if (empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Admin password required.', 'needs_auth' => true]);
                exit;
            }

            if (!$quizManager->verifyAdminPassword($quizId, $password)) {
                echo json_encode(['success' => false, 'error' => 'Invalid admin password.']);
                exit;
            }

            $_SESSION['admin_quiz_' . $quizId] = true;
            $data = $quizManager->loadQuiz($quizId);
            unset($data['quiz_info']['admin_password']);
            echo json_encode(['success' => true, 'quiz' => $data]);
            break;

        // ─── Admin: Upload Questions ────────────────────────────────────
        case 'upload_questions':
            $quizId = $_POST['quiz_id'] ?? '';

            if (empty($quizId) || empty($_SESSION['admin_quiz_' . $quizId])) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }

            if (!isset($_FILES['questions_file'])) {
                echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
                exit;
            }

            $fileContent = file_get_contents($_FILES['questions_file']['tmp_name']);
            $questions = json_decode($fileContent, true);

            if (!is_array($questions) || empty($questions)) {
                echo json_encode(['success' => false, 'error' => 'Invalid JSON file. Must be an array of question objects.']);
                exit;
            }

            if ($quizManager->uploadQuestions($quizId, $questions)) {
                echo json_encode(['success' => true, 'count' => count($questions)]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to upload. Check JSON format.']);
            }
            break;

        // ─── Admin: Add Participant ─────────────────────────────────────
        case 'add_participant':
            $quizId = $_POST['quiz_id'] ?? '';
            $name = trim($_POST['name'] ?? '');

            if (empty($quizId) || empty($_SESSION['admin_quiz_' . $quizId])) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }

            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Participant name is required.']);
                exit;
            }

            $result = $participant->addParticipant($quizId, $name);
            if ($result) {
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                    . '://' . $_SERVER['HTTP_HOST']
                    . dirname(dirname($_SERVER['SCRIPT_NAME']));
                $result['quiz_link'] = rtrim($baseUrl, '/') . '/take_quiz.php?uid=' . $result['token'];
                echo json_encode(['success' => true, 'participant' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add. Participant ID may already exist.']);
            }
            break;

        // ─── Admin: Remove Participant ──────────────────────────────────
        case 'remove_participant':
            $quizId = $_POST['quiz_id'] ?? '';
            $token = $_POST['token'] ?? '';

            if (empty($quizId) || empty($_SESSION['admin_quiz_' . $quizId])) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }

            if ($participant->removeParticipant($quizId, $token)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove participant.']);
            }
            break;

        // ─── Admin: Delete Quiz ─────────────────────────────────────────
        case 'delete_quiz':
            $quizId = $_POST['quiz_id'] ?? '';

            if (empty($quizId) || empty($_SESSION['admin_quiz_' . $quizId])) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
                exit;
            }

            if ($quizManager->deleteQuiz($quizId)) {
                unset($_SESSION['admin_quiz_' . $quizId]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete quiz.']);
            }
            break;

        // ─── Participant: Verify PIN ────────────────────────────────────
        case 'verify_pin':
            $token = $_POST['token'] ?? '';
            $pin = $_POST['pin'] ?? '';

            if (empty($token) || empty($pin)) {
                echo json_encode(['success' => false, 'error' => 'Token and PIN required.']);
                exit;
            }

            if (!preg_match('/^\d{6}$/', $pin)) {
                echo json_encode(['success' => false, 'error' => 'PIN must be exactly 6 digits.']);
                exit;
            }

            $result = $participant->verifyPin($token, $pin);
            if ($result) {
                if ($result['participant']['status'] === 'finished') {
                    echo json_encode(['success' => false, 'error' => 'You have already completed this quiz.', 'finished' => true]);
                    exit;
                }
                $_SESSION['participant_token'] = $token;
                $_SESSION['participant_quiz_id'] = $result['quiz_id'];
                echo json_encode(['success' => true, 'quiz_id' => $result['quiz_id']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid PIN. Please try again.']);
            }
            break;

        // ─── Participant: Start / Resume Quiz ───────────────────────────
        case 'start_quiz':
            $token = $_SESSION['participant_token'] ?? '';
            $quizId = $_SESSION['participant_quiz_id'] ?? '';

            if (empty($token) || empty($quizId)) {
                echo json_encode(['success' => false, 'error' => 'Session expired. Please re-enter your PIN.']);
                exit;
            }

            $quizData = $participant->startQuiz($quizId, $token);
            if ($quizData) {
                echo json_encode(['success' => true, 'data' => $quizData]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to start quiz. It may already be completed.']);
            }
            break;

        // ─── Participant: Submit Quiz ───────────────────────────────────
        case 'submit_quiz':
            $token = $_SESSION['participant_token'] ?? '';
            $quizId = $_SESSION['participant_quiz_id'] ?? '';

            if (empty($token) || empty($quizId)) {
                echo json_encode(['success' => false, 'error' => 'Session expired.']);
                exit;
            }

            // Server-side time check
            if ($participant->checkTimeExpired($quizId, $token)) {
                // Auto-submit with whatever answers were provided
            }

            $answersRaw = $_POST['answers'] ?? '[]';
            $answers = json_decode($answersRaw, true);
            if (!is_array($answers)) $answers = [];

            $results = $participant->submitQuiz($quizId, $token, $answers);
            if ($results) {
                echo json_encode(['success' => true, 'results' => $results]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to submit quiz.']);
            }
            break;

        // ─── Admin: Logout ──────────────────────────────────────────────
        case 'admin_logout':
            $quizId = $_POST['quiz_id'] ?? '';
            if (!empty($quizId)) {
                unset($_SESSION['admin_quiz_' . $quizId]);
            }
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
