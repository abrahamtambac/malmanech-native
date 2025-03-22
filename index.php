<?php
session_start();
include_once './config/db.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle all AJAX requests before rendering the page
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'chat' && $action) {
        include_once './controllers/ChatController.php';
        $chatController = new ChatController($conn);

        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            exit();
        }

        $user_id = $_SESSION['user_id'];

        // Add friend (friend request)
        if ($action === 'add_friend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_clean();
            $friend_id = $_POST['friend_id'] ?? null;
            if (!$friend_id) {
                echo json_encode(['success' => false, 'error' => 'Friend ID is required']);
                exit();
            }
            $result = $chatController->addFriend($user_id, $friend_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        // Accept friend request
        if ($action === 'accept_friend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_clean();
            $friend_id = $_POST['friend_id'] ?? null;
            if (!$friend_id) {
                echo json_encode(['success' => false, 'error' => 'Friend ID is required']);
                exit();
            }
            $result = $chatController->acceptFriend($user_id, $friend_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        // Delete friend
        if ($action === 'delete_friend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_clean();
            $friend_id = $_POST['friend_id'] ?? null;
            if (!$friend_id) {
                echo json_encode(['success' => false, 'error' => 'Friend ID is required']);
                exit();
            }
            $result = $chatController->deleteFriend($user_id, $friend_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        // Search users
        if ($action === 'search_users' && isset($_GET['query'])) {
            ob_clean();
            $query = $_GET['query'];
            $users = $chatController->searchUsers($query);
            header('Content-Type: application/json');
            echo json_encode($users);
            exit();
        }

        // Get messages
        if ($action === 'get_messages' && isset($_GET['friend_id'])) {
            ob_clean();
            $friend_id = $_GET['friend_id'];
            $messages = $chatController->getMessages($user_id, $friend_id);
            header('Content-Type: application/json');
            echo json_encode($messages);
            exit();
        }

        // Upload file
        if ($action === 'upload_file' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_clean();
            $receiver_id = $_POST['receiver_id'] ?? null;
            $message = $_POST['message'] ?? '';
            $file = $_FILES['file'] ?? null;

            if (!$receiver_id || !$file) {
                echo json_encode(['success' => false, 'error' => 'Receiver ID or file is required']);
                exit();
            }

            $result = $chatController->uploadFile($user_id, $receiver_id, $message, $file);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        // Get pending friend requests
        if ($action === 'get_pending_requests') {
            ob_clean();
            $requests = $chatController->getPendingRequests($user_id);
            header('Content-Type: application/json');
            echo json_encode($requests);
            exit();
        }

        // Get friends with latest messages
        if ($action === 'get_friends_with_latest') {
            ob_clean();
            $query = $_GET['query'] ?? '';
            $friends = $chatController->getFriendsWithLatest($user_id, $query);
            header('Content-Type: application/json');
            echo json_encode($friends);
            exit();
        }
    }

    // Handle authentication-related AJAX requests
    if ($page === 'auth' && $action) {
        include_once './controllers/AuthController.php';
        $authController = new AuthController($conn);

        if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_clean();
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $result = $authController->login($email, $password);
            if (is_string($result)) {
                // Jika login gagal, kembalikan pesan error
                echo json_encode(['success' => false, 'error' => $result]);
            } else {
                // Jika berhasil, kembalikan sukses
                echo json_encode(['success' => true]);
            }
            header('Content-Type: application/json');
            exit();
        }
    }
}

// Function to load pages
function loadPage($page, $conn) {
    $isAdmin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
    $isLoggedIn = isset($_SESSION['user_id']);

    // Pastikan folder dan file ada
    $filePath = "file_/{$page}.php";
    if (!file_exists($filePath) && $page !== 'logout' && $page !== 'verify') {
        include 'file_/404/not_found_1.php';
        return;
    }

    switch ($page) {
        case 'home':
            include 'file_/home.php';
            break;
        case 'login':
            if ($isLoggedIn) {
                header('Location: index.php?page=home');
                exit();
            }
            include 'file_/login.php';
            break;
        case 'signup':
            if ($isLoggedIn) {
                header('Location: index.php?page=home');
                exit();
            }
            include 'file_/signup.php';
            break;
        case 'verify':
            if ($isLoggedIn) {
                header('Location: index.php?page=home');
                exit();
            }
            include 'file_/verify.php';
            break;
        case 'logout':
            session_destroy();
            header('Location: index.php?page=login');
            exit();
        case 'profile':
            if ($isLoggedIn) {
                include 'file_/profile.php';
            } else {
                header('Location: index.php?page=login');
                exit();
            }
            break;
        case 'change_password':
            if ($isLoggedIn) {
                include 'file_/change_password.php';
            } else {
                header('Location: index.php?page=login');
                exit();
            }
            break;
        case 'admin_dashboard':
            if ($isLoggedIn && $isAdmin) {
                include 'file_/admin_dashboard.php';
            } else {
                include 'file_/404/not_found_1.php';
            }
            break;
        case 'chat':
            if ($isLoggedIn) {
                include 'file_/chat.php';
            } else {
                include 'file_/404/not_found_1.php';
            }
            break;
        default:
            include 'file_/404/not_found_1.php';
            break;
    }
}

// Load the requested page
loadPage($page, $conn);
?>