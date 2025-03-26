<?php
// Pastikan tidak ada spasi atau baris sebelum tag ini
//session_start(); // Tambahkan ini di awal jika belum ada
include_once './controllers/AuthController.php';
include_once './config/db.php'; // Pastikan koneksi DB ada

$auth = new AuthController($conn);
$error = null;

// Proses login sebelum output HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $error = $auth->login($email, $password); // Ini mungkin melakukan redirect di dalamnya
    // Jika login berhasil, redirect akan terjadi di AuthController
    // Jika gagal, $error akan diisi
}
?>


<body>
    <?php include '_partials/_template/header.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm p-4 mt-5 card-hover">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email</label>
                            <input type="email" name="email" id="email" required 
                                   class="form-control input-focus" 
                                   placeholder="Enter your email">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" required 
                                       class="form-control input-focus" 
                                       placeholder="Enter your password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)">
                                    <i class="bi bi-eye-slash" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe">
                                <label class="form-check-label text-muted" for="rememberMe">Remember me</label>
                            </div>
                            <a href="#" class="text-primary text-decoration-none fw-medium">Forgot Password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            Login Now
                            <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>
                    <p class="text-center mt-3 text-muted">
                        Don't have an account? 
                        <a href="index.php?page=signup" class="text-primary fw-semibold text-decoration-none">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

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
    </script>
</body>
</html>