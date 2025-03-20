<?php
if (!class_exists('AdminController')) {
    class AdminController {
        private $conn;

        public function __construct($dbConnection) {
            $this->conn = $dbConnection;
        }

        public function checkAdminAccess() {
            if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
                header("Location: index.php?page=login");
                exit();
            }
            return true;
        }

        public function getUserProfile() {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("SELECT name, email, profile_image FROM tb_users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user ?: [];
        }

        public function uploadProfileImage() {
            $uploadDir = './upload/image/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $upload_error = null;
            $profile_image = null;

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
                $file = $_FILES['profile_image'];
                $fileName = uniqid() . '-' . basename($file['name']);
                $targetFile = $uploadDir . $fileName;
                $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($fileType, $allowedTypes)) {
                    $upload_error = "Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
                } elseif ($file['size'] > 5000000) {
                    $upload_error = "File terlalu besar. Ukuran maksimum 5MB.";
                } else {
                    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                        $user_id = $_SESSION['user_id'];
                        $stmt = $this->conn->prepare("UPDATE tb_users SET profile_image = ? WHERE id = ?");
                        $stmt->bind_param("si", $fileName, $user_id);
                        if ($stmt->execute()) {
                            $profile_image = $fileName;
                        } else {
                            $upload_error = "Gagal menyimpan gambar ke database.";
                        }
                        $stmt->close();
                    } else {
                        $upload_error = "Gagal mengunggah gambar.";
                    }
                }
            }

            return [
                'profile_image' => $profile_image,
                'upload_error' => $upload_error
            ];
        }

        public function getMeetings() {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("SELECT id, date, time, title, platform FROM tb_meetings WHERE user_id = ? ORDER BY date, time");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $meetings = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $meetings;
        }

        public function addMeeting($date, $time, $title, $platform, $invited_users = []) {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("INSERT INTO tb_meetings (user_id, date, time, title, platform) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $date, $time, $title, $platform);
            
            if ($stmt->execute()) {
                $meeting_id = $stmt->insert_id;
                $stmt->close();

                if (!empty($invited_users)) {
                    $this->inviteUsers($meeting_id, $invited_users);
                }
                return true;
            }
            $stmt->close();
            return false;
        }

        public function getUsersForInvite() {
            $stmt = $this->conn->prepare("SELECT id, name, email, profile_image FROM tb_users WHERE id != ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $users;
        }

        private function inviteUsers($meeting_id, $user_ids) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO tb_meeting_invites (meeting_id, user_id) VALUES (?, ?)");
            foreach ($user_ids as $user_id) {
                $stmt->bind_param("ii", $meeting_id, $user_id);
                $stmt->execute();
            }
            $stmt->close();
        }

        public function deleteMeeting($meeting_id) {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("DELETE FROM tb_meetings WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $meeting_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                $stmt = $this->conn->prepare("DELETE FROM tb_meeting_invites WHERE meeting_id = ?");
                $stmt->bind_param("i", $meeting_id);
                $stmt->execute();
                $stmt->close();
            }
            return $success;
        }

        public function getMeetingDetails($meeting_id) {
            $stmt = $this->conn->prepare("SELECT date, time, title, platform, user_id FROM tb_meetings WHERE id = ?");
            $stmt->bind_param("i", $meeting_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $meeting = $result->fetch_assoc();
            $stmt->close();

            if ($meeting) {
                $stmt = $this->conn->prepare("SELECT name FROM tb_users WHERE id = ?");
                $stmt->bind_param("i", $meeting['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $creator = $result->fetch_assoc();
                $meeting['creator'] = $creator ? $creator['name'] : 'Unknown';
                $stmt->close();

                $stmt = $this->conn->prepare("
                    SELECT u.id, u.name, u.email, u.profile_image 
                    FROM tb_meeting_invites mi 
                    JOIN tb_users u ON mi.user_id = u.id 
                    WHERE mi.meeting_id = ?
                ");
                $stmt->bind_param("i", $meeting_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $meeting['invited_users'] = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
            return $meeting ?: [];
        }

        public function searchUsers($query) {
            $query = "%{$query}%";
            $stmt = $this->conn->prepare("SELECT id, name, email, profile_image FROM tb_users WHERE (name LIKE ? OR email LIKE ?) AND id != ?");
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return [];
            }
            $stmt->bind_param("ssi", $query, $query, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return [];
            }
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $users;
        }

        public function getInvitedMeetings() {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("
                SELECT m.id, m.date, m.time, m.title, m.platform, u.name as creator 
                FROM tb_meeting_invites mi 
                JOIN tb_meetings m ON mi.meeting_id = m.id 
                JOIN tb_users u ON m.user_id = u.id 
                WHERE mi.user_id = ? ORDER BY m.date, m.time
            ");
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return [];
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $meetings = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $meetings;
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
    
        public function getFollowing($user_id) {
            $stmt = $this->conn->prepare("
                SELECT friend_id FROM tb_friends 
                WHERE user_id = ? AND status IN ('accepted', 'pending')
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $following = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $following;
        }
    
        public function getFollowers($user_id) {
            $stmt = $this->conn->prepare("
                SELECT user_id FROM tb_friends 
                WHERE friend_id = ? AND status = 'accepted'
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $followers = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $followers;
        }
    }
}
?>