<?php
include './config/db.php';
include '_partials/_template/header.php'; // Panggil header

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
    <title>Malmanech - Verify Email</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts (Roboto) -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm p-4 mt-5 card-hover">
                    <h2 class="text-center fw-bold mb-4">Email Verification</h2>
                    <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?> text-center" role="alert">
                        <?php echo $message; ?>
                    </div>
                    <p class="text-center text-muted">
                        <a href="../index.php?page=login" class="text-primary fw-semibold text-decoration-none">Go to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>