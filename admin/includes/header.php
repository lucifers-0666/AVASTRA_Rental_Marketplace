<?php
/**
 * SpaceShare / AVASTRA Admin — Header Include Component
 */
require_once __DIR__ . '/../../classes/Auth.php';
Auth::initSession();
Auth::requireAdmin();

$currentUser = Auth::getUser();
$pageTitle = $pageTitle ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?> — AVASTRA Admin</title>

    <!-- DNS Prefetch & Preconnect for Fast CDN Asset Loading -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL; ?>/assets/images/logo/only%20logo.svg">

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

    <!-- DataTables & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?= APP_URL; ?>/assets/css/admin.css">
</head>
<body>
<div id="admin-wrapper">
