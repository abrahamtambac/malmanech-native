<!-- file_/login.php -->
<?php
include_once './controllers/AuthController.php'; // Naik satu level dari file_/ ke controllers/

$auth = new AuthController($conn); // Gunakan $conn yang diteruskan dari index.php
$error = null;

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $error = $auth->login($email, $password);
}
?>
<?= include '_partials/_template/header.php';?>
<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); padding: 50px; width: 100%; max-width: 480px; position: relative; z-index: 1; backdrop-filter: blur(10px);">
       
        <?php if ($error): ?>
            <div style="background: #dc3545; border-radius: 10px; padding: 15px; color: white; margin-bottom: 25px; text-align: center; font-family: 'Segoe UI', sans-serif; font-size: 14px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div style="margin-bottom: 30px;">
                <label style="display: block; color: #000; font-weight: bold; margin-bottom: 10px;">Email</label>
                <div style="position: relative;">
                    <input type="email" name="email" required class="form-control" 
                        style="padding: 14px; border-radius: 10px; font-size: 16px; background: #f5f6f5; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); transition: all 0.3s;" 
                        placeholder="Enter your email"
                        onfocus="this.style.background='#fff'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.2)'"
                        onblur="this.style.background='#f5f6f5'; this.style.boxShadow='inset 0 2px 5px rgba(0,0,0,0.1)'">
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; color: #000; font-weight: bold; margin-bottom: 10px;">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" required class="form-control" 
                        style="padding: 14px; border-radius: 10px; font-size: 16px; background: #f5f6f5; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); transition: all 0.3s;" 
                        placeholder="Enter your password"
                        onfocus="this.style.background='#fff'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.2)'"
                        onblur="this.style.background='#f5f6f5'; this.style.boxShadow='inset 0 2px 5px rgba(0,0,0,0.1)'">
                    <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #0d6efd;" onclick="togglePassword('password', this)">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <div>
                    <input type="checkbox" id="rememberMe" style="margin-right: 8px; accent-color: #0d6efd;">
                    <label for="rememberMe" style="color: #555; font-size: 14px;">Remember me</label>
                </div>
                <a href="#" style="color: #0d6efd; text-decoration: none; font-weight: 600; transition: color 0.3s;" 
                   onmouseover="this.style.color='#ffc107'" 
                   onmouseout="this.style.color='#0d6efd'">Forgot Password?</a>
            </div>

            <button type="submit" class="bg-primary" 
                style="width: 100%; padding: 15px; color: white; border: none; border-radius: 10px; font-size: 16px; font-family: 'Segoe UI', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 15px rgba(13,110,253,0.3);"
                onmouseover="this.style.background='#0a58ca'; this.style.boxShadow='0 8px 20px rgba(13,110,253,0.5)'"
                onmouseout="this.style.background='#0d6efd'; this.style.boxShadow='0 5px 15px rgba(13,110,253,0.3)'">
                Login Now <i class="bi bi-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </form>

        <p style="text-align: center; margin-top: 25px; color: #555; font-size: 14px;">
            Don't have an account? 
            <a href="index.php?page=signup" style="color: #0d6efd; font-weight: 600; text-decoration: none; transition: color 0.3s;"
               onmouseover="this.style.color='#ffc107'"
               onmouseout="this.style.color='#0d6efd'">Sign Up</a>
        </p>
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
</script>
</body>
</html>