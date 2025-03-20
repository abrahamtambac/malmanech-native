<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background-color: #f0f2f5;
            margin: 0;
            font-family: 'Product Sans', sans-serif;
            color: #333;
            overflow: hidden;
        }
        .app-container {
            display: flex;
            height: 100vh;
            background: linear-gradient(to bottom, #e6f0fa, #f0f2f5);
        }
        .nav-sidebar {
            width: 70px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-right: 4px solid #FFD700;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .sidebar {
            width: 360px;
            background-color: #fff;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        .chat-area {
            flex-grow: 1;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            background: #fff;
            border-radius: 0 10px 0 0;
        }
        .search-container {
            padding: 10px;
            background: #fff;
            position: relative;
        }
        .chat-list {
            overflow-y: auto;
            margin: 0;
            padding: 0;
        }
        .chat-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f3f5;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        .chat-item:hover {
            background-color: #f8f9fa;
        }
        .chat-item .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }
        .message-area {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png') repeat;
            background-size: 300px;
        }
        .message {
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 8px;
            max-width: 70%;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .message.sent {
            background-color: #cce5ff;
            align-self: flex-end;
        }
        .message.received {
            background-color: #fff;
            align-self: flex-start;
        }
        .message-container {
            width: 100%;
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
        }
        .message-container.sent {
            align-items: flex-end;
        }
        .input-area {
            padding: 15px;
            background: #fff;
            border-top: 1px solid #e9ecef;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        .input-group {
            display: flex;
            background: #f1f3f5;
            border-radius: 25px;
            padding: 5px;
            position: relative;
        }
        .input-group input {
            flex-grow: 1;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            background: transparent;
        }
        .input-group input:focus {
            outline: none;
            box-shadow: none;
        }
        .input-group button {
            background-color: transparent;
            border: none;
            padding: 0 12px;
            color: #0056b3;
        }
        .add-friend-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .timestamp {
            font-size: 0.75em;
            color: #666;
            margin-top: 2px;
        }
        .timestamp i.fa-check {
            color: #adb5bd;
        }
        .timestamp i.fa-check-double {
            color: #007bff;
        }
        .unread-count {
            background-color: #007bff;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.8em;
            margin-left: 8px;
        }
        .chat-item p {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            font-size: 0.9em;
        }
        #pending-requests h6, #contact-list h6 {
            margin: 0;
            padding: 12px 15px;
            background: #f8f9fa;
            font-size: 0.9em;
            color: #495057;
            font-weight: 600;
        }
        .chat-header {
            background: #fff;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-header .status-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.online {
            background-color: #28a745;
        }
        .status-dot.offline {
            background-color: #dc3545;
        }
        .hamburger {
            display: none;
            font-size: 1.5em;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .nav-sidebar {
                width: 100%;
                height: 60px;
                flex-direction: row;
                justify-content: space-between;
                padding: 0 15px;
                position: fixed;
                top: 0;
                left: 0;
                border-right: none;
                border-bottom: 4px solid #FFD700;
            }
            .sidebar {
                width: 100%;
                position: fixed;
                top: 60px;
                left: 0;
                height: calc(100vh - 60px);
                transform: translateX(-100%);
                z-index: 999;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .chat-area {
                width: 100%;
                margin-top: 60px;
            }
            .hamburger {
                display: block;
            }
            .chat-header .avatar {
                width: 35px;
                height: 35px;
            }
            .message {
                max-width: 85%;
            }
            .input-group {
                padding: 3px;
            }
            .input-group input {
                padding: 6px 30px 6px 10px;
            }
            .input-group button {
                padding: 0 8px;
            }
            .add-friend-icon {
                right: 8px;
                width: 20px;
                height: 20px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
<?php
include_once './controllers/AdminController.php';
include_once './controllers/ChatController.php';
include_once './config/db.php';

$adminController = new AdminController($conn);
$chatController = new ChatController($conn);
$adminController->checkAdminAccess();

$userProfile = $adminController->getUserProfile();
$friends = $chatController->getFriends($_SESSION['user_id']);
$pendingRequests = $chatController->getPendingRequests($_SESSION['user_id']);
$hasFriends = !empty($friends);
$profileImage = !empty($userProfile['profile_image']) 
    ? './upload/image/' . $userProfile['profile_image'] 
    : './image/robot-ai.png';
$totalUnread = array_sum(array_map(function($friend) use ($chatController) {
    return $chatController->getUnreadCount($_SESSION['user_id'], $friend['id']);
}, $friends));
?>

    <div class="app-container">
        <!-- Navigation Sidebar -->
        <div class="nav-sidebar">
            <i class="fas fa-bars hamburger text-white"></i>
            <a href="index.php?page=admin_dashboard" title="Back to Dashboard" class="text-white"><i class="fas fa-arrow-left"></i></a>
        </div>

        <!-- Main Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header d-flex justify-content-between align-items-center fw-bolder">
                <div class="d-flex align-items-center">
                    <img src="<?php echo $profileImage; ?>" alt="Profile" class="profile-img me-2" style="height:40px;width:40px;border-radius:50%;">
                    <span><?php echo htmlspecialchars($userProfile['name']); ?></span>
                </div>
                <a href="index.php?page=logout" class="text-muted"><i class="fas fa-sign-out-alt"></i></a>
            </div>

            <div class="search-container">
                <div class="input-group">
                    <input type="text" class="form-control" id="search-user" placeholder="Search or start new chat">
                    <span class="add-friend-icon" id="add-friend-btn"><i class="fas fa-plus"></i></span>
                </div>
            </div>

            <!-- Pending Friend Requests -->
            <div class="chat-list" id="pending-requests">
                <?php if (!empty($pendingRequests)): ?>
                <h6>Pending Requests</h6>
                <?php foreach ($pendingRequests as $request): ?>
                <div class="chat-item" data-user-id="<?php echo $request['id']; ?>">
                    <img src="<?php echo $request['profile_image'] ? './upload/image/' . $request['profile_image'] : './image/robot-ai.png'; ?>"
                         class="avatar" alt="<?php echo htmlspecialchars($request['name']); ?>">
                    <div class="d-flex justify-content-between w-100">
                        <strong><?php echo htmlspecialchars($request['name']); ?></strong>
                        <button class="btn btn-sm btn-success accept-friend-btn" data-user-id="<?php echo $request['id']; ?>">Accept</button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Friends List -->
            <div class="chat-list" id="contact-list">
                <?php if (!empty($friends)): ?>
                <h6>Chats</h6>
                <?php endif; ?>
                <?php foreach ($friends as $friend): ?>
                <div class="chat-item contact" data-user-id="<?php echo $friend['id']; ?>">
                    <img src="<?php echo $friend['profile_image'] ? './upload/image/' . $friend['profile_image'] : './image/robot-ai.png'; ?>"
                         class="avatar" alt="<?php echo htmlspecialchars($friend['name']); ?>">
                    <div class="w-100">
                        <strong><?php echo htmlspecialchars($friend['name']); ?></strong>
                        <?php 
                        $latestMessage = $chatController->getLatestMessage($_SESSION['user_id'], $friend['id']);
                        $unreadCount = $chatController->getUnreadCount($_SESSION['user_id'], $friend['id']);
                        $lastSeen = $chatController->getLastSeen($friend['id']);
                        ?>
                        <p class="text-muted <?php echo $unreadCount > 0 ? 'fw-bold' : ''; ?>" data-friend-id="<?php echo $friend['id']; ?>">
                            <?php 
                            if ($latestMessage) {
                                echo $latestMessage['sender_id'] == $_SESSION['user_id'] 
                                    ? 'You: ' . htmlspecialchars(substr($latestMessage['message'], 0, 20)) . '...' 
                                    : htmlspecialchars(substr($latestMessage['message'], 0, 20)) . '...';
                            }
                            ?>
                            <?php if ($unreadCount > 0): ?>
                            <span class="unread-count"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area" id="chat-area">
            <div class="chat-header">
                <div class="d-flex align-items-center">
                    <div class="avatar rounded-circle" style="width: 40px; height: 40px; background: #ddd;"></div>
                    <div class="ms-3">
                        <strong id="chat-header-name">Select a contact to start chatting</strong>
                        <div class="status-info">
                            <span class="text-muted mb-0" id="chat-status">Offline</span>
                            <span class="badge bg-danger rounded-pill" id="chat-unread-count" style="display: none;"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="message-area" id="chat-messages">
                <?php if (!$hasFriends && empty($pendingRequests)): ?>
                <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted" id="no-chat-message">
                    <h4>No chats yet. Start a conversation!</h4>
                    <button class="btn btn-primary mt-3 d-flex align-items-center" id="initial-add-friend-btn">
                        <i class="fas fa-user-plus me-2"></i> Find Friends
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="input-area" id="chat-input" style="display: none;">
                <div class="input-group">
                    <input type="text" class="form-control" id="message-input" placeholder="Type a message">
                    <input type="file" id="file-input" class="d-none" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
                    <button id="attach-btn"><i class="fas fa-paperclip"></i></button>
                    <button id="send-btn"><i class="fas fa-paper-plane"></i> Send</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Element for Notification -->
    <audio id="message-sound" preload="auto">
        <source src="./sounds/notification.mp3" type="audio/mpeg">
    </audio>

    <!-- Modal Add Friend -->
    <div class="modal fade" id="addFriendModal" tabindex="-1" aria-labelledby="addFriendModalLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addFriendModalLabel">Find Friends</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="modal-search-user" placeholder="Search users...">
                    <div id="modal-search-loading" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="modal-search-results"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
    let currentChatUserId = null;
    let ws = new WebSocket('ws://localhost:8080');
    const messageSound = document.getElementById('message-sound');
    let totalUnread = <?php echo $totalUnread; ?>;
    let pendingCount = <?php echo $pendingCount; ?>;

    ws.onopen = function() {
        ws.send(JSON.stringify({
            type: 'register',
            user_id: userId
        }));
    };

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        switch (data.type) {
            case 'message':
                if (data.sender_id == currentChatUserId || data.receiver_id == currentChatUserId) {
                    const messageClass = data.sender_id == userId ? 'sent' : 'received';
                    let messageHtml = `<div class="message-container ${messageClass}">
                                        <div class="message ${messageClass}">${data.message}
                                            <div class="timestamp">${data.timestamp} ${data.sender_id == userId ? (data.is_read ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') : ''}</div>
                                        </div>
                                      </div>`;
                    if (data.file_name) {
                        messageHtml += `<div class="message-container ${messageClass}">
                                        <div class="message ${messageClass}"><a href="./upload/file_chats/${data.sender_id}/${data.file_name}" download="${data.file_name}"><i class="fas fa-download"></i> ${data.file_name}</a>
                                            <div class="timestamp">${data.timestamp} ${data.sender_id == userId ? (data.is_read ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') : ''}</div>
                                        </div>
                                      </div>`;
                    }
                    $('#chat-messages').append(messageHtml);
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                }
                
                if (data.receiver_id == userId && data.sender_id != userId) {
                    messageSound.play().catch(error => console.log('Error playing sound:', error));
                    if (currentChatUserId == data.sender_id) {
                        ws.send(JSON.stringify({
                            type: 'read_message',
                            user_id: userId,
                            friend_id: data.sender_id
                        }));
                    } else {
                        totalUnread++;
                        updateChatBadge();
                    }
                }
                
                updateSidebar(data.sender_id, data.message, data.timestamp, data.receiver_id == userId, data.sender_id == userId);
                break;

            case 'friend_request':
                if (data.friend_id == userId) {
                    pendingCount++;
                    updateFriendRequestBadge();
                    if ($('#pending-requests h6').length === 0) {
                        $('#pending-requests').prepend('<h6>Pending Requests</h6>');
                    }
                    $('#pending-requests').append(`
                        <div class="chat-item" data-user-id="${data.sender_id}">
                            <img src="${data.profile_image ? './upload/image/' + data.profile_image : './image/robot-ai.png'}" class="avatar" alt="${data.friend_name}">
                            <div class="d-flex justify-content-between w-100">
                                <strong>${data.friend_name}</strong>
                                <button class="btn btn-sm btn-success accept-friend-btn" data-user-id="${data.sender_id}">Accept</button>
                            </div>
                        </div>
                    `);
                }
                break;

            case 'friend_accepted':
                if (data.user_id == userId || data.friend_id == userId) {
                    const friendId = data.user_id == userId ? data.friend_id : data.user_id;
                    const friendName = data.friend_name;
                    $('#contact-list').prepend(`
                        <div class="chat-item contact" data-user-id="${friendId}">
                            <img src="${data.profile_image ? './upload/image/' + data.profile_image : './image/robot-ai.png'}" class="avatar" alt="${friendName}">
                            <div class="w-100">
                                <strong>${friendName}</strong>
                                <p class="text-muted" data-friend-id="${friendId}"></p>
                            </div>
                        </div>
                    `);
                    if ($('#contact-list h6').length === 0) {
                        $('#contact-list').prepend('<h6>Chats</h6>');
                    }
                    if (data.friend_id == userId) {
                        $(`#pending-requests .chat-item[data-user-id="${data.user_id}"]`).remove();
                        pendingCount--;
                        updateFriendRequestBadge();
                        if ($('#pending-requests .chat-item').length === 0) {
                            $('#pending-requests h6').remove();
                        }
                    }
                    $('#no-chat-message').hide();
                }
                break;

            case 'read_message':
                if (data.user_id == currentChatUserId && data.friend_id == userId) {
                    $('#chat-messages .message.sent .timestamp').each(function() {
                        $(this).html($(this).html().replace('<i class="fas fa-check"></i>', '<i class="fas fa-check-double"></i>'));
                    });
                    totalUnread -= parseInt($('#chat-unread-count').text() || 0);
                    updateChatBadge();
                    $('#chat-unread-count').hide();
                }
                updateSidebar(data.user_id, null, null, false, false);
                break;

            case 'status_update':
                if (data.user_id == currentChatUserId) {
                    const statusText = data.last_seen ? `Last seen: ${data.last_seen}` : 'Online';
                    const statusDot = data.last_seen ? '<span class="status-dot offline"></span>' : '<span class="status-dot online"></span>';
                    $('#chat-status').html(`${statusDot} ${statusText}`);
                }
                break;
        }
    };

    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };

    ws.onclose = function() {
        console.log('WebSocket connection closed');
    };

    $('#search-user').on('input', function() {
        const query = $(this).val();
        if (query.length < 2) {
            $('#modal-search-results').empty();
            return;
        }
        $('#addFriendModal').modal('show');
        $('#modal-search-user').val(query).focus();
        $('#modal-search-loading').show();
        $('#modal-search-results').empty();

        $.ajax({
            url: 'index.php?page=chat&action=search_users',
            type: 'GET',
            data: { query: query },
            dataType: 'json',
            success: function(data) {
                $('#modal-search-loading').hide();
                if (data.length > 0) {
                    data.forEach(function(user) {
                        if (user.id != userId) {
                            $('#modal-search-results').append(`
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <img src="${user.profile_image ? './upload/image/' + user.profile_image : './image/robot-ai.png'}"
                                             alt="${user.name}" class="rounded-circle" style="width: 40px; height: 40px;">
                                        <span class="ms-2">${user.name}</span>
                                    </div>
                                    <button class="btn btn-success btn-sm start-chat-btn" data-user-id="${user.id}" data-user-name="${user.name}">Request Friend</button>
                                </div>
                            `);
                        }
                    });
                } else {
                    $('#modal-search-results').html('<p class="text-muted">No users found.</p>');
                }
            }
        });
    });

    $('#add-friend-btn').on('click', function() {
        $('#addFriendModal').modal('show');
        $('#modal-search-user').focus();
    });

    $('#initial-add-friend-btn').on('click', function() {
        $('#addFriendModal').modal('show');
        $('#modal-search-user').focus();
    });

    $(document).on('click', '.start-chat-btn', function() {
        const friendId = $(this).data('user-id');
        const friendName = $(this).data('user-name');
        ws.send(JSON.stringify({
            type: 'friend_request',
            user_id: userId,
            friend_id: friendId,
            friend_name: friendName
        }));
        alert('Friend request sent to ' + friendName + '. Waiting for approval.');
        $('#addFriendModal').modal('hide');
    });

    $(document).on('click', '.accept-friend-btn', function() {
        const friendId = $(this).data('user-id');
        const $item = $(this).closest('.chat-item');
        const friendName = $item.find('strong').text();
        ws.send(JSON.stringify({
            type: 'accept_friend',
            user_id: userId,
            friend_id: friendId,
            friend_name: friendName
        }));
    });

    $(document).on('click', '.contact', function() {
        const friendId = $(this).data('user-id');
        const friendName = $(this).find('strong').text();
        startChat(friendId, friendName);
        
        const $contact = $(this);
        const unreadCount = parseInt($contact.find('.unread-count').text() || 0);
        $contact.find('.unread-count').remove();
        $contact.find('p').removeClass('fw-bold');
        totalUnread -= unreadCount;
        updateChatBadge();
        
        ws.send(JSON.stringify({
            type: 'read_message',
            user_id: userId,
            friend_id: friendId
        }));

        if ($(window).width() <= 768) {
            $('#sidebar').removeClass('active');
        }
    });

    function startChat(friendId, friendName) {
        currentChatUserId = friendId;
        $('#chat-header-name').text(friendName);
        $('#no-chat-message').hide();
        $('#chat-messages').empty().css('display', 'block');
        $('#chat-input').show();

        $.ajax({
            url: 'index.php?page=chat&action=get_messages',
            type: 'GET',
            data: { friend_id: friendId },
            dataType: 'json',
            success: function(messages) {
                if (messages && messages.length > 0) {
                    messages.forEach(msg => {
                        const messageClass = msg.sender_id == userId ? 'sent' : 'received';
                        let messageHtml = `<div class="message-container ${messageClass}">
                                            <div class="message ${messageClass}">${msg.message}
                                                <div class="timestamp">${msg.timestamp} ${msg.sender_id == userId ? (msg.is_read ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') : ''}</div>
                                            </div>
                                          </div>`;
                        if (msg.file_name) {
                            messageHtml += `<div class="message-container ${messageClass}">
                                            <div class="message ${messageClass}"><a href="./upload/file_chats/${msg.sender_id}/${msg.file_name}" download="${msg.file_name}"><i class="fas fa-download"></i> ${msg.file_name}</a>
                                                <div class="timestamp">${msg.timestamp} ${msg.sender_id == userId ? (msg.is_read ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') : ''}</div>
                                            </div>
                                          </div>`;
                        }
                        $('#chat-messages').append(messageHtml);
                    });
                } else {
                    $('#chat-messages').append('<p class="text-muted text-center">Start a conversation!</p>');
                }
                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                const unreadCount = messages.filter(msg => msg.receiver_id == userId && !msg.is_read).length;
                $('#chat-unread-count').text(unreadCount).toggle(unreadCount > 0);
            }
        });

        ws.send(JSON.stringify({
            type: 'get_status',
            user_id: userId,
            friend_id: friendId
        }));
    }

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
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                alert('File size exceeds 10MB limit.');
                $(this).val('');
                return;
            }
            sendMessage(file);
        }
    });

    function sendMessage(file = null) {
        if (!currentChatUserId) return;
        const message = $('#message-input').val();
        if (!message && !file) return;

        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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
                    $('#message-input').val('');
                    $('#file-input').val('');
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
            $('#message-input').val('');
        }
    }

    function updateSidebar(friendId, message, timestamp, isReceived, isSent) {
        const $contact = $(`#contact-list .contact[data-user-id="${friendId}"]`);
        if ($contact.length) {
            let unreadCount = parseInt($contact.find('.unread-count').text() || 0);
            
            if (isReceived && currentChatUserId != friendId) {
                unreadCount++;
            } else if (currentChatUserId == friendId) {
                unreadCount = 0;
            }

            if (message) {
                const messageText = isSent ? `You: ${message.substring(0, 20)}...` : message.substring(0, 20) + '...';
                $contact.find('p').html(`${messageText} ${unreadCount > 0 ? '<span class="unread-count">' + unreadCount + '</span>' : ''}`)
                    .toggleClass('fw-bold', unreadCount > 0);
                $contact.prependTo('#contact-list');
            } else if (!isReceived && unreadCount === 0) {
                $contact.find('p').removeClass('fw-bold');
                $contact.find('.unread-count').remove();
            }
        }
    }

    function updateChatBadge() {
        const $badge = $('#chat-toggle .badge');
        $badge.remove();
        if (totalUnread > 0) {
            $('#chat-toggle').append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">${totalUnread}</span>`);
        }
    }

    function updateFriendRequestBadge() {
        const $badge = $('#friendRequestModal .badge');
        $badge.remove();
        if (pendingCount > 0) {
            $('#friendRequestModal').prev().append(`<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">${pendingCount}</span>`);
        }
    }

    setInterval(() => {
        ws.send(JSON.stringify({
            type: 'status_update',
            user_id: userId,
            last_seen: new Date().toLocaleString()
        }));
    }, 30000);

    $('.hamburger').on('click', function() {
        $('#sidebar').toggleClass('active');
    });

    $(document).on('click', function(e) {
        if ($(window).width() <= 768 && !$(e.target).closest('.sidebar').length && !$(e.target).hasClass('hamburger')) {
            $('#sidebar').removeClass('active');
        }
    });
    </script>
</body>
</html>