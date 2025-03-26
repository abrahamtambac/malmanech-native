<?php
include './config/db.php';
include '_partials/_template/header.php'; // Panggil header

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php'; // Sesuaikan path jika perlu

// Fungsi untuk mengirim email verifikasi
function sendVerificationEmail($to, $name, $token) {
    $command = "php -f " . __DIR__ . "/send_email.php " . escapeshellarg($to) . " " . escapeshellarg($name) . " " . escapeshellarg($token) . " > /dev/null 2>&1 &";
    exec($command);
    return true; // Asumsikan sukses karena dijalankan di latar belakang
}

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
        $errors[] = "Full name is required!";
    } elseif (strlen($name) < 3) {
        $errors[] = "Full name must be at least 3 characters long!";
    }

    // Validasi Email
    if (empty($email)) {
        $errors[] = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM tb_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered!";
        }
        $stmt->close();
    }

    // Validasi Password
    if (empty($password)) {
        $errors[] = "Password is required!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long!";
    }

    // Validasi Konfirmasi Password
    if (empty($confirm_password)) {
        $errors[] = "Password confirmation is required!";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }

    // Validasi Persetujuan Syarat
    if (!$terms) {
        $errors[] = "You must agree to the terms and conditions!";
    }

    // Jika tidak ada error, simpan ke database dan kirim email verifikasi
    if (empty($errors)) {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(16));
        $is_verified = 0;

        $stmt = $conn->prepare("INSERT INTO tb_users (name, email, password, role_id, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisi", $name, $email, $password_hashed, $role_id, $verification_token, $is_verified);
        
        if ($stmt->execute()) {
            sendVerificationEmail($email, $name, $verification_token);
            $show_success_modal = true;
        } else {
            $errors[] = "Registration failed! Please try again.";
        }
        $stmt->close();
    }
}
?>


    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        .input-focus:focus {
            background-color: #fff;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
            border-color: #0d6efd;
        }
        .form-control {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm p-4 card-hover">
                    <h2 class="text-center fw-bold mb-2">Sign Up for Free</h2>
                    <p class="text-center text-muted mb-4">Please fill in the correct details</p>

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
                            <label for="name" class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" id="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required 
                                   class="form-control input-focus" 
                                   placeholder="Enter your full name">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required 
                                   class="form-control input-focus" 
                                   placeholder="Enter your email">
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="signupPassword" class="form-label fw-bold">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="signupPassword" required 
                                       class="form-control input-focus" 
                                       placeholder="Create a password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('signupPassword', this)">
                                    <i class="bi bi-eye-slash" id="toggleIconSignup"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label fw-bold">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirmPassword" required 
                                       class="form-control input-focus" 
                                       placeholder="Confirm your password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword', this)">
                                    <i class="bi bi-eye-slash" id="toggleIconConfirm"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" name="terms" id="terms" class="form-check-input">
                            <label for="terms" class="form-check-label text-muted">
                                I agree to the <a href="#" class="text-primary text-decoration-none">Terms and Conditions</a>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="btn btn-primary w-100 py-2 fw-semibold">
                            Sign Up
                            <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>

                    <!-- Login Link -->
                    <p class="text-center mt-3 text-muted">
                        Already have an account? 
                        <a href="index.php?page=login" class="text-primary fw-semibold text-decoration-none">Login</a>
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
                    <h5 class="modal-title fw-bold" id="successModalLabel">Registration Successful!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <p class="mt-3">Your account has been created! A verification email has been sent to your inbox.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="window.location.href='index.php?page=login'">
                        Go to Login
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