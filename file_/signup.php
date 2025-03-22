<?php
include './config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php'; // Sesuaikan path jika perlu (karena signup.php ada di folder file_/)

// Fungsi untuk mengirim email verifikasi
function sendVerificationEmail($to, $name, $token) {
    // Tulis perintah untuk menjalankan pengiriman email di latar belakang
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
            // Kirim email verifikasi di latar belakang
            sendVerificationEmail($email, $name, $verification_token);
            $show_success_modal = true;
        } else {
            $errors[] = "Registration failed! Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starvee - Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        .input-focus:focus {
            background-color: #fff;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
        }
    </style>
</head>
<body>
    <div class="flex w-full">
        <!-- Left Side -->
        <!-- <div class="hidden md:flex flex-1 bg-gradient-to-br from-blue-600 to-blue-800 items-center justify-center p-6">
            <div class="max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <svg class="w-7 h-7 text-gray-500 dark:text-gray-400 mb-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M18 5h-.7c.229-.467.349-.98.351-1.5a3.5 3.5 0 0 0-3.5-3.5c-1.717 0-3.215 1.2-4.331 2.481C8.4.842 6.949 0 5.5 0A3.5 3.5 0 0 0 2 3.5c.003.52.123 1.033.351 1.5H2a2 2 0 0 0-2 2v3a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V7a2 2 0 0 0-2-2ZM8.058 5H5.5a1.5 1.5 0 0 1 0-3c.9 0 2 .754 3.092 2.122-.219.337-.392.635-.534.878Zm6.1 0h-3.742c.933-1.368 2.371-3 3.739-3a1.5 1.5 0 0 1 0 3h.003ZM11 13H9v7h2v-7Zm-4 0H2v5a2 2 0 0 0 2 2h3v-7Zm6 0v7h3a2 2 0 0 0 2-2v-5h-5Z"/>
                </svg>
                <a href="#">
                    <h5 class="mb-2 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Need a help in Claim?</h5>
                </a>
                <p class="mb-3 font-normal text-gray-500 dark:text-gray-400">Go to this step by step guideline process on how to certify for your weekly benefits:</p>
                <a href="#" class="inline-flex font-medium items-center text-blue-600 hover:underline">
                    See our guideline
                    <svg class="w-3 h-3 ms-2.5 rtl:rotate-[270deg]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11v4.833A1.166 1.166 0 0 1 13.833 17H2.167A1.167 1.167 0 0 1 1 15.833V4.167A1.166 1.166 0 0 1 2.167 3h4.618m4.447-2H17v5.768M9.111 8.889l7.778-7.778"/>
                    </svg>
                </a>
            </div>
        </div> -->

        <!-- Right Side (Form) -->
        <div class="flex-1 flex items-center justify-center p-6 md:p-12 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-md w-full bg-white border border-gray-200 rounded-lg shadow-sm p-8 dark:bg-gray-800 dark:border-gray-700 card-hover">
                <h2 class="text-2xl font-bold text-gray-900 mb-2 dark:text-white">Sign Up for Free</h2>
                <p class="text-sm text-gray-600 mb-6 dark:text-gray-400">Please fill in the correct details</p>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500 text-white rounded-lg p-4 mb-6 text-center text-sm">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <!-- Full Name -->
                    <div class="mb-6">
                        <label class="block text-gray-900 font-bold mb-2 dark:text-white">Full Name</label>
                        <input type="text" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required 
                               class="w-full p-3 rounded-lg bg-gray-100 border border-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300 input-focus dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                               placeholder="Enter your full name">
                    </div>

                    <!-- Email -->
                    <div class="mb-6">
                        <label class="block text-gray-900 font-bold mb-2 dark:text-white">Email</label>
                        <input type="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required 
                               class="w-full p-3 rounded-lg bg-gray-100 border border-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300 input-focus dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                               placeholder="Enter your email">
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label class="block text-gray-900 font-bold mb-2 dark:text-white">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="signupPassword" required 
                                   class="w-full p-3 rounded-lg bg-gray-100 border border-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300 input-focus dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                                   placeholder="Create a password">
                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-blue-700 dark:text-blue-500" onclick="togglePassword('signupPassword', this)">
                                <i class="bi bi-eye-slash" id="toggleIconSignup"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-6">
                        <label class="block text-gray-900 font-bold mb-2 dark:text-white">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirmPassword" required 
                                   class="w-full p-3 rounded-lg bg-gray-100 border border-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300 input-focus dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                                   placeholder="Confirm your password">
                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-blue-700 dark:text-blue-500" onclick="togglePassword('confirmPassword', this)">
                                <i class="bi bi-eye-slash" id="toggleIconConfirm"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="mb-8">
                        <div class="flex items-center">
                            <input type="checkbox" name="terms" id="terms" class="mr-2 accent-blue-700 dark:accent-blue-500">
                            <label for="terms" class="text-gray-600 text-sm dark:text-gray-400">
                                I agree to the <a href="#" class="text-blue-700 hover:text-yellow-500 dark:text-blue-500 dark:hover:text-yellow-400">Terms and Conditions</a>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full bg-blue-700 text-white py-3 rounded-lg font-semibold hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-all">
                        Sign Up
                        <i class="bi bi-arrow-right ml-2"></i>
                    </button>
                </form>

                <!-- Login Link -->
                <p class="text-center mt-6 text-gray-600 text-sm dark:text-gray-400">
                    Already have an account? 
                    <a href="index.php?page=login" class="text-blue-700 font-semibold hover:text-yellow-500 dark:text-blue-500 dark:hover:text-yellow-400">Login</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Flowbite Success Modal -->
    <div id="success-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Registration Successful!
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="success-modal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5 text-center">
                    <svg class="mx-auto mb-4 text-green-500 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7 10 2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <p class="mb-5 text-gray-500 dark:text-gray-300">Your account has been created! A verification email has been sent to your inbox.</p>
                </div>
                <div class="flex justify-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
                    <button data-modal-hide="success-modal" onclick="window.location.href='index.php?page=login'" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Go to Login
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
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

        <?php if ($show_success_modal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('success-modal');
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');

                const flowbiteModal = new Modal(modal, {
                    backdrop: 'static',
                    keyboard: false
                });
                flowbiteModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>