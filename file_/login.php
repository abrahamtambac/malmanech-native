<!-- file_/login.php -->
<?php
// session_start() dihapus karena sudah aktif dari index.php
include './config/db.php'; // Path ke db.php dari file_/login.php

// Proses login jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query untuk mencari pengguna berdasarkan email
    $stmt = $conn->prepare("SELECT id, name, email, password, role_id FROM tb_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Simpan data pengguna ke session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            
            // Redirect berdasarkan role (opsional)
            if ($user['role_id'] == 1) { // Admin
                header("Location: index.php?page=admin_dashboard");
            }else if ($user['role_id' == 2]) {
              header ('Location: index.php?page=admin_operator');
            }
             else { // User biasa
                header("Location: index.php?page=home");
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Email not found!";
    }
    $stmt->close();
}
?>



<div class="login-container">
  <div class="login-card">
    <h2 class=""><i class="bi bi-fingerprint text-warning"></i>Starvee Login</h2>
    
    <?php if (isset($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <div class="mb-4">
        <label for="email" class="form-label fw-bold">Email</label>
        <div class="input-group">
          <span class="input-group-text bg-primary text-white"><i class="bi bi-envelope"></i></span>
          <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
        </div>
      </div>
      <div class="mb-4">
        <label for="password" class="form-label fw-bold">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-primary text-white"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="rememberMe">
          <label class="form-check-label" for="rememberMe">Remember me</label>
        </div>
        <a href="#" class="forgot-password">Forgot Password?</a>
      </div>
      <button type="submit" class="btn btn-login w-100">Login Now <i class="bi bi-arrow-right"></i></button>
    </form>
    <p class="text-center mt-3">Don't have an account? <a href="#" class="text-primary fw-bold">Sign Up</a></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>