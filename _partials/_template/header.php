<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malmanech - Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <link rel="stylesheet" href="./_partials/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
<body> <nav class="navbar navbar-expand-lg bg-primary border-bottom border-5 border-warning">
        <div class="container-fluid">
            <a class="navbar-brand text-white fw-bold" href="index.php?page=home">
                <h1 class="mb-0"><i class="bi bi-fingerprint text-warning"></i><b> Malmanech</b></h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll"
                aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarScroll">
                <ul class="navbar-nav me-3 my-2 my-lg-0 navbar-nav-scroll">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php?page=home" style="font-size: 20px;">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#" style="font-size: 20px;">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="#" style="font-size: 20px;">AI Products offer</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false" style="font-size: 20px;">
                            Documentation
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">API Integrations</a></li>
                            <li><a class="dropdown-item" href="#">Embedded AI Chatbots</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Cloud Datasets</a></li>
                        </ul>
                    </li>
                </ul>

                <?php
                // Include dan inisialisasi controller dengan include_once
                include_once './config/db.php';
                include_once './controllers/AuthController.php';
                $auth = new AuthController($conn);
                $currentUser = $auth->getCurrentUser();

                if ($currentUser) {
                    // Jika pengguna sudah login
                    $profileImage = !empty($currentUser['profile_image']) 
                        ? './upload/image/' . $currentUser['profile_image'] 
                        : './image/robot-ai.png'; // Gambar default jika tidak ada foto profil
                ?>
                    <div class="dropdown">
                        <a class=" btn btn-warning fw-bolder" href="#" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 20px;">
                            <img src="<?php echo $profileImage; ?>" alt="Profile" class="profile-img me-2" style="height:35px;width:35px;">
                            <span><?php echo htmlspecialchars($currentUser['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=profile">Profile</a></li>
                            <li><a class="dropdown-item" href="index.php?page=change_password">Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=logout">Logout</a></li>
                        </ul>
                    </div>
                <?php } else { ?>
                    <!-- Jika belum login -->
                    <a href="index.php?page=login" class="btn btn-warning fw-bolder" style="border-radius: 20px;">
                        Login Now Buddy <i class="bi bi-arrow-right"></i>
                    </a>
                <?php } ?>
            </div>
        </div>
    </nav>
   