<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starvee - Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Plus+Jakarta+Sans:wght@200..800&family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Product Sans', sans-serif; }
        .profile-img { border-radius: 50%; object-fit: cover; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-blue-600  ">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <a href="index.php?page=home" class="text-white font-bold text-2xl">
                    <i class="bi bi-fingerprint text-yellow-400"></i> Mal +
                </a>
                <div class="hidden md:flex space-x-6 items-center">
                    
                    <!-- User Section -->
                    <?php
                    include_once './config/db.php';
                    include_once './controllers/AuthController.php';
                    $auth = new AuthController($conn);
                    $currentUser = $auth->getCurrentUser();
                    if ($currentUser) {
                        $profileImage = !empty($currentUser['profile_image']) 
                            ? './upload/image/' . $currentUser['profile_image'] 
                            : './image/robot-ai.png';
                    ?>
                     <a href="index.php?page=admin_dashboard" class="text-white text-lg hover:text-yellow-400 transition duration-300">Dashboard</a>
                        <div class="relative group">
                    
                            <button class="bg-yellow-400 text-black font-bold px-4 py-2 rounded-full flex items-center hover:bg-yellow-500 transition duration-300">
                                <img src="<?php echo $profileImage; ?>" alt="Profile" class="profile-img h-8 w-8 mr-2">
                                <span><?php echo htmlspecialchars($currentUser['name']); ?></span>
                            </button>
                            <div class="absolute right-0 hidden group-hover:block bg-white shadow-lg rounded-md mt-2">
                            <a href="index.php?page=admin_dashboard" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                                <a href="index.php?page=profile" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                                <a href="index.php?page=change_password" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Change Password</a>
                                <a href="index.php?page=logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <a href="index.php?page=home" class="text-white text-lg hover:text-yellow-400 transition duration-300">Home</a>
                    <a href="#" class="text-white text-lg hover:text-yellow-400 transition duration-300">Pricing</a>
                    <a href="#" class="text-white text-lg hover:text-yellow-400 transition duration-300">AI Products</a>
                    <div class="relative group">
                        <a href="#" class="text-white text-lg hover:text-yellow-400 transition duration-300">Documentation</a>
                        <div class="absolute hidden group-hover:block bg-white shadow-lg rounded-md mt-2">
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">API Integrations</a>
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Embedded AI Chatbots</a>
                            <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Cloud Datasets</a>
                        </div>
                    </div>
                        <a href="index.php?page=login" class="bg-blue-400 text-black font-bold px-4 py-2 rounded-full hover:bg-blue-500 transition duration-300 flex items-center">
                            Masuk Sekarang <i class="bi bi-arrow-right ml-2"></i>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Jumbotron -->
    <div class="bg-blue-600  text-white py-20">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 text-center md:text-left">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to Mal +</h1>
                    <p class="text-lg md:text-xl mb-6">Atur Penjadwalan kamu, meeting, chat dengan kolega ...</p>
                    <a href="#" class="bg-yellow-400 text-black font-bold px-6 py-3 rounded-full hover:bg-yellow-500 transition duration-300 inline-block">
                        Get Started
                    </a>
                </div>
                <div class="md:w-1/2 mt-8 md:mt-0">
                  
                </div>
            </div>
        </div>
    </div>

    