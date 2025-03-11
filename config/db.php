<!-- config/db.php -->
<?php
// Fungsi untuk memuat file .env
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip komentar
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv("$name=$value");
    }
}

// Memuat .env dari root proyek
loadEnv(__DIR__ . '/../.env');

// Mengambil variabel dari .env
$dbHost = getenv('DB_HOST');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
$dbName = getenv('DB_NAME');

// Koneksi ke database
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>