<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING); // Nonaktifkan deprecated dan warning
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/db.php';
require dirname(__DIR__) . '/controllers/ChatController.php';

class ChatServer implements MessageComponentInterface {
    private $clients;
    private $chatController;
    private $userIds;
    private $classroomCalls;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userIds = [];
        $this->classroomCalls = [];
        global $conn;
        $this->chatController = new ChatController($conn);
        $this->log("ChatServer initialized");
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents('websocket.log', "[$timestamp] $message\n", FILE_APPEND);
        echo "[$timestamp] $message\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->log("New connection established! Resource ID: {$conn->resourceId}");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) {
            $this->log("Invalid message received from Resource ID: {$from->resourceId}");
            return;
        }

        $this->log("Message received from Resource ID: {$from->resourceId}, Type: {$data['type']}");

        switch ($data['type']) {
            case 'register':
                $userId = $data['user_id'];
                $this->userIds[$from->resourceId] = $userId;
                $this->chatController->updateLastSeen($userId, null);
                $this->broadcastStatusUpdate($userId, null);
                $this->log("User $userId registered with Resource ID: {$from->resourceId}");
                $this->checkOngoingCallsForUser($userId, $from);
                break;

            case 'message':
                $sender_id = $this->userIds[$from->resourceId];
                $receiver_id = $data['receiver_id'];
                $message = $data['message'];
                $file_name = $data['file_name'] ?? null;

                $result = $this->chatController->sendMessage($sender_id, $receiver_id, $message, $file_name);
                if ($result['success']) {
                    $messageData = [
                        'type' => 'message',
                        'message_id' => $result['message_id'],
                        'sender_id' => $sender_id,
                        'receiver_id' => $receiver_id,
                        'message' => $message,
                        'file_name' => $file_name,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'is_read' => 0
                    ];
                    foreach ($this->clients as $client) {
                        $clientUserId = $this->userIds[$client->resourceId] ?? null;
                        if ($clientUserId == $sender_id || $clientUserId == $receiver_id) {
                            $client->send(json_encode($messageData));
                            $this->log("Message sent to User ID: $clientUserId");
                        }
                    }
                }
                break;

            case 'friend_request':
                $sender_id = $this->userIds[$from->resourceId];
                $friend_id = $data['friend_id'];
                $friend_name = $data['friend_name'];
                $profile_image = $data['profile_image'];

                $result = $this->chatController->addFriend($sender_id, $friend_id);
                if ($result['success']) {
                    $requestData = [
                        'type' => 'friend_request',
                        'sender_id' => $sender_id,
                        'friend_id' => $friend_id,
                        'friend_name' => $friend_name,
                        'profile_image' => $profile_image
                    ];
                    $this->relayToUser($friend_id, $requestData);
                    $this->log("Friend request sent from $sender_id to $friend_id");
                }
                break;

            case 'friend_accepted':
                $user_id = $this->userIds[$from->resourceId];
                $friend_id = $data['friend_id'];
                $friend_name = $data['friend_name'];

                $result = $this->chatController->acceptFriend($user_id, $friend_id);
                if ($result['success']) {
                    $acceptData = [
                        'type' => 'friend_accepted',
                        'user_id' => $user_id,
                        'friend_id' => $friend_id,
                        'friend_name' => $friend_name,
                        'profile_image' => $this->chatController->getUserProfile($friend_id)['profile_image'] ?? './image/robot-ai.png'
                    ];
                    $this->relayToUser($friend_id, $acceptData);
                    $this->relayToUser($user_id, $acceptData);
                    $this->log("Friend request accepted between $user_id and $friend_id");
                }
                break;

            case 'read_message':
                $user_id = $this->userIds[$from->resourceId];
                $friend_id = $data['friend_id'];
                $this->chatController->markMessagesAsRead($user_id, $friend_id);
                $readData = [
                    'type' => 'read_message',
                    'user_id' => $user_id,
                    'friend_id' => $friend_id
                ];
                $this->relayToUser($friend_id, $readData);
                $this->log("Messages marked as read for $user_id from $friend_id");
                break;

            case 'status_update':
                $user_id = $this->userIds[$from->resourceId];
                $last_seen = isset($data['last_seen']) ? $this->convertToMySQLDateTime($data['last_seen']) : null;
                $this->chatController->updateLastSeen($user_id, $last_seen);
                $this->broadcastStatusUpdate($user_id, $last_seen);
                $this->log("Status updated for User ID: $user_id, Last seen: " . ($last_seen ?? 'Online'));
                break;

            case 'get_status':
                $user_id = $this->userIds[$from->resourceId];
                $friend_id = $data['friend_id'];
                $last_seen = $this->chatController->getLastSeen($friend_id);
                $statusData = [
                    'type' => 'status_update',
                    'user_id' => $friend_id,
                    'last_seen' => $last_seen
                ];
                $this->relayToUser($user_id, $statusData);
                $this->log("Status requested for Friend ID: $friend_id by User ID: $user_id");
                break;

            case 'start_video_call':
                $classroom_id = $data['classroom_id'];
                $user_id = $this->userIds[$from->resourceId];
                $role = $this->getUserRole($user_id, $classroom_id);
                if ($role !== 'lecturer') {
                    $from->send(json_encode(['type' => 'error', 'message' => 'Only lecturers can start a video call']));
                    $this->log("Error: User $user_id (not lecturer) tried to start video call in classroom $classroom_id");
                    break;
                }
                if (!isset($this->classroomCalls[$classroom_id])) {
                    $this->classroomCalls[$classroom_id] = [];
                }
                $this->classroomCalls[$classroom_id][$user_id] = $from;
                $this->broadcastToAllClassroomMembers($classroom_id, [
                    'type' => 'video_call_started',
                    'classroom_id' => $classroom_id,
                    'user_id' => $user_id
                ], null);
                $this->log("Video call started in classroom $classroom_id by User $user_id");
                break;

            case 'participant_joined':
                $classroom_id = $data['classroom_id'];
                $user_id = $this->userIds[$from->resourceId];
                if (isset($this->classroomCalls[$classroom_id])) {
                    $this->classroomCalls[$classroom_id][$user_id] = $from;
                    $this->broadcastToClassroom($classroom_id, [
                        'type' => 'participant_joined',
                        'classroom_id' => $classroom_id,
                        'user_id' => $user_id
                    ], $from);
                    $this->log("User $user_id joined video call in classroom $classroom_id");
                }
                break;

            case 'participant_left':
                $classroom_id = $data['classroom_id'];
                $user_id = $this->userIds[$from->resourceId];
                if (isset($this->classroomCalls[$classroom_id][$user_id])) {
                    unset($this->classroomCalls[$classroom_id][$user_id]);
                    $this->broadcastToClassroom($classroom_id, [
                        'type' => 'participant_left',
                        'classroom_id' => $classroom_id,
                        'user_id' => $user_id
                    ], $from);
                    $this->log("User $user_id left video call in classroom $classroom_id");
                }
                break;

            case 'offer':
            case 'answer':
            case 'ice_candidate':
            case 'screen_shared':
                $this->relayToUser($data['to_user_id'], $data);
                $this->log("Relayed {$data['type']} to User ID: {$data['to_user_id']} from User ID: {$this->userIds[$from->resourceId]}");
                break;

           case 'video_call_ended':
    $user_id = $this->userIds[$from->resourceId];
    $to_user_id = $data['to_user_id'] ?? null;
    $classroom_id = $data['classroom_id'] ?? null;

    if ($classroom_id && isset($this->classroomCalls[$classroom_id])) {
        unset($this->classroomCalls[$classroom_id][$user_id]);
        if (empty($this->classroomCalls[$classroom_id]) || $this->getUserRole($user_id, $classroom_id) === 'lecturer') {
            unset($this->classroomCalls[$classroom_id]);
            $this->broadcastToAllClassroomMembers($classroom_id, [
                'type' => 'video_call_ended',
                'classroom_id' => $classroom_id,
                'user_id' => $user_id
            ], $from);
            $this->log("Video call ended in classroom $classroom_id by User $user_id");
        }
    } elseif ($to_user_id) {
        $this->relayToUser($to_user_id, $data);
        $this->log("One-on-one video call ended for User $user_id to User $to_user_id");
    }
    break;

            case 'check_call_status':
                $classroom_id = $data['classroom_id'];
                $user_id = $this->userIds[$from->resourceId];
                if (isset($this->classroomCalls[$classroom_id]) && !empty($this->classroomCalls[$classroom_id])) {
                    $initiator = array_key_first($this->classroomCalls[$classroom_id]);
                    $from->send(json_encode([
                        'type' => 'video_call_started',
                        'classroom_id' => $classroom_id,
                        'user_id' => $initiator
                    ]));
                    $this->log("Checked call status for User $user_id in classroom $classroom_id: Call active, initiator $initiator");
                } else {
                    $this->log("Checked call status for User $user_id in classroom $classroom_id: No active call");
                }
                break;

            default:
                $this->log("Unknown message type: {$data['type']} from Resource ID: {$from->resourceId}");
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        if (isset($this->userIds[$conn->resourceId])) {
            $userId = $this->userIds[$conn->resourceId];
            foreach ($this->classroomCalls as $classroom_id => &$participants) {
                if (isset($participants[$userId])) {
                    unset($participants[$userId]);
                    $this->broadcastToClassroom($classroom_id, [
                        'type' => 'participant_left',
                        'classroom_id' => $classroom_id,
                        'user_id' => $userId
                    ], $conn);
                    if (empty($participants) || $this->getUserRole($userId, $classroom_id) === 'lecturer') {
                        unset($this->classroomCalls[$classroom_id]);
                        $this->broadcastToAllClassroomMembers($classroom_id, [
                            'type' => 'video_call_ended',
                            'classroom_id' => $classroom_id,
                            'user_id' => $userId
                        ], $conn);
                        $this->log("Video call ended in classroom $classroom_id due to User $userId disconnection");
                    }
                    $this->log("User $userId disconnected and left classroom $classroom_id");
                }
            }
            $this->chatController->updateLastSeen($userId, date('Y-m-d H:i:s'));
            $this->broadcastStatusUpdate($userId, date('Y-m-d H:i:s'));
            unset($this->userIds[$conn->resourceId]);
        }
        $this->clients->detach($conn);
        $this->log("Connection closed! Resource ID: {$conn->resourceId}");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->log("Error occurred: {$e->getMessage()} for Resource ID: {$conn->resourceId}");
        $conn->close();
    }

    private function relayToUser($toUserId, $data) {
        foreach ($this->clients as $client) {
            if (isset($this->userIds[$client->resourceId]) && $this->userIds[$client->resourceId] == $toUserId) {
                $client->send(json_encode($data));
                $this->log("Message relayed to User ID: $toUserId");
                break;
            }
        }
    }

    private function broadcastToClassroom($classroom_id, $data, $excludeConn = null) {
        if (isset($this->classroomCalls[$classroom_id])) {
            foreach ($this->classroomCalls[$classroom_id] as $participantConn) {
                if ($participantConn !== $excludeConn) {
                    $participantConn->send(json_encode($data));
                    $this->log("Broadcasted to classroom $classroom_id participant Resource ID: {$participantConn->resourceId}");
                }
            }
        }
    }

    private function broadcastToAllClassroomMembers($classroom_id, $data, $excludeConn = null) {
        $sentTo = [];
        foreach ($this->clients as $client) {
            $userId = $this->userIds[$client->resourceId] ?? null;
            if ($userId && $this->isMemberOfClassroom($userId, $classroom_id) && $client !== $excludeConn) {
                $client->send(json_encode($data));
                $sentTo[] = $userId;
            }
        }
        $this->log("Broadcasted to all members of classroom $classroom_id: " . implode(', ', $sentTo));
    }

    private function checkOngoingCallsForUser($userId, $conn) {
        foreach ($this->classroomCalls as $classroom_id => $participants) {
            if ($this->isMemberOfClassroom($userId, $classroom_id) && !empty($participants)) {
                $initiator = array_key_first($participants);
                if ($this->getUserRole($initiator, $classroom_id) === 'lecturer') {
                    $conn->send(json_encode([
                        'type' => 'video_call_started',
                        'classroom_id' => $classroom_id,
                        'user_id' => $initiator
                    ]));
                    $this->log("Sent ongoing call notification to User $userId for classroom $classroom_id, Initiator: $initiator");
                }
            }
        }
    }

    private function broadcastStatusUpdate($userId, $lastSeen) {
        $statusData = [
            'type' => 'status_update',
            'user_id' => $userId,
            'last_seen' => $lastSeen
        ];
        foreach ($this->clients as $client) {
            $clientUserId = $this->userIds[$client->resourceId] ?? null;
            if ($clientUserId != $userId && $this->isFriend($clientUserId, $userId)) {
                $client->send(json_encode($statusData));
                $this->log("Status update sent to Friend ID: $clientUserId for User ID: $userId");
            }
        }
    }

    private function isFriend($userId, $friendId) {
        if (!$userId || !$friendId) return false;
        $friends = $this->chatController->getFriends($userId);
        return in_array($friendId, array_column($friends, 'id'));
    }

    private function isMemberOfClassroom($userId, $classroomId) {
        global $conn;
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tb_classroom_members WHERE classroom_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $classroomId, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row();
        $stmt->close();
        $this->log("Checked membership: User $userId " . ($result[0] > 0 ? "is" : "is not") . " a member of classroom $classroomId");
        return $result[0] > 0;
    }

    private function getUserRole($userId, $classroomId) {
        global $conn;
        $stmt = $conn->prepare("SELECT role FROM tb_classroom_members WHERE classroom_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $classroomId, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $role = $result['role'] ?? null;
        $this->log("User $userId role in classroom $classroomId: " . ($role ?? 'None'));
        return $role;
    }

    private function convertToMySQLDateTime($dateString) {
        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->log("Error converting datetime: {$e->getMessage()}");
            return date('Y-m-d H:i:s');
        }
    }
}

echo "Starting WebSocket server on port 8080...\n";
$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new ChatServer()
        )
    ),
    8080
);
$server->run();