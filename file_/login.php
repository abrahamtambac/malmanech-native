<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malmanech - Login</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Flowbite CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    <!-- Google Fonts (Roboto sebagai pengganti Product Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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

    <!-- Login Card -->
    <div class="max-w-md w-full bg-white border border-gray-200 rounded-lg shadow-sm p-8 dark:bg-gray-800 dark:border-gray-700 card-hover">
        <?php if ($error): ?>
            <div class="bg-red-500 text-white rounded-lg p-4 mb-6 text-center text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- Email Field -->
            <div class="mb-6">
                <label class="block text-gray-900 font-bold mb-2 dark:text-white">Email</label>
                <input type="email" name="email" required 
                       class="w-full p-3 rounded-lg bg-gray-100 border border-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300 input-focus dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                       placeholder="Enter your email">
            </div>

            <!-- Password Field -->
            <div class="mb-6">
                <label class="block text-gray-900 font-bold mb-2 dark:text-white">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required 
                           class="w-full p-3 rounded-lg bg-gray-100 border border-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300 input-focus dark:bg-gray-700 dark:border-gray-600 dark:text-white" 
                           placeholder="Enter your password">
                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-blue-700 dark:text-blue-500" onclick="togglePassword('password', this)">
                        <svg class="w-5 h-5" id="toggleIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </span>
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center">
                    <input type="checkbox" id="rememberMe" class="mr-2 accent-blue-700 dark:accent-blue-500">
                    <label for="rememberMe" class="text-gray-600 text-sm dark:text-gray-400">Remember me</label>
                </div>
                <a href="#" class="text-blue-700 hover:text-yellow-500 text-sm font-medium dark:text-blue-500 dark:hover:text-yellow-400">Forgot Password?</a>
            </div>

            <!-- Login Button -->
            <button type="submit" 
                    class="w-full bg-blue-700 text-white py-3 rounded-lg font-semibold hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-all">
                Login Now
                <svg class="w-4 h-4 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </form>

        <!-- Sign Up Link -->
        <p class="text-center mt-6 text-gray-600 text-sm dark:text-gray-400">
            Don't have an account? 
            <a href="index.php?page=signup" class="text-blue-700 font-semibold hover:text-yellow-500 dark:text-blue-500 dark:hover:text-yellow-400">Sign Up</a>
        </p>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            } else {
                input.type = 'password';
                iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            }
        }
    </script>
</body>
</html>