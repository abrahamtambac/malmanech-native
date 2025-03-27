<?php include '_partials/_template/header.php'; ?>

<style>
    @font-face {
        font-family: 'Product Sans';
        src: url('./fonts/ProductSans-Regular.ttf') format('truetype');
    }

    :root {
        --primary: #0d6efd;
        --secondary: #f8f9fa;
        --dark: #212529;
        --light: #ffffff;
        --gradient: linear-gradient(135deg, #0d6efd, #0a58ca);
    }

    body {
        font-family: 'Product Sans', sans-serif;
        line-height: 1.6;
        color: var(--dark);
    }

    .hero {
        background: var(--secondary);
        padding: 120px 0;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: url('https://www.transparenttextures.com/patterns/subtle-white-feathers.png');
        opacity: 0.1;
        animation: subtleMove 20s infinite linear;
    }

    @keyframes subtleMove {
        0% { transform: translate(0, 0); }
        100% { transform: translate(50px, 50px); }
    }

    .hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 1.5rem;
        animation: fadeInUp 1s ease-out;
    }

    .hero p {
        font-size: 1.25rem;
        max-width: 700px;
        margin: 0 auto 2rem;
        color: #666;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .feature-card {
        border-radius: 15px;
        padding: 2rem;
        background: var(--light);
        transition: all 0.3s ease;
        height: 100%;
    }

    .feature-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 15px 30px rgba(13, 110, 253, 0.1);
    }

    .feature-card i {
        background: var(--gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .btn-primary {
        background: var(--gradient);
        border: none;
        padding: 12px 32px;
        border-radius: 50px;
        font-weight: 600;
      
        letter-spacing: 1px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
    }

    .navbar {
        padding: 1rem 0;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    .feature-card {
        position: relative;
        border-radius: 20px;
        padding: 2.5rem;
        background: #ffffff;
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
    }

    .feature-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 15px 30px rgba(13, 110, 253, 0.15);
    }

    .abstract-bg {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 30% 30%, rgba(13, 110, 253, 0.1) 0%, transparent 70%);
        opacity: 0.5;
        transform: rotate(45deg);
        transition: all 0.5s ease;
        pointer-events: none;
    }

    .feature-card:hover .abstract-bg {
        transform: rotate(50deg) scale(1.1);
        opacity: 0.7;
    }

    .feature-card i {
        position: relative;
        z-index: 1;
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        transition: transform 0.3s ease;
    }

    .feature-card:hover i {
        transform: scale(1.1);
    }

    .feature-card h5 {
        position: relative;
        z-index: 1;
        color: #212529;
        font-size: 1.25rem;
    }

    .feature-card p {
        position: relative;
        z-index: 1;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    @media (max-width: 768px) {
        .feature-card {
            padding: 1.5rem;
        }
        
        .feature-card i {
            font-size: 2.5rem;
        }
        
        .feature-card h5 {
            font-size: 1.1rem;
        }
        
        .feature-card p {
            font-size: 0.85rem;
        }
    }

    .footer {
        background: #ffffff; /* Background putih */
        color: #212529; /* Warna teks default hitam */
        padding: 80px 0 40px;
        position: relative;
    }

    .footer h5 {
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: #212529;
        display: flex;
        align-items: center;
    }

    .footer h6 {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 20px;
        color: #212529;
    }

    .footer p {
        color: #666;
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .footer a {
        color: #0d6efd;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer a:hover {
        color: #0a58ca;
    }

    .footer .bi {
        font-size: 1.2rem;
        color: #666;
        transition: color 0.3s;
    }

    .footer .bi:hover {
        color: #0d6efd;
    }

    .footer hr {
        border: 0;
        height: 1px;
        background: #dee2e6;
        margin: 40px 0;
    }

    .footer .text-center {
        color: #666;
        font-size: 0.85rem;
    }

    .footer .btn-primary {
        background: linear-gradient(135deg, #0d6efd, #0a58ca);
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .footer .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
    }

    .footer .text-warning {
        color: #ffc107 !important; /* Warna kuning untuk ikon fingerprint */
    }
    @media (max-width: 768px) {
        .hero {
            padding: 80px 0;
        }
        .hero h1 {
            font-size: 2.5rem;
        }
        .hero p {
            font-size: 1rem;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero text-center">
    <div class="container position-relative" style="max-width: 1280px;">
        <a href="index.php?page=register" class="btn btn-primary mb-3">
            <img src="./image/save_money.png" alt="Promo" style="width: 50px; vertical-align: middle; margin-right: 8px;">
            Simpan Uangmu, Kita Lagi Gratis !!!
        </a>
        <h1 class="display-4">Kolaborasi Tim yang Lebih Mudah dengan <span style="background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Malmanech</span></h1>
        <p class="lead">Kelola rapat, chat, dan laporan dalam satu platform modern yang dirancang untuk produktivitas maksimal.</p>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5">
    <div class="container" style="max-width: 1280px;">
        <h2 class="text-center fw-bold mb-5" style="font-size: 2.5rem;">Fitur Unggulan Malmanech</h2>
        <div class="row g-4">
            <div class="col-md-3 d-flex">
                <div class="card feature-card border-0 shadow-sm">
                    <div class="abstract-bg"></div>
                    <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                    <h5 class="fw-semibold mt-4">Chat Real-Time</h5>
                    <p class="text-muted">Berkomunikasi dengan tim Anda secara instan, kapan saja, di mana saja.</p>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card feature-card border-0 shadow-sm">
                    <div class="abstract-bg"></div>
                    <i class="bi bi-backpack" style="font-size: 3rem;"></i>
                    <h5 class="fw-semibold mt-4">Classroom</h5>
                    <p class="text-muted">Atur Grup anda dan memungkinkan kamu menambah aktivitas hingga ujian</p>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card feature-card border-0 shadow-sm">
                    <div class="abstract-bg"></div>
                    <i class="bi bi-calendar-check" style="font-size: 3rem;"></i>
                    <h5 class="fw-semibold mt-4">Jadwal Rapat</h5>
                    <p class="text-muted">Atur dan kelola rapat dengan mudah dalam hitungan detik.</p>
                </div>
            </div>
            <div class="col-md-3 d-flex">
                <div class="card feature-card border-0 shadow-sm">
                    <div class="abstract-bg"></div>
                    <i class="bi bi-graph-up" style="font-size: 3rem;"></i>
                    <h5 class="fw-semibold mt-4">Laporan Produktivitas</h5>
                    <p class="text-muted">Pantau kinerja tim Anda dengan laporan yang jelas dan actionable.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 text-center text-white" style="background: var(--white);">
    <div class="container">
        <h2 class="fw-bold mb-4 text-dark">Siap Memulai?</h2>
        <p class="lead mb-5 text-dark">Bergabunglah dengan ribuan tim yang telah meningkatkan produktivitas mereka dengan Malmanech.</p>
        <a href="index.php?page=signup" class="btn btn-light btn-lg text-primary fw-bold">Coba Gratis Sekarang</a>
    </div>
</section>

<!-- Footer -->
<footer id="contact" class="footer bg-light">
    <div class="container" style="max-width: 1280px;">
        <div class="row g-4">
            <div class="col-md-4">
                <h5>
                    <i class="bi bi-fingerprint text-warning me-2"></i>Malmanech
                </h5>
                <p>Platform kolaborasi modern untuk meningkatkan produktivitas tim Anda.</p>
                <div class="mt-4">
                    <a href="https://facebook.com" class="me-3"><i class="bi bi-facebook"></i></a>
                    <a href="https://twitter.com" class="me-3"><i class="bi bi-twitter"></i></a>
                    <a href="https://instagram.com" class="me-3"><i class="bi bi-instagram"></i></a>
                    <a href="https://linkedin.com"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="col-md-2">
                <h6>Produk</h6>
                <ul class="list-unstyled">
                    <li><a href="#features">Fitur</a></li>
                    <li><a href="#how-it-works">Cara Kerja</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6>Perusahaan</h6>
                <ul class="list-unstyled">
                    <li><a href="#">Tentang Kami</a></li>
                    <li><a href="#">Kontak</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Hubungi Kami</h6>
                <p>
                    Email: <a href="mailto:support@malmanech.com">admin@malmanech.com</a><br>
                    
                </p>
                <a href="index.php?page=signup" class="btn btn-primary mt-3 text-white">Daftar Sekarang</a>
            </div>
        </div>
        <hr>
        <p class="text-center mb-0">© 2025 Malmanech. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>