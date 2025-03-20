<!-- file_/signup.php -->
<?php
include './config/db.php';

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

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO tb_users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $password_hashed, $role_id);
        
        if ($stmt->execute()) {
            $show_success_modal = true; // Set flag untuk menampilkan modal
        } else {
            $errors[] = "Registration failed! Please try again.";
        }
        $stmt->close();
    }
}
?>
<?= include '_partials/_template/header.php';?>
<div style="min-height: 100vh; display: flex; align-items: stretch; background: #fff;">
    <!-- Left Side -->
    <div style="flex: 1; background: linear-gradient(135deg, #0d6efd, #0a58ca); display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="text-align: center; color: white;">
            <h1 style="font-size: 48px; margin-bottom: 20px; letter-spacing: 3px;">
                <i class="bi bi-star-fill" style="color: #ffc107;"></i> <i class="bi bi-star-fill" style="color: #ffc107;"></i> <i class="bi bi-star-fill" style="color: #ffc107;"></i> 
                <br/><a class="fw-bolder text-white">Starvee</a>
            </h1>
            <p style="font-size: 18px; opacity: 0.9;">Join us and explore a world of possibilities!</p>
        </div>
    </div>

    <!-- Right Side -->
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 50px; background: rgba(255, 255, 255, 0.95);">
        <div style="width: 100%; max-width: 480px;">
            <h2 style="color: #000; font-size: 25px; margin-bottom: 40px;">
                Sign Up for Free<br/>
                <small class="text-muted">Please fill the correct answer</small>
            </h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="background: #dc3545; border-radius: 10px; padding: 15px; color: white; margin-bottom: 25px; text-align: center; font-family: 'Segoe UI', sans-serif; font-size: 14px;">
                    <?php foreach ($errors as $error): ?>
                        <p style="margin: 0;"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px;">Full Name</label>
                    <input type="text" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required class="form-control" 
                        style="padding: 14px; border-radius: 10px; font-size: 16px; background: #f5f6f5; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); transition: all 0.3s;" 
                        placeholder="Enter your full name"
                        onfocus="this.style.background='#fff'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.2)'"
                        onblur="this.style.background='#f5f6f5'; this.style.boxShadow='inset 0 2px 5px rgba(0,0,0,0.1)'">
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px;">Email</label>
                    <input type="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required class="form-control" 
                        style="padding: 14px; border-radius: 10px; font-size: 16px; background: #f5f6f5; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); transition: all 0.3s;" 
                        placeholder="Enter your email"
                        onfocus="this.style.background='#fff'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.2)'"
                        onblur="this.style.background='#f5f6f5'; this.style.boxShadow='inset 0 2px 5px rgba(0,0,0,0.1)'">
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px;">Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="signupPassword" required class="form-control" 
                            style="padding: 14px; border-radius: 10px; font-size: 16px; background: #f5f6f5; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); transition: all 0.3s;" 
                            placeholder="Create a password"
                            onfocus="this.style.background='#fff'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.2)'"
                            onblur="this.style.background='#f5f6f5'; this.style.boxShadow='inset 0 2px 5px rgba(0,0,0,0.1)'">
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #000;" onclick="togglePassword('signupPassword', this)">
                            <i class="bi bi-eye-slash" id="toggleIconSignup"></i>
                        </span>
                    </div>
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px;">Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="confirmPassword" required class="form-control" 
                            style="padding: 14px; border-radius: 10px; font-size: 16px; background: #f5f6f5; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); transition: all 0.3s;" 
                            placeholder="Confirm your password"
                            onfocus="this.style.background='#fff'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.2)'"
                            onblur="this.style.background='#f5f6f5'; this.style.boxShadow='inset 0 2px 5px rgba(0,0,0,0.1)'">
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #000;" onclick="togglePassword('confirmPassword', this)">
                            <i class="bi bi-eye-slash" id="toggleIconConfirm"></i>
                        </span>
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <div style="display: flex; align-items: center;">
                        <input type="checkbox" name="terms" id="terms" style="margin-right: 10px; accent-color: #0d6efd;">
                        <label for="terms" style="color: #000; font-family: 'Segoe UI', sans-serif; font-size: 14px;">
                            I agree to the <a href="#" style="color: #0d6efd; text-decoration: none; font-weight: 600;" onmouseover="this.style.color='#ffc107'" onmouseout="this.style.color='#0d6efd'">Terms and Conditions</a>
                        </label>
                    </div>
                </div>

                <button type="submit" class="bg-primary" 
                    style="width: 100%; padding: 15px; color: white; border: none; border-radius: 10px; font-size: 16px; font-family: 'Segoe UI', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 15px rgba(13,110,253,0.3);"
                    onmouseover="this.style.background='#0a58ca'; this.style.boxShadow='0 8px 20px rgba(13,110,253,0.5)'"
                    onmouseout="this.style.background='#0d6efd'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.3)'">
                    Sign Up <i class="bi bi-arrow-right" style="margin-left: 8px;"></i>
                </button>
            </form>

            <p style="text-align: center; margin-top: 25px; color: #555; font-family: 'Segoe UI', sans-serif; font-size: 14px;">
                Already have an account? 
                <a href="index.php?page=login" style="color: #0d6efd; font-weight: 600; text-decoration: none; transition: color 0.3s;"
                   onmouseover="this.style.color='#ffc107'"
                   onmouseout="this.style.color='#0d6efd'">Login</a>
            </p>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Registration Successful!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-check-circle-fill" style="font-size: 50px; color: #28a745;"></i>
                <p class="mt-3">Your account has been successfully created! You will be redirected to the login page shortly.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.location.href='index.php?page=login'">Go to Login</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    const iconElement = icon.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        iconElement.classList.remove('bi-eye-slash');
        iconElement.classList.add('bi-eye');
    } else {
        input.type = 'password';
        iconElement.classList.remove('bi-eye');
        iconElement.classList.add('bi-eye-slash');
    }
}

// Show success modal if registration is successful
<?php if ($show_success_modal): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var successModal = new bootstrap.Modal(document.getElementById('successModal'), {
            backdrop: 'static',
            keyboard: false
        });
        successModal.show();

        // Redirect to login page after 3 seconds
        setTimeout(function() {
            window.location.href = 'index.php?page=login';
        }, 3000);
    });
<?php endif; ?>
</script>
</body>
</html>