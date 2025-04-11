<?php
session_start();
require_once './config/db.php';

$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? '';

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['success' => false, 'error' => 'User not authenticated']);
    }
    return $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'chat' && $action) {
        require_once './controllers/ChatController.php';
        $chatController = new ChatController($conn);
        $user_id = requireAuth();

        switch ($action) {
            case 'add_friend':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $friend_id = $_POST['friend_id'] ?? null;
                    if (!$friend_id) sendJsonResponse(['success' => false, 'error' => 'Friend ID is required']);
                    $result = $chatController->addFriend($user_id, $friend_id);
                    sendJsonResponse($result);
                }
                break;

            case 'accept_friend':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $friend_id = $_POST['friend_id'] ?? null;
                    if (!$friend_id) sendJsonResponse(['success' => false, 'error' => 'Friend ID is required']);
                    $result = $chatController->acceptFriend($user_id, $friend_id);
                    sendJsonResponse($result);
                }
                break;

            case 'delete_friend':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $friend_id = $_POST['friend_id'] ?? null;
                    if (!$friend_id) sendJsonResponse(['success' => false, 'error' => 'Friend ID is required']);
                    $result = $chatController->deleteFriend($user_id, $friend_id);
                    sendJsonResponse($result);
                }
                break;

            case 'search_users':
                if (isset($_GET['query'])) {
                    ob_clean();
                    $query = $_GET['query'];
                    $users = $chatController->searchUsers($query);
                    sendJsonResponse($users);
                }
                break;

            case 'get_messages':
                if (isset($_GET['friend_id'])) {
                    ob_clean();
                    $friend_id = $_GET['friend_id'];
                    $messages = $chatController->getMessages($user_id, $friend_id);
                    sendJsonResponse($messages);
                }
                break;

            case 'upload_file':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $receiver_id = $_POST['receiver_id'] ?? null;
                    $message = $_POST['message'] ?? '';
                    $file = $_FILES['file'] ?? null;
                    if (!$receiver_id || !$file) sendJsonResponse(['success' => false, 'error' => 'Receiver ID or file is required']);
                    $result = $chatController->uploadFile($user_id, $receiver_id, $message, $file);
                    sendJsonResponse($result);
                }
                break;

            case 'get_pending_requests':
                ob_clean();
                $requests = $chatController->getPendingRequests($user_id);
                sendJsonResponse($requests);
                break;

            case 'get_friends_with_latest':
                ob_clean();
                $query = $_GET['query'] ?? '';
                $friends = $chatController->getFriendsWithLatest($user_id, $query);
                sendJsonResponse($friends);
                break;
        }
    }

    if ($page === 'classroom' && $action) {
        require_once './controllers/ClassroomController.php';
        $classroomController = new ClassroomController($conn);
        $user_id = requireAuth();

        switch ($action) {
            case 'get_details':
                if (isset($_GET['classroom_id'])) {
                    ob_clean();
                    $classroom_id = $_GET['classroom_id'];
                    $details = $classroomController->getClassroomDetails($classroom_id);
                    sendJsonResponse($details);
                }
                break;

            case 'invite_users':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $classroom_id = $_POST['classroom_id'] ?? null;
                    $user_ids = $_POST['user_ids'] ?? [];
                    if (!$classroom_id || empty($user_ids)) sendJsonResponse(['success' => false, 'error' => 'Classroom ID or user IDs missing']);
                    $result = $classroomController->inviteUsers($classroom_id, $user_ids);
                    sendJsonResponse(['success' => $result]);
                }
                break;

            case 'delete_classroom':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $classroom_id = $_POST['classroom_id'] ?? null;
                    if (!$classroom_id) sendJsonResponse(['success' => false, 'error' => 'Classroom ID is required']);
                    $result = $classroomController->deleteClassroom($classroom_id);
                    sendJsonResponse(['success' => $result]);
                }
                break;

            case 'join':
                if (isset($_GET['code'])) {
                    ob_clean();
                    $code = $_GET['code'];
                    $result = $classroomController->joinClassroom($code);
                    sendJsonResponse($result);
                }
                break;

            case 'get_student_activities':
                if (isset($_GET['user_id']) && isset($_GET['classroom_id'])) {
                    ob_clean();
                    $student_id = $_GET['user_id'];
                    $classroom_id = $_GET['classroom_id'];
                    $stmt = $conn->prepare("
                        SELECT a.title, s.file_name, s.status, s.submitted_at 
                        FROM tb_classroom_activities a
                        LEFT JOIN tb_activity_submissions s ON a.id = s.activity_id AND s.user_id = ?
                        WHERE a.classroom_id = ?
                    ");
                    $stmt->bind_param("ii", $student_id, $classroom_id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    sendJsonResponse($result);
                }
                break;

            case 'search_members':
                if (isset($_GET['classroom_id']) && isset($_GET['query'])) {
                    ob_clean();
                    $classroom_id = $_GET['classroom_id'];
                    $query = "%" . $_GET['query'] . "%";
                    $stmt = $conn->prepare("
                        SELECT u.id, u.name, u.profile_image, cm.role 
                        FROM tb_classroom_members cm 
                        JOIN tb_users u ON cm.user_id = u.id 
                        WHERE cm.classroom_id = ? AND u.name LIKE ?
                    ");
                    $stmt->bind_param("is", $classroom_id, $query);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    sendJsonResponse($result);
                }
                break;

            case 'can_start_video_call':
                if (isset($_GET['classroom_id'])) {
                    ob_clean();
                    $classroom_id = $_GET['classroom_id'];
                    $canStart = $classroomController->canStartVideoCall($classroom_id);
                    sendJsonResponse(['success' => $canStart]);
                }
                break;

            case 'create_meeting':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $classroom_id = $_GET['classroom_id'] ?? null;
                    $type = $_GET['type'] ?? null;
                    $data = json_decode(file_get_contents('php://input'), true) ?? [];
                    $date = $data['date'] ?? null;
                    $time = $data['time'] ?? null;

                    if (!$classroom_id || !$type) {
                        sendJsonResponse(['success' => false, 'error' => 'Classroom ID or type missing']);
                    }

                    $result = $classroomController->createMeeting($classroom_id, $type, $date, $time);
                    sendJsonResponse($result);
                }
                break;

            case 'create_attendance':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $activity_id = $_POST['activity_id'] ?? null;
                    $classroom_id = $_POST['classroom_id'] ?? null;
                    $title = $_POST['title'] ?? null;
                    $start_time = $_POST['start_time'] ?? null;
                    $end_time = $_POST['end_time'] ?? null;
                    if (!$activity_id || !$classroom_id || !$title) {
                        sendJsonResponse(['success' => false, 'error' => 'Activity ID, Classroom ID, and Title are required']);
                    }
                    $result = $classroomController->createAttendance($activity_id, $classroom_id, $title, $start_time, $end_time);
                    sendJsonResponse($result);
                }
                break;

            case 'submit_attendance':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $attendance_id = $_POST['attendance_id'] ?? null;
                    $status = $_POST['status'] ?? null;
                    $photo = $_FILES['photo'] ?? null;
                    $latitude = $_POST['latitude'] ?? null;
                    $longitude = $_POST['longitude'] ?? null;
                    if (!$attendance_id || !$status || !$photo) {
                        sendJsonResponse(['success' => false, 'error' => 'Attendance ID, Status, and Photo are required']);
                    }
                    $result = $classroomController->submitAttendance($attendance_id, $status, $photo, $latitude, $longitude);
                    sendJsonResponse($result);
                }
                break;

            case 'reset_attendance':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $attendance_id = $_POST['attendance_id'] ?? null;
                    $user_id = $_POST['user_id'] ?? null;
                    if (!$attendance_id) {
                        sendJsonResponse(['success' => false, 'error' => 'Attendance ID is required']);
                    }
                    $result = $classroomController->resetAttendance($attendance_id, $user_id);
                    sendJsonResponse($result);
                }
                break;

            case 'get_attendance_records':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    ob_clean();
                    $attendance_id = $_GET['attendance_id'] ?? null;
                    if (!$attendance_id) {
                        sendJsonResponse(['success' => false, 'error' => 'Attendance ID is required']);
                    }
                    $records = $classroomController->getAttendanceRecords($attendance_id);
                    sendJsonResponse($records);
                }
                break;

            case 'get_classroom_members':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    ob_clean();
                    $classroom_id = $_GET['classroom_id'] ?? null;
                    if (!$classroom_id) sendJsonResponse(['success' => false, 'error' => 'Classroom ID is required']);
                    $members = $classroomController->getClassroomMembers($classroom_id);
                    sendJsonResponse($members);
                }
                break;

            case 'get_activity_details':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    ob_clean();
                    $activity_id = $_GET['activity_id'] ?? null;
                    if (!$activity_id) sendJsonResponse(['success' => false, 'error' => 'Activity ID is required']);
                    $activity = $classroomController->getActivityDetails($activity_id);
                    sendJsonResponse($activity);
                }
                break;

            case 'get_attendance_details':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    ob_clean();
                    $attendance_id = $_GET['attendance_id'] ?? null;
                    if (!$attendance_id) sendJsonResponse(['success' => false, 'error' => 'Attendance ID is required']);
                    $attendance = $classroomController->getAttendanceDetails($attendance_id);
                    sendJsonResponse($attendance);
                }
                break;

            case 'update_activity':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $activity_id = $_POST['activity_id'] ?? null;
                    $title = $_POST['edit_activity_title'] ?? null;
                    $description = $_POST['edit_activity_description'] ?? null;
                    $content = $_POST['edit_activity_content'] ?? null;
                    $file = $_FILES['edit_activity_file'] ?? null;

                    if (!$activity_id || !$title) {
                        sendJsonResponse(['success' => false, 'error' => 'Activity ID and Title are required']);
                    }

                    $result = $classroomController->updateActivity($activity_id, $title, $description, $content, $file);
                    sendJsonResponse($result);
                }
                break;

            case 'update_attendance':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $attendance_id = $_POST['attendance_id'] ?? null;
                    $title = $_POST['edit_attendance_title'] ?? null;
                    $start_time = $_POST['edit_attendance_start'] ?? null;
                    $end_time = $_POST['edit_attendance_end'] ?? null;

                    if (!$attendance_id || !$title || !$start_time || !$end_time) {
                        sendJsonResponse(['success' => false, 'error' => 'Attendance ID, Title, Start Time, and End Time are required']);
                    }

                    $result = $classroomController->updateAttendance($attendance_id, $title, $start_time, $end_time);
                    sendJsonResponse($result);
                }
                break;

            case 'create_assignment':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $classroom_id = $_POST['classroom_id'] ?? null;
                    $activity_id = $_POST['activity_id'] ?? null;
                    $title = $_POST['assignment_title'] ?? null;
                    $description = $_POST['assignment_description'] ?? null;
                    $due_date = $_POST['due_date'] ?? null;
                    $content = $_POST['assignment_content'] ?? null;
                    $file = $_FILES['assignment_file'] ?? null;

                    if (!$classroom_id || !$activity_id || !$title) {
                        sendJsonResponse(['success' => false, 'error' => 'Classroom ID, Activity ID, dan Title diperlukan']);
                    }

                    $result = $classroomController->createAssignment($classroom_id, $activity_id, $title, $description, $due_date, $content, $file);
                    sendJsonResponse($result);
                }
                break;

            case 'get_assignment_details':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    ob_clean();
                    $assignment_id = $_GET['assignment_id'] ?? null;
                    if (!$assignment_id) sendJsonResponse(['success' => false, 'error' => 'Assignment ID diperlukan']);
                    $assignment = $classroomController->getAssignmentDetails($assignment_id);
                    sendJsonResponse($assignment);
                }
                break;

            case 'update_assignment':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $assignment_id = $_POST['assignment_id'] ?? null;
                    $activity_id = $_POST['edit_assignment_activity'] ?? null;
                    $title = $_POST['edit_assignment_title'] ?? null;
                    $description = $_POST['edit_assignment_description'] ?? null;
                    $due_date = $_POST['edit_assignment_due_date'] ?? null;
                    $content = $_POST['edit_assignment_content'] ?? null;
                    $file = $_FILES['edit_assignment_file'] ?? null;

                    if (!$assignment_id || !$activity_id || !$title) {
                        sendJsonResponse(['success' => false, 'error' => 'Assignment ID, Activity ID, dan Title diperlukan']);
                    }

                    $result = $classroomController->updateAssignment($assignment_id, $activity_id, $title, $description, $due_date, $content, $file);
                    sendJsonResponse($result);
                }
                break;

            case 'submit_assignment':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $assignment_id = $_POST['assignment_id'] ?? null;
                    $submission_type = $_POST['submission_type'] ?? null;
                    $submission_content = $_POST['submission_content'] ?? '';
                    $file = $_FILES['submission_file'] ?? null;

                    if (!$assignment_id || !$submission_type) {
                        sendJsonResponse(['success' => false, 'error' => 'Assignment ID dan tipe pengumpulan diperlukan']);
                    }

                    $result = $classroomController->submitAssignment($assignment_id, $user_id, $submission_type, $submission_content, $file);
                    sendJsonResponse($result);
                }
                break;

            case 'get_assignment_submissions':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    ob_clean();
                    $assignment_id = $_GET['assignment_id'] ?? null;
                    if (!$assignment_id) sendJsonResponse(['success' => false, 'error' => 'Assignment ID diperlukan']);
                    $submissions = $classroomController->getAssignmentSubmissions($assignment_id);
                    sendJsonResponse($submissions);
                }
                break;

            case 'grade_assignment':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $submission_id = $_POST['submission_id'] ?? null;
                    $grade = $_POST['grade'] ?? null;
                    $feedback = $_POST['feedback'] ?? '';

                    if (!$submission_id || $grade === null) {
                        sendJsonResponse(['success' => false, 'error' => 'Submission ID dan grade diperlukan']);
                    }

                    $result = $classroomController->gradeAssignment($submission_id, $grade, $feedback);
                    sendJsonResponse($result);
                }
                break;

            case 'reset_assignment_submission':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ob_clean();
                    $submission_id = $_POST['submission_id'] ?? null;
                    if (!$submission_id) {
                        sendJsonResponse(['success' => false, 'error' => 'Submission ID diperlukan']);
                    }
                    $result = $classroomController->resetAssignmentSubmission($submission_id);
                    sendJsonResponse($result);
                }
                break;
        }
    }

    if ($page === 'auth' && $action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once './controllers/AuthController.php';
        $authController = new AuthController($conn);

        ob_clean();
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $result = $authController->login($email, $password);
        sendJsonResponse(is_string($result) ? ['success' => false, 'error' => $result] : ['success' => true]);
    }
}

function loadPage($page, $conn) {
    $isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    $isLoggedIn = isset($_SESSION['user_id']);
    $filePath = "file_/{$page}.php";

    if ($page === 'classroom' && isset($_GET['code']) && $isLoggedIn) {
        require_once './controllers/ClassroomController.php';
        $classroomController = new ClassroomController($conn);
        $code = $_GET['code'];
        $result = $classroomController->joinClassroom($code);
        if ($result['success']) {
            header("Location: index.php?page=classroom&classroom_id=" . $result['classroom_id']);
            exit;
        } else {
            $GLOBALS['join_error'] = $result['error'];
        }
    }

    $specialPages = ['logout', 'verify', 'video_call_meeting'];
    if (!file_exists($filePath) && !in_array($page, $specialPages)) {
        include 'file_/404/not_found_1.php';
        return;
    }

    switch ($page) {
        case 'home':
            include $filePath;
            break;
        case 'login':
        case 'signup':
        case 'verify':
            if ($isLoggedIn) {
                header('Location: index.php?page=home');
                exit;
            }
            include $filePath;
            break;
        case 'logout':
            session_destroy();
            header('Location: index.php?page=login');
            exit;
        case 'profile':
        case 'change_password':
        case 'classroom_dashboard':
        case 'classroom':
        case 'video_call_meeting':
            if ($isLoggedIn) {
                include $filePath;
            } else {
                header('Location: index.php?page=login');
                exit;
            }
            break;
        case 'admin_dashboard':
            if ($isLoggedIn && $isAdmin) {
                include $filePath;
            } else {
                include 'file_/404/not_found_1.php';
            }
            break;
        case 'chat':
            if ($isLoggedIn) {
                include $filePath;
            } else {
                include 'file_/404/not_found_1.php';
            }
            break;
        default:
            include 'file_/404/not_found_1.php';
            break;
    }
}

loadPage($page, $conn);
?>