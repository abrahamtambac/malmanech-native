<!-- index.php -->
<?php 
session_start();
include_once './config/db.php'; // Pastikan path benar relatif ke index.php

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

function loadPage($page, $conn) { // Tambahkan parameter $conn
    switch ($page) {
        case 'home':
            include 'file_/home.php';
            break;
        case 'login':
            include 'file_/login.php';
            break;
        case 'signup':
            include 'file_/signup.php';
            break;
        case 'logout':
            include 'file_/logout.php';
            break;
        case 'admin_dashboard':
            if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
                include 'file_/admin_dashboard.php';
            } else {
                include 'file_/404/not_found_1.php';
            }
            break;
        default:
            include 'file_/404/not_found_1.php';
            break;
    }
}

include '_partials/_template/header.php';

loadPage($page, $conn); // Teruskan $conn ke fungsi
?>