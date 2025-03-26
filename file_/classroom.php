<?php
//session_start();
include_once './controllers/ClassroomController.php';
include_once './config/db.php';

// Set timezone ke Indonesia
date_default_timezone_set('Asia/Jakarta');

$classroomController = new ClassroomController($conn);
$classroom_id = $_GET['classroom_id'] ?? null;
$code = $_GET['code'] ?? null;
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// Jika ada code, coba cari classroom_id
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

// Ambil detail classroom
$classroom = $classroomController->getClassroomDetails($classroom_id);
if (!$classroom) {
    include 'file_/404/not_found_1.php';
    exit();
}

// Cek apakah user sudah bergabung dan role-nya
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

// Proses join classroom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_class']) && $isLoggedIn) {
    $result = $classroomController->joinClassroom($classroom['class_code']);
    if ($result['success']) {
        header("Location: index.php?page=classroom&classroom_id=" . $classroom_id);
        exit();
    }
}

// Proses leave classroom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_class']) && $isLoggedIn && $isMember && !$isLecturer) {
    $stmt = $conn->prepare("DELETE FROM tb_classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $classroom_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=admin_dashboard");
    exit();
}

// Proses tambah activity (dosen only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity']) && $isLecturer) {
    $title = $_POST['activity_title'];
    $description = $_POST['activity_description'];
    $content = $_POST['activity_content'];
    $file_name = null;
    $is_link = filter_var($content, FILTER_VALIDATE_URL) ? 1 : 0;

    if (!$is_link && isset($_FILES['activity_file']) && $_FILES['activity_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['activity_file'];
        $uploadDir = "./upload/dosen/activity/{$user_id}/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '-' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $file_name = $fileName;
        }
    } else {
        $file_name = $content; // Jika link, simpan sebagai file_name
    }

    $stmt = $conn->prepare("INSERT INTO tb_classroom_activities (classroom_id, title, description, file_name, is_link, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssi", $classroom_id, $title, $description, $file_name, $is_link);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=classroom&classroom_id=" . $classroom_id);
    exit();
}

// Proses upload submission (student, sekali upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_activity']) && $isMember && !$isLecturer) {
    $activity_id = $_POST['activity_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tb_activity_submissions WHERE activity_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $activity_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_row();
    if ($result[0] == 0) { // Hanya izinkan jika belum upload
        $file = $_FILES['submission_file'];
        $uploadDir = "./upload/mahasiswa/activity/{$user_id}/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
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

// Ambil daftar activity
$stmt = $conn->prepare("SELECT * FROM tb_classroom_activities WHERE classroom_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $classroom_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ambil submission per activity
$submissions = [];
foreach ($activities as $activity) {
    $stmt = $conn->prepare("SELECT s.*, u.name FROM tb_activity_submissions s JOIN tb_users u ON s.user_id = u.id WHERE activity_id = ?");
    $stmt->bind_param("i", $activity['id']);
    $stmt->execute();
    $submissions[$activity['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<?php include '_partials/_admin_head.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item text-dark"><a class="text-dark" href="index.php?page=home" style="text-decoration: none;">Home</a></li>
            <li class="breadcrumb-item text-dark"><a class="text-dark" href="index.php?page=admin_dashboard" style="text-decoration: none;">Dashboard</a></li>
            <li class="breadcrumb-item active text-primary fw-bolder" aria-current="page"><?php echo htmlspecialchars($classroom['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Classroom Header -->
        <div class="col-12 mb-4">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo $classroom['classroom_image'] ? './upload/classroom/' . $classroom['classroom_image'] : './image/classroom_icon.png'; ?>" 
                             alt="Classroom Icon" class="rounded-circle me-3 border border-primary border-2 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                        <div>
                            <h1 class="fw-bolder mb-1 text-dark"><?php echo htmlspecialchars($classroom['title']); ?></h1>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($classroom['description']); ?></p>
                            <small class="text-muted">Class Code: <span class="badge bg-primary-subtle text-primary"><?php echo $classroom['class_code']; ?></span> | Created by: <span class="badge bg-success-subtle text-success"><?php echo $classroom['creator_name']; ?></span></small>
                        </div>
                    </div>
                </div>
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
                            <div class="list-group-item d-flex align-items-center justify-content-between shadow-sm mb-2" style="border-radius: 10px;"
                                 <?php if ($isLecturer && $member['role'] !== 'lecturer'): ?>
                                     data-bs-toggle="modal" data-bs-target="#studentActivityModal" 
                                     data-user-id="<?php echo $member['id']; ?>" 
                                     data-user-name="<?php echo htmlspecialchars($member['name']); ?>"
                                 <?php endif; ?>>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $member['profile_image'] ? './upload/image/' . $member['profile_image'] : './image/robot-ai.png'; ?>" 
                                         alt="Profile" class="rounded-circle me-2 border border-primary border-2" style="width: 40px; height: 40px;">
                                    <div>
                                        <strong class="text-dark"><?php echo htmlspecialchars($member['name']); ?></strong>
                                        <small class="text-muted d-block"><?php echo $member['role'] === 'lecturer' ? 'Dosen' : 'Mahasiswa'; ?></small>
                                    </div>
                                </div>
                                <?php if ($isLoggedIn && $user_id != $member['id']): ?>
                                    <button class="btn btn-sm btn-outline-primary add-friend shadow-sm" data-friend-id="<?php echo $member['id']; ?>" style="border-radius: 8px;">Add Friend</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classroom Content -->
        <div class="col-md-9">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bolder text-primary">Selamat Datang di Classroom</h5>
                        <?php if ($isLecturer): ?>
                            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addActivityModal" style="border-radius: 10px;">
                                <i class="bi bi-plus"></i> Tambah Activity
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted mb-3">Ini adalah ruang belajar Anda. Berikut adalah aktivitas yang tersedia:</p>
                    <?php if ($isMember): ?>
                        <div class="alert alert-success shadow-sm" role="alert" style="border-radius: 10px;">
                            Anda sudah bergabung di classroom ini!
                            <?php if (!$isLecturer): ?>
                                <button class="btn btn-sm btn-danger float-end shadow-sm" data-bs-toggle="modal" data-bs-target="#leaveClassModal" style="border-radius: 8px;">Leave Classroom</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div id="loginAlert" class="alert alert-warning d-none shadow-sm" role="alert" style="border-radius: 10px;">
                        Anda harus <a href="index.php?page=login" class="alert-link">login terlebih dahulu</a> untuk bergabung ke classroom ini.
                    </div>

                    <!-- Activity Accordion -->
                    <div class="accordion" id="activityAccordion">
                        <?php foreach ($activities as $index => $activity): ?>
                            <div class="accordion-item shadow-sm mb-3" style="border-radius: 10px;">
                                <h2 class="accordion-header" id="heading<?php echo $activity['id']; ?>">
                                    <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?php echo $activity['id']; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                            aria-controls="collapse<?php echo $activity['id']; ?>" style="border-radius: 10px; background-color: #f1f3f5;">
                                        <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $activity['id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                     aria-labelledby="heading<?php echo $activity['id']; ?>" data-bs-parent="#activityAccordion">
                                    <div class="accordion-body">
                                        <p class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <?php if ($activity['file_name']): ?>
                                            <p>
                                                <?php if ($activity['is_link']): ?>
                                                    <a href="<?php echo $activity['file_name']; ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 8px;">
                                                        <i class="bi bi-link-45deg"></i> Link Materi
                                                    </a>
                                                <?php else: ?>
                                                    <a href="./upload/dosen/activity/<?php echo $classroom['creator_id']; ?>/<?php echo urlencode($activity['file_name']); ?>" 
                                                       download class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 8px;">
                                                        <i class="bi bi-download"></i> Download Materi
                                                    </a>
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($isMember && !$isLecturer): ?>
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
                                                <div class="mt-3">
                                                    <?php foreach ($submissions[$activity['id']] as $submission): ?>
                                                        <?php if ($submission['user_id'] == $user_id): ?>
                                                            <p>
                                                                <i class="bi bi-folder-fill text-warning"></i> 
                                                                <?php echo htmlspecialchars($submission['name']); ?>
                                                                <a href="./upload/mahasiswa/activity/<?php echo $user_id; ?>/<?php echo urlencode($submission['file_name']); ?>" 
                                                                   download class="btn btn-sm btn-outline-primary shadow-sm ms-2" style="border-radius: 8px;">
                                                                    <i class="bi bi-download"></i> Download
                                                                </a>
                                                            </p>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($isLecturer && isset($submissions[$activity['id']])): ?>
                                            <hr/>
                                            <h6 class="mt-3">Submissions:</h6>
                                            <div class="d-flex flex-wrap">
                                                <?php foreach ($submissions[$activity['id']] as $submission): ?>
                                                    <div class="me-3 mb-3 text-center">
                                                        <i class="bi bi-folder-fill text-warning" style="font-size: 2rem;"></i>
                                                        <p class="text-dark"><?php echo htmlspecialchars($submission['name']); ?></p>
                                                        <a href="./upload/mahasiswa/activity/<?php echo $submission['user_id']; ?>/<?php echo urlencode($submission['file_name']); ?>" 
                                                           download class="btn btn-sm btn-primary shadow-sm" style="border-radius: 8px;">
                                                            <i class="bi bi-download"></i> Download
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
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

<!-- Modal Tambah Activity (Dosen) -->
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addActivityModalLabel">Tambah Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Activity</label>
                        <input type="text" name="activity_title" class="form-control shadow-sm" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="activity_description" class="form-control shadow-sm" rows="3" style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File atau Link</label>
                        <input type="text" name="activity_content" class="form-control shadow-sm" placeholder="Masukkan link atau kosongkan jika upload file" style="border-radius: 10px;">
                        <input type="file" name="activity_file" class="form-control shadow-sm mt-2" style="border-radius: 10px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_activity" class="btn btn-primary shadow-sm" style="border-radius: 10px;">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Join Classroom -->
<div class="modal fade" id="joinClassModal" tabindex="-1" aria-labelledby="joinClassModalLabel" aria-hidden="true">
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

<!-- Modal Leave Classroom -->
<div class="modal fade" id="leaveClassModal" tabindex="-1" aria-labelledby="leaveClassModalLabel" aria-hidden="true">
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

<!-- Modal Status Activity Student -->
<div class="modal fade" id="studentActivityModal" tabindex="-1" aria-labelledby="studentActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title" id="studentActivityModalLabel">Status Activity - <span id="studentName"></span></h5>
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
                    <tbody id="activityStatusTable">
                        <!-- Diisi via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Toast untuk Add Friend -->
<div class="toast-container position-fixed top-50 start-50 translate-middle">
    <div id="friendToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body"></div>
    </div>
</div>

<style>
.card {
    border-radius: 10px;
    transition: transform 0.2s ease-in-out;
}
.card:hover {
    transform: translateY(-5px);
}
.list-group-item {
    border: none;
    padding: 10px;
    transition: background-color 0.3s ease;
}
.list-group-item:hover {
    background-color: #f1f3f5;
}
.list-group-item.disabled {
    cursor: default;
    opacity: 0.7;
}
.accordion-button {
    background-color: #f1f3f5;
    border-radius: 10px !important;
    transition: background-color 0.3s ease;
}
.accordion-button:not(.collapsed) {
    background-color: #e7f3ff;
    color: #007bff;
}
.alert-success {
    background-color: #e6ffed;
    border-color: #b3ffcc;
    color: #006633;
}
.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeeba;
    color: #856404;
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
.table {
    border-radius: 10px;
    overflow: hidden;
}
.table th, .table td {
    padding: 12px;
}
</style>

<?php include '_partials/_admin_scripts.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan modal join saat halaman dimuat jika user belum bergabung
    <?php if (!$isMember): ?>
        var joinModal = new bootstrap.Modal(document.getElementById('joinClassModal'), {});
        joinModal.show();
    <?php endif; ?>

    // Handle klik tombol "Ya" di modal join
    document.getElementById('confirmJoin').addEventListener('click', function() {
        <?php if ($isLoggedIn): ?>
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="join_class" value="1">';
            document.body.appendChild(form);
            form.submit();
        <?php else: ?>
            document.getElementById('loginAlert').classList.remove('d-none');
            joinModal.hide();
        <?php endif; ?>
    });

    // Handle tombol cari anggota dengan AJAX
    document.getElementById('searchButton').addEventListener('click', function() {
        const query = document.getElementById('memberSearch').value;
        fetch('index.php?page=classroom&action=search_members&classroom_id=<?php echo $classroom_id; ?>&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const memberList = document.getElementById('memberList');
                memberList.innerHTML = '';
                data.forEach(member => {
                    const isLecturer = member.role === 'lecturer';
                    const clickable = <?php echo json_encode($isLecturer); ?> && !isLecturer ? 
                        `data-bs-toggle="modal" data-bs-target="#studentActivityModal" data-user-id="${member.id}" data-user-name="${member.name}"` : '';
                    const html = `
                        <div class="list-group-item d-flex align-items-center justify-content-between shadow-sm mb-2 ${clickable ? '' : 'disabled'}" style="border-radius: 10px;" ${clickable}>
                            <div class="d-flex align-items-center">
                                <img src="${member.profile_image ? './upload/image/' + member.profile_image : './image/robot-ai.png'}" 
                                     alt="Profile" class="rounded-circle me-2 border border-primary border-2" style="width: 40px; height: 40px;">
                                <div>
                                    <strong class="text-dark">${member.name}</strong>
                                    <small class="text-muted d-block">${member.role === 'lecturer' ? 'Dosen' : 'Mahasiswa'}</small>
                                </div>
                            </div>
                            <?php if ($isLoggedIn && $user_id != '${member.id}'): ?>
                                <button class="btn btn-sm btn-outline-primary add-friend shadow-sm" data-friend-id="${member.id}" style="border-radius: 8px;">Add Friend</button>
                            <?php endif; ?>
                        </div>
                    `;
                    memberList.innerHTML += html;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal mencari anggota. Silakan coba lagi.');
            });
    });

    // Handle klik nama student untuk modal activity (hanya dosen)
    document.querySelectorAll('.list-group-item:not(.disabled)').forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            document.getElementById('studentName').textContent = userName;
            document.getElementById('addFriendButton').innerHTML = 
                `<button class="btn btn-sm btn-outline-primary add-friend shadow-sm" data-friend-id="${userId}" style="border-radius: 8px;">Add Friend</button>`;
            fetch('index.php?page=classroom&action=get_student_activities&user_id=' + userId + '&classroom_id=<?php echo $classroom_id; ?>')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('activityStatusTable');
                    tbody.innerHTML = '';
                    data.forEach(activity => {
                        const downloadLink = activity.file_name ? 
                            `<a href="./upload/<?php echo urlencode($classroom['title']); ?>/activity/${userId}/${encodeURIComponent(activity.file_name)}" download class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 8px;"><i class="bi bi-download"></i></a>` : '';
                        const row = `<tr>
                            <td>${activity.title}</td>
                            <td>${activity.file_name || 'Belum upload'}</td>
                            <td>${activity.status || 'Pending'}</td>
                            <td>${activity.submitted_at || '-'}</td>
                            <td>${downloadLink}</td>
                        </tr>`;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal memuat status aktivitas. Silakan coba lagi.');
                });
        });
    });

    // Handle add friend
    document.querySelectorAll('.add-friend').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const friendId = this.getAttribute('data-friend-id');
            fetch('index.php?page=chat&action=add_friend', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ friend_id: friendId })
            })
            .then(response => response.json())
            .then(data => {
                const toast = new bootstrap.Toast(document.getElementById('friendToast'));
                const toastBody = document.querySelector('#friendToast .toast-body');
                toastBody.textContent = data.message;
                if (data.message.includes('berhasil')) {
                    toastBody.parentElement.classList.remove('bg-danger');
                    toastBody.parentElement.classList.add('bg-success');
                } else {
                    toastBody.parentElement.classList.remove('bg-success');
                    toastBody.parentElement.classList.add('bg-danger');
                }
                toast.show();
            })
            .catch(error => {
                console.error('Error:', error);
                const toast = new bootstrap.Toast(document.getElementById('friendToast'));
                const toastBody = document.querySelector('#friendToast .toast-body');
                toastBody.textContent = 'Gagal menambahkan teman. Silakan coba lagi.';
                toastBody.parentElement.classList.remove('bg-success');
                toastBody.parentElement.classList.add('bg-danger');
                toast.show();
            });
        });
    });
});
</script>