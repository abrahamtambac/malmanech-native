<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malmanech - Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <link rel="stylesheet" href="./_partials/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
    body {
        background: #f0f2f5;
        font-family: 'Product Sans', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
    }
    .navbar {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 10px 20px;
    }
    .navbar-brand h1 {
        font-size: 1.8rem;
        font-weight: 700;
    }
    .btn-modern {
        border-radius: 25px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-modern:hover {
        transform: scale(1.05);
    }
    .chat-modal .modal-dialog {
        max-width: 1000px;
        height: 75vh;
        margin: 0 auto;
        top: 10%;
    }
    .chat-modal .modal-content {
        height: 100%;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    .chat-modal-body {
        display: flex;
        height: calc(100% - 60px);
        padding: 0;
    }
    .friend-list {
        width: 300px;
        background: #ffffff;
        border-right: 1px solid #e9ecef;
        overflow-y: auto;
        padding: 10px;
    }
    .chat-area {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .chat-header {
        padding: 15px 20px;
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        display: none;
        justify-content: space-between;
        align-items: center;
    }
    .chat-messages {
        flex-grow: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f0f2f5;
    }
    .chat-input {
        padding: 15px;
        background: #fff;
        border-top: 1px solid #e9ecef;
        display: none;
    }
    .chat-item {
        padding: 12px;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.2s ease;
        margin-bottom: 5px;
    }
    .chat-item:hover {
        background: #e9ecef;
    }
    .chat-item.active {
        background: #cce5ff;
    }
    .message {
        max-width: 30%; /* Batasan lebar maksimum tetap */
        min-width: 100px; /* Lebar minimum agar tidak terlalu kecil */
        max-width: 300px; /* Batasan lebar maksimum spesifik */
        margin-bottom: 15px;
        padding: 12px 18px;
        border-radius: 15px;
        position: relative;
        animation: slideIn 0.3s ease;
       
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
        word-break: break-all; /* Memastikan nama file panjang dipotong */
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
    .notification-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1050;
    }
    .profile-img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
    }
    .badge-notif {
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 0.7em;
        padding: 4px 6px;
    }
    @keyframes slideIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @media (max-width: 768px) {
        .chat-modal .modal-dialog {
            max-width: 100%;
            height: 100vh;
            top: 0;
            margin: 0;
        }
        .friend-list {
            width: 100%;
            max-height: 30vh;
        }
        .chat-area {
            height: 70vh;
        }
        .message {
            max-width: 85%; /* Lebih lebar di layar kecil */
            max-width: 300px; /* Tetap batasi di layar kecil */
        }
    }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-primary border-bottom border-5 border-warning">
    <div class="container-fluid">
        <a class="navbar-brand text-white fw-bold" href="index.php?page=home">
            <h1 class="mb-0"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b></h1>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll"
            aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarScroll">
            <ul class="navbar-nav me-3 my-2 my-lg-0 navbar-nav-scroll"></ul>
            <?php
            include_once './config/db.php';
            include_once './controllers/AuthController.php';
            include_once './controllers/ChatController.php';
            
            $auth = new AuthController($conn);
            $chatController = new ChatController($conn);
            $currentUser = $auth->getCurrentUser();

            if ($currentUser) {
                $profileImage = !empty($currentUser['profile_image']) ? './upload/image/' . $currentUser['profile_image'] : './image/robot-ai.png';
                $pendingRequests = $chatController->getPendingRequests($_SESSION['user_id']);
                $pendingCount = count($pendingRequests);
                $friends = $chatController->getFriends($_SESSION['user_id']);
                $userId = $_SESSION['user_id'];
                $totalUnread = array_sum(array_map(function($friend) use ($chatController, $userId) {
                    return $chatController->getUnreadCount($userId, $friend['id']);
                }, $friends));
            ?>
            <div class="d-flex align-items-center me-3">
                <a href="#" class="text-white me-3 position-relative" id="chat-toggle">
                    <i class="bi bi-chat-dots fs-4"></i>
                    <?php if ($totalUnread > 0): ?>
                        <span class="badge bg-danger badge-notif" id="chat-badge"><?php echo $totalUnread; ?></span>
                    <?php endif; ?>
                </a>
                <a href="#" class="text-white me-3 position-relative" id="friend-request-toggle" data-bs-toggle="modal" data-bs-target="#friendRequestModal">
                    <i class="bi bi-person-plus fs-4"></i>
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-danger badge-notif" id="friend-request-badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown">
                    <a class="btn btn-warning btn-modern fw-bolder" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $profileImage; ?>" alt="Profile" class="profile-img me-2">
                        <span><?php echo htmlspecialchars($currentUser['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="index.php?page=profile">Profil</a></li>
                        <li><a class="dropdown-item" href="index.php?page=change_password">Ganti Kata Sandi</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?page=logout">Keluar</a></li>
                    </ul>
                </div>
            </div>
            <?php } else { ?>
                <a href="index.php?page=login" class="btn btn-warning btn-modern fw-bolder">
                    Masuk Sekarang <i class="bi bi-arrow-right"></i>
                </a>
            <?php } ?>
        </div>
    </div>
</nav>

<!-- Chat Modal with WebSocket -->
<div class="modal fade chat-modal" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="chatModalLabel">Ruang Obrolan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body chat-modal-body">
                <div class="friend-list">
                    <?php foreach ($friends as $friend): 
                        $unreadCount = $chatController->getUnreadCount($_SESSION['user_id'], $friend['id']);
                        $friendImage = $friend['profile_image'] ? './upload/image/' . $friend['profile_image'] : './image/robot-ai.png';
                        $lastSeen = $chatController->getLastSeen($friend['id']);
                    ?>
                    <div class="chat-item chat-friend" data-user-id="<?php echo $friend['id']; ?>" data-user-name="<?php echo htmlspecialchars($friend['name']); ?>">
                        <div class="d-flex align-items-center position-relative">
                            <div class="position-relative">
                                <img src="<?php echo $friendImage; ?>" class="profile-img me-2 <?php echo $unreadCount > 0 ? 'border border-primary' : 'border-0'; ?>">
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge bg-danger badge-notif"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <strong>&nbsp;&nbsp;<?php echo htmlspecialchars($friend['name']); ?></strong>
                                <span class="last-seen"><?php echo $lastSeen ? '<span class="status-dot offline"></span>' . $lastSeen : '<span class="status-dot online"></span>Online'; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-area">
                    <div class="chat-header">
                        <div class="d-flex align-items-center">
                            <img src="" class="profile-img me-2" id="chat-header-img">
                            <div>
                                <strong id="chat-header-name">Pilih teman</strong>
                                <span class="last-seen" id="chat-last-seen"></span>
                            </div>
                        </div>
                        <span class="badge bg-danger rounded-pill" id="chat-unread-count" style="display: none;"></span>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        <div class="text-center mt-5">
                            <h1 class="mb-0 text-primary"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b></h1>
                            <h4 class="text-muted">Pilih teman untuk mulai mengobrol</h4>
                        </div>
                    </div>
                    <div class="chat-input">
                        <div class="input-group">
                            <button class="btn btn-outline-primary" id="attach-btn"><i class="bi bi-paperclip"></i></button>
                            <input type="text" class="form-control border-primary" id="message-input" placeholder="Ketik pesan...">
                            <input type="file" id="file-input" class="d-none" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                            <button class="btn btn-primary btn-modern" id="send-btn"><i class="bi bi-send"></i> Kirim</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Friend Request Modal -->
<div class="modal fade" id="friendRequestModal" tabindex="-1" aria-labelledby="friendRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="friendRequestModalLabel">Permintaan Teman</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="friend-notification" class="alert alert-success alert-dismissible fade" role="alert" style="display: none;">
                    <strong>Keputusan Hebat!</strong> Mulai terhubung dengan <span id="accepted-friend-name"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="friend-search" placeholder="Cari teman...">
                    <button class="btn btn-outline-primary" type="button" id="search-btn"><i class="bi bi-search"></i></button>
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
                                <img src="<?php echo $requestImage; ?>" class="profile-img me-2">
                                <span><?php echo htmlspecialchars($request['name']); ?></span>
                            </div>
                            <button class="btn btn-sm btn-success btn-modern accept-friend-btn" data-user-id="<?php echo $request['id']; ?>" data-user-name="<?php echo htmlspecialchars($request['name']); ?>">Terima</button>
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
$(document).ready(function() {
    const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
    let currentChatUserId = null;
    let ws = new WebSocket('ws://localhost:8080');
    const messageSound = document.getElementById('message-sound');
    const friendRequestSound = document.getElementById('friend-request-sound');
    const friendIds = <?php echo json_encode(array_column($friends, 'id')); ?>;
    let totalUnread = <?php echo $totalUnread; ?>;
    let pendingCount = <?php echo $pendingCount; ?>;

    // Modal Setup
    $('#chatModal').modal({ backdrop: 'static', keyboard: false });
    $('#chat-toggle').on('click', function(e) {
        e.preventDefault();
        $('#chatModal').modal('show');
    });
    $('#friend-request-toggle').on('click', function(e) {
        e.preventDefault();
        $('#friendRequestModal').modal('show');
    });

    // WebSocket Setup
    ws.onopen = function() {
        ws.send(JSON.stringify({ type: 'register', user_id: userId }));
    };

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        switch (data.type) {
            case 'message':
                const messageClass = data.sender_id == userId ? 'sent' : 'received';
                let messageHtml = `
                    <div class="message ${messageClass}" data-message-id="${data.message_id || Date.now()}">
                        ${data.message}
                        <div class="timestamp">${data.timestamp} ${data.sender_id == userId ? '<i class="bi bi-check"></i>' : ''}</div>
                `;
                if (data.file_name) {
                    messageHtml += `<a href="./upload/file_chats/${data.sender_id}/${data.file_name}" download="${data.file_name}" class="attachment"><i class="bi bi-download"></i> ${data.file_name}</a>`;
                }
                messageHtml += `</div>`;
                
                if (data.sender_id == currentChatUserId || data.receiver_id == currentChatUserId) {
                    $('#chat-messages').append(messageHtml);
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                    if (data.receiver_id == userId && data.sender_id != currentChatUserId) {
                        const unreadCount = parseInt($('#chat-unread-count').text() || 0) + 1;
                        $('#chat-unread-count').text(unreadCount).show();
                    }
                }
                
                if (data.receiver_id == userId) {
                    messageSound.play().catch(err => console.log('Audio error:', err));
                    const $friendItem = $(`.chat-friend[data-user-id="${data.sender_id}"]`);
                    let unreadCount = parseInt($friendItem.find('.badge').text() || 0) + 1;
                    $friendItem.find('.badge').remove();
                    if (unreadCount > 0 && data.sender_id != currentChatUserId) {
                        $friendItem.find('.position-relative').append(`<span class="badge bg-danger badge-notif">${unreadCount}</span>`);
                        totalUnread++;
                        updateChatBadge();
                    }
                    
                    if (!$('#chatModal').hasClass('show')) {
                        $('body').append(`
                            <div class="notification-toast toast show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header">
                                    <img src="${$friendItem.find('img').attr('src')}" class="profile-img me-2">
                                    <strong class="me-auto">${$friendItem.data('user-name')}</strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body">${data.message.substring(0, 50)}${data.message.length > 50 ? '...' : ''}</div>
                            </div>
                        `);
                        setTimeout(() => $('.notification-toast').remove(), 5000);
                    }
                }
                break;

            case 'friend_request':
                if (data.friend_id == userId) {
                    friendRequestSound.play().catch(err => console.log('Audio error:', err));
                    pendingCount++;
                    updateFriendRequestBadge();
                    $('#pending-requests').prepend(`
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                            <div class="d-flex align-items-center">
                                <img src="${data.profile_image || './image/robot-ai.png'}" class="profile-img me-2">
                                <span>${data.friend_name}</span>
                            </div>
                            <button class="btn btn-sm btn-success btn-modern accept-friend-btn" data-user-id="${data.sender_id}" data-user-name="${data.friend_name}">Terima</button>
                        </div>
                    `);
                    if ($('#pending-requests h6').length === 0) {
                        $('#pending-requests').prepend('<h6>Permintaan Tertunda</h6>');
                    }
                }
                break;

            case 'friend_accepted':
                if (data.user_id == userId || data.friend_id == userId) {
                    const friendId = data.user_id == userId ? data.friend_id : data.user_id;
                    const friendName = data.friend_name;
                    const friendImage = data.profile_image || './image/robot-ai.png';
                    $('.friend-list').prepend(`
                        <div class="chat-item chat-friend" data-user-id="${friendId}" data-user-name="${friendName}">
                            <div class="d-flex align-items-center position-relative">
                                <div class="position-relative">
                                    <img src="${friendImage}" class="profile-img me-2">
                                </div>
                                <div class="flex-grow-1">
                                    <strong>${friendName}</strong>
                                    <span class="last-seen"><span class="status-dot online"></span>Online</span>
                                </div>
                            </div>
                        </div>
                    `);
                    friendIds.push(friendId);
                    if (data.friend_id == userId) {
                        pendingCount--;
                        updateFriendRequestBadge();
                        $(`#pending-requests .d-flex:has(button[data-user-id="${data.user_id}"])`).remove();
                        if ($('#pending-requests .d-flex').length === 0) {
                            $('#pending-requests').html('<p class="text-muted">Tidak ada permintaan tertunda</p>');
                        }
                    }
                }
                break;

            case 'read_message':
                if (data.user_id == currentChatUserId && data.friend_id == userId) {
                    $('#chat-messages .message.sent .timestamp').each(function() {
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
                break;

            case 'status_update':
                const $friend = $(`.chat-friend[data-user-id="${data.user_id}"]`);
                if ($friend.length) {
                    const statusText = data.last_seen ? `${data.last_seen}` : 'Online';
                    const statusDot = data.last_seen ? '<span class="status-dot offline"></span>' : '<span class="status-dot online"></span>';
                    $friend.find('.last-seen').html(`${statusDot} ${statusText}`);
                }
                if (data.user_id == currentChatUserId) {
                    $('#chat-last-seen').html(data.last_seen ? `<span class="status-dot offline"></span>${data.last_seen}` : '<span class="status-dot online"></span>Online');
                }
                break;
        }
    };

    // Chat Functionality
    $(document).on('click', '.chat-friend', function() {
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
            success: function(messages) {
                let unreadCount = 0;
                messages.forEach(msg => {
                    const messageClass = msg.sender_id == userId ? 'sent' : 'received';
                    let messageHtml = `
                        <div class="message ${messageClass}" data-message-id="${msg.id || Date.now()}">
                            ${msg.message}
                            <div class="timestamp">${msg.timestamp} ${msg.sender_id == userId ? (msg.is_read ? '<i class="bi bi-check2-all"></i>' : '<i class="bi bi-check"></i>') : ''}</div>
                    `;
                    if (msg.file_name) {
                        messageHtml += `<a href="./upload/file_chats/${msg.sender_id}/${msg.file_name}" download="${msg.file_name}" class="attachment"><i class="bi bi-download"></i> ${msg.file_name}</a>`;
                    }
                    messageHtml += `</div>`;
                    $('#chat-messages').append(messageHtml);
                    if (msg.receiver_id == userId && !msg.is_read) unreadCount++;
                });
                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                $('#chat-unread-count').text(unreadCount).toggle(unreadCount > 0);
            }
        });

        const unreadCount = parseInt($(this).find('.badge').text() || 0);
        $(this).find('.badge').remove();
        if (unreadCount > 0) {
            totalUnread -= unreadCount;
            updateChatBadge();
        }
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
    });

    $('#send-btn').on('click', sendMessage);
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13) sendMessage();
    });

    $('#attach-btn').on('click', function() {
        $('#file-input').click();
    });

    $('#file-input').on('change', function() {
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

    function sendMessage(file = null) {
        if (!currentChatUserId) return;
        const message = $('#message-input').val().trim();

        if (!message && !file) return;

        const timestamp = new Date().toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
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
                success: function(response) {
                    if (response.success) {
                        ws.send(JSON.stringify({
                            type: 'message',
                            user_id: userId,
                            receiver_id: currentChatUserId,
                            message: message,
                            file_name: response.file_name,
                            timestamp: timestamp,
                            is_read: false
                        }));
                    } else {
                        alert(response.message);
                    }
                }
            });
        } else {
            ws.send(JSON.stringify({
                type: 'message',
                user_id: userId,
                receiver_id: currentChatUserId,
                message: message,
                timestamp: timestamp,
                is_read: false
            }));
        }
        $('#message-input').val('');
        $('#file-input').val('');
    }

    function updateChatBadge() {
        $('#chat-badge').remove();
        if (totalUnread > 0) {
            $('#chat-toggle').append(`<span class="badge bg-danger badge-notif" id="chat-badge">${totalUnread}</span>`);
        }
    }

    function updateFriendRequestBadge() {
        $('#friend-request-badge').remove();
        if (pendingCount > 0) {
            $('#friend-request-toggle').append(`<span class="badge bg-danger badge-notif" id="friend-request-badge">${pendingCount}</span>`);
        }
    }

    // Friend Request Functionality
    $('#friend-search').on('input', function() {
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
            success: function(data) {
                $('#search-results').empty();
                if (data.length > 0) {
                    data.forEach(function(user) {
                        if (user.id != userId) {
                            const isFriend = friendIds.includes(user.id);
                            $('#search-results').append(`
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <img src="${user.profile_image ? './upload/image/' + user.profile_image : './image/robot-ai.png'}" 
                                             class="profile-img me-2">
                                        <span>${user.name}</span>
                                    </div>
                                    ${isFriend ? 
                                        `<button class="btn btn-sm btn-danger btn-modern delete-friend-btn" data-user-id="${user.id}">Hapus</button>` : 
                                        `<button class="btn btn-sm btn-primary btn-modern send-friend-request" data-user-id="${user.id}" data-status="available">Tambah</button>`}
                                </div>
                            `);
                        }
                    });
                } else {
                    $('#search-results').html('<p class="text-muted">Tidak ada pengguna ditemukan</p>');
                }
            }
        });
    });

    $(document).on('click', '.accept-friend-btn', function() {
        const friendId = $(this).data('user-id');
        const friendName = $(this).data('user-name');
        $.ajax({
            url: 'index.php?page=chat&action=accept_friend',
            type: 'POST',
            data: { friend_id: friendId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $(`button[data-user-id="${friendId}"]`).closest('.d-flex').remove();
                    if ($('#pending-requests').children().length === 1) {
                        $('#pending-requests').html('<p class="text-muted">Tidak ada permintaan tertunda</p>');
                    }
                    $('#friend-notification').show().addClass('show');
                    $('#accepted-friend-name').text(friendName);
                    setTimeout(() => $('#friend-notification').removeClass('show').hide(), 5000);
                    ws.send(JSON.stringify({
                        type: 'friend_accepted',
                        user_id: userId,
                        friend_id: friendId,
                        friend_name: friendName,
                        profile_image: '<?php echo $profileImage; ?>'
                    }));
                    pendingCount--;
                    updateFriendRequestBadge();
                }
            }
        });
    });

    $(document).on('click', '.send-friend-request', function() {
        const friendId = $(this).data('user-id');
        const $button = $(this);
        $.ajax({
            url: 'index.php?page=chat&action=add_friend',
            type: 'POST',
            data: { friend_id: friendId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $button.text('Menunggu').prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
                    ws.send(JSON.stringify({
                        type: 'friend_request',
                        user_id: userId,
                        friend_id: friendId,
                        friend_name: '<?php echo htmlspecialchars($currentUser['name']); ?>',
                        profile_image: '<?php echo $profileImage; ?>'
                    }));
                }
                alert(response.message);
            }
        });
    });

    $(document).on('click', '.delete-friend-btn', function() {
        const friendId = $(this).data('user-id');
        if (confirm('Apakah Anda yakin ingin menghapus teman ini?')) {
            $.ajax({
                url: 'index.php?page=chat&action=delete_friend',
                type: 'POST',
                data: { friend_id: friendId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(`button[data-user-id="${friendId}"]`).closest('.d-flex').remove();
                        $(`.chat-friend[data-user-id="${friendId}"]`).remove();
                        friendIds.splice(friendIds.indexOf(friendId), 1);
                        if (currentChatUserId == friendId) {
                            currentChatUserId = null;
                            $('.chat-header, .chat-input').hide();
                            $('#chat-messages').html(`
                                <div class="text-center mt-5">
                                    <h1 class="mb-0 text-primary"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b></h1>
                                    <h4 class="text-muted">Pilih teman untuk mulai mengobrol</h4>
                                </div>
                            `);
                        }
                        alert('Teman berhasil dihapus');
                    }
                }
            });
        }
    });

    // Periodically update status
    setInterval(() => {
        ws.send(JSON.stringify({
            type: 'status_update',
            user_id: userId,
            last_seen: new Date().toISOString().slice(0, 19).replace('T', ' ')
        }));
        if (currentChatUserId) {
            ws.send(JSON.stringify({
                type: 'get_status',
                user_id: userId,
                friend_id: currentChatUserId
            }));
        }
    }, 30000);
});
</script>
</body>
</html>