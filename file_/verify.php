<?php
include './config/db.php';
include '_partials/_template/header.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id FROM tb_users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE tb_users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $stmt->bind_param("s", $token);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Gagal memverifikasi email. Silakan coba lagi.";
        }
    } else {
        $error = "Token tidak valid atau akun sudah diverifikasi.";
    }
    $stmt->close();
} else {
    $error = "Tidak ada token verifikasi yang diberikan.";
}
?>

</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm p-4 card-hover">
                    <h2 class="text-center fw-bold mb-4 text-primary">Verifikasi Email</h2>
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            <p class="mt-3">Email Anda telah diverifikasi! Anda sekarang dapat masuk ke akun Mal + Anda.</p>
                            <a href="index.php?page=login" class="btn btn-primary mt-2">Ke Halaman Masuk</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-exclamation-circle" style="font-size: 2rem;"></i>
                            <p class="mt-3"><?php echo $error; ?></p>
                            <a href="index.php?page=signup" class="btn btn-primary mt-2">Coba Lagi</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
</body>
</html>