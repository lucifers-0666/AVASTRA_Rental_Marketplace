<?php
/**
 * SpaceShare Admin — Header Include Component
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
    <title><?= htmlspecialchars($pageTitle); ?> — AVASTRA SpaceShare Admin</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL; ?>/assets/images/logo/only%20logo.svg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- DataTables & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?= APP_URL; ?>/assets/css/admin.css">
</head>
<body>
<div id="admin-wrapper">
