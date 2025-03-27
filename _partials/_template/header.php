<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malmanech - Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="./_partials/css/style.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Rubik:ital,wght@0,300..900;1,300..900&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Product Sans';
            src: url('./fonts/ProductSans-Regular.ttf') format('truetype');
        }

        body {
            font-family: 'Product Sans';
            src: url('./fonts/ProductSans-Regular.ttf') format('truetype');
        }

        .dashboard-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card-widget {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }

        .card-widget:hover {
            transform: translateY(-2px);
        }

        .profile-card,
        .user-result {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0d6efd;
        }

        .meeting-item {
            background: #f5f7fa;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            transition: background 0.2s;
        }

        .meeting-item:hover {
            background: #e0e7f0;
        }

        .modal-content {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border-radius: 15px 15px 0 0;
        }

        .form-control:focus {
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
        }

        .invited-user-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #0d6efd;
            margin-right: 10px;
        }

        #search-results {
            max-height: 200px;
            overflow-y: auto;
        }

        .chat-modal .modal-dialog {
            max-width: 1000px;
            height: 60vh;
            margin: 0 auto;
            top: 15%;
        }

        .chat-modal .modal-content {
            height: 100%;
            border-radius: 15px;
            overflow: hidden;
        }

        .chat-modal-body {
            display: flex;
            height: calc(100% - 60px);
            padding: 0;
        }

        .friend-list {
            width: 300px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            overflow-y: auto;
        }

        .chat-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 10px 15px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            display: none;
            justify-content: space-between;
            align-items: center;
        }

        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
            background: #f0f2f5;
            max-width: 100%;
        }

        .chat-input {
            padding: 10px;
            background: #fff;
            border-top: 1px solid #e9ecef;
            display: none;
        }

        .chat-item {
            padding: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .chat-item:hover {
            background: #e9ecef;
        }

        .chat-item.active {
            background: #cce5ff;
        }

        .message {
            max-width: 70%;
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 15px;
            position: relative;
            animation: slideIn 0.3s ease;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 300px;
        }

        .message.sent {
            background: #0056b3;
            color: white;
            margin-left: auto;
        }

        .message.received {
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .message::after {
            content: '';
            position: absolute;
            bottom: -5px;
            width: 10px;
            height: 10px;
        }

        .message.sent::after {
            right: 10px;
            background: #0056b3;
            clip-path: polygon(0 0, 100% 0, 100% 100%);
        }

        .message.received::after {
            left: 10px;
            background: #fff;
            clip-path: polygon(0 0, 100% 100%, 0 100%);
        }

        .message .timestamp {
            font-size: 0.75em;
            opacity: 0.7;
            margin-top: 5px;
            display: flex;
            justify-content: flex-end;
        }

        .message .attachment {
            margin-top: 5px;
            display: flex;
            align-items: center;
            color: #007bff;
            text-decoration: none;
        }

        .message.sent .attachment {
            color: #fff;
        }

        .message .attachment i {
            margin-right: 5px;
        }

        .last-seen {
            font-size: 0.8em;
            color: #666;
            display: block;
            margin-top: 2px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-dot.online {
            background-color: #28a745;
        }

        .status-dot.offline {
            background-color: #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .notification-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }

        .message .file-details {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        .message.sent .file-details {
            color: #ddd;
        }

        .message .file-details .file-name {
            font-weight: bold;
        }

        .message .file-details .file-type {
            margin-left: 5px;
        }

        .message .file-details .file-size {
            float: right;
        }

        .message .file-divider {
            border-top: 1px solid #ccc;
            margin: 5px 0;
        }

        .message.sent .file-divider {
            border-color: #88b7ff;
        }

        .message .file-download {
            display: flex;
            align-items: center;
            color: #007bff;
            text-decoration: none;
            font-size: 0.9em;
        }

        .message.sent .file-download {
            color: #fff;
        }

        .message .file-download i {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-primary ">
        <div class="container-fluid">
            <a class="navbar-brand text-white fw-bold" href="index.php?page=home">
                <h1 class="mb-0"><i class="bi bi-fingerprint text-warning"></i><b> Mal + </b></h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll"
                aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarScroll">

                <?php
                include_once './config/db.php';
                include_once './controllers/AuthController.php';
                include_once './controllers/ChatController.php';

                $auth = new AuthController($conn);
                $chatController = new ChatController($conn);
                $currentUser = $auth->getCurrentUser();

                if ($currentUser) {
                    $profileImage = !empty($currentUser['profile_image'])
                        ? './upload/image/' . $currentUser['profile_image']
                        : './image/robot-ai.png';
                    $pendingRequests = $chatController->getPendingRequests($_SESSION['user_id']);
                    $pendingCount = count($pendingRequests);
                    $friends = $chatController->getFriends($_SESSION['user_id']);
                    $userId = $_SESSION['user_id'];
                    $totalUnread = array_sum(array_map(function ($friend) use ($chatController, $userId) {
                        return $chatController->getUnreadCount($userId, $friend['id']);
                    }, $friends));
                    ?>
                    <ul class="navbar-nav me-3 my-2 my-lg-0 navbar-nav-scroll">
                        <!-- Previous nav items remain the same -->
                        <a class="dropdown-item text-white" href="index.php?page=admin_dashboard" style="font-size: 20px;">Dashboard</a>

                    </ul>
                    <div class="d-flex align-items-center me-3">
                        <div class="dropdown">
                            <button class="btn btn-warning fw-bolder dropdown-toggle d-flex align-items-center"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 20px;">
                                <img src="<?php echo $profileImage; ?>" alt="Profile" class="profile-img me-2"
                                    style="height:35px;width:35px;border-radius:50%;">
                                <span><?php echo htmlspecialchars($currentUser['name']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="index.php?page=profile">Profile</a></li>
                                <li><a class="dropdown-item" href="index.php?page=change_password">Change Password</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="index.php?page=logout">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php } else { ?>
                   
                    <a href="index.php?page=login" class="btn btn-warning fw-bolder d-flex align-items-center"
                        style="border-radius: 20px;">
                        Login <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                <?php } ?>
               
            </div>
            
        </div>
    </nav>