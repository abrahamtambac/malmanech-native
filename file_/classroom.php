<?php
include_once './controllers/ClassroomController.php';
date_default_timezone_set('Asia/Jakarta');

$classroomController = new ClassroomController($conn);
$classroom_id = $_GET['classroom_id'] ?? null;
$code = $_GET['code'] ?? null;
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if ($code && !$classroom_id) {
    $stmt = $conn->prepare("SELECT id FROM tb_classrooms WHERE class_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $classroom = $result->fetch_assoc();
    $stmt->close();
    if ($classroom) {
        $classroom_id = $classroom['id'];
    }
}

$classroom = $classroomController->getClassroomDetails($classroom_id);
if (!$classroom) {
    include 'file_/404/not_found_1.php';
    exit();
}

$isMember = false;
$isLecturer = false;
if ($isLoggedIn) {
    foreach ($classroom['members'] as $member) {
        if ($member['id'] == $user_id) {
            $isMember = true;
            $isLecturer = ($member['role'] === 'lecturer');
            break;
        }
    }
}

// Handler untuk join_class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_class']) && $isLoggedIn) {
    $result = $classroomController->joinClassroom($classroom['class_code']);
    if ($result['success']) {
        header("Location: index.php?page=classroom&classroom_id=" . $classroom_id);
        exit();
    }
}

// Handler untuk leave_class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_class']) && $isLoggedIn && $isMember && !$isLecturer) {
    $stmt = $conn->prepare("DELETE FROM tb_classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $classroom_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=admin_dashboard");
    exit();
}

// Handler untuk add_activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity']) && $isLecturer) {
    $title = $_POST['activity_title'];
    $description = $_POST['activity_description'];
    $content = $_POST['activity_content'] ?? '';
    $type = $_POST['activity_type'] ?? 'material';
    $file_name = null;
    $is_link = filter_var($content, FILTER_VALIDATE_URL) ? 1 : 0;

    if (!$is_link && isset($_FILES['activity_file']) && $_FILES['activity_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['activity_file'];
        $uploadDir = "./upload/dosen/activity/{$user_id}/";
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid() . '-' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) $file_name = $fileName;
    } else {
        $file_name = $content;
    }

    $stmt = $conn->prepare("INSERT INTO tb_classroom_activities (classroom_id, title, description, file_name, is_link, type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssis", $classroom_id, $title, $description, $file_name, $is_link, $type);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=classroom&classroom_id=" . $classroom_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'submit_assignment') {
    $assignment_id = $_POST['assignment_id'] ?? null;
    $submission_type = $_POST['submission_type'] ?? null;
    $submission_content = $_POST['submission_content'] ?? null;
    $file = $_FILES['submission_file'] ?? null;

    // Debugging: Log semua data yang diterima
    error_log("Received Data - Assignment ID: " . ($assignment_id ?? 'null') . 
              ", Type: " . ($submission_type ?? 'null') . 
              ", Content: " . ($submission_content ?? 'null') . 
              ", File: " . ($file ? print_r($file, true) : 'null'));

    if ($assignment_id && $submission_type && $user_id) {
        $result = $classroomController->submitAssignment($assignment_id, $user_id, $submission_type, $submission_content, $file);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Data tidak lengkap: ' . 
            "assignment_id=$assignment_id, submission_type=$submission_type, user_id=$user_id"]);
        exit();
    }
}

// Handler untuk submit_activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_activity']) && $isMember && !$isLecturer) {
    $activity_id = $_POST['activity_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tb_activity_submissions WHERE activity_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $activity_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_row();
    if ($result[0] == 0) {
        $file = $_FILES['submission_file'];
        $uploadDir = "./upload/mahasiswa/activity/{$user_id}/";
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid() . '-' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $stmt = $conn->prepare("INSERT INTO tb_activity_submissions (activity_id, user_id, file_name, submitted_at, status) VALUES (?, ?, ?, NOW(), 'submitted')");
            $stmt->bind_param("iis", $activity_id, $user_id, $fileName);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: index.php?page=classroom&classroom_id=" . $classroom_id);
    exit();
}

// Handler untuk add_assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment']) && $isLecturer) {
    $activity_id = $_POST['activity_id'];
    $title = $_POST['assignment_title'];
    $description = $_POST['assignment_description'];
    $due_date = $_POST['due_date'];
    $content = $_POST['assignment_content'] ?? '';
    $file_name = null;
    $is_link = filter_var($content, FILTER_VALIDATE_URL) ? 1 : 0;

    if (!$is_link && isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['assignment_file'];
        $uploadDir = "./upload/dosen/assignment/{$user_id}/";
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid() . '-' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) $file_name = $fileName;
    } else {
        $file_name = $content;
    }

    $result = $classroomController->createAssignment($classroom_id, $activity_id, $title, $description, $due_date, $file_name);
    if ($result['success']) {
        header("Location: index.php?page=classroom&classroom_id=" . $classroom_id);
        exit();
    }
}

// Ambil data activities dan submissions
$stmt = $conn->prepare("SELECT * FROM tb_classroom_activities WHERE classroom_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$submissions = [];
foreach ($activities as $activity) {
    $stmt = $conn->prepare("SELECT s.*, u.name FROM tb_activity_submissions s JOIN tb_users u ON s.user_id = u.id WHERE activity_id = ?");
    $stmt->bind_param("i", $activity['id']);
    $stmt->execute();
    $submissions[$activity['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$assignments = [];
$assignment_submissions = [];
foreach ($activities as $activity) {
    $stmt = $conn->prepare("SELECT * FROM tb_assignments WHERE activity_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $activity['id']);
    $stmt->execute();
    $assignments[$activity['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($assignments[$activity['id']] as $assignment) {
        $stmt = $conn->prepare("SELECT s.*, u.name FROM tb_assignment_submissions s JOIN tb_users u ON s.user_id = u.id WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignment['id']);
        $stmt->execute();
        $assignment_submissions[$assignment['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<?php include '_partials/_admin_head.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item text-dark">
                <a class="text-dark" href="index.php?page=home" style="text-decoration: none;">Home</a>
            </li>
            <li class="breadcrumb-item text-dark">
                <a class="text-dark" href="index.php?page=admin_dashboard" style="text-decoration: none;">Dashboard</a>
            </li>
            <li class="breadcrumb-item active text-primary fw-bolder" aria-current="page">
                <?php echo htmlspecialchars($classroom['title']); ?>
            </li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $classroom['classroom_image'] ? './upload/classroom/' . $classroom['classroom_image'] : './image/classroom_icon.png'; ?>" 
                                 alt="Classroom Icon" 
                                 class="rounded-circle me-3 border border-primary border-2 shadow-sm" 
                                 style="width: 80px; height: 80px; object-fit: cover;">
                            <div>
                                <h1 class="fw-bolder mb-1 text-dark"><?php echo htmlspecialchars($classroom['title']); ?></h1>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($classroom['description']); ?></p>
                                <small class="text-muted">
                                    Class Code: <span class="badge bg-primary-subtle text-primary"><?php echo $classroom['class_code']; ?></span> | 
                                    Created by: <span class="badge bg-success-subtle text-success"><?php echo $classroom['creator_name']; ?></span>
                                </small>
                            </div>
                        </div>
                        <?php if ($isMember && $isLecturer): ?>
                            <button class="btn btn-success shadow-sm" 
                                    id="start-video-call" 
                                    data-classroom-id="<?php echo $classroom_id; ?>" 
                                    style="border-radius: 10px;">
                                <i class="bi bi-camera-video"></i> Start Video Call
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if ($isMember && !$isLecturer): ?>
                        <div id="meeting-notification" 
                             class="alert alert-info mt-3 d-none" 
                             role="alert" 
                             style="border-radius: 10px;">
                            <span id="meeting-message"></span>
                            <button class="btn btn-sm btn-primary ms-2" id="join-meeting-btn">Join Now</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Video Call Modal -->
        <div class="modal fade" 
             id="videoCallModal" 
             tabindex="-1" 
             aria-labelledby="videoCallModalLabel" 
             aria-hidden="true" 
             data-bs-backdrop="static" 
             data-bs-keyboard="false">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="videoCallModalLabel">
                            Video Call - <?php echo htmlspecialchars($classroom['title']); ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 d-flex">
                        <div id="participant-sidebar" 
                             class="bg-light p-3" 
                             style="width: 250px; height: 60vh; overflow-y: auto; border-right: 1px solid #ddd;">
                            <h6 class="fw-bold">Peserta</h6>
                            <ul id="participant-list" class="list-unstyled"></ul>
                        </div>
                        <div id="video-container" 
                             class="flex-grow-1" 
                             style="background: #f0f2f5; height: 60vh; position: relative; overflow: hidden;">
                            <div id="screen-share-container" 
                                 class="w-100 d-none" 
                                 style="height: 50%; margin-bottom: 10px;">
                                <video id="screen-share-video" 
                                       autoplay 
                                       playsinline 
                                       class="w-100 h-100" 
                                       style="border: 2px solid #ff5733; border-radius: 10px; cursor: pointer;"></video>
                                <span id="screen-share-label" 
                                      class="video-label" 
                                      style="background: rgba(255, 87, 51, 0.7);"></span>
                            </div>
                            <div id="participant-videos" 
                                 class="d-flex flex-wrap justify-content-center w-100" 
                                 style="height: 50%; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div id="video-controls" class="me-auto d-flex align-items-center">
                            <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" 
                                    id="mute-audio" 
                                    title="Mute">
                                <i class="bi bi-mic"></i>
                            </button>
                            <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" 
                                    id="disable-video" 
                                    title="Turn off camera">
                                <i class="bi bi-camera-video"></i>
                            </button>
                            <button class="btn btn-outline-secondary rounded-circle me-2 meet-btn" 
                                    id="share-screen" 
                                    title="Share screen">
                                <i class="bi bi-display"></i>
                            </button>
                            <select id="cameraSelect" class="form-select me-2" style="width: auto;"></select>
                            <select id="micSelect" class="form-select me-2" style="width: auto;"></select>
                            <canvas id="audioVisualizer" width="100" height="50" class="me-2"></canvas>
                        </div>
                        <button class="btn btn-danger shadow-sm" 
                                id="end-video-call-btn" 
                                style="border-radius: 10px;">End Call</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Activity Modal -->
        <div class="modal fade" 
             id="addActivityModal" 
             tabindex="-1" 
             aria-labelledby="addActivityModalLabel" 
             aria-hidden="true" 
             data-bs-backdrop="static">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addActivityModalLabel">
                            Tambah Activity - <?php echo htmlspecialchars($classroom['title']); ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 d-flex">
                        <div id="activity-sidebar" 
                             class="bg-light p-3" 
                             style="width: 250px; height: 60vh; overflow-y: auto; border-right: 1px solid #ddd;">
                            <h6 class="fw-bold">Pilihan</h6>
                            <ul class="list-unstyled">
                                <li><button class="btn btn-link text-start w-100 activity-option text-dark" data-type="material" style="text-decoration:none;"><i class="bi bi-book me-2"></i>Activity dan Materi</button></li>
                                <li><button class="btn btn-link text-start w-100 activity-option text-dark" data-type="attendance" style="text-decoration:none;"><i class="bi bi-check-square me-2"></i>Setting Presensi</button></li>
                                <li><button class="btn btn-link text-start w-100 activity-option text-dark" data-type="assignment" style="text-decoration:none;"><i class="bi bi-file-earmark-text me-2"></i>Tambah Penugasan</button></li>
                            </ul>
                        </div>
                        <div id="activity-content" 
                             class="flex-grow-1 p-4" 
                             style="height: 60vh; overflow-y: auto;">
                            <form method="POST" enctype="multipart/form-data" id="materialForm" class="activity-form">
                                <input type="hidden" name="activity_type" value="material">
                                <div class="mb-3">
                                    <label class="form-label">Judul Activity</label>
                                    <input type="text" name="activity_title" class="form-control shadow-sm" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="activity_description" class="form-control shadow-sm" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">File atau Link</label>
                                    <input type="text" name="activity_content" class="form-control shadow-sm" placeholder="Masukkan link atau kosongkan jika upload file">
                                    <input type="file" name="activity_file" class="form-control shadow-sm mt-2">
                                </div>
                                <button type="submit" name="add_activity" class="btn btn-primary shadow-sm">Tambah</button>
                            </form>

                            <form id="attendanceForm" class="activity-form d-none">
                                <div class="mb-3">
                                    <label class="form-label">Judul Absensi</label>
                                    <input type="text" id="attendance_title" class="form-control shadow-sm" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Pilih Activity</label>
                                    <select id="attendance_activity_id" class="form-select shadow-sm" required>
                                        <option value="">Pilih Activity</option>
                                        <?php foreach ($activities as $activity): ?>
                                            <option value="<?php echo $activity['id']; ?>"><?php echo htmlspecialchars($activity['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Waktu Mulai</label>
                                    <input type="datetime-local" id="attendance_start_time" class="form-control shadow-sm" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Waktu Selesai</label>
                                    <input type="datetime-local" id="attendance_end_time" class="form-control shadow-sm" required>
                                </div>
                                <button type="button" id="createAttendance" class="btn btn-primary shadow-sm">Buat Absensi</button>
                            </form>

                            <form method="POST" enctype="multipart/form-data" id="assignmentForm" class="activity-form d-none">
                                <div class="mb-3">
                                    <label class="form-label">Pilih Activity</label>
                                    <select name="activity_id" class="form-select shadow-sm" required>
                                        <option value="">Pilih Activity</option>
                                        <?php foreach ($activities as $activity): ?>
                                            <option value="<?php echo $activity['id']; ?>"><?php echo htmlspecialchars($activity['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Judul Assignment</label>
                                    <input type="text" name="assignment_title" class="form-control shadow-sm" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="assignment_description" class="form-control shadow-sm" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Jatuh Tempo</label>
                                    <input type="datetime-local" name="due_date" class="form-control shadow-sm" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Link atau File</label>
                                    <input type="text" name="assignment_content" class="form-control shadow-sm" placeholder="Masukkan link atau kosongkan jika upload file">
                                    <input type="file" name="assignment_file" class="form-control shadow-sm mt-2">
                                </div>
                                <button type="submit" name="add_assignment" class="btn btn-primary shadow-sm">Tambah</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Modal -->
        <div class="modal fade" 
             id="attendanceModal" 
             tabindex="-1" 
             aria-labelledby="attendanceModalLabel" 
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header">
                        <h5 class="modal-title" id="attendanceModalLabel">Absensi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <video id="attendanceVideo" autoplay playsinline style="width: 100%; border-radius: 10px;"></video>
                            <canvas id="attendanceCanvas" class="d-none"></canvas>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select id="attendanceStatus" class="form-select shadow-sm">
                                <option value="present">Hadir</option>
                                <option value="late">Terlambat</option>
                                <option value="sick">Sakit</option>
                                <option value="absent">Tidak Hadir</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="captureAttendance" class="btn btn-primary shadow-sm">Submit Absensi</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Activity Modal -->
        <div class="modal fade" 
             id="editActivityModal" 
             tabindex="-1" 
             aria-labelledby="editActivityModalLabel" 
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editActivityModalLabel">Edit Activity</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editActivityForm" enctype="multipart/form-data">
                            <!-- Form akan diisi secara dinamis oleh JavaScript -->
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary" id="saveActivityChanges">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Attendance Modal -->
        <div class="modal fade" 
             id="editAttendanceModal" 
             tabindex="-1" 
             aria-labelledby="editAttendanceModalLabel" 
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editAttendanceModalLabel">Edit Attendance</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editAttendanceForm">
                            <!-- Form akan diisi secara dinamis oleh JavaScript -->
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary" id="saveAttendanceChanges">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Assignment Modal -->
        <div class="modal fade" 
             id="editAssignmentModal" 
             tabindex="-1" 
             aria-labelledby="editAssignmentModalLabel" 
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editAssignmentModalLabel">Edit Assignment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editAssignmentForm" enctype="multipart/form-data">
                            <!-- Form akan diisi secara dinamis oleh JavaScript -->
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary" id="saveAssignmentChanges">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade Assignment Modal -->
        <div class="modal fade" 
             id="gradeAssignmentModal" 
             tabindex="-1" 
             aria-labelledby="gradeAssignmentModalLabel" 
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="gradeAssignmentModalLabel">Beri Nilai Assignment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="gradeAssignmentForm"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary" id="saveGradeChanges">Simpan Nilai</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Assignment Modal -->
        <div class="modal fade" 
     id="submitAssignmentModal" 
     tabindex="-1" 
     aria-labelledby="submitAssignmentModalLabel" 
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="submitAssignmentModalLabel">Submit Assignment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="submitAssignmentForm" enctype="multipart/form-data">
                    <input type="hidden" name="assignment_id" id="assignmentId">
                    <div class="mb-3">
                        <label class="form-label">Tipe Pengumpulan</label>
                        <select name="submission_type" id="submissionType" class="form-select shadow-sm" required>
                            <option value="text">Jawaban Teks</option>
                            <option value="file">Upload File</option>
                            <option value="link">Link</option>
                        </select>
                    </div>
                    <div class="mb-3" id="submissionContentWrapper">
                        <label class="form-label" id="submissionContentLabel">Konten Pengumpulan</label>
                        <textarea name="submission_content_text" id="submissionContentText" class="form-control shadow-sm" rows="3" placeholder="Ketik jawaban Anda di sini"></textarea>
                        <input type="file" name="submission_file" id="submissionContentFile" class="form-control shadow-sm d-none">
                        <input type="url" name="submission_content_link" id="submissionContentLink" class="form-control shadow-sm d-none" placeholder="Masukkan URL">
                    </div>
                    <button type="submit" class="btn btn-primary shadow-sm">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

        <!-- Modal untuk Menampilkan Jawaban Teks -->
        <div class="modal fade" 
             id="viewTextSubmissionModal" 
             tabindex="-1" 
             aria-labelledby="viewTextSubmissionModalLabel" 
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border-radius: 15px;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="viewTextSubmissionModalLabel">Jawaban Teks Mahasiswa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="textSubmissionContent" class="text-break" style="white-space: pre-wrap;"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="joinToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto">Meeting Notification</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body"></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <h5 class="fw-bolder mb-3 text-primary">Anggota (<?php echo count($classroom['members']); ?>)</h5>
                    <div class="input-group mb-3">
                        <input type="text" id="memberSearch" class="form-control shadow-sm" placeholder="Cari nama..." style="border-radius: 10px 0 0 10px;">
                        <button class="btn btn-primary shadow-sm" id="searchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                    </div>
                    <div class="list-group" id="memberList">
                        <?php foreach ($classroom['members'] as $member): ?>
                            <div class="list-group-item d-flex align-items-center justify-content-between shadow-sm mb-2" 
                                 style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $member['profile_image'] ? './upload/image/' . $member['profile_image'] : './image/robot-ai.png'; ?>" 
                                         alt="Profile" 
                                         class="rounded-circle me-2 border border-primary border-2" 
                                         style="width: 40px; height: 40px;">
                                    <div>
                                        <strong class="text-dark"><?php echo htmlspecialchars($member['name']); ?></strong>
                                        <small class="text-muted d-block">
                                            <?php echo $member['role'] === 'lecturer' ? 'Dosen' : 'Mahasiswa'; ?>
                                        </small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-info shadow-sm view-member-activity" 
                                        data-user-id="<?php echo $member['id']; ?>" 
                                        data-user-name="<?php echo htmlspecialchars($member['name']); ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#memberActivityModal" 
                                        style="border-radius: 8px;">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="memberActivityModal" tabindex="-1" aria-labelledby="memberActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title" id="memberActivityModalLabel">Aktivitas - <span id="memberActivityName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="memberActivityTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="true">Activity</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance" aria-selected="false">Attendance</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="assignment-tab" data-bs-toggle="tab" data-bs-target="#assignment" type="button" role="tab" aria-controls="assignment" aria-selected="false">Assignments</button>
                    </li>
                </ul>
                <div class="tab-content" id="memberActivityTabContent">
                    <div class="tab-pane fade show active" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                        <table class="table table-striped shadow-sm mt-3">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>File</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <?php if ($isLecturer): ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="memberActivityTable"></tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                        <table class="table table-striped shadow-sm mt-3">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <th>Foto</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody id="memberAttendanceTable"></tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="assignment" role="tabpanel" aria-labelledby="assignment-tab">
                        <table class="table table-striped shadow-sm mt-3">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Tipe</th>
                                    <th>Konten</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <th>Grade</th>
                                    <th>Feedback</th>
                                    <?php if ($isLecturer): ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="memberAssignmentTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
        <div class="col-md-9">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bolder text-primary">Selamat Datang di Classroom</h5>
                        <?php if ($isLecturer): ?>
                            <button class="btn btn-primary shadow-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#addActivityModal" 
                                    style="border-radius: 10px;">
                                <i class="bi bi-plus"></i> Tambah Activity
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted mb-3">Ini adalah ruang belajar Anda. Berikut adalah aktivitas yang tersedia:</p>
                    <?php if ($isMember): ?>
                        <div class="alert alert-success shadow-sm" role="alert" style="border-radius: 10px;">
                            Anda sudah bergabung di classroom ini!
                            <?php if (!$isLecturer): ?>
                                <button class="btn btn-sm btn-danger float-end shadow-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#leaveClassModal" 
                                        style="border-radius: 8px;">Leave Classroom</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div id="loginAlert" class="alert alert-warning d-none shadow-sm" role="alert" style="border-radius: 10px;">
                        Anda harus <a href="index.php?page=login" class="alert-link">login terlebih dahulu</a> untuk bergabung ke classroom ini.
                    </div>

                    <div class="accordion" id="activityAccordion">
                        <?php foreach ($activities as $index => $activity): ?>
                            <div class="accordion-item shadow-sm mb-3" style="border-radius: 10px;">
                                <h2 class="accordion-header" id="heading<?php echo $activity['id']; ?>">
                                    <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?php echo $activity['id']; ?>" 
                                            aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                            aria-controls="collapse<?php echo $activity['id']; ?>" 
                                            style="border-radius: 10px; background-color: #f1f3f5;">
                                        <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $activity['id']; ?>" 
                                     class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                     aria-labelledby="heading<?php echo $activity['id']; ?>" 
                                     data-bs-parent="#activityAccordion">
                                    <div class="accordion-body">
                                        <p class="text-dark"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <?php if ($activity['file_name']): ?>
                                            <p>
                                                <?php if ($activity['is_link']): ?>
                                                    <a href="<?php echo $activity['file_name']; ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary shadow-sm" 
                                                       style="border-radius: 8px;">
                                                        <i class="bi bi-link-45deg"></i> Link Materi
                                                    </a>
                                                <?php else: ?>
                                                    <a href="./upload/dosen/activity/<?php echo $classroom['creator_id']; ?>/<?php echo urlencode($activity['file_name']); ?>" 
                                                       download 
                                                       class="btn btn-sm btn-outline-primary shadow-sm" 
                                                       style="border-radius: 8px;">
                                                        <i class="bi bi-download"></i> Download Materi
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($isLecturer): ?>
                                                    <button class="btn btn-sm btn-outline-warning shadow-sm edit-activity" 
                                                            data-activity-id="<?php echo $activity['id']; ?>" 
                                                            style="border-radius: 8px;">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>

                                        <!-- Attendance Section -->
                                        <?php 
                                        $attendances = $classroomController->getAttendanceByActivity($activity['id']);
                                        foreach ($attendances as $attendance):
                                            $stmt = $conn->prepare("SELECT * FROM tb_attendance_records WHERE attendance_id = ? AND user_id = ?");
                                            $stmt->bind_param("ii", $attendance['id'], $user_id);
                                            $stmt->execute();
                                            $attendance_record = $stmt->get_result()->fetch_assoc();
                                            $stmt->close();
                                        ?>
                                            <hr/>
                                            <div class="container alert alert-primary rounded">
                                            <h5 class="fw-bolder" style="font-weight: 800;">Attendance:</h5>
                                            <h6><?php echo htmlspecialchars($attendance['title']); ?></h6>
                                            <p>
                                                <?php if ($attendance_record): ?>
                                                    <span class="text-success">
                                                        Anda sudah mengabsen pada <?php echo $attendance_record['submitted_at']; ?> 
                                                        <a href="./upload/attendance/<?php echo $attendance_record['photo_path']; ?>" target="_blank">Lihat Foto</a>
                                                    </span>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn btn-primary rounded shadow-sm attendance-link" 
                                                            data-attendance-id="<?php echo $attendance['id']; ?>" 
                                                            data-start-time="<?php echo $attendance['start_time']; ?>" 
                                                            data-end-time="<?php echo $attendance['end_time']; ?>">
                                                        <i class="bi bi-check-circle"></i> Absen Sekarang
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($isLecturer): ?>
                                                    <br/><br/>
                                                    <button class="btn btn-sm btn-outline-primary shadow-sm recap-attendance rounded" 
                                                            data-attendance-id="<?php echo $attendance['id']; ?>">
                                                        <i class="bi bi-table"></i> Rekap
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary shadow-sm reset-attendance" 
                                                            data-attendance-id="<?php echo $attendance['id']; ?>">
                                                        <i class="bi bi-arrow-repeat"></i> Reset
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning shadow-sm edit-attendance" 
                                                            data-attendance-id="<?php echo $attendance['id']; ?>" 
                                                            style="border-radius: 8px;">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                <?php endif; ?>
                                            </p>
                                            </div>
                                        <?php endforeach; ?>

                                        <!-- Assignment Section -->
                                        <?php if (isset($assignments[$activity['id']]) && !empty($assignments[$activity['id']])): ?>
                                            <hr/>
                                           
                                                <div class="container alert alert-primary rounded">
                                            <h5 class="fw-bolder" style="font-weight: 800;">Assignments</h5>
                                            <?php foreach ($assignments[$activity['id']] as $assignment): ?>
                                                <div class="mb-3">
                                                    <p><strong><?php echo htmlspecialchars($assignment['title']); ?></strong> 
                                                        <small class="text-muted">Due: <?php echo date('d M Y, H:i', strtotime($assignment['due_date'])); ?></small>
                                                        <?php if ($isLecturer): ?>
                                                            <button class="btn btn-sm btn-outline-warning shadow-sm edit-assignment" 
                                                                    data-assignment-id="<?php echo $assignment['id']; ?>" 
                                                                    style="border-radius: 8px; margin-left: 10px;">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </button>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="text-muted"><?php echo htmlspecialchars($assignment['description']); ?></p>
                                                    <?php if ($assignment['file_name']): ?>
                                                        <p>
                                                            <?php if ($assignment['is_link']): ?>
                                                                <a href="<?php echo $assignment['file_name']; ?>" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-outline-primary shadow-sm" 
                                                                   style="border-radius: 8px;">
                                                                    <i class="bi bi-link-45deg"></i> Link Tugas
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="./upload/dosen/assignment/<?php echo $classroom['creator_id']; ?>/<?php echo urlencode($assignment['file_name']); ?>" 
                                                                   download 
                                                                   class="btn btn-sm btn-outline-primary shadow-sm" 
                                                                   style="border-radius: 8px;">
                                                                    <i class="bi bi-download"></i> Download Tugas
                                                                </a>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if ($isMember && !$isLecturer): ?>
                                                        <?php
                                                        $hasSubmitted = false;
                                                        $userSubmission = null;
                                                        foreach ($assignment_submissions[$assignment['id']] as $submission) {
                                                            if ($submission['user_id'] == $user_id) {
                                                                $hasSubmitted = true;
                                                                $userSubmission = $submission;
                                                                break;
                                                            }
                                                        }
                                                        ?>
                                                        <?php if (!$hasSubmitted): ?>
                                                            <button class="btn btn-sm btn-primary shadow-sm submit-assignment-btn" 
                                                                    data-assignment-id="<?php echo $assignment['id']; ?>" 
                                                                    style="border-radius: 8px;">
                                                                <i class="bi bi-upload"></i> Submit Assignment
                                                            </button>
                                                        <?php else: ?>
                                                            <h6 class="mt-3">Submission Anda:</h6>
                                                                <table class="table table-striped shadow-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Tipe</th>
                                                                            <th>Konten</th>
                                                                            <th>Status</th>
                                                                            <th>Submitted At</th>
                                                                            <th>Grade</th>
                                                                            <th>Feedback</th>
                                                                            <th>Aksi</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($userSubmission['submission_type']); ?></td>
                                                                            <td>
                                                                                <?php if ($userSubmission['submission_type'] === 'file'): ?>
                                                                                    <a href="./upload/mahasiswa/assignment/<?php echo $user_id; ?>/<?php echo urlencode($userSubmission['file_name']); ?>" 
                                                                                       download><?php echo htmlspecialchars($userSubmission['file_name']); ?></a>
                                                                                <?php elseif ($userSubmission['submission_type'] === 'link'): ?>
                                                                                    <a href="<?php echo htmlspecialchars($userSubmission['file_name']); ?>" 
                                                                                       target="_blank"><?php echo htmlspecialchars($userSubmission['file_name']); ?></a>
                                                                                <?php else: ?>
                                                                                    <a href="#" 
                                                                                       class="view-text-submission" 
                                                                                       data-text="<?php echo htmlspecialchars($userSubmission['file_name'] ?? 'Belum ada jawaban'); ?>" 
                                                                                       data-bs-toggle="modal" 
                                                                                       data-bs-target="#viewTextSubmissionModal">Lihat Jawaban Teks</a>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td><?php echo htmlspecialchars($userSubmission['status']); ?></td>
                                                                            <td><?php echo $userSubmission['submitted_at']; ?></td>
                                                                            <td><?php echo $userSubmission['grade'] ?? '-'; ?></td>
                                                                            <td><?php echo htmlspecialchars($userSubmission['feedback'] ?? '-'); ?></td>
                                                                            <td>
                                                                                <button class="btn btn-sm btn-outline-secondary shadow-sm" disabled>
                                                                                    <i class="bi bi-lock"></i> Terkunci
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if ($isLecturer && isset($assignment_submissions[$assignment['id']])): ?>
                                                        <h6 class="mt-3">Submissions Mahasiswa:</h6>
                                                        <table class="table table-striped shadow-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Nama</th>
                                                                    <th>Tipe</th>
                                                                    <th>Konten</th>
                                                                    <th>Status</th>
                                                                    <th>Submitted At</th>
                                                                    <th>Grade</th>
                                                                    <th>Feedback</th>
                                                                    <th>Aksi</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($assignment_submissions[$assignment['id']] as $submission): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($submission['name']); ?></td>
                                                                        <td><?php echo htmlspecialchars($submission['submission_type']); ?></td>
                                                                        <td>
                                                                            <?php if ($submission['submission_type'] === 'file'): ?>
                                                                                <a href="./upload/mahasiswa/assignment/<?php echo $submission['user_id']; ?>/<?php echo urlencode($submission['file_name']); ?>" 
                                                                                   download><?php echo htmlspecialchars($submission['file_name']); ?></a>
                                                                            <?php elseif ($submission['submission_type'] === 'link'): ?>
                                                                                <a href="<?php echo htmlspecialchars($submission['file_name']); ?>" 
                                                                                   target="_blank"><?php echo htmlspecialchars($submission['file_name']); ?></a>
                                                                            <?php else: // tipe 'text' ?>
                                                                                <?php $textContent = !empty($submission['file_name']) ? htmlspecialchars($submission['file_name']) : 'Belum ada jawaban'; ?>

                                                                                <a href="#" 
                                                                                   class="view-text-submission" 
                                                                                   data-text="<?php echo $textContent; ?>" 
                                                                                   data-bs-toggle="modal" 
                                                                                   data-bs-target="#viewTextSubmissionModal">Lihat Jawaban Teks</a>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?php echo htmlspecialchars($submission['status']); ?></td>
                                                                        <td><?php echo $submission['submitted_at']; ?></td>
                                                                        <td><?php echo $submission['grade'] ?? '-'; ?></td>
                                                                        <td><?php echo htmlspecialchars($submission['feedback'] ?? '-'); ?></td>
                                                                        <td>
                                                                            <button class="btn btn-sm btn-outline-danger shadow-sm reset-submission" 
                                                                                    data-submission-id="<?php echo $submission['id']; ?>" 
                                                                                    style="border-radius: 8px;">
                                                                                <i class="bi bi-arrow-repeat"></i> Reset
                                                                            </button>
                                                                            <button class="btn btn-sm btn-outline-success shadow-sm grade-assignment" 
                                                                                    data-submission-id="<?php echo $submission['id']; ?>" 
                                                                                    style="border-radius: 8px;">
                                                                                <i class="bi bi-check2-square"></i> Grade
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Activity Submission Section -->
                                        <?php if ($isMember && !$isLecturer && $activity['type'] === 'assignment'): ?>
                                            <?php
                                            $hasSubmitted = false;
                                            foreach ($submissions[$activity['id']] as $submission) {
                                                if ($submission['user_id'] == $user_id) {
                                                    $hasSubmitted = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if (!$hasSubmitted): ?>
                                                <hr/>
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Upload Tugas</label>
                                                        <input type="file" name="submission_file" class="form-control shadow-sm" style="border-radius: 10px;" required>
                                                    </div>
                                                    <button type="submit" name="submit_activity" class="btn btn-primary shadow-sm" style="border-radius: 10px;">Submit</button>
                                                </form>
                                            <?php else: ?>
                                                <hr/>
                                                <h6 class="mt-3">Submissions Anda:</h6>
                                                <table class="table table-striped shadow-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Nama</th>
                                                            <th>File</th>
                                                            <th>Status</th>
                                                            <th>Submitted At</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($submissions[$activity['id']] as $submission): ?>
                                                            <?php if ($submission['user_id'] == $user_id): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($submission['name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($submission['file_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($submission['status']); ?></td>
                                                                    <td><?php echo $submission['submitted_at']; ?></td>
                                                                    <td>
                                                                        <a href="./upload/mahasiswa/activity/<?php echo $user_id; ?>/<?php echo urlencode($submission['file_name']); ?>" 
                                                                           download 
                                                                           class="btn btn-sm btn-outline-primary shadow-sm" 
                                                                           style="border-radius: 8px;">
                                                                            <i class="bi bi-download"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Join Classroom Modal -->
<div class="modal fade" 
     id="joinClassModal" 
     tabindex="-1" 
     aria-labelledby="joinClassModalLabel" 
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title" id="joinClassModalLabel">Join Classroom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda ingin bergabung ke classroom "<?php echo htmlspecialchars($classroom['title']); ?>"?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal" style="border-radius: 10px;">Tidak</button>
                <button type="button" id="confirmJoin" class="btn btn-primary shadow-sm" style="border-radius: 10px;">Ya</button>
            </div>
        </div>
    </div>
</div>

<!-- Leave Classroom Modal -->
<div class="modal fade" 
     id="leaveClassModal" 
     tabindex="-1" 
     aria-labelledby="leaveClassModalLabel" 
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveClassModalLabel">Leave Classroom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin meninggalkan classroom "<?php echo htmlspecialchars($classroom['title']); ?>"?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal" style="border-radius: 10px;">Tidak</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="leave_class" class="btn btn-danger shadow-sm" style="border-radius: 10px;">Ya</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Student Activity Modal -->
<div class="modal fade" 
     id="studentActivityModal" 
     tabindex="-1" 
     aria-labelledby="studentActivityModalLabel" 
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title" id="studentActivityModalLabel">
                    Status Activity - <span id="studentName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <span id="addFriendButton"></span>
                </div>
                <table class="table table-striped shadow-sm">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>File</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="activityStatusTable"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Friend Toast -->
<div class="toast-container position-fixed top-50 start-50 translate-middle">
    <div id="friendToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body"></div>
    </div>
</div>

<div id="attendanceRecapModalContainer"></div>

<style>
    .card { border-radius: 10px; transition: transform 0.2s ease-in-out; }
    .card:hover { transform: translateY(-5px); }
    .list-group-item { border: none; padding: 10px; transition: background-color 0.3s ease; }
    .list-group-item:hover { background-color: #f1f3f5; }
    .list-group-item.disabled { cursor: default; opacity: 0.7; }
    .accordion-button { background-color: #f1f3f5; border-radius: 10px !important; transition: background-color 0.3s ease; }
    .accordion-button:not(.collapsed) { background-color: #e7f3ff; color: #007bff; }
    .alert-success { background-color: #e6ffed; border-color: #b3ffcc; color: #006633; }
    .alert-warning { background-color: #fff3cd; border-color: #ffeeba; color: #856404; }
    .alert-info { background-color: #cce5ff; border-color: #b8daff; color: #004085; }
    .btn-primary { background-color: #007bff; border-color: #007bff; transition: background-color 0.3s ease; }
    .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
    .btn-outline-primary { border-color: #007bff; color: #007bff; transition: background-color 0.3s ease, color 0.3s ease; }
    .btn-outline-primary:hover { background-color: #007bff; color: #fff; }
    .btn-outline-danger { border-color: #dc3545; color: #dc3545; transition: background-color 0.3s ease, color 0.3s ease; }
    .btn-outline-danger:hover { background-color: #dc3545; color: #fff; }
    .form-control, .form-select { border-radius: 10px; transition: border-color 0.3s ease; }
    .form-control:focus, .form-select:focus { border-color: #007bff; box-shadow: 0 0 5px rgba(0, 123, 255, 0.3); }
    .table { border-radius: 10px; overflow: hidden; }
    .table th, .table td { padding: 12px; }
    #video-container .video-wrapper { position: absolute; width: 300px; max-width: 300px; margin: 5px; z-index: 10; }
    #video-container video { width: 100%; border: 2px solid #007bff; background: #000; object-fit: cover; border-radius: 10px; }
    #video-container .video-label { position: absolute; bottom: 5px; left: 5px; background: rgba(0, 0, 0, 0.7); color: white; padding: 2px 8px; border-radius: 5px; font-size: 0.9em; }
    .toast-container .toast { border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
    #audioVisualizer { background: #f0f2f5; border-radius: 5px; }
    .meet-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; padding: 0; }
    .meet-btn i { font-size: 1.2rem; }
    #participant-sidebar { background: #f8f9fa; }
    #participant-list li { padding: 5px 0; }
    #screen-share-container.fullscreen { position: absolute; top: 0; left: 0; height: 100%; width: 100%; z-index: 20; margin-bottom: 0; }
    .activity-option.active { color: #007bff; font-weight: bold; }
    .text-red { color: #dc3545; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://unpkg.com/interactjs/dist/interact.min.js"></script>


<script>
window.userId = <?php echo json_encode($user_id); ?>;
window.classroomId = <?php echo json_encode($classroom_id); ?>;
window.classroomMembers = <?php echo json_encode(array_column($classroom['members'], 'id')); ?>;
window.memberNames = <?php echo json_encode(array_column($classroom['members'], 'name', 'id')); ?>;
window.isLecturer = <?php echo json_encode($isLecturer); ?>;
</script>
<script src="./js/video_call.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let joinModal = null;
    <?php if (!$isMember): ?>
        joinModal = new bootstrap.Modal(document.getElementById('joinClassModal'), {});
        if (joinModal) joinModal.show();
    <?php endif; ?>

    const confirmJoin = document.getElementById('confirmJoin');
    if (confirmJoin) {
        confirmJoin.addEventListener('click', function() {
            <?php if ($isLoggedIn): ?>
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="join_class" value="1">';
                document.body.appendChild(form);
                form.submit();
            <?php else: ?>
                const loginAlert = document.getElementById('loginAlert');
                if (loginAlert) loginAlert.classList.remove('d-none');
                if (joinModal) joinModal.hide();
            <?php endif; ?>
        });
    }

    const activityOptions = document.querySelectorAll('.activity-option');
    activityOptions.forEach(option => {
        option.addEventListener('click', function() {
            activityOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            const type = this.dataset.type;
            document.querySelectorAll('.activity-form').forEach(form => form.classList.add('d-none'));
            const targetForm = document.getElementById(type + 'Form');
            if (targetForm) targetForm.classList.remove('d-none');
        });
    });

    const createAttendanceBtn = document.getElementById('createAttendance');
    if (createAttendanceBtn) {
        createAttendanceBtn.addEventListener('click', function() {
            const title = document.getElementById('attendance_title')?.value || '';
            const activityId = document.getElementById('attendance_activity_id')?.value || '';
            const startTime = document.getElementById('attendance_start_time')?.value || '';
            const endTime = document.getElementById('attendance_end_time')?.value || '';
            if (!title || !activityId || !startTime || !endTime) {
                alert('Harap isi semua field: judul, activity, waktu mulai, dan waktu selesai.');
                return;
            }
            fetch('index.php?page=classroom&action=create_attendance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `activity_id=${encodeURIComponent(activityId)}&classroom_id=${<?php echo $classroom_id ?? 0; ?>}&title=${encodeURIComponent(title)}&start_time=${encodeURIComponent(startTime)}&end_time=${encodeURIComponent(endTime)}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Gagal membuat absensi: ' + (data.error || 'Unknown error'));
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat membuat absensi.');
                });
        });
    }

    let stream = null;
    document.querySelectorAll('.attendance-link').forEach(link => {
        link.addEventListener('click', async function() {
            const attendanceId = this.dataset.attendanceId;
            const startTime = new Date(this.dataset.startTime);
            const endTime = new Date(this.dataset.endTime);
            const now = new Date();

            if (now < startTime || now > endTime) {
                alert('Absensi hanya dapat dilakukan antara ' + startTime.toLocaleString() + ' hingga ' + endTime.toLocaleString());
                return;
            }

            const modalElement = document.getElementById('attendanceModal');
            if (!modalElement) return console.error('Attendance modal not found');
            const modal = new bootstrap.Modal(modalElement);
            const video = document.getElementById('attendanceVideo');
            const canvas = document.getElementById('attendanceCanvas');
            const captureBtn = document.getElementById('captureAttendance');

            if (!video || !canvas || !captureBtn) return console.error('Required elements not found');

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.onloadedmetadata = () => {
                    video.play();
                    modal.show();
                };
            } catch (error) {
                console.error('Error accessing camera:', error);
                alert('Gagal mengakses kamera. Pastikan izin kamera diaktifkan.');
                return;
            }

            captureBtn.onclick = async function() {
                if (!stream || !video.videoWidth || !video.videoHeight) {
                    alert('Kamera belum siap.');
                    return;
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const quality = 0.5;
                const photo = canvas.toDataURL('image/jpeg', quality);

                const formData = new FormData();
                formData.append('attendance_id', attendanceId);
                const statusSelect = document.getElementById('attendanceStatus');
                formData.append('status', statusSelect ? statusSelect.value : 'present');
                formData.append('photo', dataURLtoBlob(photo), 'attendance.jpg');

                try {
                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 5000 });
                    });
                    formData.append('latitude', position.coords.latitude);
                    formData.append('longitude', position.coords.longitude);
                } catch (error) {
                    console.warn('Geolocation not available:', error.message);
                }

                try {
                    const response = await fetch('index.php?page=classroom&action=submit_attendance', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                            stream = null;
                        }
                        modal.hide();
                        alert('Absensi berhasil disubmit');
                        location.reload();
                    } else {
                        alert('Gagal submit absensi: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error submitting attendance:', error);
                    alert('Terjadi kesalahan saat submit absensi: ' + error.message);
                }
            };

            modalElement.addEventListener('hidden.bs.modal', function() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
            });
        });
    });
    document.querySelectorAll('.view-member-activity').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const memberNameEl = document.getElementById('memberActivityName');
            if (memberNameEl) {
                memberNameEl.textContent = userName;
            }

            // Fetch activity data
            fetch(`index.php?page=classroom&action=get_member_activities&user_id=${userId}&classroom_id=${<?php echo $classroom_id ?? 0; ?>}`)
                .then(response => response.json())
                .then(data => {
                    const activityTable = document.getElementById('memberActivityTable');
                    activityTable.innerHTML = '';
                    if (data.length === 0) {
                        activityTable.innerHTML = '<tr><td colspan="<?php echo $isLecturer ? 5 : 4; ?>">Belum ada aktivitas</td></tr>';
                    } else {
                        data.forEach(activity => {
                            const contentCell = activity.file_name ? 
                                (<?php echo json_encode($isLecturer); ?> ? 
                                    `<a href="./upload/mahasiswa/activity/${userId}/${encodeURIComponent(activity.file_name)}" download>${activity.file_name}</a>` : 
                                    activity.file_name) : '-';
                            const actionCell = <?php echo json_encode($isLecturer); ?> ? 
                                `<td><button class="btn btn-sm btn-outline-danger shadow-sm reset-submission" data-submission-id="${activity.id}" style="border-radius: 8px;"><i class="bi bi-arrow-repeat"></i> Reset</button></td>` : '';
                            const row = `
                                <tr>
                                    <td>${activity.title || '-'}</td>
                                    <td>${contentCell}</td>
                                    <td>${activity.status || '-'}</td>
                                    <td>${activity.submitted_at || '-'}</td>
                                    ${actionCell}
                                </tr>
                            `;
                            activityTable.innerHTML += row;
                        });
                    }
                })
                .catch(error => console.error('Error fetching activities:', error));

            // Fetch attendance data
            fetch(`index.php?page=classroom&action=get_member_attendance&user_id=${userId}&classroom_id=${<?php echo $classroom_id ?? 0; ?>}`)
                .then(response => response.json())
                .then(data => {
                    const attendanceTable = document.getElementById('memberAttendanceTable');
                    attendanceTable.innerHTML = '';
                    if (data.length === 0) {
                        attendanceTable.innerHTML = '<tr><td colspan="5">Belum ada data absensi</td></tr>';
                    } else {
                        data.forEach(record => {
                            const photoLink = record.photo_path ? 
                                (<?php echo json_encode($isLecturer); ?> ? 
                                    `<a href="./upload/attendance/${record.photo_path}" target="_blank"><img src="./upload/attendance/${record.photo_path}" style="width: 50px; height: auto;" alt="Attendance Photo"></a>` : 
                                    'Foto Tersedia (Hanya Dosen)') : '-';
                            const locationLink = record.latitude && record.longitude ? 
                                `<a href="https://www.google.com/maps?q=${record.latitude},${record.longitude}" target="_blank">${record.latitude}, ${record.longitude}</a>` : '-';
                            const row = `
                                <tr>
                                    <td>${record.title || '-'}</td>
                                    <td>${record.status || '-'}</td>
                                    <td>${record.submitted_at || '-'}</td>
                                    <td>${photoLink}</td>
                                    <td>${locationLink}</td>
                                </tr>
                            `;
                            attendanceTable.innerHTML += row;
                        });
                    }
                })
                .catch(error => console.error('Error fetching attendance:', error));

            // Fetch assignment data
            fetch(`index.php?page=classroom&action=get_member_assignments&user_id=${userId}&classroom_id=${<?php echo $classroom_id ?? 0; ?>}`)
                .then(response => response.json())
                .then(data => {
                    const assignmentTable = document.getElementById('memberAssignmentTable');
                    assignmentTable.innerHTML = '';
                    if (data.length === 0) {
                        assignmentTable.innerHTML = '<tr><td colspan="<?php echo $isLecturer ? 8 : 7; ?>">Belum ada pengumpulan tugas</td></tr>';
                    } else {
                        data.forEach(submission => {
                            let contentCell = '-';
                            if (submission.submission_type === 'file' && <?php echo json_encode($isLecturer); ?>) {
                                contentCell = `<a href="./upload/mahasiswa/assignment/${userId}/${encodeURIComponent(submission.file_name)}" download>${submission.file_name}</a>`;
                            } else if (submission.submission_type === 'file') {
                                contentCell = submission.file_name;
                            } else if (submission.submission_type === 'link') {
                                contentCell = `<a href="${submission.file_name}" target="_blank">${submission.file_name}</a>`;
                            } else if (submission.submission_type === 'text') {
                                contentCell = `<a href="#" class="view-text-submission" data-text="${submission.file_name || 'Belum ada jawaban'}" data-bs-toggle="modal" data-bs-target="#viewTextSubmissionModal">Lihat Jawaban Teks</a>`;
                            }
                            const actionCell = <?php echo json_encode($isLecturer); ?> ? 
                                `<td><button class="btn btn-sm btn-outline-danger shadow-sm reset-submission" data-submission-id="${submission.id}" style="border-radius: 8px;"><i class="bi bi-arrow-repeat"></i> Reset</button></td>` : '';
                            const row = `
                                <tr>
                                    <td>${submission.title || '-'}</td>
                                    <td>${submission.submission_type || '-'}</td>
                                    <td>${contentCell}</td>
                                    <td>${submission.status || '-'}</td>
                                    <td>${submission.submitted_at || '-'}</td>
                                    <td>${submission.grade || '-'}</td>
                                    <td>${submission.feedback || '-'}</td>
                                    ${actionCell}
                                </tr>
                            `;
                            assignmentTable.innerHTML += row;
                        });
                    }
                })
                .catch(error => console.error('Error fetching assignments:', error));
        });
    });
    document.querySelectorAll('.recap-attendance').forEach(btn => {
        btn.addEventListener('click', async function() {
            const attendanceId = this.dataset.attendanceId;
            try {
                const response = await fetch(`index.php?page=classroom&action=get_attendance_records&attendance_id=${attendanceId}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Network response was not ok');
                const records = await response.json();

                const membersResponse = await fetch(`index.php?page=classroom&action=get_classroom_members&classroom_id=${<?php echo $classroom_id ?? 0; ?>}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                if (!membersResponse.ok) throw new Error('Network response was not ok');
                const allMembers = await membersResponse.json();

                const submittedUserIds = records.map(r => r.user_id);
                const notSubmittedMembers = allMembers.filter(member => !submittedUserIds.includes(member.id) && member.role !== 'lecturer');

                const modalContainer = document.getElementById('attendanceRecapModalContainer');
                modalContainer.innerHTML = `
                    <div class="modal fade" id="attendanceRecapModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Rekap Absensi</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Status</th>
                                                <th>Waktu</th>
                                                <th>Foto</th>
                                                <th>Lokasi</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${records.length > 0 ? records.map(r => `
                                                <tr>
                                                    <td>${r.name || '-'}</td>
                                                    <td>${r.status || '-'}</td>
                                                    <td>${r.submitted_at || '-'}</td>
                                                    <td>${r.photo_path ? `<a href="./upload/attendance/${r.photo_path}" target="_blank"><img src="./upload/attendance/${r.photo_path}" style="width: 50px; height: auto;" alt="Attendance Photo"></a>` : '-'}</td>
                                                   <td>
  ${r.latitude && r.longitude ? 
    `<a href="https://www.google.com/maps?q=${r.latitude},${r.longitude}" target="_blank">${r.latitude}, ${r.longitude}</a>` 
    : '-'}
</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-danger reset-attendance" 
                                                                data-attendance-id="${attendanceId}" 
                                                                data-user-id="${r.user_id}">
                                                            <i class="bi bi-arrow-repeat"></i> Reset
                                                        </button>
                                                    </td>
                                                </tr>
                                            `).join('') : '<tr><td colspan="6">Belum ada data absensi.</td></tr>'}
                                            ${notSubmittedMembers.length > 0 ? notSubmittedMembers.map(m => `
                                                <tr class="text-red">
                                                    <td>${m.name || '-'}</td>
                                                    <td>Belum Melakukan Absensi</td>
                                                    <td>-</td>
                                                    <td>-</td>
                                                    <td>-</td>
                                                    <td>-</td>
                                                </tr>
                                            `).join('') : ''}
                                        </tbody>
                                    </table>
                                    <button class="btn btn-primary mt-3" id="downloadRecap">Download Rekap (CSV)</button>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const modalElement = modalContainer.querySelector('#attendanceRecapModal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();

                modalElement.querySelectorAll('.reset-attendance').forEach(resetBtn => {
                    resetBtn.addEventListener('click', async function() {
                        const attendanceId = this.dataset.attendanceId;
                        const userId = this.dataset.userId;
                        if (confirm(`Apakah Anda yakin ingin mereset absensi untuk user ${userId}?`)) {
                            try {
                                const response = await fetch('index.php?page=classroom&action=reset_attendance', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `attendance_id=${encodeURIComponent(attendanceId)}&user_id=${encodeURIComponent(userId)}`
                                });
                                if (!response.ok) throw new Error('Network response was not ok');
                                const data = await response.json();
                                if (data.success) {
                                    alert('Absensi berhasil direset.');
                                    modal.hide();
                                    location.reload();
                                } else {
                                    alert('Gagal mereset absensi: ' + (data.error || 'Unknown error'));
                                }
                            } catch (error) {
                                console.error('Error resetting attendance:', error);
                                alert('Terjadi kesalahan saat mereset absensi.');
                            }
                        }
                    });
                });

                modalElement.querySelector('#downloadRecap').addEventListener('click', function() {
                    const csvContent = "data:text/csv;charset=utf-8," +
                        "Nama,Status,Waktu,Foto,Lokasi\n" +
                        records.map(r => `${r.name || '-'},${r.status || '-'},${r.submitted_at || '-'},${r.photo_path || '-'},${r.latitude && r.longitude ? `${r.latitude},${r.longitude}` : '-'}`).join("\n") +
                        "\n" +
                        notSubmittedMembers.map(m => `${m.name || '-'},Belum Melakukan Absensi,-,-,-`).join("\n");

                    const encodedUri = encodeURI(csvContent);
                    const link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", `rekap_absensi_${attendanceId}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            } catch (error) {
                console.error('Error fetching attendance records:', error);
                alert('Gagal memuat rekap absensi: ' + error.message);
            }
        });
    });

    document.querySelectorAll('.reset-attendance').forEach(btn => {
        btn.addEventListener('click', async function() {
            const attendanceId = this.dataset.attendanceId;
            if (confirm('Apakah Anda yakin ingin mereset absensi ini? Semua data absensi akan dihapus.')) {
                try {
                    const response = await fetch('index.php?page=classroom&action=reset_attendance', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `attendance_id=${encodeURIComponent(attendanceId)}`
                    });
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();
                    if (data.success) {
                        alert('Absensi berhasil direset.');
                        location.reload();
                    } else {
                        alert('Gagal mereset absensi: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error resetting attendance:', error);
                    alert('Terjadi kesalahan saat mereset absensi.');
                }
            }
        });
    });

    document.querySelectorAll('.edit-activity').forEach(btn => {
        btn.addEventListener('click', async function() {
            const activityId = this.dataset.activityId;

            try {
                const response = await fetch(`index.php?page=classroom&action=get_activity_details&activity_id=${activityId}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                const activityData = await response.json();

                const editForm = document.getElementById('editActivityForm');
                editForm.innerHTML = `
                    <input type="hidden" name="activity_id" value="${activityData.id}">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input type="text" class="form-control" name="edit_activity_title" value="${activityData.title || ''}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="edit_activity_description" rows="3">${activityData.description || ''}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File atau Link</label>
                        <input type="text" class="form-control" name="edit_activity_content" value="${activityData.file_name || ''}" placeholder="Masukkan link atau kosongkan jika upload file">
                        <input type="file" class="form-control mt-2" name="edit_activity_file">
                    </div>
                `;

                const modal = new bootstrap.Modal(document.getElementById('editActivityModal'));
                modal.show();

                document.getElementById('saveActivityChanges').onclick = async function() {
                    const formData = new FormData(document.getElementById('editActivityForm'));

                    try {
                        const response = await fetch('index.php?page=classroom&action=update_activity', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (!data.success) throw new Error(data.error || 'Unknown error');
                        alert('Perubahan berhasil disimpan.');
                        modal.hide();
                        location.reload();
                    } catch (error) {
                        console.error('Error updating activity:', error);
                        alert('Gagal memperbarui activity: ' + error.message);
                    }
                };
            } catch (error) {
                console.error('Error fetching activity details:', error);
                alert('Gagal memuat detail activity.');
            }
        });
    });

    document.querySelectorAll('.edit-attendance').forEach(btn => {
        btn.addEventListener('click', async function() {
            const attendanceId = this.dataset.attendanceId;

            try {
                const response = await fetch(`index.php?page=classroom&action=get_attendance_details&attendance_id=${attendanceId}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                const attendanceData = await response.json();

                const editForm = document.getElementById('editAttendanceForm');
                editForm.innerHTML = `
                    <input type="hidden" name="attendance_id" value="${attendanceData.id}">
                    <div class="mb-3">
                        <label class="form-label">Judul Absensi</label>
                        <input type="text" class="form-control" name="edit_attendance_title" value="${attendanceData.title || ''}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Waktu Mulai</label>
                        <input type="datetime-local" class="form-control" name="edit_attendance_start" value="${attendanceData.start_time ? attendanceData.start_time.slice(0, 16) : ''}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Waktu Selesai</label>
                        <input type="datetime-local" class="form-control" name="edit_attendance_end" value="${attendanceData.end_time ? attendanceData.end_time.slice(0, 16) : ''}" required>
                    </div>
                `;

                const modal = new bootstrap.Modal(document.getElementById('editAttendanceModal'));
                modal.show();

                document.getElementById('saveAttendanceChanges').onclick = async function() {
                    const formData = new FormData(document.getElementById('editAttendanceForm'));
                    const attendanceId = formData.get('attendance_id');
                    const title = formData.get('edit_attendance_title');
                    const startTime = formData.get('edit_attendance_start');
                    const endTime = formData.get('edit_attendance_end');

                    try {
                        const response = await fetch('index.php?page=classroom&action=update_attendance', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `attendance_id=${encodeURIComponent(attendanceId)}&edit_attendance_title=${encodeURIComponent(title)}&edit_attendance_start=${encodeURIComponent(startTime)}&edit_attendance_end=${encodeURIComponent(endTime)}`
                        });
                        const data = await response.json();
                        if (!data.success) throw new Error(data.error || 'Unknown error');
                        alert('Perubahan berhasil disimpan.');
                        modal.hide();
                        location.reload();
                    } catch (error) {
                        console.error('Error updating attendance:', error);
                        alert('Gagal memperbarui absensi: ' + error.message);
                    }
                };
            } catch (error) {
                console.error('Error fetching attendance details:', error);
                alert('Gagal memuat detail absensi.');
            }
        });
    });

    document.querySelectorAll('.edit-assignment').forEach(btn => {
        btn.addEventListener('click', async function() {
            const assignmentId = this.dataset.assignmentId;

            try {
                const response = await fetch(`index.php?page=classroom&action=get_assignment_details&assignment_id=${assignmentId}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                const assignmentData = await response.json();

                const editForm = document.getElementById('editAssignmentForm');
                editForm.innerHTML = `
                    <input type="hidden" name="assignment_id" value="${assignmentData.id}">
                    <div class="mb-3">
                        <label class="form-label">Pilih Activity</label>
                        <select name="edit_assignment_activity" class="form-control" required>
                            <option value="">Pilih Activity</option>
                            <?php foreach ($activities as $activity): ?>
                                <option value="<?php echo $activity['id']; ?>" ${assignmentData.activity_id == <?php echo $activity['id']; ?> ? 'selected' : ''}>
                                    <?php echo htmlspecialchars($activity['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input type="text" class="form-control" name="edit_assignment_title" value="${assignmentData.title || ''}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="edit_assignment_description" rows="3">${assignmentData.description || ''}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Jatuh Tempo</label>
                        <input type="datetime-local" class="form-control" name="edit_assignment_due_date" value="${assignmentData.due_date ? assignmentData.due_date.slice(0, 16) : ''}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link atau File</label>
                        <input type="text" class="form-control" name="edit_assignment_content" value="${assignmentData.file_name || ''}" placeholder="Masukkan link atau kosongkan jika upload file">
                        <input type="file" class="form-control mt-2" name="edit_assignment_file">
                    </div>
                `;

                const modal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
                modal.show();

                document.getElementById('saveAssignmentChanges').onclick = async function() {
                    const formData = new FormData(document.getElementById('editAssignmentForm'));

                    try {
                        const response = await fetch('index.php?page=classroom&action=update_assignment', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (!data.success) throw new Error(data.error || 'Unknown error');
                        alert('Perubahan berhasil disimpan.');
                        modal.hide();
                        location.reload();
                    } catch (error) {
                        console.error('Error updating assignment:', error);
                        alert('Gagal memperbarui assignment: ' + error.message);
                    }
                };
            } catch (error) {
                console.error('Error fetching assignment details:', error);
                alert('Gagal memuat detail assignment.');
            }
        });
    });

    document.querySelectorAll('.grade-assignment').forEach(btn => {
        btn.addEventListener('click', async function() {
            const submissionId = this.dataset.submissionId;

            const editForm = document.getElementById('gradeAssignmentForm');
            editForm.innerHTML = `
                <input type="hidden" name="submission_id" value="${submissionId}">
                <div class="mb-3">
                    <label class="form-label">Nilai (0-100)</label>
                    <input type="number" class="form-control" id="grade_value" name="grade" min="0" max="100" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Feedback</label>
                    <textarea class="form-control" id="grade_feedback" name="feedback" rows="3"></textarea>
                </div>
            `;

            const modal = new bootstrap.Modal(document.getElementById('gradeAssignmentModal'));
            modal.show();

            document.getElementById('saveGradeChanges').onclick = async function() {
                const grade = document.getElementById('grade_value').value;
                const feedback = document.getElementById('grade_feedback').value;

                if (!grade) {
                    alert('Nilai harus diisi!');
                    return;
                }

                try {
                    const response = await fetch('index.php?page=classroom&action=grade_assignment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `submission_id=${encodeURIComponent(submissionId)}&grade=${encodeURIComponent(grade)}&feedback=${encodeURIComponent(feedback)}`
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.error || 'Unknown error');
                    alert('Nilai berhasil disimpan.');
                    modal.hide();
                    location.reload();
                } catch (error) {
                    console.error('Error grading assignment:', error);
                    alert('Gagal menyimpan nilai: ' + error.message);
                }
            };
        });
    });

    document.querySelectorAll('.submit-assignment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const assignmentId = this.dataset.assignmentId;
            document.getElementById('assignmentId').value = assignmentId;
            const modal = new bootstrap.Modal(document.getElementById('submitAssignmentModal'));
            modal.show();
        });
    });

    document.getElementById('submissionType').addEventListener('change', function() {
        const type = this.value;
        const textInput = document.getElementById('submissionContentText');
        const fileInput = document.getElementById('submissionContentFile');
        const linkInput = document.getElementById('submissionContentLink');
        const label = document.getElementById('submissionContentLabel');

        textInput.classList.add('d-none');
        fileInput.classList.add('d-none');
        linkInput.classList.add('d-none');

        if (type === 'text') {
            textInput.classList.remove('d-none');
            label.textContent = 'Jawaban Teks';
        } else if (type === 'file') {
            fileInput.classList.remove('d-none');
            label.textContent = 'Upload File';
        } else if (type === 'link') {
            linkInput.classList.remove('d-none');
            label.textContent = 'Masukkan Link';
        }
    });

    document.getElementById('submitAssignmentForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submissionType = formData.get('submission_type');

    // Sesuaikan submission_content berdasarkan tipe
    let submissionContent = '';
    if (submissionType === 'text') {
        submissionContent = formData.get('submission_content_text');
    } else if (submissionType === 'link') {
        submissionContent = formData.get('submission_content_link');
    }

    // Debugging: Log nilai langsung dari elemen form
    console.log('Nilai langsung dari form:');
    console.log('assignment_id:', formData.get('assignment_id'));
    console.log('submission_type:', submissionType);
    console.log('submission_content_text:', formData.get('submission_content_text'));
    console.log('submission_content_link:', formData.get('submission_content_link'));
    console.log('submission_file:', formData.get('submission_file'));

    // Tambahkan submission_content ke FormData jika bukan file
    if (submissionType !== 'file') {
        formData.set('submission_content', submissionContent);
    }

    // Debugging: Log data yang dikirim melalui FormData
    console.log('Data dalam FormData:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    try {
        const response = await fetch('index.php?page=classroom&action=submit_assignment', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        console.log('Respons dari server:', data);
        if (data.success) {
            alert('Assignment berhasil disubmit');
            location.reload();
        } else {
            alert('Gagal submit assignment: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error submitting assignment:', error);
        alert('Terjadi kesalahan saat submit assignment');
    }
});

// Logika untuk toggle input berdasarkan submission_type
document.getElementById('submissionType').addEventListener('change', function() {
    const type = this.value;
    const textInput = document.getElementById('submissionContentText');
    const fileInput = document.getElementById('submissionContentFile');
    const linkInput = document.getElementById('submissionContentLink');
    const label = document.getElementById('submissionContentLabel');

    textInput.classList.add('d-none');
    fileInput.classList.add('d-none');
    linkInput.classList.add('d-none');

    if (type === 'text') {
        textInput.classList.remove('d-none');
        label.textContent = 'Jawaban Teks';
    } else if (type === 'file') {
        fileInput.classList.remove('d-none');
        label.textContent = 'Upload File';
    } else if (type === 'link') {
        linkInput.classList.remove('d-none');
        label.textContent = 'Masukkan Link';
    }
});
    document.querySelectorAll('.reset-submission').forEach(btn => {
        btn.addEventListener('click', async function() {
            const submissionId = this.dataset.submissionId;
            if (confirm('Apakah Anda yakin ingin mereset pengumpulan ini?')) {
                try {
                    const response = await fetch('index.php?page=classroom&action=reset_assignment_submission', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `submission_id=${encodeURIComponent(submissionId)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert('Pengumpulan berhasil direset');
                        location.reload();
                    } else {
                        alert('Gagal mereset pengumpulan: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error resetting submission:', error);
                    alert('Terjadi kesalahan saat mereset pengumpulan');
                }
            }
        });
    });

    document.querySelectorAll('.view-text-submission').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const textContent = this.getAttribute('data-text');
            console.log('Text Content:', textContent); // Debugging

            const modalContent = document.getElementById('textSubmissionContent');
            if (modalContent) {
                modalContent.textContent = textContent || 'Belum ada jawaban';
            } else {
                console.error('Elemen textSubmissionContent tidak ditemukan.');
            }

            const modal = new bootstrap.Modal(document.getElementById('viewTextSubmissionModal'));
            modal.show();
        });
    });

    const searchButton = document.getElementById('searchButton');
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            const query = document.getElementById('memberSearch')?.value || '';
            fetch('index.php?page=classroom&action=search_members&classroom_id=<?php echo $classroom_id ?? 0; ?>&query=' + encodeURIComponent(query))
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const memberList = document.getElementById('memberList');
                    if (!memberList) return;
                    memberList.innerHTML = '';
                    data.forEach(member => {
                        const isLecturer = member.role === 'lecturer';
                        const clickable = <?php echo json_encode($isLecturer); ?> && !isLecturer ? 
                            `data-bs-toggle="modal" data-bs-target="#studentActivityModal" data-user-id="${member.id}" data-user-name="${member.name}"` : '';
                        const html = `
                            <div class="list-group-item d-flex align-items-center justify-content-between shadow-sm mb-2 ${clickable ? '' : 'disabled'}" 
                                 style="border-radius: 10px;" 
                                 ${clickable}>
                                <div class="d-flex align-items-center">
                                    <img src="${member.profile_image ? './upload/image/' + member.profile_image : './image/robot-ai.png'}" 
                                         alt="Profile" 
                                         class="rounded-circle me-2 border border-primary border-2" 
                                         style="width: 40px; height: 40px;">
                                    <div>
                                        <strong class="text-dark">${member.name}</strong>
                                        <small class="text-muted d-block">${member.role === 'lecturer' ? 'Dosen' : 'Mahasiswa'}</small>
                                    </div>
                                </div>
                                <?php if ($isLoggedIn && $user_id != '${member.id}'): ?>
                                    <button class="btn btn-sm btn-outline-primary add-friend shadow-sm" 
                                            data-friend-id="${member.id}" 
                                            style="border-radius: 8px;">Add Friend</button>
                                <?php endif; ?>
                            </div>
                        `;
                        memberList.innerHTML += html;
                    });
                })
                .catch(error => console.error('Error:', error));
        });
    }

    document.querySelectorAll('.list-group-item:not(.disabled)').forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const studentNameEl = document.getElementById('studentName');
            const addFriendBtnEl = document.getElementById('addFriendButton');
            if (studentNameEl && addFriendBtnEl) {
                studentNameEl.textContent = userName;
                addFriendBtnEl.innerHTML = 
                    `<button class="btn btn-sm btn-outline-primary add-friend shadow-sm" data-friend-id="${userId}" style="border-radius: 8px;">Add Friend</button>`;
            }
            fetch('index.php?page=classroom&action=get_student_activities&user_id=' + userId + '&classroom_id=<?php echo $classroom_id ?? 0; ?>')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const tbody = document.getElementById('activityStatusTable');
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    data.forEach(activity => {
                        const downloadLink = activity.file_name ? 
                            `<a href="./upload/mahasiswa/activity/${userId}/${encodeURIComponent(activity.file_name)}" 
                               download 
                               class="btn btn-sm btn-outline-primary shadow-sm" 
                               style="border-radius: 8px;"><i class="bi bi-download"></i></a>` : '';
                        const row = `
                            <tr>
                                <td>${activity.title || '-'}</td>
                                <td>${activity.file_name || 'Belum upload'}</td>
                                <td>${activity.status || 'Pending'}</td>
                                <td>${activity.submitted_at || '-'}</td>
                                <td>${downloadLink}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error:', error));
        });
    });

    document.querySelectorAll('.add-friend').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const friendId = this.getAttribute('data-friend-id');
            fetch('index.php?page=chat&action=add_friend', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ friend_id: friendId })
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const toast = new bootstrap.Toast(document.getElementById('friendToast'));
                    const toastBody = document.querySelector('#friendToast .toast-body');
                    if (toastBody) {
                        toastBody.textContent = data.message || 'Unknown response';
                        toastBody.parentElement.classList.toggle('bg-success', data.message?.includes('berhasil'));
                        toastBody.parentElement.classList.toggle('bg-danger', !data.message?.includes('berhasil'));
                        toast.show();
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });

    function dataURLtoBlob(dataurl) {
        const arr = dataurl.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new Blob([u8arr], { type: mime });
    }
});
</script>

</div>
</body>
</html>