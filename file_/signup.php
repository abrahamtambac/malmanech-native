<?php
include './config/db.php';
include '_partials/_template/header.php'; // Panggil header

$show_success_modal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? true : false;
    $role_id = 1;

    $errors = [];

    // Validasi Nama
    if (empty($name)) {
        $errors[] = "Nama lengkap wajib diisi!";
    } elseif (strlen($name) < 3) {
        $errors[] = "Nama lengkap harus minimal 3 karakter!";
    }

    // Validasi Email
    if (empty($email)) {
        $errors[] = "Email wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM tb_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email sudah terdaftar!";
        }
        $stmt->close();
    }

    // Validasi Password
    if (empty($password)) {
        $errors[] = "Kata sandi wajib diisi!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Kata sandi harus minimal 6 karakter!";
    }

    // Validasi Konfirmasi Password
    if (empty($confirm_password)) {
        $errors[] = "Konfirmasi kata sandi wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Kata sandi tidak cocok!";
    }

    // Validasi Persetujuan Syarat
    if (!$terms) {
        $errors[] = "Anda harus menyetujui syarat dan ketentuan!";
    }

    // Jika tidak ada error, simpan ke database dan kirim email verifikasi
    if (empty($errors)) {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(16));
        $is_verified = 0; // Akun belum diverifikasi

        $stmt = $conn->prepare("INSERT INTO tb_users (name, email, password, role_id, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisi", $name, $email, $password_hashed, $role_id, $verification_token, $is_verified);
        
        if ($stmt->execute()) {
            // Panggil send_email.php untuk mengirim email verifikasi
            $command = "php file_/send_email.php " . escapeshellarg($email) . " " . escapeshellarg($name) . " " . escapeshellarg($verification_token) . " 2>&1";
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                $show_success_modal = true;
            } else {
                $errors[] = "Gagal mengirim email verifikasi. Silakan coba lagi.";
                error_log("Email sending failed: " . implode("\n", $output));
            }
        } else {
            $errors[] = "Pendaftaran gagal! Silakan coba lagi.";
        }
        $stmt->close();
    }
}
?>

</head>
<body>
    <!-- Main Content -->
    <div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">
        <div class="row justify-content-center w-100">
            <div class="col-md-6">
                <h2>Alur Pendaftaran</h2>
                <ol>
                    <li><strong>Isi Formulir Pendaftaran:</strong> Lengkapi nama, email, kata sandi, dan konfirmasi kata sandi.</li>
                    <li><strong>Setujui Syarat & Ketentuan:</strong> Centang kotak untuk menyetujui syarat dan ketentuan.</li>
                    <li><strong>Kirim Formulir:</strong> Klik tombol "Daftar" untuk mengirimkan data.</li>
                    <li><strong>Verifikasi Email:</strong> Periksa kotak masuk email Anda dan klik tautan verifikasi.</li>
                    <li><strong>Masuk ke Akun:</strong> Setelah verifikasi, masuk dengan email dan kata sandi Anda.</li>
                </ol>
            </div>
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm p-4 card-hover">
                    <h2 class="text-center fw-bold mb-2">Daftar Gratis</h2>
                    <p class="text-center text-muted mb-4">Isi data dengan benar</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="name" id="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required 
                                   class="form-control input-focus" 
                                   placeholder="Masukkan nama lengkap">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required 
                                   class="form-control input-focus" 
                                   placeholder="Masukkan email">
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="signupPassword" class="form-label fw-bold">Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" name="password" id="signupPassword" required 
                                       class="form-control input-focus" 
                                       placeholder="Buat kata sandi">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('signupPassword', this)">
                                    <i class="bi bi-eye-slash" id="toggleIconSignup"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label fw-bold">Konfirmasi Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirmPassword" required 
                                       class="form-control input-focus" 
                                       placeholder="Konfirmasi kata sandi">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword', this)">
                                    <i class="bi bi-eye-slash" id="toggleIconConfirm"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" name="terms" id="terms" class="form-check-input">
                            <label for="terms" class="form-check-label text-muted">
                                Saya setuju dengan <a href="#" class="text-primary text-decoration-none">Syarat dan Ketentuan</a>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="btn btn-primary w-100 py-2 fw-semibold">
                            Daftar
                            <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>

                    <!-- Login Link -->
                    <p class="text-center mt-3 text-muted">
                        Sudah punya akun? 
                        <a href="index.php?page=login" class="text-primary fw-semibold text-decoration-none">Masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal (Bootstrap) -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="successModalLabel">Periksa Email Anda!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-envelope-check text-success" style="font-size: 3rem;"></i>
                    <p class="mt-3">Kami telah mengirim email verifikasi ke kotak masuk Anda. Silakan klik tautan untuk mengaktifkan akun Anda.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        }

        <?php if ($show_success_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('successModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>