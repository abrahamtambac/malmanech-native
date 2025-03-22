<?php
include './config/db.php';

$message = '';
$status = 'info';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT id FROM tb_users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE tb_users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        if ($stmt->execute()) {
            $message = "Your email has been verified! You can now login.";
            $status = 'success';
        } else {
            $message = "Verification failed!";
            $status = 'error';
        }
    } else {
        $message = "Invalid or expired verification token!";
        $status = 'error';
    }
    $stmt->close();
} else {
    $message = "No verification token provided!";
    $status = 'error';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starvee - Verify Email</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f7fa;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="max-w-md w-full bg-white border border-gray-200 rounded-lg shadow-sm p-8 dark:bg-gray-800 dark:border-gray-700 card-hover">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 dark:text-white">Email Verification</h2>
        <div class="bg-<?php echo $status === 'success' ? 'green' : 'red'; ?>-500 text-white rounded-lg p-4 mb-6 text-center text-sm">
            <?php echo $message; ?>
        </div>
        <p class="text-center text-gray-600 text-sm dark:text-gray-400">
            <a href="../index.php?page=login" class="text-blue-700 font-semibold hover:text-yellow-500 dark:text-blue-500 dark:hover:text-yellow-400">Go to Login</a>
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
</body>
</html>