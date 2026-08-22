<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · SpaceShare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= e(url()) ?>"><i class="bi bi-box-seam"></i> SpaceShare</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= e(url('visitor/browse.php')) ?>">Browse Spaces</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('visitor/how-it-works.php')) ?>">How It Works</a></li>
      </ul>
      <ul class="navbar-nav">
        <?php if (Auth::check()): ?>
          <?php if (Auth::isAdmin()): ?>
            <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/dashboard.php')) ?>">Admin Panel</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= e(url('user/dashboard.php')) ?>">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= e(url('owner/spaces.php')) ?>">My Spaces</a></li>
          <?php endif; ?>
          <li class="nav-item"><span class="nav-link disabled">Hi, <?= e($_SESSION['user_name'] ?? '') ?></span></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('visitor/logout.php')) ?>">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('visitor/login.php')) ?>">Login</a></li>
          <li class="nav-item"><a class="btn btn-primary btn-sm ms-lg-2 mt-1 mt-lg-0" href="<?= e(url('visitor/register.php')) ?>">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
