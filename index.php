<?php
session_start();
require_once './config/db.php'; // Menggunakan require_once untuk memastikan file hanya dimuat sekali

$page = $_GET['page'] ?? 'home'; // Menggunakan null coalescing operator untuk lebih ringkas
$action = $_GET['action'] ?? '';

// Fungsi untuk mengirim respons JSON dan keluar
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Memeriksa autentikasi pengguna
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['success' => false, 'error' => 'User not authenticated']);
    }
    return $_SESSION['user_id'];
}

// Menangani AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Chat-related AJAX requests
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

    // Classroom-related AJAX requests
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
        }
    }

    // Auth-related AJAX requests
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

// Fungsi untuk memuat halaman
function loadPage($page, $conn) {
    $isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    $isLoggedIn = isset($_SESSION['user_id']);
    $filePath = "file_/{$page}.php";

    // Handle classroom join via code
    if ($page === 'classroom' && isset($_GET['code']) && $isLoggedIn) {
        require_once './controllers/ClassroomController.php';
        $classroomController = new ClassroomController($conn);
        $code = $_GET['code'];
        $result = $classroomController->joinClassroom($code);
        if ($result['success']) {
            header("Location: index.php?page=classroom&classroom_id=" . $result['classroom_id']);
            exit;
        } else {
            $GLOBALS['join_error'] = $result['error']; // Simpan error untuk ditampilkan
        }
    }

    // Daftar halaman yang tidak memerlukan file fisik
    $specialPages = ['logout', 'verify', 'video_call_meeting'];
    if (!file_exists($filePath) && !in_array($page, $specialPages)) {
        include 'file_/404/not_found_1.php';
        return;
    }

    // Penanganan halaman
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

// Memuat halaman
loadPage($page, $conn);
?>