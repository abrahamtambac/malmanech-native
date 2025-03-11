<!-- _partials/_template/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="./css/style.css"> <!-- Path relatif dari root -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg bg-primary border-bottom border-5 border-warning">
    <div class="container-fluid">
      <a class="navbar-brand text-white fw-bold" href="#">
        <h1 class="mb-0"><i class="bi bi-fingerprint text-warning"></i><b>Starvee</b></h1>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll"
        aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarScroll">
        <ul class="navbar-nav me-3 my-2 my-lg-0 navbar-nav-scroll">
          <li class="nav-item">
            <a class="nav-link text-white" href="index.php?page=home" style="font-size: 20px;">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" style="font-size: 20px;">Pricing</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white" style="font-size: 20px;">AI Products offer</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown"
              aria-expanded="false" style="font-size: 20px;">
              Documentation
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">API Integrations</a></li>
              <li><a class="dropdown-item" href="#">Embedded AI Chatbots</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#">Cloud Datasets</a></li>
            </ul>
          </li>
        </ul>
        <a href="index.php?page=login" class="btn btn-warning fw-bolder" style="border-radius: 20px;">Login Now Buddy <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </nav>