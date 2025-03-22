<?php
class ChatController {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function addFriend($user_id, $friend_id) {
        $stmt = $this->conn->prepare("SELECT * FROM tb_friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Sudah meminta atau berteman'];
        }
        $stmt->close();
    
        $stmt = $this->conn->prepare("INSERT INTO tb_friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $user_id, $friend_id);
        $success = $stmt->execute();
        $stmt->close();
        return ['success' => $success, 'message' => $success ? 'Permintaan teman terkirim' : 'Gagal mengirim permintaan', 'friend_id' => $friend_id];
    }

    public function acceptFriend($user_id, $friend_id) {
        $stmt = $this->conn->prepare("UPDATE tb_friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $friend_id, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        return ['success' => $success, 'message' => $success ? 'Friend request accepted' : 'Failed to accept request', 'friend_id' => $friend_id];
    }

    public function getFriends($user_id) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.name, u.email, u.profile_image 
            FROM tb_friends f 
            JOIN tb_users u ON f.friend_id = u.id 
            WHERE f.user_id = ? AND f.status = 'accepted'
            UNION
            SELECT u.id, u.name, u.email, u.profile_image 
            FROM tb_friends f 
            JOIN tb_users u ON f.user_id = u.id 
            WHERE f.friend_id = ? AND f.status = 'accepted'
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $friends = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $friends;
    }

    public function getFriendsWithLatest($user_id, $query = '') {
        $friends = $this->getFriends($user_id);
        if (empty($friends)) {
            return [];
        }
        foreach ($friends as &$friend) {
            $friend['unread_count'] = $this->getUnreadCount($user_id, $friend['id']);
            $friend['last_seen'] = $this->getLastSeen($friend['id']);
            $friend['latest_message'] = $this->getLatestMessage($user_id, $friend['id']);
        }
        if ($query) {
            $query = strtolower($query);
            $friends = array_filter($friends, function($friend) use ($query) {
                return stripos(strtolower($friend['name']), $query) !== false || 
                       stripos(strtolower($friend['email']), $query) !== false;
            });
        }
        usort($friends, function($a, $b) {
            $aTime = $a['latest_message'] ? strtotime($a['latest_message']['timestamp']) : 0;
            $bTime = $b['latest_message'] ? strtotime($b['latest_message']['timestamp']) : 0;
            return $bTime - $aTime; // Urutkan berdasarkan pesan terbaru
        });
        return array_values($friends); // Reset indeks array setelah filter
    }

    public function getPendingRequests($user_id) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.name, u.email, u.profile_image 
            FROM tb_friends f 
            JOIN tb_users u ON f.user_id = u.id 
            WHERE f.friend_id = ? AND f.status = 'pending'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $requests = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $requests;
    }

    public function getMessages($user_id, $friend_id) {
        $stmt = $this->conn->prepare("
            SELECT id, sender_id, receiver_id, message, file_name, file_type, file_size, timestamp, is_read 
            FROM tb_messages 
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY timestamp ASC
        ");
        $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    
        $this->markMessagesAsRead($user_id, $friend_id);
        return $messages;
    }

    public function markMessagesAsRead($user_id, $friend_id) {
        $stmt = $this->conn->prepare("
            UPDATE tb_messages 
            SET is_read = 1 
            WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
        ");
        $stmt->bind_param("ii", $user_id, $friend_id);
        $stmt->execute();
        $stmt->close();
    }

    public function getUnreadCount($user_id, $friend_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as unread_count 
            FROM tb_messages 
            WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
        ");
        $stmt->bind_param("ii", $user_id, $friend_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['unread_count'];
        $stmt->close();
        return $count;
    }

    public function searchUsers($query) {
        $query = "%$query%";
        $stmt = $this->conn->prepare("
            SELECT id, name, email, profile_image 
            FROM tb_users 
            WHERE (name LIKE ? OR email LIKE ?) AND id != ?
        ");
        $stmt->bind_param("ssi", $query, $query, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    }

    public function sendMessage($sender_id, $receiver_id, $message, $file_name = null, $file_type = null, $file_size = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO tb_messages (sender_id, receiver_id, message, file_name, file_type, file_size, is_read) 
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->bind_param("iisssi", $sender_id, $receiver_id, $message, $file_name, $file_type, $file_size);
        $success = $stmt->execute();
        $stmt->close();
        return ['success' => $success];
    }

    public function getLatestMessage($user_id, $friend_id) {
        $stmt = $this->conn->prepare("
            SELECT sender_id, message, file_name, file_type, file_size, timestamp 
            FROM tb_messages 
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY timestamp DESC LIMIT 1
        ");
        $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $latest = $result->fetch_assoc();
        $stmt->close();
        return $latest;
    }

    public function deleteFriend($user_id, $friend_id) {
        $stmt = $this->conn->prepare("DELETE FROM tb_friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        return ['success' => $success, 'message' => $success ? 'Friend deleted' : 'Failed to delete friend'];
    }

    public function getLastSeen($user_id) {
        $stmt = $this->conn->prepare("SELECT last_seen FROM tb_users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['last_seen'] ?? null;
    }
    
    public function updateLastSeen($user_id, $last_seen) {
        $stmt = $this->conn->prepare("UPDATE tb_users SET last_seen = ? WHERE id = ?");
        $stmt->bind_param("si", $last_seen, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    public function uploadFile($sender_id, $receiver_id, $message, $file) {
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size exceeds 10MB limit'];
        }

        $uploadDir = "./upload/file_chats/$sender_id/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '-' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        $fileType = $file['type'];
        $fileSize = $file['size'];

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $this->sendMessage($sender_id, $receiver_id, $message, $fileName, $fileType, $fileSize);
            return [
                'success' => true,
                'file_name' => $fileName,
                'file_type' => $fileType,
                'file_size' => $fileSize
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file'];
        }
    }

    public function createGroup($user_id, $name) {
        $stmt = $this->conn->prepare("INSERT INTO tb_groups (name, created_by) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $user_id);
        $success = $stmt->execute();
        $group_id = $this->conn->insert_id;
        $stmt->close();

        if ($success) {
            $stmt = $this->conn->prepare("INSERT INTO tb_group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $group_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        return ['success' => $success, 'message' => $success ? 'Group created' : 'Failed to create group'];
    }

    public function getGroups($user_id) {
        $stmt = $this->conn->prepare("
            SELECT g.id, g.name, g.created_by 
            FROM tb_groups g 
            JOIN tb_group_members gm ON g.id = gm.group_id 
            WHERE gm.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($groups as &$group) {
            $group['latest_message'] = $this->getLatestGroupMessage($group['id']);
        }
        usort($groups, function($a, $b) {
            $aTime = $a['latest_message'] ? strtotime($a['latest_message']['timestamp']) : 0;
            $bTime = $b['latest_message'] ? strtotime($b['latest_message']['timestamp']) : 0;
            return $bTime - $aTime;
        });
        return $groups;
    }

    public function getGroupMessages($group_id) {
        $stmt = $this->conn->prepare("
            SELECT id, sender_id, message, file_name, file_type, file_size, timestamp 
            FROM tb_group_messages 
            WHERE group_id = ? 
            ORDER BY timestamp ASC
        ");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $messages;
    }

    public function sendGroupMessage($sender_id, $group_id, $message, $file_name = null, $file_type = null, $file_size = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO tb_group_messages (sender_id, group_id, message, file_name, file_type, file_size) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisssi", $sender_id, $group_id, $message, $file_name, $file_type, $file_size);
        $success = $stmt->execute();
        $stmt->close();
        return ['success' => $success];
    }

    public function getLatestGroupMessage($group_id) {
        $stmt = $this->conn->prepare("
            SELECT sender_id, message, timestamp 
            FROM tb_group_messages 
            WHERE group_id = ? 
            ORDER BY timestamp DESC LIMIT 1
        ");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $latest = $result->fetch_assoc();
        $stmt->close();
        return $latest;
    }

    public function uploadGroupFile($sender_id, $group_id, $message, $file) {
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size exceeds 10MB limit'];
        }

        $uploadDir = "./upload/file_chats/$sender_id/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '-' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        $fileType = $file['type'];
        $fileSize = $file['size'];

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $this->sendGroupMessage($sender_id, $group_id, $message, $fileName, $fileType, $fileSize);
            return [
                'success' => true,
                'file_name' => $fileName,
                'file_type' => $fileType,
                'file_size' => $fileSize
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file'];
        }
    }
}
?>