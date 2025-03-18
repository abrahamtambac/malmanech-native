<?php

include_once './controllers/AdminController.php';
include_once './config/db.php';

$adminController = new AdminController($conn);
$adminController->checkAdminAccess();

// Ambil data profil
$userProfile = $adminController->getUserProfile();
$profile_image = $userProfile['profile_image'];
$show_upload_modal = empty($profile_image);

// Proses upload gambar
$uploadResult = $adminController->uploadProfileImage();
if ($uploadResult['profile_image']) {
    $profile_image = $uploadResult['profile_image'];
    $show_upload_modal = false;
}
$upload_error = $uploadResult['upload_error'];

// Ambil data meeting
$meetings = $adminController->getMeetings();
$invitedMeetings = $adminController->getInvitedMeetings();

// Proses tambah meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meeting'])) {
    $date = $_POST['meeting_date'];
    $time = $_POST['meeting_time'];
    $title = $_POST['meeting_title'];
    $platform = $_POST['meeting_platform'];
    $invited_users = isset($_POST['invited_users']) ? $_POST['invited_users'] : [];
    
    if (empty($invited_users)) {
        $meeting_error = "Harap pilih setidaknya satu pengguna untuk diundang.";
    } elseif ($adminController->addMeeting($date, $time, $title, $platform, $invited_users)) {
        header("Location: index.php?page=admin_dashboard");
        exit();
    } else {
        $meeting_error = "Gagal menambah meeting.";
    }
}

// Proses hapus meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meeting'])) {
    $meeting_id = $_POST['meeting_id'];
    if ($adminController->deleteMeeting($meeting_id)) {
        header("Location: index.php?page=admin_dashboard");
        exit();
    } else {
        $delete_error = "Gagal menghapus meeting.";
    }
}

// AJAX handler untuk pencarian pengguna
if (isset($_GET['action']) && $_GET['action'] === 'search_users' && isset($_GET['query'])) {
    ob_clean(); // Bersihkan buffer output
    $search_query = $_GET['query'];
    $search_results = $adminController->searchUsers($search_query);
    header('Content-Type: application/json');
    echo json_encode($search_results);
    exit();
}

// AJAX handler untuk detail meeting
if (isset($_GET['action']) && $_GET['action'] === 'get_meeting_details' && isset($_GET['meeting_id'])) {
    ob_clean(); // Bersihkan buffer output
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
?>


    <style>
        .dashboard-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .card-widget { background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); padding: 20px; margin-bottom: 20px; transition: transform 0.2s; }
        .card-widget:hover { transform: translateY(-2px); }
        .profile-card, .user-result { display: flex; align-items: center; gap: 15px; }
        .profile-img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #0d6efd; }
        .meeting-item { background: #f5f7fa; border-radius: 10px; padding: 10px; margin-bottom: 10px; transition: background 0.2s; }
        .meeting-item:hover { background: #e0e7f0; }
        .modal-content { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 15px 15px 0 0; }
        .form-control:focus { box-shadow: 0 0 10px rgba(13,110,253,0.3); }
        .invited-user-img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 2px solid #0d6efd; margin-right: 10px; }
        #search-results { max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <br/>
                <h2 class="text-dark fw-bolder">Selamat datang, <?php echo htmlspecialchars($userProfile['name']); ?></h2>
                <p class="text-muted">Ikhtisar dashboard pribadi Anda</p>
            </div>
            <div>
                <a href="index.php?page=logout" class="ms-2 text-muted"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>

        <div class="row">
            <!-- Profile Card -->
            <div class="col-md-3">
                <div class="card-widget border">
                    <div class="profile-card">
                        <img src="<?php echo !empty($profile_image) ? '../upload/image/' . $profile_image : '../image/robot-ai.png'; ?>" 
                             alt="Profil Admin" class="profile-img">
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($userProfile['name']); ?></h5>
                            <p class="text-muted mb-0">Manajer Admin</p>
                            <p class="text-muted small">Email: <?php echo htmlspecialchars($userProfile['email']); ?></p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-around mt-3 text-muted">
                        <span><i class="bi bi-people"></i> 11</span>
                        <span><i class="bi bi-clock"></i> 56</span>
                        <span><i class="bi bi-trophy"></i> 12</span>
                    </div>
                </div>
            </div>

            <!-- My Meetings -->
            <div class="col-md-5">
                <div class="card-widget border">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Meeting Saya</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMeetingModal">
                            <i class="bi bi-plus"></i> Tambah Meeting
                        </button>
                    </div>
                    <?php foreach ($meetings as $meeting): ?>
                        <div class="meeting-item">
                            <span class="text-muted"><?php echo date('D, d M', strtotime($meeting['date'])); ?></span>
                            <p>
                                <?php echo htmlspecialchars($meeting['title']); ?> 
                                <span class="badge bg-<?php echo $meeting['platform'] === 'zoom' ? 'primary' : 'success'; ?>">
                                    <?php echo date('h:i A', strtotime($meeting['time'])); ?>
                                </span>
                                <i class="bi bi-<?php echo $meeting['platform'] === 'zoom' ? 'zoom-in' : 'google'; ?> ms-2"></i>
                            </p>
                            <div>
                                <small class="text-muted">Peserta:</small>
                                <?php
                                $details = $adminController->getMeetingDetails($meeting['id']);
                                if (!empty($details['invited_users'])) {
                                    foreach ($details['invited_users'] as $user): ?>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($user['profile_image']) ? '../upload/image/' . $user['profile_image'] : '../image/robot-ai.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($user['name']); ?>" class="invited-user-img">
                                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                                        </div>
                                    <?php endforeach;
                                } else {
                                    echo '<p class="text-muted">Tidak ada peserta.</p>';
                                }
                                ?>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-info view-meeting" data-meeting-id="<?php echo $meeting['id']; ?>">Lihat</button>
                                <button class="btn btn-sm btn-danger delete-meeting" data-meeting-id="<?php echo $meeting['id']; ?>">Hapus</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="#" class="text-primary mt-2 d-block">Lihat semua meeting <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <!-- Invited Meetings -->
            <div class="col-md-4">
                <div class="card-widget border">
                    <h5>Undangan Meeting</h5>
                    <?php foreach ($invitedMeetings as $meeting): ?>
                        <div class="meeting-item">
                            <span class="text-muted"><?php echo date('D, d M', strtotime($meeting['date'])); ?></span>
                            <p>
                                <?php echo htmlspecialchars($meeting['title']); ?> 
                                <span class="badge bg-<?php echo $meeting['platform'] === 'zoom' ? 'primary' : 'success'; ?>">
                                    <?php echo date('h:i A', strtotime($meeting['time'])); ?>
                                </span>
                                <i class="bi bi-<?php echo $meeting['platform'] === 'zoom' ? 'zoom-in' : 'google'; ?> ms-2"></i>
                                <br>
                                <small class="text-muted">Dibuat oleh: <?php echo htmlspecialchars($meeting['creator']); ?></small>
                            </p>
                            <button class="btn btn-sm btn-info view-meeting" data-meeting-id="<?php echo $meeting['id']; ?>">Lihat</button>
                        </div>
                    <?php endforeach; ?>
                    <a href="#" class="text-primary mt-2 d-block">Lihat semua undangan meeting <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Image Modal -->
    <div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadImageModalLabel">Unggah Gambar Profil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($upload_error): ?>
                        <div class="alert alert-danger"><?php echo $upload_error; ?></div>
                    <?php endif; ?>
                    <p>Silakan unggah gambar profil Anda untuk melanjutkan.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Pilih gambar:</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Unggah</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Meeting Modal -->
    <div class="modal fade" id="addMeetingModal" tabindex="-1" aria-labelledby="addMeetingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title" id="addMeetingModalLabel">Tambah Meeting Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($meeting_error)): ?>
                        <div class="alert alert-danger"><?php echo $meeting_error; ?></div>
                    <?php endif; ?>
                    <form method="POST" id="addMeetingForm">
                        <div class="mb-3">
                            <label for="meeting_title" class="form-label">Judul</label>
                            <input type="text" class="form-control" id="meeting_title" name="meeting_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="meeting_date" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="meeting_date" name="meeting_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="meeting_time" class="form-label">Waktu</label>
                            <input type="time" class="form-control" id="meeting_time" name="meeting_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="meeting_platform" class="form-label">Platform</label>
                            <select class="form-control" id="meeting_platform" name="meeting_platform" required>
                                <option value="zoom">Zoom</option>
                                <option value="google">Google Meet</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Undang Pengguna</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="search_query" placeholder="Masukkan nama atau email" required>
                                <button class="btn btn-primary" type="button" id="search_users">Cari</button>
                            </div>
                            <div id="loading" class="text-center" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Memuat...</span>
                                </div>
                            </div>
                            <div id="search-results"></div>
                            <small class="text-muted">Centang pengguna yang ingin diundang</small>
                        </div>
                        <button type="submit" name="add_meeting" class="btn btn-primary w-100">Tambah Meeting</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Meeting Modal -->
    <div class="modal fade" id="viewMeetingModal" tabindex="-1" aria-labelledby="viewMeetingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title" id="viewMeetingModalLabel">Detail Meeting</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="meetingDetails"></div>
            </div>
        </div>
    </div>

    <!-- Delete Meeting Modal -->
    <div class="modal fade" id="deleteMeetingModal" tabindex="-1" aria-labelledby="deleteMeetingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteMeetingModalLabel">Hapus Meeting</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus meeting ini?</p>
                    <form method="POST" id="deleteMeetingForm">
                        <input type="hidden" name="meeting_id" id="deleteMeetingId">
                        <button type="submit" name="delete_meeting" class="btn btn-danger">Ya, Hapus</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    <?php if ($show_upload_modal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var uploadModal = new bootstrap.Modal(document.getElementById('uploadImageModal'), {
                backdrop: 'static',
                keyboard: false
            });
            uploadModal.show();
        });
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        var viewModal = new bootstrap.Modal(document.getElementById('viewMeetingModal'));
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteMeetingModal'));

        // AJAX untuk pencarian pengguna
        $('#search_users').on('click', function() {
            var query = $('#search_query').val();
            if (query.length < 2) {
                alert('Masukkan setidaknya 2 karakter untuk pencarian.');
                return;
            }

            $('#loading').show();
            $('#search-results').empty();

            $.ajax({
                url: 'index.php?page=admin_dashboard&action=search_users',
                type: 'GET',
                data: { query: query },
                dataType: 'json',
                success: function(data) {
                    $('#loading').hide();
                    if (data.length > 0) {
                        var html = '<div id="search-results">';
                        data.forEach(function(user) {
                            html += `
                                <div class="user-result">
                                    <input type="checkbox" name="invited_users[]" value="${user.id}" class="me-2">
                                    <img src="${user.profile_image ? '../upload/image/' + user.profile_image : '../image/robot-ai.png'}" 
                                         alt="${user.name}" class="invited-user-img">
                                    <span>${user.name} (${user.email})</span>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $('#search-results').html(html);
                    } else {
                        $('#search-results').html('<p class="text-muted">Tidak ada pengguna ditemukan.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading').hide();
                    $('#search-results').html('<p class="text-danger">Terjadi kesalahan saat pencarian: ' + error + '</p>');
                    console.log('Search AJAX Error: ' + xhr.responseText);
                }
            });
        });

        // Tampilkan detail meeting di modal
        $('.view-meeting').on('click', function() {
            var meetingId = $(this).data('meeting-id');
            $.ajax({
                url: 'index.php?page=admin_dashboard&action=get_meeting_details',
                type: 'GET',
                data: { meeting_id: meetingId },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $('#meetingDetails').html('<p class="text-danger">' + data.error + '</p>');
                    } else {
                        var html = `
                            <p><strong>Judul:</strong> ${data.title}</p>
                            <p><strong>Tanggal:</strong> ${data.date}</p>
                            <p><strong>Waktu:</strong> ${data.time}</p>
                            <p><strong>Platform:</strong> ${data.platform}</p>
                            <p><strong>Dibuat oleh:</strong> ${data.creator}</p>
                            <p><strong>Peserta:</strong></p><ul>
                        `;
                        if (data.invited_users.length > 0) {
                            data.invited_users.forEach(function(user) {
                                html += `
                                    <li>
                                        <img src="${user.profile_image ? '../upload/image/' + user.profile_image : '../image/robot-ai.png'}" 
                                             class="invited-user-img">
                                        ${user.name} (${user.email})
                                    </li>
                                `;
                            });
                        } else {
                            html += '<li>Tidak ada peserta.</li>';
                        }
                        html += '</ul>';
                        $('#meetingDetails').html(html);
                        viewModal.show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#meetingDetails').html('<p class="text-danger">Gagal memuat detail meeting: ' + error + '</p>');
                    console.log('Details AJAX Error: ' + xhr.responseText);
                    viewModal.show();
                }
            });
        });

        // Hapus meeting
        $('.delete-meeting').on('click', function() {
            var meetingId = $(this).data('meeting-id');
            $('#deleteMeetingId').val(meetingId);
            deleteModal.show();
        });

        // Validasi form sebelum submit
        $('#addMeetingForm').on('submit', function(e) {
            const invitedUsers = $('input[name="invited_users[]"]:checked').length;
            if (invitedUsers === 0) {
                e.preventDefault();
                alert('Harap pilih setidaknya satu pengguna untuk diundang.');
            }
        });
    });
    </script>
</body>
</html>