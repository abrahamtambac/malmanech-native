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
            $success = $stmt->execute();
            $meeting_id = $stmt->insert_id;
            $stmt->close();

            return ['success' => $success, 'meeting_link' => $meeting_link, 'meeting_id' => $meeting_id];
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
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $result ?: [];
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
                $stmt->close();
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

        private function uploadAttendancePhoto($file) {
            if (!$file || !isset($file['name']) || $file['error'] == UPLOAD_ERR_NO_FILE) {
                return null;
            }

            $uploadDir = './upload/attendance/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '-' . basename($file['name']);
            $targetFile = $uploadDir . $fileName;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png'];

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
                SELECT c.*, u.name as creator_name, u.id as creator_id
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

        public function getClassroomMembers($classroom_id) {
            $stmt = $this->conn->prepare("
                SELECT u.id, u.name, u.email, u.profile_image, cm.role, cm.joined_at
                FROM tb_classroom_members cm
                JOIN tb_users u ON cm.user_id = u.id
                WHERE cm.classroom_id = ?
            ");
            $stmt->bind_param("i", $classroom_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result;
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

        public function createAttendance($activity_id, $classroom_id, $title, $start_time = null, $end_time = null) {
            $user_id = $_SESSION['user_id'];
            if (!$this->isLecturer($classroom_id)) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa membuat absensi'];
            }

            $stmt = $this->conn->prepare("
                INSERT INTO tb_attendance (activity_id, classroom_id, creator_id, title, start_time, end_time, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiisss", $activity_id, $classroom_id, $user_id, $title, $start_time, $end_time);
            $success = $stmt->execute();
            $attendance_id = $stmt->insert_id;
            $stmt->close();
            return $success ? ['success' => true, 'attendance_id' => $attendance_id] : ['success' => false, 'error' => 'Gagal membuat absensi'];
        }

        public function submitAttendance($attendance_id, $status, $photo, $latitude = null, $longitude = null) {
            $user_id = $_SESSION['user_id'];
            $photo_path = $this->uploadAttendancePhoto($photo);

            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tb_attendance_records WHERE attendance_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $attendance_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_row();
            $stmt->close();

            if ($result[0] > 0) {
                return ['success' => false, 'error' => 'Anda sudah melakukan absensi untuk sesi ini.'];
            }

            $stmt = $this->conn->prepare("
                INSERT INTO tb_attendance_records (attendance_id, user_id, status, photo_path, latitude, longitude, submitted_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iissss", $attendance_id, $user_id, $status, $photo_path, $latitude, $longitude);
            $success = $stmt->execute();
            $stmt->close();
            return $success ? ['success' => true] : ['success' => false, 'error' => 'Gagal menyimpan absensi'];
        }

        public function resetAttendance($attendance_id, $user_id = null) {
            if (!$this->isLecturer($this->getClassroomIdFromAttendance($attendance_id))) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa mereset absensi'];
            }

            if ($user_id) {
                $stmt = $this->conn->prepare("DELETE FROM tb_attendance_records WHERE attendance_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $attendance_id, $user_id);
            } else {
                $stmt = $this->conn->prepare("DELETE FROM tb_attendance_records WHERE attendance_id = ?");
                $stmt->bind_param("i", $attendance_id);
            }
            $success = $stmt->execute();
            $stmt->close();
            return $success ? ['success' => true] : ['success' => false, 'error' => 'Gagal mereset absensi'];
        }

        public function getAttendanceRecords($attendance_id) {
            $stmt = $this->conn->prepare("
                SELECT ar.*, u.name 
                FROM tb_attendance_records ar
                JOIN tb_users u ON ar.user_id = u.id
                WHERE ar.attendance_id = ?
            ");
            $stmt->bind_param("i", $attendance_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result;
        }

        public function getAttendanceByActivity($activity_id) {
            $stmt = $this->conn->prepare("
                SELECT * FROM tb_attendance 
                WHERE activity_id = ?
            ");
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result;
        }

        public function getActivityDetails($activity_id) {
            $stmt = $this->conn->prepare("SELECT * FROM tb_classroom_activities WHERE id = ?");
            $stmt->bind_param("i", $activity_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result ?: [];
        }

        public function getAttendanceDetails($attendance_id) {
            $stmt = $this->conn->prepare("SELECT * FROM tb_attendance WHERE id = ?");
            $stmt->bind_param("i", $attendance_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result ?: [];
        }

        public function updateActivity($activity_id, $title, $description = null, $content = null, $file = null) {
            if (!$activity_id || !$title) {
                return ['success' => false, 'error' => 'Activity ID and Title are required'];
            }

            if (!$this->isLecturer($this->conn->query("SELECT classroom_id FROM tb_classroom_activities WHERE id = $activity_id")->fetch_assoc()['classroom_id'])) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa mengedit activity'];
            }

            $file_name = $file && $file['error'] != UPLOAD_ERR_NO_FILE ? $this->uploadImage($file) : $content;
            $is_link = $content && filter_var($content, FILTER_VALIDATE_URL) ? 1 : 0;

            $stmt = $this->conn->prepare("
                UPDATE tb_classroom_activities 
                SET title = ?, description = ?, file_name = ?, is_link = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssii", $title, $description, $file_name, $is_link, $activity_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success ? ['success' => true] : ['success' => false, 'error' => 'Gagal memperbarui activity'];
        }

        public function updateAttendance($attendance_id, $title, $start_time, $end_time) {
            if (!$attendance_id || !$title || !$start_time || !$end_time) {
                return ['success' => false, 'error' => 'Attendance ID, Title, Start Time, and End Time are required'];
            }

            if (!$this->isLecturer($this->getClassroomIdFromAttendance($attendance_id))) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa mengedit absensi'];
            }

            $stmt = $this->conn->prepare("
                UPDATE tb_attendance 
                SET title = ?, start_time = ?, end_time = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $title, $start_time, $end_time, $attendance_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success ? ['success' => true] : ['success' => false, 'error' => 'Gagal memperbarui absensi'];
        }

        public function createAssignment($classroom_id, $activity_id, $title, $description, $due_date, $content, $file = null) {
            if (!$this->isLecturer($classroom_id)) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa membuat assignment'];
            }

            $file_name = $file ? $this->uploadImage($file) : $content;
            $is_link = filter_var($content, FILTER_VALIDATE_URL) ? 1 : 0;

            $stmt = $this->conn->prepare("
                INSERT INTO tb_assignments (classroom_id, activity_id, title, description, file_name, is_link, due_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iisssis", $classroom_id, $activity_id, $title, $description, $file_name, $is_link, $due_date);
            $success = $stmt->execute();
            $assignment_id = $stmt->insert_id;
            $stmt->close();

            return $success ? ['success' => true, 'assignment_id' => $assignment_id] : ['success' => false, 'error' => 'Gagal membuat assignment'];
        }

        public function getAssignmentDetails($assignment_id) {
            $stmt = $this->conn->prepare("
                SELECT a.*, ca.title as activity_title 
                FROM tb_assignments a 
                JOIN tb_classroom_activities ca ON a.activity_id = ca.id 
                WHERE a.id = ?
            ");
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result ?: [];
        }

        public function updateAssignment($assignment_id, $activity_id, $title, $description = null, $due_date = null, $content = null, $file = null) {
            if (!$assignment_id || !$activity_id || !$title) {
                return ['success' => false, 'error' => 'Assignment ID, Activity ID, dan Title diperlukan'];
            }

            $assignment = $this->getAssignmentDetails($assignment_id);
            if (!$this->isLecturer($assignment['classroom_id'])) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa mengedit assignment'];
            }

            $file_name = $file && $file['error'] != UPLOAD_ERR_NO_FILE ? $this->uploadImage($file) : $content;
            $is_link = $content && filter_var($content, FILTER_VALIDATE_URL) ? 1 : 0;

            $stmt = $this->conn->prepare("
                UPDATE tb_assignments 
                SET activity_id = ?, title = ?, description = ?, file_name = ?, is_link = ?, due_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("isssisi", $activity_id, $title, $description, $file_name, $is_link, $due_date, $assignment_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success ? ['success' => true] : ['success' => false, 'error' => 'Gagal memperbarui assignment'];
        }

        public function submitAssignment($assignment_id, $user_id, $submission_type, $submission_content, $file = null) {
            $assignment = $this->getAssignmentDetails($assignment_id);
            if (!$this->isMember($assignment['classroom_id'], $user_id)) {
                return ['success' => false, 'error' => 'Anda bukan anggota kelas ini'];
            }
        
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tb_assignment_submissions WHERE assignment_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $assignment_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_row();
            if ($result[0] > 0) {
                return ['success' => false, 'error' => 'Anda sudah mengumpulkan tugas ini'];
            }
            $stmt->close();
        
            $file_name = null;
            if ($submission_type === 'file' && $file && $file['error'] != UPLOAD_ERR_NO_FILE) {
                $uploadDir = "./upload/mahasiswa/assignment/{$user_id}/";
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = uniqid() . '-' . basename($file['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $file_name = $fileName;
                } else {
                    return ['success' => false, 'error' => 'Gagal mengupload file'];
                }
            } elseif ($submission_type === 'link') {
                $file_name = filter_var($submission_content, FILTER_VALIDATE_URL) ? $submission_content : null;
                if (!$file_name) return ['success' => false, 'error' => 'Link tidak valid'];
            } elseif ($submission_type === 'text') {
                // Debugging: Log nilai submission_content
                error_log("Received Text Submission - Content: " . ($submission_content ?? 'null'));
                if (empty(trim($submission_content))) {
                    error_log("Validation failed: Text content is empty or null");
                    return ['success' => false, 'error' => 'Jawaban teks tidak boleh kosong'];
                }
                $file_name = $submission_content;
            } else {
                return ['success' => false, 'error' => 'Tipe pengumpulan tidak valid'];
            }
        
            error_log("Saving - Assignment ID: $assignment_id, User ID: $user_id, Type: $submission_type, File Name: " . ($file_name ?? 'null'));
        
            $status = (new DateTime() > new DateTime($assignment['due_date'])) ? 'late' : 'submitted';
        
            $stmt = $this->conn->prepare("
                INSERT INTO tb_assignment_submissions (assignment_id, user_id, submission_type, file_name, submitted_at, status)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return ['success' => false, 'error' => 'Gagal menyiapkan query'];
            }
        
            $stmt->bind_param("iisss", $assignment_id, $user_id, $submission_type, $file_name, $status);
            $success = $stmt->execute();
            if (!$success) {
                error_log("Execute failed: " . $stmt->error);
                return ['success' => false, 'error' => 'Gagal menyimpan: ' . $stmt->error];
            }
            $stmt->close();
        
            return ['success' => true];
        }
        public function getAssignmentSubmissions($assignment_id) {
            $stmt = $this->conn->prepare("
                SELECT s.*, u.name 
                FROM tb_assignment_submissions s 
                JOIN tb_users u ON s.user_id = u.id 
                WHERE s.assignment_id = ?
            ");
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result;
        }

        public function gradeAssignment($submission_id, $grade, $feedback) {
            $stmt = $this->conn->prepare("
                SELECT a.classroom_id 
                FROM tb_assignment_submissions s 
                JOIN tb_assignments a ON s.assignment_id = a.id 
                WHERE s.id = ?
            ");
            $stmt->bind_param("i", $submission_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$this->isLecturer($result['classroom_id'])) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa memberikan nilai'];
            }

            $stmt = $this->conn->prepare("
                UPDATE tb_assignment_submissions 
                SET grade = ?, feedback = ?, status = 'graded' 
                WHERE id = ?
            ");
            $stmt->bind_param("dsi", $grade, $feedback, $submission_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success ? ['success' => true] : ['success' => false, 'error' => 'Gagal memberikan nilai'];
        }

        public function resetAssignmentSubmission($submission_id) {
            $stmt = $this->conn->prepare("
                SELECT a.classroom_id 
                FROM tb_assignment_submissions s 
                JOIN tb_assignments a ON s.assignment_id = a.id 
                WHERE s.id = ?
            ");
            $stmt->bind_param("i", $submission_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$this->isLecturer($result['classroom_id'])) {
                return ['success' => false, 'error' => 'Hanya dosen yang bisa mereset pengumpulan tugas'];
            }

            $stmt = $this->conn->prepare("DELETE FROM tb_assignment_submissions WHERE id = ?");
            $stmt->bind_param("i", $submission_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success ? ['success' => true] : ['success' => false, 'error' => 'Gagal mereset pengumpulan tugas'];
        }

        private function isLecturer($classroom_id) {
            $user_id = $_SESSION['user_id'];
            $stmt = $this->conn->prepare("SELECT role FROM tb_classroom_members WHERE classroom_id = ? AND user_id = ? AND role = 'lecturer'");
            $stmt->bind_param("ii", $classroom_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result && $result['role'] === 'lecturer';
        }

        private function isMember($classroom_id, $user_id) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tb_classroom_members WHERE classroom_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $classroom_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_row();
            $stmt->close();
            return $result[0] > 0;
        }

        private function getClassroomIdFromAttendance($attendance_id) {
            $stmt = $this->conn->prepare("SELECT classroom_id FROM tb_attendance WHERE id = ?");
            $stmt->bind_param("i", $attendance_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result ? $result['classroom_id'] : null;
        }
        public function getMemberActivities($user_id, $classroom_id) {
            $stmt = $this->conn->prepare("
                SELECT a.title, s.file_name, s.status, s.submitted_at, s.id
                FROM tb_classroom_activities a
                LEFT JOIN tb_activity_submissions s ON a.id = s.activity_id AND s.user_id = ?
                WHERE a.classroom_id = ?
            ");
            $stmt->bind_param("ii", $user_id, $classroom_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $this->sendJsonResponse($result);
        }

        public function sendJsonResponse($data) {
            header('Content-Type: application/json');
            echo json_encode($data);
            exit();
        }
    }
}
?>