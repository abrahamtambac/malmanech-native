<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/db.php';
require dirname(__DIR__) . '/controllers/ChatController.php';

class ChatServer implements MessageComponentInterface {
    private $clients;
    private $chatController;
    private $userIds; // Array untuk menyimpan mapping resourceId ke userId

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userIds = []; // Inisialisasi array untuk userId
        global $conn;
        $this->chatController = new ChatController($conn);
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'register':
                $this->userIds[$from->resourceId] = $data['user_id'];
                $this->chatController->updateLastSeen($data['user_id'], null); // Set null untuk menandakan online
                $this->broadcastStatusUpdate($data['user_id'], null);
                echo "User {$data['user_id']} registered\n";
                break;

                case 'message':
                    $sender_id = $this->userIds[$from->resourceId];
                    $receiver_id = $data['receiver_id'];
                    $message = $data['message'];
                    $file_name = $data['file_name'] ?? null;
                
                    $result = $this->chatController->sendMessage($sender_id, $receiver_id, $message, $file_name);
                
                    if ($result['success']) {
                        $messageId = $this->conn->insert_id; // Ambil ID pesan terakhir yang disisipkan
                        $messageData = [
                            'type' => 'message',
                            'message_id' => $messageId,
                            'sender_id' => $sender_id,
                            'receiver_id' => $receiver_id,
                            'message' => $message,
                            'file_name' => $file_name,
                            'timestamp' => date('Y-m-d H:i:s'),
                            'is_read' => 0
                        ];
                
                        foreach ($this->clients as $client) {
                            if ($this->userIds[$client->resourceId] == $sender_id || $this->userIds[$client->resourceId] == $receiver_id) {
                                $client->send(json_encode($messageData));
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

                    foreach ($this->clients as $client) {
                        if ($this->userIds[$client->resourceId] == $friend_id) {
                            $client->send(json_encode($requestData));
                        }
                    }
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

                    foreach ($this->clients as $client) {
                        if ($this->userIds[$client->resourceId] == $friend_id || $this->userIds[$client->resourceId] == $user_id) {
                            $client->send(json_encode($acceptData));
                        }
                    }
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

                foreach ($this->clients as $client) {
                    if ($this->userIds[$client->resourceId] == $friend_id) {
                        $client->send(json_encode($readData));
                    }
                }
                break;

            case 'status_update':
                $user_id = $this->userIds[$from->resourceId];
                $last_seen = isset($data['last_seen']) ? $this->convertToMySQLDateTime($data['last_seen']) : null;
                
                $this->chatController->updateLastSeen($user_id, $last_seen);
                $this->broadcastStatusUpdate($user_id, $last_seen);
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

                foreach ($this->clients as $client) {
                    if ($this->userIds[$client->resourceId] == $user_id) {
                        $client->send(json_encode($statusData));
                    }
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        if (isset($this->userIds[$conn->resourceId])) {
            $userId = $this->userIds[$conn->resourceId];
            $this->chatController->updateLastSeen($userId, date('Y-m-d H:i:s'));
            $this->broadcastStatusUpdate($userId, date('Y-m-d H:i:s'));
            unset($this->userIds[$conn->resourceId]);
        }
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function broadcastStatusUpdate($userId, $lastSeen) {
        $statusData = [
            'type' => 'status_update',
            'user_id' => $userId,
            'last_seen' => $lastSeen
        ];

        foreach ($this->clients as $client) {
            if ($this->userIds[$client->resourceId] != $userId && $this->isFriend($this->userIds[$client->resourceId], $userId)) {
                $client->send(json_encode($statusData));
            }
        }
    }

    private function isFriend($userId, $friendId) {
        $friends = $this->chatController->getFriends($userId);
        return in_array($friendId, array_column($friends, 'id'));
    }

    private function convertToMySQLDateTime($dateString) {
        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            echo "Error converting datetime: {$e->getMessage()}\n";
            return date('Y-m-d H:i:s'); // Fallback ke waktu saat ini jika gagal
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