<!-- file_/admin_dashboard.php -->

<!-- file_/admin_dashboard.php -->
<!-- file_/admin_dashboard.php -->
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php?page=login");
    exit();
}

include './config/db.php';

// Membuat folder upload/image jika belum ada
$uploadDir = '../upload/image/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Cek apakah pengguna sudah memiliki gambar profil
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT profile_image FROM tb_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$profile_image = $user['profile_image'];
$stmt->close();

$show_upload_modal = empty($profile_image); // Tampilkan modal jika belum ada gambar

// Proses upload gambar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $fileName = uniqid() . '-' . basename($file['name']);
    $targetFile = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    // Validasi file
    if (!in_array($fileType, $allowedTypes)) {
        $upload_error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
    } elseif ($file['size'] > 5000000) { // Maksimal 5MB
        $upload_error = "File is too large. Maximum size is 5MB.";
    } else {
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Simpan nama file ke database
            $stmt = $conn->prepare("UPDATE tb_users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $fileName, $user_id);
            if ($stmt->execute()) {
                $profile_image = $fileName; // Update variabel untuk menghindari modal muncul lagi
                $show_upload_modal = false;
            } else {
                $upload_error = "Failed to save image to database.";
            }
            $stmt->close();
        } else {
            $upload_error = "Failed to upload image.";
        }
    }
}
?>

    <style>
        
        .dashboard-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .card-widget {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .card-widget:hover {
            transform: translateY(-2px);
        }
        .profile-card {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0d6efd;
        }
        .task-card {
            background: linear-gradient(135deg, #ff8a80, #ffd180);
            color: white;
        }
        .task-card.secondary {
            background: linear-gradient(135deg, #80d8ff, #a7ffeb);
        }
        .stats-card {
            background: linear-gradient(135deg, #e57373, #f06292);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 15px;
        }
        .meeting-item {
            background: #f5f7fa;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        .meeting-item:hover {
            background: #e0e7f0;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .interest-bar {
            background: #e0e7f0;
            border-radius: 5px;
            height: 10px;
            overflow: hidden;
        }
        .interest-fill {
            background: #0d6efd;
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
<div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <br/>
                <h2 class="text-dark fw-bolder">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                <p class="text-muted">Your personal dashboard overview</p>
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
                        <img src="<?php echo !empty($profile_image) ? '../upload/image/' . $profile_image : './image/robot-ai.png'; ?>" alt="Admin Profile" class="profile-img">
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                            <p class="text-muted mb-0">Admin Manager</p>
                            <p class="text-muted small">Email: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
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
            <div class="col-md-4">
                <div class="card-widget border">
                    <h5>My meetings</h5>
                    <div class="meeting-item">
                        <span class="text-muted">Tue, 11 Jul</span>
                        <p>Quick Daily Meeting <span class="badge bg-primary">08:15 am</span> <i class="bi bi-zoom-in ms-2"></i></p>
                    </div>
                    <div class="meeting-item">
                        <span class="text-muted">Tue, 11 Jul</span>
                        <p>John Onboarding <span class="badge bg-success">09:30 pm</span> <i class="bi bi-google ms-2"></i></p>
                    </div>
                    <div class="meeting-item">
                        <span class="text-muted">Tue, 12 Jul</span>
                        <p>Call With a New Team <span class="badge bg-success">02:30 pm</span> <i class="bi bi-google ms-2"></i></p>
                    </div>
                    <div class="meeting-item">
                        <span class="text-muted">Tue, 15 Jul</span>
                        <p>Lead Designers Event <span class="badge bg-primary">04:00 pm</span> <i class="bi bi-zoom-in ms-2"></i></p>
                    </div>
                    <a href="#" class="text-primary mt-2 d-block">See all meetings <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Image Modal -->
    <div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadImageModalLabel">Upload Profile Image</h5>
                </div>
                <div class="modal-body">
                    <?php if (isset($upload_error)): ?>
                        <div class="alert alert-danger"><?php echo $upload_error; ?></div>
                    <?php endif; ?>
                    <p>Please upload your profile image to continue.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Choose an image:</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // Show upload modal if no profile image
        <?php if ($show_upload_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var uploadModal = new bootstrap.Modal(document.getElementById('uploadImageModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                uploadModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>