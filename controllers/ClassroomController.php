<?php
if (!class_exists('ClassroomController')) {
    class ClassroomController {
        private $conn;

        public function __construct($dbConnection) {
            $this->conn = $dbConnection;
        }

        public function checkAccess() {
            if (!isset($_SESSION['user_id'])) {
                header("Location: index.php?page=login");
                exit();
            }
            return true;
        }

        public function canStartVideoCall($classroom_id) {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("SELECT role FROM tb_classroom_members WHERE classroom_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $classroom_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result && $result['role'] === 'lecturer';
        }

        public function createMeeting($classroom_id, $type, $date = null, $time = null) {
            $user_id = $_SESSION['user_id'];
            $meeting_code = strtoupper(substr(md5(uniqid()), 0, 8));
            $meeting_link = "index.php?page=video_call_meeting&code=" . $meeting_code;

            $scheduled_at = ($type === 'scheduled' && $date && $time) ? "$date $time" : null;

            $stmt = $this->conn->prepare("
                INSERT INTO tb_meetings (classroom_id, creator_id, meeting_code, meeting_link, type, scheduled_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iissss", $classroom_id, $user_id, $meeting_code, $meeting_link, $type, $scheduled_at);
            $stmt->execute();
            $meeting_id = $stmt->insert_id;
            $stmt->close();

            return ['success' => true, 'meeting_link' => $meeting_link, 'meeting_id' => $meeting_id];
        }

        public function getMeetingDetails($meeting_code) {
            $stmt = $this->conn->prepare("
                SELECT m.*, c.title as classroom_title 
                FROM tb_meetings m 
                JOIN tb_classrooms c ON m.classroom_id = c.id 
                WHERE m.meeting_code = ?
            ");
            $stmt->bind_param("s", $meeting_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $meeting = $result->fetch_assoc();
            $stmt->close();

            return $meeting ?: [];
        }

        public function createClassroom($title, $description, $image = null) {
            $user_id = $_SESSION['user_id'];
            $class_code = $this->generateClassCode();
            $class_link = "index.php?page=classroom&code=" . $class_code;
            $classroom_image = $this->uploadImage($image);

            $stmt = $this->conn->prepare("
                INSERT INTO tb_classrooms (creator_id, title, description, class_code, class_link, type, classroom_image, created_at) 
                VALUES (?, ?, ?, ?, ?, 'public', ?, NOW())
            ");
            $stmt->bind_param("isssss", $user_id, $title, $description, $class_code, $class_link, $classroom_image);
            
            if ($stmt->execute()) {
                $classroom_id = $stmt->insert_id;
                $this->addMember($classroom_id, $user_id, 'lecturer');
                return [
                    'success' => true,
                    'classroom_id' => $classroom_id,
                    'class_link' => $class_link,
                    'class_code' => $class_code
                ];
            }
            $stmt->close();
            return ['success' => false, 'error' => 'Failed to create classroom'];
        }

        private function generateClassCode() {
            return strtoupper(substr(md5(uniqid()), 0, 8));
        }

        private function uploadImage($file) {
            if (!$file || !isset($file['name']) || $file['error'] == UPLOAD_ERR_NO_FILE) {
                return null;
            }

            $uploadDir = './upload/classroom/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '-' . basename($file['name']);
            $targetFile = $uploadDir . $fileName;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($fileType, $allowedTypes)) {
                return null;
            } elseif ($file['size'] > 5000000) {
                return null;
            } elseif (move_uploaded_file($file['tmp_name'], $targetFile)) {
                return $fileName;
            }
            return null;
        }

        private function addMember($classroom_id, $user_id, $role = 'student') {
            $stmt = $this->conn->prepare("
                INSERT IGNORE INTO tb_classroom_members (classroom_id, user_id, role, joined_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iis", $classroom_id, $user_id, $role);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }

        public function getMyClassrooms() {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("
                SELECT c.id, c.title, c.description, c.class_code, c.class_link, c.type, c.classroom_image, 
                       COUNT(cm.user_id) as member_count
                FROM tb_classrooms c
                LEFT JOIN tb_classroom_members cm ON c.id = cm.classroom_id
                WHERE c.creator_id = ?
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $classrooms = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $classrooms;
        }

        public function getJoinedClassrooms() {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("
                SELECT c.id, c.title, c.description, c.class_code, c.class_link, c.type, c.classroom_image, 
                       u.name as creator_name
                FROM tb_classroom_members cm
                JOIN tb_classrooms c ON cm.classroom_id = c.id
                JOIN tb_users u ON c.creator_id = u.id
                WHERE cm.user_id = ? AND cm.role = 'student'
                ORDER BY cm.joined_at DESC
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $classrooms = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $classrooms;
        }

        public function getClassroomDetails($classroom_id) {
            $stmt = $this->conn->prepare("
                SELECT c.*, u.name as creator_name 
                FROM tb_classrooms c 
                JOIN tb_users u ON c.creator_id = u.id 
                WHERE c.id = ?
            ");
            $stmt->bind_param("i", $classroom_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $classroom = $result->fetch_assoc();
            $stmt->close();

            if ($classroom) {
                $classroom['members'] = $this->getClassroomMembers($classroom_id);
            }
            return $classroom ?: [];
        }

        private function getClassroomMembers($classroom_id) {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.name, u.email, u.profile_image, cm.role, cm.joined_at
                FROM tb_classroom_members cm
                JOIN tb_users u ON cm.user_id = u.id
                WHERE cm.classroom_id = ?
            ");
            $stmt->bind_param("i", $classroom_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $members = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $members;
        }

        public function inviteUsers($classroom_id, $user_ids) {
            $success = true;
            foreach ($user_ids as $user_id) {
                if (!$this->addMember($classroom_id, $user_id, 'student')) {
                    $success = false;
                }
            }
            return $success;
        }

        public function deleteClassroom($classroom_id) {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("DELETE FROM tb_classrooms WHERE id = ? AND creator_id = ?");
            $stmt->bind_param("ii", $classroom_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                $stmt = $this->conn->prepare("DELETE FROM tb_classroom_members WHERE classroom_id = ?");
                $stmt->bind_param("i", $classroom_id);
                $stmt->execute();
                $stmt->close();
            }
            return $success;
        }

        public function joinClassroom($class_code) {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("SELECT id FROM tb_classrooms WHERE class_code = ?");
            $stmt->bind_param("s", $class_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $classroom = $result->fetch_assoc();
            $stmt->close();

            if ($classroom) {
                if ($this->addMember($classroom['id'], $user_id, 'student')) {
                    return ['success' => true, 'classroom_id' => $classroom['id']];
                }
                return ['success' => false, 'error' => 'Failed to join classroom'];
            }
            return ['success' => false, 'error' => 'Invalid class code'];
        }
    }
}
?>