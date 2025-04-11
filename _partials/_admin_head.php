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
            font-family: 'CircularStd-Book';
            src: url('./fonts/CircularStd-Book.ttf') format('truetype');
        }

        body {
            font-family: 'CircularStd-Book';

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
        #video-container video {
    border: 2px solid #007bff;
    background: #000;
    object-fit: cover;
}
#video-container {
    display: flex;
    flex-direction: row;
    height: 60vh;
    position: relative;
}

#screen-share-container {
    width: 75%;
    height: 100%;
    float: left;
}

#participant-videos {
    width: 25%;
    height: 100%;
    float: right;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.video-wrapper {
    width: 200px;
    height: 150px;
    position: relative;
    margin: 5px;
}

.remote-video {
    width: 100%;
    height: 100%;
    border: 2px solid #007bff;
    background: #000;
    object-fit: cover;
    border-radius: 10px;
}

.video-label {
    position: absolute;
    bottom: 5px;
    left: 5px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 2px 8px;
    border-radius: 5px;
    font-size: 0.9em;
}
.card {
    border-radius: 10px;
    transition: transform 0.2s ease-in-out;
}
.card:hover {
    transform: translateY(-5px);
}
.alert-info {
    background-color: #e7f3ff;
    border-color: #b8daff;
    color: #004085;
}
.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeeba;
    color: #856404;
}
.alert-primary {
    background-color: #e7f3ff;
    border-color: #b8daff;
    color: #004085;
}
.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
.badge {
    font-size: 0.8rem;
}
.status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}
.status-dot.online {
    background-color: #28a745;
}
.status-dot.offline {
    background-color: #dc3545;
}
.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    transition: background-color 0.3s ease;
}
.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}
.btn-outline-primary {
    border-color: #007bff;
    color: #007bff;
    transition: background-color 0.3s ease, color 0.3s ease;
}
.btn-outline-primary:hover {
    background-color: #007bff;
    color: #fff;
}
.btn-outline-warning {
    border-color: #ffc107;
    color: #ffc107;
    transition: background-color 0.3s ease, color 0.3s ease;
}
.btn-outline-warning:hover {
    background-color: #ffc107;
    color: #fff;
}
.btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
    transition: background-color 0.3s ease, color 0.3s ease;
}
.btn-outline-danger:hover {
    background-color: #dc3545;
    color: #fff;
}
.form-control, .form-select {
    border-radius: 10px;
    transition: border-color 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
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
                <ul class="navbar-nav me-3 my-2 my-lg-0 navbar-nav-scroll">
                    <!-- Previous nav items remain the same -->
                </ul>
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
                    <div class="d-flex align-items-center me-3">
                        <a href="#" class="text-white me-3 position-relative" id="chat-toggle">
                            <i class="bi bi-chat-dots fs-4"></i>
                            <?php if ($totalUnread > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="chat-badge">
                                    <?php echo $totalUnread; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="#" class="text-white me-3 position-relative" id="friend-request-toggle"
                            data-bs-toggle="modal" data-bs-target="#friendRequestModal">
                            <i class="bi bi-person-plus fs-4"></i>
                            <?php if ($pendingCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="friend-request-badge">
                                    <?php echo $pendingCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
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
                        Login Now <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                <?php } ?>
            </div>
        </div>
    </nav>

    <!-- Chat Modal with WebSocket -->
    <div class="modal fade chat-modal" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true"
        data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="chatModalLabel">Chats Room</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body chat-modal-body">
                    <div class="friend-list">
                        <div class="input-group mb-3 p-2">
                            <input type="text" class="form-control border border-primary" id="friend-list-search"
                                placeholder="Cari teman...">
                            <button class="btn btn-outline-primary" type="button" id="search-friend-btn"><i
                                    class="bi bi-search"></i></button>
                        </div>
                        <div id="friend-list-container">
                            <?php
                            $initialFriends = $chatController->getFriendsWithLatest($_SESSION['user_id']);
                            if (empty($initialFriends)) {
                                echo '<p class="text-muted p-2">Tidak ada teman untuk ditampilkan</p>';
                            } else {
                                foreach ($initialFriends as $friend) {
                                    $unreadCount = $friend['unread_count'] ?? 0;
                                    $friendImage = $friend['profile_image'] ? './upload/image/' . $friend['profile_image'] : './image/robot-ai.png';
                                    $lastSeen = $friend['last_seen'] ?? 'Tidak diketahui';
                                    $latestMessage = $friend['latest_message'] ? substr($friend['latest_message']['message'], 0, 20) . (strlen($friend['latest_message']['message']) > 20 ? '...' : '') : '';
                                    ?>
                                    <div class="chat-item chat-friend" data-user-id="<?php echo $friend['id']; ?>"
                                        data-user-name="<?php echo htmlspecialchars($friend['name']); ?>">
                                        <div class="d-flex align-items-center position-relative">
                                            <div class="position-relative">
                                                <?php if ($unreadCount > 0): ?>
                                                    <img src="<?php echo $friendImage; ?>" class="rounded-circle me-2 profile-img"
                                                        style="width: 35px; height: 35px;">
                                                    <span class="badge bg-primary rounded-pill position-absolute"
                                                        style="top: -5px; right: -5px; font-size: 0.7em;border-width: thick;"><?php echo $unreadCount; ?></span>
                                                <?php else: ?>
                                                    <img src="<?php echo $friendImage; ?>"
                                                        class="rounded-circle me-2 border border-primary"
                                                        style="width: 35px; height: 35px;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <strong>   <?php echo htmlspecialchars($friend['name']); ?></strong>
                                                <span
                                                    class="last-seen"><?php echo $lastSeen ? '<span class="status-dot offline"></span>Last seen: ' . $lastSeen : '<span class="status-dot online"></span>Online'; ?></span>
                                                <div class="latest-message text-muted small">
                                                    <?php echo htmlspecialchars($latestMessage); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="chat-area">
                        <div class="chat-header">
                            <div class="d-flex align-items-center">
                                <img src="" class="rounded-circle me-2 profile-img" id="chat-header-img"
                                    style="width: 35px; height: 35px;">
                                <div>
                                    <strong id="chat-header-name">Pilih teman</strong>
                                    <span class="last-seen" id="chat-last-seen"></span>
                                </div>
                            </div>
                            <span class="badge bg-danger rounded-pill" id="chat-unread-count"
                                style="display: none;"></span>
                        </div>
                        <div class="chat-messages" id="chat-messages">
                            <h4 class="text-muted text-center mt-5">
                                <i class="bi bi-fingerprint text-warning mt-5 text-center"></i><b>
                                    Malmanech</b><br />Pilih teman untuk mulai mengobrol
                            </h4>
                        </div>
                        <div class="chat-input">
                            <div class="input-group">
                                <button class="btn btn-outline-primary" id="attach-btn"><i
                                        class="bi bi-paperclip"></i></button>
                                <input type="text" class="form-control border border-primary" id="message-input"
                                    placeholder="Ketik pesan...">
                                <input type="file" id="file-input" class="d-none"
                                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                                <button class="btn btn-primary" id="send-btn"><i class="bi bi-send"></i> Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Friend Request Modal -->
    <div class="modal fade" id="friendRequestModal" tabindex="-1" aria-labelledby="friendRequestModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="friendRequestModalLabel">Permintaan Teman</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="friend-notification" class="alert alert-success alert-dismissible fade" role="alert"
                        style="display: none;">
                        <strong>Keputusan Hebat!</strong> Mulai terhubung dengan <span id="accepted-friend-name"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="friend-search" placeholder="Cari teman...">
                        <button class="btn btn-outline-primary" type="button" id="search-btn"><i
                                class="bi bi-search"></i></button>
                    </div>
                    <div id="search-results" class="mb-3" style="max-height: 200px; overflow-y: auto;"></div>
                    <div id="pending-requests">
                        <?php if (!empty($pendingRequests)): ?>
                            <h6>Permintaan Tertunda</h6>
                            <?php foreach ($pendingRequests as $request):
                                $requestImage = $request['profile_image'] ? './upload/image/' . $request['profile_image'] : './image/robot-ai.png';
                                ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $requestImage; ?>" class="rounded-circle me-2"
                                            style="width: 40px; height: 40px;">
                                        <span><?php echo htmlspecialchars($request['name']); ?></span>
                                    </div>
                                    <button class="btn btn-sm btn-success accept-friend-btn"
                                        data-user-id="<?php echo $request['id']; ?>"
                                        data-user-name="<?php echo htmlspecialchars($request['name']); ?>">Terima</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada permintaan tertunda</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Elements -->
    <audio id="message-sound" preload="auto">
        <source src="./sounds/notification.mp3" type="audio/mpeg">
    </audio>
    <audio id="friend-request-sound" preload="auto">
        <source src="./sounds/friend-request.mp3" type="audio/mpeg">
    </audio>

    <script>
        $(document).ready(function () {
            updateFriendList();
            const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
            let currentChatUserId = null;
            let ws = null;
            const messageSound = document.getElementById('message-sound');
            const friendRequestSound = document.getElementById('friend-request-sound');
            const friendIds = <?php echo json_encode(array_column($friends, 'id')); ?>;
            let totalUnread = <?php echo $totalUnread; ?>;
            let pendingCount = <?php echo $pendingCount; ?>;
            let isSending = false;
            let statusInterval = null;

            function connectWebSocket() {
                // Tentukan URL WebSocket berdasarkan hostname
                const isLocalhost = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
                const wsUrl = isLocalhost
                    ? `${window.location.protocol === 'https:' ? 'wss' : 'ws'}://${window.location.hostname}:8080`
                    : `${window.location.protocol === 'https:' ? 'wss' : 'ws'}://${window.location.hostname}/ws`;

                ws = new WebSocket(wsUrl);

                ws.onopen = function () {
                    ws.send(JSON.stringify({ type: 'register', user_id: userId }));
                    console.log('WebSocket connection opened');
                    startStatusUpdate();
                    updateFriendList();
                };

                ws.onmessage = function (event) {
                    try {
                        const data = JSON.parse(event.data);
                        handleWebSocketMessage(data);
                    } catch (error) {
                        console.error('Error parsing WebSocket message:', error);
                    }
                };

                ws.onerror = function (error) {
                    console.error('WebSocket error:', error);
                };

                ws.onclose = function () {
                    console.log('WebSocket connection closed, attempting to reconnect...');
                    clearInterval(statusInterval);
                    setTimeout(connectWebSocket, 2000);
                };
            }
            function handleWebSocketMessage(data) {
                switch (data.type) {
                    case 'message':
                        handleMessage(data);
                        break;
                    case 'friend_request':
                        handleFriendRequest(data);
                        break;
                    case 'friend_accepted':
                        handleFriendAccepted(data);
                        break;
                    case 'read_message':
                        handleReadMessage(data);
                        break;
                    case 'status_update':
                        handleStatusUpdate(data);
                        break;
                    default:
                        console.log('Unhandled WebSocket message type:', data.type);
                }
            }

            function handleMessage(data) {
                if (data.sender_id == userId) return;

                const messageClass = data.sender_id == userId ? 'sent' : 'received';
                let messageHtml = `
            <div class="message ${messageClass}" data-message-id="${data.message_id || Date.now()}">
                ${data.message ? '<div class="message-text">' + data.message + '</div>' : ''}
                <div class="timestamp">${data.timestamp} ${data.sender_id == userId ? '<i class="bi bi-check"></i>' : ''}</div>
        `;
                if (data.file_name) {
                    const fileIcon = getFileIcon(data.file_type, data.file_name);
                    const fileTypeText = data.file_type ? data.file_type.split('/')[1] || data.file_type : (data.file_name.split('.').pop() || 'unknown');
                    const fileSizeText = formatFileSize(data.file_size);
                    messageHtml += `
                <div class="file-details">
                    <span class="file-name">${data.file_name}</span>
                    <span class="file-type">(${fileTypeText})</span>
                    <br/>
                </div>
                <div class="file-divider"></div>
                <span class="file-size">${fileSizeText}</span>
                <a href="./upload/file_chats/${data.sender_id}/${data.file_name}" 
                   download="${data.file_name}" 
                   class="file-download">${fileIcon} Download</a>
            `;
                }
                messageHtml += `</div>`;

                if (data.sender_id == currentChatUserId || data.receiver_id == currentChatUserId) {
                    $('#chat-messages').append(messageHtml); // Append untuk pesan baru di bawah
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                    if (data.receiver_id == userId && data.sender_id != currentChatUserId) {
                        const unreadCount = parseInt($('#chat-unread-count').text() || 0) + 1;
                        $('#chat-unread-count').text(unreadCount).show();
                    }
                }

                if (data.receiver_id == userId) {
                    messageSound.play().catch(err => console.log('Audio error:', err));
                    updateFriendList();
                    if (!$('#chatModal').hasClass('show')) {
                        const $friendItem = $(`.chat-friend[data-user-id="${data.sender_id}"]`);
                        $('body').append(`
                    <div class="notification-toast toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <img src="${$friendItem.find('img').attr('src') || './image/robot-ai.png'}" class="rounded-circle me-2" style="width: 20px; height: 20px;">
                            <strong class="me-auto">${$friendItem.data('user-name') || 'Unknown'}</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">${data.message ? data.message.substring(0, 50) : 'File attachment'}${data.message && data.message.length > 50 ? '...' : ''}</div>
                    </div>
                `);
                        setTimeout(() => $('.notification-toast').remove(), 5000);
                    }
                }
            }

            function handleFriendRequest(data) {
                if (data.friend_id == userId) {
                    friendRequestSound.play().catch(err => console.log('Audio error:', err));
                    pendingCount++;
                    updateFriendRequestBadge();
                    $('#pending-requests').prepend(`
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="${data.profile_image ? './upload/image/' + data.profile_image : './image/robot-ai.png'}" class="rounded-circle me-2" style="width: 40px; height: 40px;">
                        <span>${data.friend_name}</span>
                    </div>
                    <button class="btn btn-sm btn-success accept-friend-btn" data-user-id="${data.sender_id}" data-user-name="${data.friend_name}">Terima</button>
                </div>
            `);
                    if ($('#pending-requests h6').length === 0) {
                        $('#pending-requests').prepend('<h6>Permintaan Tertunda</h6>');
                    }
                }
            }

            function handleFriendAccepted(data) {
                if (data.user_id == userId || data.friend_id == userId) {
                    const friendId = data.user_id == userId ? data.friend_id : data.user_id;
                    const friendName = data.friend_name;
                    const friendImage = data.profile_image || './image/robot-ai.png';
                    friendIds.push(friendId);
                    if (data.friend_id == userId) {
                        pendingCount--;
                        updateFriendRequestBadge();
                        $(`#pending-requests .d-flex:has(button[data-user-id="${data.user_id}"])`).remove();
                        if ($('#pending-requests .d-flex').length === 0) {
                            $('#pending-requests').html('<p class="text-muted">Tidak ada permintaan tertunda</p>');
                        }
                    }
                    updateFriendList();
                }
            }

            function handleReadMessage(data) {
                if (data.user_id == currentChatUserId && data.friend_id == userId) {
                    $('#chat-messages .message.sent .timestamp').each(function () {
                        const $timestamp = $(this);
                        if (!$timestamp.find('.bi-check2-all').length) {
                            $timestamp.html($timestamp.text().replace('<i class="bi bi-check"></i>', '') + ' <i class="bi bi-check2-all"></i>');
                        }
                    });
                    totalUnread -= parseInt($('#chat-unread-count').text() || 0);
                    updateChatBadge();
                    $('#chat-unread-count').hide();
                }
                const $friendItem = $(`.chat-friend[data-user-id="${data.user_id}"]`);
                $friendItem.find('.badge').remove();
                updateFriendList();
            }

            function handleStatusUpdate(data) {
                try {
                    if (!data || !data.user_id) {
                        console.warn('Invalid status update data:', data);
                        return;
                    }
                    const safeUserId = $('<div>').text(data.user_id).html();
                    const $friend = $(`.chat-friend[data-user-id="${safeUserId}"]`);
                    if ($friend.length) {
                        let statusText, statusDot;
                        if (data.last_seen) {
                            const lastSeenDate = new Date(data.last_seen);
                            statusText = `Last seen: ${lastSeenDate.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short', year: 'numeric' })}`;
                            statusDot = '<span class="status-dot offline"></span>';
                        } else {
                            statusText = 'Online';
                            statusDot = '<span class="status-dot online"></span>';
                        }
                        $friend.find('.last-seen').html(`${statusDot} ${statusText}`);
                    }
                    if (data.user_id === currentChatUserId) {
                        let chatStatusHtml;
                        if (data.last_seen) {
                            const lastSeenDate = new Date(data.last_seen);
                            chatStatusHtml = `<span class="status-dot offline"></span>Last seen: ${lastSeenDate.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short', year: 'numeric' })}`;
                        } else {
                            chatStatusHtml = '<span class="status-dot online"></span>Online';
                        }
                        $('#chat-last-seen').html(chatStatusHtml);
                    }
                } catch (error) {
                    console.error('Error in handleStatusUpdate:', error);
                }
            }

            function getFileIcon(fileType, fileName) {
                if (!fileType && fileName) {
                    const ext = fileName.split('.').pop().toLowerCase();
                    switch (ext) {
                        case 'jpg': case 'jpeg': case 'png': case 'gif': return '<i class="bi bi-image"></i>';
                        case 'doc': case 'docx': return '<i class="bi bi-file-word"></i>';
                        case 'xls': case 'xlsx': return '<i class="bi bi-file-excel"></i>';
                        case 'ppt': case 'pptx': return '<i class="bi bi-file-ppt"></i>';
                        case 'pdf': return '<i class="bi bi-file-pdf"></i>';
                        default: return '<i class="bi bi-file-earmark"></i>';
                    }
                }
                if (!fileType) return '<i class="bi bi-file-earmark"></i>';
                if (fileType.includes('image')) return '<i class="bi bi-image"></i>';
                if (fileType.includes('word')) return '<i class="bi bi-file-word"></i>';
                if (fileType.includes('excel') || fileType.includes('spreadsheet')) return '<i class="bi bi-file-excel"></i>';
                if (fileType.includes('powerpoint') || fileType.includes('presentation')) return '<i class="bi bi-file-ppt"></i>';
                if (fileType.includes('pdf')) return '<i class="bi bi-file-pdf"></i>';
                return '<i class="bi bi-file-earmark"></i>';
            }

            function formatFileSize(bytes) {
                if (!bytes) return '0 B';
                if (bytes < 1024) return bytes + ' B';
                else if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
                else if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
                else return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
            }

            function updateChatBadge() {
                $('#chat-badge').remove();
                if (totalUnread > 0) {
                    $('#chat-toggle').append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="chat-badge">${totalUnread}</span>`);
                }
            }

            function updateFriendRequestBadge() {
                $('#friend-request-badge').remove();
                if (pendingCount > 0) {
                    $('#friend-request-toggle').append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="friend-request-badge">${pendingCount}</span>`);
                }
            }

            function updateFriendList(query = '') {
                $.ajax({
                    url: 'index.php?page=chat&action=get_friends_with_latest',
                    type: 'GET',
                    data: { query: query }, // Kirim query ke server
                    dataType: 'json',
                    success: function (friends) {
                        console.log('Friends data received:', friends); // Debugging
                        $('#friend-list-container').empty();
                        if (!friends || friends.length === 0) {
                            $('#friend-list-container').html('<p class="text-muted p-2">Tidak ada teman untuk ditampilkan</p>');
                            return;
                        }

                        if (Array.isArray(friends)) {
                            friends.forEach(friend => {
                                const unreadCount = friend.unread_count || 0;
                                const friendImage = friend.profile_image ? `./upload/image/${friend.profile_image}` : './image/robot-ai.png';
                                const lastSeen = friend.last_seen || 'Tidak diketahui';
                                const latestMessage = friend.latest_message && friend.latest_message.message ?
                                    friend.latest_message.message.substring(0, 20) + (friend.latest_message.message.length > 20 ? '...' : '') :
                                    'Belum ada pesan';
                                const isActive = currentChatUserId == friend.id ? 'active' : '';

                                const chatItem = `
                        <div class="chat-item chat-friend ${isActive}" data-user-id="${friend.id}" data-user-name="${friend.name}">
                            <div class="d-flex align-items-center position-relative">
                                <div class="position-relative">
                                    ${unreadCount > 0 ?
                                        `<img src="${friendImage}" class="rounded-circle me-2 profile-img" style="width: 35px; height: 35px;">
                                         <span class="badge bg-primary rounded-pill position-absolute" style="top: -5px; right: -5px; font-size: 0.7em;border-width: thick;">${unreadCount}</span>` :
                                        `<img src="${friendImage}" class="rounded-circle me-2 border border-primary" style="width: 35px; height: 35px;">`}
                                </div>
                                <div class="flex-grow-1">
                                    <strong>   ${friend.name}</strong>
                                    <span class="last-seen">${lastSeen.includes('Last seen') ? lastSeen : (lastSeen ? `<span class="status-dot offline"></span>Last seen: ${lastSeen}` : '<span class="status-dot online"></span>Online')}</span>
                                    
                                </div>
                            </div>
                        </div>`;
                                $('#friend-list-container').append(chatItem);
                            });
                        }

                        if (currentChatUserId) {
                            $(`.chat-friend[data-user-id="${currentChatUserId}"]`).addClass('active');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching friend list:', xhr.responseText, status, error);
                        $('#friend-list-container').html('<p class="text-danger p-2">Gagal memuat daftar teman. Silakan coba lagi.</p>');
                    }
                });
            }

            function setupModals() {
                $('#chatModal').modal({ backdrop: 'static', keyboard: false });
                $('#chat-toggle').on('click', function (e) {
                    e.preventDefault();
                    $('#chatModal').modal('show');
                    updateFriendList();
                });
                $('#friend-request-toggle').on('click', function (e) {
                    e.preventDefault();
                    $('#friendRequestModal').modal('show');
                });

                $('#chatModal').on('shown.bs.modal', function () {
                    updateFriendList();
                });

                $('#chatModal').on('hidden.bs.modal', function () {
                    currentChatUserId = null;
                    $('.chat-header, .chat-input').hide();
                    $('#chat-messages').html('<h4 class="text-muted text-center mt-5"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b><br/>Pilih teman untuk mulai mengobrol</h4>');
                });

                $('#friendRequestModal').on('hidden.bs.modal', function () {
                    $('#friend-search').val('');
                    $('#search-results').empty();
                });
            }

            function setupChat() {
                $(document).on('click', '.chat-friend', function () {
                    currentChatUserId = $(this).data('user-id');
                    const friendName = $(this).data('user-name');
                    const friendImg = $(this).find('img').attr('src');

                    $('.chat-item').removeClass('active');
                    $(this).addClass('active');
                    $('#chat-header-img').attr('src', friendImg);
                    $('#chat-header-name').text(friendName);
                    $('.chat-header, .chat-input').show();
                    $('#chat-messages').empty();

                    $.ajax({
                        url: 'index.php?page=chat&action=get_messages',
                        type: 'GET',
                        data: { friend_id: currentChatUserId },
                        dataType: 'json',
                        success: function (messages) {
                            let unreadCount = 0;
                            messages.forEach(msg => {
                                const messageClass = msg.sender_id == userId ? 'sent' : 'received';
                                let messageHtml = `
                        <div class="message ${messageClass}" data-message-id="${msg.id || Date.now()}">
                            ${msg.message ? '<div class="message-text">' + msg.message + '</div>' : ''}
                            <div class="timestamp">${msg.timestamp} ${msg.sender_id == userId ? (msg.is_read ? '<i class="bi bi-check2-all"></i>' : '<i class="bi bi-check"></i>') : ''}</div>
                    `;
                                if (msg.file_name) {
                                    const fileIcon = getFileIcon(msg.file_type, msg.file_name);
                                    const fileTypeText = msg.file_type ? msg.file_type.split('/')[1] || msg.file_type : (msg.file_name.split('.').pop() || 'unknown');
                                    const fileSizeText = formatFileSize(msg.file_size);
                                    messageHtml += `
                            <div class="file-details">
                                <span class="file-name">${msg.file_name}</span>
                                <span class="file-type">(${fileTypeText})</span>
                                <br/>
                            </div>
                            <div class="file-divider"></div>
                            <span class="file-size">${fileSizeText}</span><br/><br/>
                            <a class="btn btn-light text-dark btn-sm" href="./upload/file_chats/${msg.sender_id}/${msg.file_name}" 
                               download="${msg.file_name}" 
                               class="file-download">${fileIcon} Download</a>
                        `;
                                }
                                messageHtml += `</div>`;
                                $('#chat-messages').append(messageHtml);
                                if (msg.receiver_id == userId && !msg.is_read) unreadCount++;
                            });
                            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                            $('#chat-unread-count').text(unreadCount).toggle(unreadCount > 0);
                        },
                        error: function (xhr, status, error) {
                            console.error('Error fetching messages:', error);
                            $('#chat-messages').html('<p class="text-danger text-center">Gagal memuat pesan. Silakan coba lagi.</p>');
                        }
                    });

                    const unreadCount = parseInt($(this).find('.badge').text() || 0);
                    $(this).find('.badge').remove();
                    if (unreadCount > 0) {
                        totalUnread -= unreadCount;
                        updateChatBadge();
                    }
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            type: 'read_message',
                            user_id: userId,
                            friend_id: currentChatUserId
                        }));
                        ws.send(JSON.stringify({
                            type: 'get_status',
                            user_id: userId,
                            friend_id: currentChatUserId
                        }));
                    }
                });

                function sendMessage(file = null) {
                    if (!currentChatUserId || isSending || !ws || ws.readyState !== WebSocket.OPEN) {
                        isSending = false;
                        return;
                    }
                    isSending = true;

                    const message = $('#message-input').val().trim();
                    if (!message && !file) {
                        isSending = false;
                        return;
                    }

                    const timestamp = new Date().toLocaleString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    const messageId = Date.now();

                    if (file) {
                        const formData = new FormData();
                        formData.append('file', file);
                        formData.append('sender_id', userId);
                        formData.append('receiver_id', currentChatUserId);
                        formData.append('message', message);

                        $.ajax({
                            url: 'index.php?page=chat&action=upload_file',
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    const fileType = file.type;
                                    const fileSize = file.size;
                                    const fileTypeText = fileType.split('/')[1] || fileType;
                                    const fileSizeText = formatFileSize(fileSize);
                                    const messageData = {
                                        type: 'message',
                                        user_id: userId,
                                        receiver_id: currentChatUserId,
                                        message: message,
                                        file_name: response.file_name,
                                        file_type: fileType,
                                        file_size: fileSize,
                                        timestamp: timestamp,
                                        is_read: false,
                                        message_id: messageId
                                    };
                                    ws.send(JSON.stringify(messageData));

                                    let messageHtml = `
                            <div class="message sent" data-message-id="${messageId}">
                                ${message ? '<div class="message-text">' + message + '</div>' : ''}
                                <div class="timestamp">${timestamp} <i class="bi bi-check"></i></div>
                        `;
                                    if (response.file_name) {
                                        const fileIcon = getFileIcon(fileType, response.file_name);
                                        messageHtml += `
                                <div class="file-details">
                                    <span class="file-name">${response.file_name}</span>
                                    <span class="file-type">(${fileTypeText})</span>
                                    <span class="file-size">${fileSizeText}</span>
                                </div>
                                <div class="file-divider"></div>
                                <a href="./upload/file_chats/${userId}/${response.file_name}" 
                                   download="${response.file_name}" 
                                   class="file-download">${fileIcon} Download</a>
                            `;
                                    }
                                    messageHtml += `</div>`;
                                    $('#chat-messages').append(messageHtml);
                                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                                } else {
                                    alert(response.message);
                                }
                                isSending = false;
                            },
                            error: function (xhr, status, error) {
                                console.error('Error uploading file:', error);
                                alert('Gagal mengunggah file. Silakan coba lagi.');
                                isSending = false;
                            }
                        });
                    } else if (message) {
                        const messageData = {
                            type: 'message',
                            user_id: userId,
                            receiver_id: currentChatUserId,
                            message: message,
                            timestamp: timestamp,
                            is_read: false,
                            message_id: messageId
                        };
                        ws.send(JSON.stringify(messageData));

                        const messageHtml = `
                <div class="message sent" data-message-id="${messageId}">
                    <div class="message-text">${message}</div>
                    <div class="timestamp">${timestamp} <i class="bi bi-check"></i></div>
                </div>`;
                        $('#chat-messages').append(messageHtml);
                        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                        isSending = false;
                    }

                    $('#message-input').val('');
                    $('#file-input').val('');
                    updateFriendList();
                }

                $('#send-btn').on('click', function (e) {
                    e.preventDefault();
                    sendMessage();
                });

                $('#message-input').on('keypress', function (e) {
                    if (e.which === 13 && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });

                $('#attach-btn').on('click', function () {
                    $('#file-input').click();
                });

                $('#file-input').on('change', function () {
                    const file = this.files[0];
                    if (file) {
                        if (file.size > 10 * 1024 * 1024) {
                            alert('Ukuran file melebihi batas 10MB.');
                            $(this).val('');
                            return;
                        }
                        sendMessage(file);
                    }
                });

                // Perbaikan fitur pencarian teman di modal chat
                $('#friend-list-search').on('input', debounce(function () {
                    const query = $(this).val().trim();
                    updateFriendList(query);
                }, 300));

                $('#search-friend-btn').on('click', function () {
                    const query = $('#friend-list-search').val().trim();
                    updateFriendList(query);
                });
            }

            function setupFriendRequests() {
                $('#friend-search').on('input', debounce(function () {
                    const query = $(this).val();
                    if (query.length < 2) {
                        $('#search-results').empty();
                        return;
                    }

                    $.ajax({
                        url: 'index.php?page=chat&action=search_users',
                        type: 'GET',
                        data: { query: query },
                        dataType: 'json',
                        success: function (data) {
                            $('#search-results').empty();
                            if (data.length > 0) {
                                data.forEach(function (user) {
                                    if (user.id != userId) {
                                        const isFriend = friendIds.includes(user.id);
                                        $('#search-results').append(`
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                        <div class="d-flex align-items-center">
                                            <img src="${user.profile_image ? './upload/image/' + user.profile_image : './image/robot-ai.png'}" 
                                                 class="rounded-circle me-2" style="width: 40px; height: 40px;">
                                            <span>${user.name}</span>
                                        </div>
                                        ${isFriend ?
                                                `<button class="btn btn-sm btn-danger delete-friend-btn" data-user-id="${user.id}">Hapus Teman</button>` :
                                                `<button class="btn btn-sm btn-primary send-friend-request" data-user-id="${user.id}">Tambah Teman</button>`}
                                    </div>
                                `);
                                    }
                                });
                            } else {
                                $('#search-results').html('<p class="text-muted">Tidak ada pengguna ditemukan</p>');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error searching users:', error);
                            $('#search-results').html('<p class="text-danger">Gagal mencari pengguna. Silakan coba lagi.</p>');
                        }
                    });
                }, 300));

                $('#search-btn').on('click', function () {
                    const query = $('#friend-search').val();
                    if (query.length < 2) {
                        $('#search-results').empty();
                        return;
                    }
                    $.ajax({
                        url: 'index.php?page=chat&action=search_users',
                        type: 'GET',
                        data: { query: query },
                        dataType: 'json',
                        success: function (data) {
                            $('#search-results').empty();
                            if (data.length > 0) {
                                data.forEach(function (user) {
                                    if (user.id != userId) {
                                        const isFriend = friendIds.includes(user.id);
                                        $('#search-results').append(`
                                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                        <div class="d-flex align-items-center">
                                            <img src="${user.profile_image ? './upload/image/' + user.profile_image : './image/robot-ai.png'}" 
                                                 class="rounded-circle me-2" style="width: 40px; height: 40px;">
                                            <span>${user.name}</span>
                                        </div>
                                        ${isFriend ?
                                                `<button class="btn btn-sm btn-danger delete-friend-btn" data-user-id="${user.id}">Hapus Teman</button>` :
                                                `<button class="btn btn-sm btn-primary send-friend-request" data-user-id="${user.id}">Tambah Teman</button>`}
                                    </div>
                                `);
                                    }
                                });
                            } else {
                                $('#search-results').html('<p class="text-muted">Tidak ada pengguna ditemukan</p>');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error searching users:', error);
                            $('#search-results').html('<p class="text-danger">Gagal mencari pengguna. Silakan coba lagi.</p>');
                        }
                    });
                });

                $(document).on('click', '.accept-friend-btn', function () {
                    const friendId = $(this).data('user-id');
                    const friendName = $(this).data('user-name');
                    $.ajax({
                        url: 'index.php?page=chat&action=accept_friend',
                        type: 'POST',
                        data: { friend_id: friendId },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                $(`button[data-user-id="${friendId}"]`).closest('.d-flex').remove();
                                if ($('#pending-requests').children().length === 1) {
                                    $('#pending-requests').html('<p class="text-muted">Tidak ada permintaan tertunda</p>');
                                }
                                $('#friend-notification').show().addClass('show');
                                $('#accepted-friend-name').text(friendName);
                                setTimeout(() => $('#friend-notification').removeClass('show').hide(), 5000);
                                if (ws && ws.readyState === WebSocket.OPEN) {
                                    ws.send(JSON.stringify({
                                        type: 'friend_accepted',
                                        user_id: userId,
                                        friend_id: friendId,
                                        friend_name: friendName,
                                        profile_image: '<?php echo $profileImage; ?>'
                                    }));
                                }
                                pendingCount--;
                                updateFriendRequestBadge();
                                updateFriendList();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error accepting friend request:', error);
                            alert('Gagal menerima permintaan teman. Silakan coba lagi.');
                        }
                    });
                });

                $(document).on('click', '.send-friend-request', function () {
                    const friendId = $(this).data('user-id');
                    const $button = $(this);
                    $.ajax({
                        url: 'index.php?page=chat&action=add_friend',
                        type: 'POST',
                        data: { friend_id: friendId },
                        dataType: 'json',
                        beforeSend: function () {
                            $button.prop('disabled', true).text('Mengirim...');
                        },
                        success: function (response) {
                            if (response.success) {
                                $button.text('Menunggu Konfirmasi').removeClass('btn-primary').addClass('btn-secondary');
                                if (ws && ws.readyState === WebSocket.OPEN) {
                                    ws.send(JSON.stringify({
                                        type: 'friend_request',
                                        user_id: userId,
                                        friend_id: friendId,
                                        friend_name: '<?php echo htmlspecialchars($currentUser['name']); ?>',
                                        profile_image: '<?php echo $profileImage; ?>'
                                    }));
                                }
                                alert('Permintaan teman berhasil dikirim!');
                            } else {
                                alert(response.message);
                                $button.prop('disabled', false).text('Tambah Teman');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error sending friend request:', error);
                            alert('Gagal mengirim permintaan teman. Silakan coba lagi.');
                            $button.prop('disabled', false).text('Tambah Teman');
                        }
                    });
                });

                $(document).on('click', '.delete-friend-btn', function () {
                    const friendId = $(this).data('user-id');
                    if (confirm('Apakah Anda yakin ingin menghapus teman ini?')) {
                        $.ajax({
                            url: 'index.php?page=chat&action=delete_friend',
                            type: 'POST',
                            data: { friend_id: friendId },
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    $(`button[data-user-id="${friendId}"]`).closest('.d-flex').remove();
                                    $(`.chat-friend[data-user-id="${friendId}"]`).remove();
                                    friendIds.splice(friendIds.indexOf(friendId), 1);
                                    if (currentChatUserId == friendId) {
                                        currentChatUserId = null;
                                        $('.chat-header, .chat-input').hide();
                                        $('#chat-messages').html('<h4 class="text-muted text-center mt-5"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b><br/>Pilih teman untuk mulai mengobrol</h4>');
                                    }
                                    alert('Teman berhasil dihapus');
                                    updateFriendList();
                                } else {
                                    alert(response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Error deleting friend:', error);
                                alert('Gagal menghapus teman. Silakan coba lagi.');
                            }
                        });
                    }
                });
            }

            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            function startStatusUpdate() {
                clearInterval(statusInterval);
                statusInterval = setInterval(() => {
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        const lastSeen = new Date().toISOString().slice(0, 19).replace('T', ' ');
                        ws.send(JSON.stringify({
                            type: 'status_update',
                            user_id: userId,
                            last_seen: lastSeen
                        }));
                        if (currentChatUserId) {
                            ws.send(JSON.stringify({
                                type: 'get_status',
                                user_id: userId,
                                friend_id: currentChatUserId
                            }));
                        }
                    }
                }, 30000);
            }

            function cleanup() {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.close();
                    console.log('WebSocket connection closed by cleanup');
                }
                clearInterval(statusInterval);
                $(document).off();
                $('.modal').modal('hide');
            }

            try {
                connectWebSocket();
                setupModals();
                setupChat();
                setupFriendRequests();
                updateChatBadge();
                updateFriendRequestBadge();
            } catch (error) {
                console.error('Error during initialization:', error);
            }

            $(window).on('beforeunload', cleanup);
        });
    </script>
</body>

</html>