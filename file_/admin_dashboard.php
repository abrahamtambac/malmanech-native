<?php
include_once './controllers/AdminController.php';
include_once './controllers/ClassroomController.php';
include_once './config/db.php';

$adminController = new AdminController($conn);
$adminController->checkAdminAccess();
$classroomController = new ClassroomController($conn);

// Ambil data profil
$userProfile = $adminController->getUserProfile();
$profile_image = $userProfile['profile_image'];
$show_upload_modal = empty($profile_image);

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $image = $_FILES['profile_image'] ?? null;

    $uploadDir = './upload/image/';
    $newImage = $profile_image;
    if ($image && $image['error'] != UPLOAD_ERR_NO_FILE) {
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '-' . basename($image['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($image['tmp_name'], $targetFile)) {
            $newImage = $fileName;
        }
    }

    $stmt = $conn->prepare("UPDATE tb_users SET name = ?, email = ?, profile_image = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $newImage, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=admin_dashboard");
    exit();
}

// Proses upload gambar profil
$uploadResult = $adminController->uploadProfileImage();
if ($uploadResult['profile_image']) {
    $profile_image = $uploadResult['profile_image'];
    $show_upload_modal = false;
}
$upload_error = $uploadResult['upload_error'];

// Ambil data meeting (batasi 2 untuk tampilan awal)
$meetings = array_slice($adminController->getMeetings(), 0, 2);
$invitedMeetings = array_slice($adminController->getInvitedMeetings(), 0, 2);

// Ambil semua meeting untuk modal
$allMeetings = $adminController->getMeetings();
$allInvitedMeetings = $adminController->getInvitedMeetings();

// Ambil data classroom (batasi 2 untuk tampilan awal)
$myClassrooms = array_slice($classroomController->getMyClassrooms(), 0, 2);
$joinedClassrooms = array_slice($classroomController->getJoinedClassrooms(), 0, 2);

// Ambil semua classroom untuk modal
$allMyClassrooms = $classroomController->getMyClassrooms();
$allJoinedClassrooms = $classroomController->getJoinedClassrooms();

// Ambil data following dan followers
$following = count($adminController->getFollowing($_SESSION['user_id']));
$followers = count($adminController->getFollowers($_SESSION['user_id']));

// Proses pencarian classroom
$searchedClassroom = null;
$searchClassroomError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_classroom'])) {
    $class_code = $_POST['class_code'];
    if (empty($class_code)) {
        $searchClassroomError = "Silahkan masukkan kode classroom.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM tb_classrooms WHERE class_code = ?");
        $stmt->bind_param("s", $class_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $searchedClassroom = $result->fetch_assoc();
        $stmt->close();
    }
}

// Proses tambah meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meeting'])) {
    $date = $_POST['meeting_date'];
    $time = $_POST['meeting_time'];
    $title = $_POST['meeting_title'];
    $type = $_POST['meeting_type'];
    $platform = ($type === 'online') ? $_POST['meeting_platform'] : 'Luring'; // Set default to 'Luring' if offline
    $invited_users = isset($_POST['invited_users']) ? $_POST['invited_users'] : [];

    if (empty($invited_users)) {
        $meeting_error = "Pilih minimal satu pengguna untuk diundang.";
    } elseif ($adminController->addMeeting($date, $time, $title, $platform, $invited_users)) {
        header("Location: index.php?page=admin_dashboard");
        exit();
    } else {
        $meeting_error = "Tidak dapat menambahkan meeting.";
    }
}

// Proses hapus meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meeting'])) {
    $meeting_id = $_POST['meeting_id'];
    if ($adminController->deleteMeeting($meeting_id)) {
        header("Location: index.php?page=admin_dashboard");
        exit();
    } else {
        $delete_error = "Tidak dapat menghapus meeting.";
    }
}

// Proses tambah classroom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_classroom'])) {
    $title = $_POST['classroom_title'];
    $description = $_POST['classroom_description'];
    $image = $_FILES['classroom_image'] ?? null;
    $result = $classroomController->createClassroom($title, $description, $image);
    if ($result['success']) {
        header("Location: index.php?page=admin_dashboard&classroom_added=1");
        exit();
    } else {
        $classroom_error = "Tidak dapat menambahkan classroom.";
    }
}

// Proses edit classroom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_classroom'])) {
    $classroom_id = $_POST['classroom_id'];
    $title = $_POST['classroom_title'];
    $description = $_POST['classroom_description'];
    $image = $_FILES['classroom_image'] ?? null;

    // Ambil data classroom saat ini untuk mendapatkan gambar lama
    $stmt = $conn->prepare("SELECT classroom_image FROM tb_classrooms WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $classroom_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $current_image = $result['classroom_image'];
    $new_image = $current_image; // Default ke gambar lama

    // Proses upload gambar baru jika ada
    if ($image && $image['error'] != UPLOAD_ERR_NO_FILE) {
        $uploadDir = './upload/classroom/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '-' . basename($image['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($image['tmp_name'], $targetFile)) {
            $new_image = $fileName;
            // Hapus gambar lama jika ada
            if ($current_image && file_exists($uploadDir . $current_image)) {
                unlink($uploadDir . $current_image);
            }
        }
    }

    // Update data classroom di database
    $stmt = $conn->prepare("UPDATE tb_classrooms SET title = ?, description = ?, classroom_image = ? WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("sssii", $title, $description, $new_image, $classroom_id, $_SESSION['user_id']);
    if ($stmt->execute()) {
        header("Location: index.php?page=admin_dashboard&classroom_updated=1");
        exit();
    } else {
        $classroom_error = "Tidak dapat mengupdate classroom.";
    }
    $stmt->close();
}

// AJAX handler untuk pencarian pengguna
if (isset($_GET['action']) && $_GET['action'] === 'search_users' && isset($_GET['query'])) {
    ob_clean();
    $search_query = $_GET['query'];
    $search_results = $adminController->searchUsers($search_query);
    header('Content-Type: application/json');
    echo json_encode($search_results);
    exit();
}

// AJAX handler untuk detail meeting
if (isset($_GET['action']) && $_GET['action'] === 'get_meeting_details' && isset($_GET['meeting_id'])) {
    ob_clean();
    $meeting_id = $_GET['meeting_id'];
    $details = $adminController->getMeetingDetails($meeting_id);
    header('Content-Type: application/json');
    if ($details) {
        $response = [
            'title' => $details['title'],
            'date' => date('D, d M', strtotime($details['date'])),
            'time' => date('h:i A', strtotime($details['time'])),
            'platform' => ucfirst($details['platform']),
            'invited_users' => $details['invited_users'],
            'creator' => $details['creator']
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Meeting tidak ditemukan']);
    }
    exit();
}

// AJAX handler untuk detail classroom
if (isset($_GET['action']) && $_GET['action'] === 'get_classroom_details' && isset($_GET['classroom_id'])) {
    ob_clean();
    $classroom_id = $_GET['classroom_id'];
    $stmt = $conn->prepare("SELECT title, description, classroom_image FROM tb_classrooms WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $classroom_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    header('Content-Type: application/json');
    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Classroom tidak ditemukan']);
    }
    exit();
}

// AJAX handler untuk pencarian classroom
if (isset($_GET['action']) && $_GET['action'] === 'search_my_classrooms' && isset($_GET['query'])) {
    ob_clean();
    $query = "%" . $_GET['query'] . "%";
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.description, c.class_code, c.class_link, c.type, c.classroom_image, 
               COUNT(cm.user_id) as member_count
        FROM tb_classrooms c
        LEFT JOIN tb_classroom_members cm ON c.id = cm.classroom_id
        WHERE c.creator_id = ? AND (c.title LIKE ? OR c.class_code LIKE ?)
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("iss", $user_id, $query, $query);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'search_joined_classrooms' && isset($_GET['query'])) {
    ob_clean();
    $query = "%" . $_GET['query'] . "%";
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.description, c.class_code, c.class_link, c.type, c.classroom_image, 
               u.name as creator_name
        FROM tb_classroom_members cm
        JOIN tb_classrooms c ON cm.classroom_id = c.id
        JOIN tb_users u ON c.creator_id = u.id
        WHERE cm.user_id = ? AND cm.role = 'student' AND (c.title LIKE ? OR c.class_code LIKE ?)
        ORDER BY cm.joined_at DESC
    ");
    $stmt->bind_param("iss", $user_id, $query, $query);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'search_meetings' && isset($_GET['query'])) {
    ob_clean();
    $query = "%" . $_GET['query'] . "%";
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT m.id, m.title, m.date, m.time, m.platform 
        FROM tb_meetings m 
        WHERE m.creator_id = ? AND (m.title LIKE ?)
        ORDER BY m.date DESC, m.time DESC
    ");
    $stmt->bind_param("is", $user_id, $query);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'search_invited_meetings' && isset($_GET['query'])) {
    ob_clean();
    $query = "%" . $_GET['query'] . "%";
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT m.id, m.title, m.date, m.time, m.platform, u.name as creator 
        FROM tb_meeting_invites mi 
        JOIN tb_meetings m ON mi.meeting_id = m.id 
        JOIN tb_users u ON m.creator_id = u.id 
        WHERE mi.user_id = ? AND (m.title LIKE ?)
        ORDER BY m.date DESC, m.time DESC
    ");
    $stmt->bind_param("is", $user_id, $query);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}
?>

<?php include '_partials/_admin_head.php'; ?>

<div class="container-fluid py-4">
    <!-- Header Profil -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex align-items-center">
                <img src="<?php echo !empty($profile_image) ? './upload/image/' . $profile_image : './image/robot-ai.png'; ?>"
                     alt="Profil Admin" class="rounded-circle me-3 border border-primary border-3 shadow-sm" style="width: 90px; height: 90px; object-fit: cover;">
                <div>
                    <h2 class="text-dark fw-bolder mb-1">Selamat Datang, <?php echo htmlspecialchars($userProfile['name']); ?>!</h2>
                    <p class="small text-muted mb-2">
                        <span class="status-dot <?php echo $lastSeen ? 'offline' : 'online'; ?>"></span>
                        <?php echo $lastSeen ? 'Terakhir aktif: ' . $lastSeen : 'Sedang aktif'; ?>
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <span class="badge bg-primary-subtle text-primary"><i class="bi bi-person-plus"></i> Mengikuti: <?php echo $following; ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-person-check"></i> Pengikut: <?php echo $followers; ?></span>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#updateProfileModal">Update Profil</button>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <a href="index.php?page=logout" class="btn btn-outline-danger shadow-sm"><i class="bi bi-box-arrow-right fs-4"></i> Keluar</a>
        </div>
    </div>

    <!-- Informasi Penting -->
    <div class="card border shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-body">
            <h5 class="fw-bold mb-3 text-primary">Cari Classroom</h5>
            <div class="alert alert-warning p-3 mb-3" style="border-radius: 10px;">
                <h6 class="fw-bold mb-1">Temukan Classroom Anda</h6>
                <p class="text-muted small mb-0">Masukkan kode classroom untuk menemukan dan mengunjungi ruang belajar yang Anda inginkan.</p>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <center>
                       <img src="./image/meeting-illustration.jpg" alt="Classroom Icon" class="rounded-circle me-2"
                       style="width: 300px; height: 300px; object-fit: cover;"></center>
                </div>
                <div class="col-md-6">
                    <form method="POST" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="class_code" class="form-control shadow-sm" placeholder="Masukkan kode classroom..." style="border-radius: 10px 0 0 10px;" required>
                            <button type="submit" name="search_classroom" class="btn btn-primary shadow-sm" style="border-radius: 0 10px 10px 0;">Cari</button>
                        </div>
                    </form>
                    <?php if ($searchClassroomError): ?>
                        <div class="alert alert-danger text-center shadow-sm" style="border-radius: 10px;">
                            <?php echo $searchClassroomError; ?>
                        </div>
                    <?php elseif ($searchedClassroom): ?>
                        <div class="card border rounded p-3 alert alert-primary shadow-sm" style="border-radius: 15px;">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $searchedClassroom['classroom_image'] ? './upload/classroom/' . $searchedClassroom['classroom_image'] : './image/classroom_icon.png'; ?>" 
                                     alt="Classroom Icon" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <a href="index.php?page=classroom&classroom_id=<?php echo $searchedClassroom['id']; ?>" class="text-dark fw-bold text-decoration-none">
                                        <?php echo htmlspecialchars($searchedClassroom['title']); ?>
                                    </a>
                                    <p class="text-muted small mb-1"><?php echo htmlspecialchars($searchedClassroom['description']); ?></p>
                                    <small class="text-muted">Kode: <?php echo $searchedClassroom['class_code']; ?></small>
                                </div>
                                <div class="ms-2">
                                    <a href="index.php?page=classroom&classroom_id=<?php echo $searchedClassroom['id']; ?>" class="btn btn-sm btn-primary shadow-sm" style="border-radius: 10px;">Kunjungi</a>
                                </div>
                            </div>
                        </div>
                    <?php elseif (isset($_POST['search_classroom'])): ?>
                        <div class="alert alert-danger text-center shadow-sm" style="border-radius: 10px;">
                            Classroom dengan kode tersebut tidak ditemukan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Classroom Saya & Classroom yang Diikuti -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bolder text-primary">Classroom Saya</h5>
                        <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addClassroomModal" style="border-radius: 10px;">
                            <i class="bi bi-plus"></i> Tambah
                        </button>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" id="myClassroomSearch" class="form-control shadow-sm" placeholder="Cari classroom..." style="border-radius: 10px 0 0 10px;">
                        <button class="btn btn-primary shadow-sm" id="myClassroomSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                    </div>
                    <div id="myClassroomList">
                        <?php if (empty($myClassrooms)): ?>
                            <p class="text-muted mt-3">Belum ada classroom yang dibuat. Tambah sekarang!</p>
                        <?php else: ?>
                            <?php foreach ($myClassrooms as $classroom): ?>
                                <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $classroom['classroom_image'] ? './upload/classroom/' . $classroom['classroom_image'] : './image/classroom_icon.png'; ?>" 
                                             alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <a href="index.php?page=classroom&classroom_id=<?php echo $classroom['id']; ?>" class="text-dark fw-bold text-decoration-none">
                                                <?php echo htmlspecialchars($classroom['title']); ?>
                                            </a>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($classroom['description']); ?></p>
                                            <small class="text-muted">Code: <?php echo $classroom['class_code']; ?></small>
                                        </div>
                                        <div class="ms-2 d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="<?php echo $classroom['class_link']; ?>" style="border-radius: 8px;">Copy Link</button>
                                            <button class="btn btn-sm btn-outline-warning edit-classroom shadow-sm" data-classroom-id="<?php echo $classroom['id']; ?>" data-bs-toggle="modal" data-bs-target="#editClassroomModal" style="border-radius: 8px;">Edit</button>
                                            <button class="btn btn-sm btn-outline-danger delete-classroom shadow-sm" data-classroom-id="<?php echo $classroom['id']; ?>" style="border-radius: 8px;">Hapus</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-outline-primary btn-sm mt-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#allMyClassroomsModal" style="border-radius: 10px;">Tampilkan Lebih Banyak</button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <h5 class="fw-bolder mb-3 text-primary">Classroom yang Diikuti</h5>
                    <div class="input-group mb-3">
                        <input type="text" id="joinedClassroomSearch" class="form-control shadow-sm" placeholder="Cari classroom..." style="border-radius: 10px 0 0 10px;">
                        <button class="btn btn-primary shadow-sm" id="joinedClassroomSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                    </div>
                    <div id="joinedClassroomList">
                        <?php if (empty($joinedClassrooms)): ?>
                            <p class="text-muted mt-3">Belum ada classroom yang diikuti.</p>
                        <?php else: ?>
                            <?php foreach ($joinedClassrooms as $classroom): ?>
                                <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $classroom['classroom_image'] ? './upload/classroom/' . $classroom['classroom_image'] : './image/classroom_icon.png'; ?>" 
                                             alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <a href="index.php?page=classroom&classroom_id=<?php echo $classroom['id']; ?>" class="text-dark fw-bold text-decoration-none">
                                                <?php echo htmlspecialchars($classroom['title']); ?>
                                            </a>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($classroom['description']); ?></p>
                                            <small class="text-muted">Code: <?php echo $classroom['class_code']; ?></small>
                                        </div>
                                        <div class="ms-2">
                                            <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="<?php echo $classroom['class_link']; ?>" style="border-radius: 8px;">Copy Link</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-outline-primary btn-sm mt-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#allJoinedClassroomsModal" style="border-radius: 10px;">Tampilkan Lebih Banyak</button>
                </div>
            </div>
        </div>
    </div>

    <!-- My Meetings & Invited Meetings -->
    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bolder text-primary">Meeting Saya</h5>
                        <div class="d-flex align-items-center gap-2">
                            <select id="filterPlatform" class="form-select form-select-sm shadow-sm" style="width: auto; border-radius: 10px;">
                                <option value="all">Semua Platform</option>
                                <option value="zoom">Zoom</option>
                                <option value="google">Google Meet</option>
                            </select>
                            <button class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addMeetingModal" style="border-radius: 10px;">
                                <i class="bi bi-plus"></i> Tambah
                            </button>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" id="meetingSearch" class="form-control shadow-sm" placeholder="Cari meeting..." style="border-radius: 10px 0 0 10px;">
                        <button class="btn btn-primary shadow-sm" id="meetingSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                    </div>
                    <div id="meetingList">
                        <?php if (empty($meetings)): ?>
                            <p class="text-muted mt-3">Belum ada meeting yang dibuat.</p>
                        <?php else: ?>
                            <?php foreach ($meetings as $meeting): ?>
                                <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center">
                                        <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo date('D, d M Y', strtotime($meeting['date'])); ?> | <?php echo date('h:i A', strtotime($meeting['time'])); ?></p>
                                            <small class="text-muted"><?php echo $meeting['platform'] === 'Luring' ? 'Luring' : ucfirst($meeting['platform']); ?></small>
                                        </div>
                                        <div class="ms-2 d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary view-meeting shadow-sm" data-meeting-id="<?php echo $meeting['id']; ?>" style="border-radius: 8px;">
                                                <i class="bi bi-eye"></i> Lihat
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-meeting shadow-sm" data-meeting-id="<?php echo $meeting['id']; ?>" style="border-radius: 8px;">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-outline-primary btn-sm mt-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#allMeetingsModal" style="border-radius: 10px;">Tampilkan Lebih Banyak</button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border shadow-sm" style="border-radius: 15px;">
                <div class="card-body">
                    <h5 class="fw-bolder mb-3 text-primary">Undangan Meeting</h5>
                    <div class="input-group mb-3">
                        <input type="text" id="invitedMeetingSearch" class="form-control shadow-sm" placeholder="Cari undangan..." style="border-radius: 10px 0 0 10px;">
                        <button class="btn btn-primary shadow-sm" id="invitedMeetingSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                    </div>
                    <div id="invitedMeetingList">
                        <?php if (empty($invitedMeetings)): ?>
                            <p class="text-muted mt-3">Belum ada undangan meeting.</p>
                        <?php else: ?>
                            <?php foreach ($invitedMeetings as $meeting): ?>
                                <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center">
                                        <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <h6 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo date('D, d M Y', strtotime($meeting['date'])); ?> | <?php echo date('h:i A', strtotime($meeting['time'])); ?></p>
                                            <small class="text-muted"><?php echo $meeting['platform'] === 'Luring' ? 'Luring' : ucfirst($meeting['platform']); ?> | Oleh: <?php echo htmlspecialchars($meeting['creator']); ?></small>
                                        </div>
                                        <div class="ms-2">
                                            <button class="btn btn-sm btn-primary view-meeting shadow-sm" data-meeting-id="<?php echo $meeting['id']; ?>" style="border-radius: 8px;">Lihat</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-outline-primary btn-sm mt-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#allInvitedMeetingsModal" style="border-radius: 10px;">Tampilkan Lebih Banyak</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Update Profil -->
<div class="modal fade" id="updateProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Update Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control shadow-sm" value="<?php echo htmlspecialchars($userProfile['name']); ?>" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control shadow-sm" value="<?php echo htmlspecialchars($userProfile['email']); ?>" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto Profil</label>
                        <input type="file" name="profile_image" class="form-control shadow-sm" accept="image/*" style="border-radius: 10px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_profile" class="btn btn-primary shadow-sm" style="border-radius: 10px;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Classroom -->
<div class="modal fade" id="addClassroomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Classroom Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Classroom</label>
                        <input type="text" name="classroom_title" class="form-control shadow-sm" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="classroom_description" class="form-control shadow-sm" rows="3" style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Classroom</label>
                        <input type="file" name="classroom_image" class="form-control shadow-sm" accept="image/*" style="border-radius: 10px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_classroom" class="btn btn-primary shadow-sm" style="border-radius: 10px;">Generate Classroom</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Classroom -->
<div class="modal fade" id="editClassroomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Classroom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="classroom_id" id="editClassroomId">
                    <div class="mb-3">
                        <label class="form-label">Judul Classroom</label>
                        <input type="text" name="classroom_title" id="editClassroomTitle" class="form-control shadow-sm" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="classroom_description" id="editClassroomDescription" class="form-control shadow-sm" rows="3" style="border-radius: 10px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Classroom</label>
                        <input type="file" name="classroom_image" class="form-control shadow-sm" accept="image/*" style="border-radius: 10px;">
                        <small class="text-muted">Biarkan kosong jika tidak ingin mengganti gambar.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_classroom" class="btn btn-primary shadow-sm" style="border-radius: 10px;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Meeting -->
<div class="modal fade" id="addMeetingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px;">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Meeting Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Meeting</label>
                        <input type="text" name="meeting_title" class="form-control shadow-sm" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="meeting_date" class="form-control shadow-sm" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Waktu</label>
                        <input type="time" name="meeting_time" class="form-control shadow-sm" style="border-radius: 10px;" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Meeting</label>
                        <select name="meeting_type" class="form-select shadow-sm" id="meetingType" style="border-radius: 10px;" required>
                            <option value="offline">Luring</option>
                            <option value="online">Daring</option>
                        </select>
                    </div>
                    <div class="mb-3" id="platformField" style="display: none;">
                        <label class="form-label">Platform</label>
                        <select name="meeting_platform" class="form-select shadow-sm" style="border-radius: 10px;">
                            <option value="zoom">Zoom</option>
                            <option value="google">Google Meet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Undang Peserta</label>
                        <input type="text" id="searchUsers" class="form-control shadow-sm" placeholder="Cari pengguna..." style="border-radius: 10px;">
                        <div id="userList" class="mt-2"></div>
                        <div id="selectedUsers" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_meeting" class="btn btn-primary shadow-sm" style="border-radius: 10px;">Tambah Meeting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Semua Classroom Saya -->
<div class="modal fade" id="allMyClassroomsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title">Semua Classroom Saya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" id="allMyClassroomSearch" class="form-control shadow-sm" placeholder="Cari classroom..." style="border-radius: 10px 0 0 10px;">
                    <button class="btn btn-primary shadow-sm" id="allMyClassroomSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                </div>
                <div id="allMyClassroomList">
                    <?php foreach ($allMyClassrooms as $classroom): ?>
                        <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $classroom['classroom_image'] ? './upload/classroom/' . $classroom['classroom_image'] : './image/classroom_icon.png'; ?>" 
                                     alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <a href="index.php?page=classroom&classroom_id=<?php echo $classroom['id']; ?>" class="text-dark fw-bold text-decoration-none">
                                        <?php echo htmlspecialchars($classroom['title']); ?>
                                    </a>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($classroom['description']); ?></p>
                                    <small class="text-muted">Code: <?php echo $classroom['class_code']; ?></small>
                                </div>
                                <div class="ms-2 d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="<?php echo $classroom['class_link']; ?>" style="border-radius: 8px;">Copy Link</button>
                                    <button class="btn btn-sm btn-outline-warning edit-classroom shadow-sm" data-classroom-id="<?php echo $classroom['id']; ?>" data-bs-toggle="modal" data-bs-target="#editClassroomModal" style="border-radius: 8px;">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger delete-classroom shadow-sm" data-classroom-id="<?php echo $classroom['id']; ?>" style="border-radius: 8px;">Hapus</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Semua Classroom yang Diikuti -->
<div class="modal fade" id="allJoinedClassroomsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title">Semua Classroom yang Diikuti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" id="allJoinedClassroomSearch" class="form-control shadow-sm" placeholder="Cari classroom..." style="border-radius: 10px 0 0 10px;">
                    <button class="btn btn-primary shadow-sm" id="allJoinedClassroomSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                </div>
                <div id="allJoinedClassroomList">
                    <?php foreach ($allJoinedClassrooms as $classroom): ?>
                        <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $classroom['classroom_image'] ? './upload/classroom/' . $classroom['classroom_image'] : './image/classroom_icon.png'; ?>" 
                                     alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <a href="index.php?page=classroom&classroom_id=<?php echo $classroom['id']; ?>" class="text-dark fw-bold text-decoration-none">
                                        <?php echo htmlspecialchars($classroom['title']); ?>
                                    </a>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($classroom['description']); ?></p>
                                    <small class="text-muted">Code: <?php echo $classroom['class_code']; ?></small>
                                </div>
                                <div class="ms-2">
                                    <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="<?php echo $classroom['class_link']; ?>" style="border-radius: 8px;">Copy Link</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Semua Meeting Saya -->
<div class="modal fade" id="allMeetingsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title">Semua Meeting Saya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" id="allMeetingSearch" class="form-control shadow-sm" placeholder="Cari meeting..." style="border-radius: 10px 0 0 10px;">
                    <button class="btn btn-primary shadow-sm" id="allMeetingSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                </div>
                <div id="allMeetingList">
                    <?php foreach ($allMeetings as $meeting): ?>
                        <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                            <div class="d-flex align-items-center">
                                <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo date('D, d M Y', strtotime($meeting['date'])); ?> | <?php echo date('h:i A', strtotime($meeting['time'])); ?></p>
                                    <small class="text-muted"><?php echo $meeting['platform'] === 'Luring' ? 'Luring' : ucfirst($meeting['platform']); ?></small>
                                </div>
                                <div class="ms-2 d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary view-meeting shadow-sm" data-meeting-id="<?php echo $meeting['id']; ?>" style="border-radius: 8px;">
                                        <i class="bi bi-eye"></i> Lihat
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-meeting shadow-sm" data-meeting-id="<?php echo $meeting['id']; ?>" style="border-radius: 8px;">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Semua Undangan Meeting -->
<div class="modal fade" id="allInvitedMeetingsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header">
                <h5 class="modal-title">Semua Undangan Meeting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" id="allInvitedMeetingSearch" class="form-control shadow-sm" placeholder="Cari undangan..." style="border-radius: 10px 0 0 10px;">
                    <button class="btn btn-primary shadow-sm" id="allInvitedMeetingSearchButton" style="border-radius: 0 10px 10px 0;">Cari</button>
                </div>
                <div id="allInvitedMeetingList">
                    <?php foreach ($allInvitedMeetings as $meeting): ?>
                        <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                            <div class="d-flex align-items-center">
                                <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo date('D, d M Y', strtotime($meeting['date'])); ?> | <?php echo date('h:i A', strtotime($meeting['time'])); ?></p>
                                    <small class="text-muted"><?php echo $meeting['platform'] === 'Luring' ? 'Luring' : ucfirst($meeting['platform']); ?> | Oleh: <?php echo htmlspecialchars($meeting['creator']); ?></small>
                                </div>
                                <div class="ms-2">
                                    <button class="btn btn-sm btn-primary view-meeting shadow-sm" data-meeting-id="<?php echo $meeting['id']; ?>" style="border-radius: 8px;">Lihat</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast untuk Copy Link -->
<div class="toast-container position-fixed top-50 start-50 translate-middle">
    <div id="copyToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body">
            Link berhasil dicopy, silahkan share...
        </div>
    </div>
</div>

<?php include '_partials/_admin_modals.php'; ?>
<?php include '_partials/_admin_scripts.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy invite link
    document.querySelectorAll('.copy-link').forEach(button => {
        button.addEventListener('click', function() {
            const baseUrl = window.location.origin + '/chatai/'; // Sesuaikan dengan base_url situs Anda
            const link = baseUrl + this.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                const toast = new bootstrap.Toast(document.getElementById('copyToast'));
                toast.show();
            });
        });
    });

    // Delete classroom
    document.querySelectorAll('.delete-classroom').forEach(button => {
        button.addEventListener('click', function() {
            const classroomId = this.getAttribute('data-classroom-id');
            if (confirm('Apakah Anda yakin ingin menghapus classroom ini?')) {
                fetch('index.php?page=classroom&action=delete_classroom', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ classroom_id: classroomId })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Gagal menghapus classroom.');
                    }
                });
            }
        });
    });

    // Edit classroom
    document.querySelectorAll('.edit-classroom').forEach(button => {
        button.addEventListener('click', function() {
            const classroomId = this.getAttribute('data-classroom-id');
            fetch('index.php?page=admin_dashboard&action=get_classroom_details&classroom_id=' + classroomId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    document.getElementById('editClassroomId').value = classroomId;
                    document.getElementById('editClassroomTitle').value = data.title;
                    document.getElementById('editClassroomDescription').value = data.description;
                })
                .catch(error => {
                    console.error('Error fetching classroom details:', error);
                    alert('Gagal mengambil data classroom.');
                });
        });
    });

    // Search My Classrooms
    document.getElementById('myClassroomSearchButton').addEventListener('click', function() {
        const query = document.getElementById('myClassroomSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_my_classrooms&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('myClassroomList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada classroom yang ditemukan.</p>';
                } else {
                    data.forEach(classroom => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="${classroom.classroom_image ? './upload/classroom/' + classroom.classroom_image : './image/classroom_icon.png'}" 
                                         alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <a href="index.php?page=classroom&classroom_id=${classroom.id}" class="text-dark fw-bold text-decoration-none">
                                            ${classroom.title}
                                        </a>
                                        <p class="text-muted small mb-0">${classroom.description}</p>
                                        <small class="text-muted">Code: ${classroom.class_code}</small>
                                    </div>
                                    <div class="ms-2 d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="${classroom.class_link}" style="border-radius: 8px;">Copy Link</button>
                                        <button class="btn btn-sm btn-outline-warning edit-classroom shadow-sm" data-classroom-id="${classroom.id}" data-bs-toggle="modal" data-bs-target="#editClassroomModal" style="border-radius: 8px;">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger delete-classroom shadow-sm" data-classroom-id="${classroom.id}" style="border-radius: 8px;">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Search Joined Classrooms
    document.getElementById('joinedClassroomSearchButton').addEventListener('click', function() {
        const query = document.getElementById('joinedClassroomSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_joined_classrooms&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('joinedClassroomList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada classroom yang ditemukan.</p>';
                } else {
                    data.forEach(classroom => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="${classroom.classroom_image ? './upload/classroom/' + classroom.classroom_image : './image/classroom_icon.png'}" 
                                         alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <a href="index.php?page=classroom&classroom_id=${classroom.id}" class="text-dark fw-bold text-decoration-none">
                                            ${classroom.title}
                                        </a>
                                        <p class="text-muted small mb-0">${classroom.description}</p>
                                        <small class="text-muted">Code: ${classroom.class_code}</small>
                                    </div>
                                    <div class="ms-2">
                                        <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="${classroom.class_link}" style="border-radius: 8px;">Copy Link</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Search Meetings
    document.getElementById('meetingSearchButton').addEventListener('click', function() {
        const query = document.getElementById('meetingSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_meetings&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('meetingList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada meeting yang ditemukan.</p>';
                } else {
                    data.forEach(meeting => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="text-dark fw-bold mb-1">${meeting.title}</h6>
                                        <p class="text-muted small mb-0">${new Date(meeting.date).toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })} | ${meeting.time}</p>
                                        <small class="text-muted">${meeting.platform === 'Luring' ? 'Luring' : meeting.platform.charAt(0).toUpperCase() + meeting.platform.slice(1)}</small>
                                    </div>
                                    <div class="ms-2 d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-meeting shadow-sm" data-meeting-id="${meeting.id}" style="border-radius: 8px;">
                                            <i class="bi bi-eye"></i> Lihat
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-meeting shadow-sm" data-meeting-id="${meeting.id}" style="border-radius: 8px;">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Search Invited Meetings
    document.getElementById('invitedMeetingSearchButton').addEventListener('click', function() {
        const query = document.getElementById('invitedMeetingSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_invited_meetings&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('invitedMeetingList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada undangan meeting yang ditemukan.</p>';
                } else {
                    data.forEach(meeting => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="text-dark fw-bold mb-1">${meeting.title}</h6>
                                        <p class="text-muted small mb-0">${new Date(meeting.date).toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })} | ${meeting.time}</p>
                                        <small class="text-muted">${meeting.platform === 'Luring' ? 'Luring' : meeting.platform.charAt(0).toUpperCase() + meeting.platform.slice(1)} | Oleh: ${meeting.creator}</small>
                                    </div>
                                    <div class="ms-2">
                                        <button class="btn btn-sm btn-primary view-meeting shadow-sm" data-meeting-id="${meeting.id}" style="border-radius: 8px;">Lihat</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Search All My Classrooms (Modal)
    document.getElementById('allMyClassroomSearchButton').addEventListener('click', function() {
        const query = document.getElementById('allMyClassroomSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_my_classrooms&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('allMyClassroomList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada classroom yang ditemukan.</p>';
                } else {
                    data.forEach(classroom => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="${classroom.classroom_image ? './upload/classroom/' + classroom.classroom_image : './image/classroom_icon.png'}" 
                                         alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <a href="index.php?page=classroom&classroom_id=${classroom.id}" class="text-dark fw-bold text-decoration-none">
                                            ${classroom.title}
                                        </a>
                                        <p class="text-muted small mb-0">${classroom.description}</p>
                                        <small class="text-muted">Code: ${classroom.class_code}</small>
                                    </div>
                                    <div class="ms-2 d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="${classroom.class_link}" style="border-radius: 8px;">Copy Link</button>
                                        <button class="btn btn-sm btn-outline-warning edit-classroom shadow-sm" data-classroom-id="${classroom.id}" data-bs-toggle="modal" data-bs-target="#editClassroomModal" style="border-radius: 8px;">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger delete-classroom shadow-sm" data-classroom-id="${classroom.id}" style="border-radius: 8px;">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Search All Joined Classrooms (Modal)
    document.getElementById('allJoinedClassroomSearchButton').addEventListener('click', function() {
        const query = document.getElementById('allJoinedClassroomSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_joined_classrooms&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('allJoinedClassroomList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada classroom yang ditemukan.</p>';
                } else {
                    data.forEach(classroom => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="${classroom.classroom_image ? './upload/classroom/' + classroom.classroom_image : './image/classroom_icon.png'}" 
                                         alt="Classroom Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <a href="index.php?page=classroom&classroom_id=${classroom.id}" class="text-dark fw-bold text-decoration-none">
                                            ${classroom.title}
                                        </a>
                                        <p class="text-muted small mb-0">${classroom.description}</p>
                                        <small class="text-muted">Code: ${classroom.class_code}</small>
                                    </div>
                                    <div class="ms-2">
                                        <button class="btn btn-sm btn-outline-primary copy-link shadow-sm" data-link="${classroom.class_link}" style="border-radius: 8px;">Copy Link</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Search All Meetings (Modal)
    document.getElementById('allMeetingSearchButton').addEventListener('click', function() {
        const query = document.getElementById('allMeetingSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_meetings&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('allMeetingList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada meeting yang ditemukan.</p>';
                } else {
                    data.forEach(meeting => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="text-dark fw-bold mb-1">${meeting.title}</h6>
                                        <p class="text-muted small mb-0">${new Date(meeting.date).toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })} | ${meeting.time}</p>
                                        <small class="text-muted">${meeting.platform === 'Luring' ? 'Luring' : meeting.platform.charAt(0).toUpperCase() + meeting.platform.slice(1)}</small>
                                    </div>
                                    <div class="ms-2 d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-meeting shadow-sm" data-meeting-id="${meeting.id}" style="border-radius: 8px;">
                                            <i class="bi bi-eye"></i> Lihat
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-meeting shadow-sm" data-meeting-id="${meeting.id}" style="border-radius: 8px;">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Search All Invited Meetings (Modal)
    document.getElementById('allInvitedMeetingSearchButton').addEventListener('click', function() {
        const query = document.getElementById('allInvitedMeetingSearch').value;
        fetch('index.php?page=admin_dashboard&action=search_invited_meetings&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('allInvitedMeetingList');
                list.innerHTML = '';
                if (data.length === 0) {
                    list.innerHTML = '<p class="text-muted mt-3">Tidak ada undangan meeting yang ditemukan.</p>';
                } else {
                    data.forEach(meeting => {
                        const html = `
                            <div class="card border rounded p-2 mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <img src="./image/meeting_icon.png" alt="Meeting Icon" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="text-dark fw-bold mb-1">${meeting.title}</h6>
                                        <p class="text-muted small mb-0">${new Date(meeting.date).toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })} | ${meeting.time}</p>
                                        <small class="text-muted">${meeting.platform === 'Luring' ? 'Luring' : meeting.platform.charAt(0).toUpperCase() + meeting.platform.slice(1)} | Oleh: ${meeting.creator}</small>
                                    </div>
                                    <div class="ms-2">
                                        <button class="btn btn-sm btn-primary view-meeting shadow-sm" data-meeting-id="${meeting.id}" style="border-radius: 8px;">Lihat</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.innerHTML += html;
                    });
                }
            });
    });

    // Toggle platform field based on meeting type
    document.getElementById('meetingType').addEventListener('change', function() {
        const platformField = document.getElementById('platformField');
        if (this.value === 'online') {
            platformField.style.display = 'block';
        } else {
            platformField.style.display = 'none';
        }
    });

    // Search users for meeting invites
    document.getElementById('searchUsers').addEventListener('input', function() {
        const query = this.value;
        if (query.length < 2) {
            document.getElementById('userList').innerHTML = '';
            return;
        }
        fetch('index.php?page=admin_dashboard&action=search_users&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                const userList = document.getElementById('userList');
                userList.innerHTML = '';
                if (data.length === 0) {
                    userList.innerHTML = '<p class="text-muted">Tidak ada pengguna yang ditemukan.</p>';
                } else {
                    data.forEach(user => {
                        const html = `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="invited_users[]" value="${user.id}" id="user_${user.id}">
                                <label class="form-check-label" for="user_${user.id}">
                                    ${user.name} (${user.email})
                                </label>
                            </div>
                        `;
                        userList.innerHTML += html;
                    });
                }
            });
    });
});
</script>

<style>
.card {
    border-radius: 10px;
    transition: transform 0.2s ease-in-out;
}
.card:hover {
    transform: translateY(-5px);
}
.alert-info {
    background-color: #e7f3ff;
    border-color: #b8daff;
    color: #004085;
}
.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeeba;
    color: #856404;
}
.alert-primary {
    background-color: #e7f3ff;
    border-color: #b8daff;
    color: #004085;
}
.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
.badge {
    font-size: 0.8rem;
}
.status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}
.status-dot.online {
    background-color: #28a745;
}
.status-dot.offline {
    background-color: #dc3545;
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
.btn-outline-warning {
    border-color: #ffc107;
    color: #ffc107;
    transition: background-color 0.3s ease, color 0.3s ease;
}
.btn-outline-warning:hover {
    background-color: #ffc107;
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
</style>