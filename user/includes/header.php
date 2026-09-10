<?php
/**
 * AVASTRA User App — Header Include Component
 * Every page under /user/*.php starts by requiring this file.
 */
require_once __DIR__ . '/../../classes/Auth.php';
Auth::initSession();
Auth::requireLogin();   // bounces to public/login.php if not logged in

$currentUser = Auth::getUser();
$pageTitle   = $pageTitle ?? 'Overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle); ?> — AVASTRA</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,400;0,500;1,400&family=DM+Serif+Display:ital@0;1&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="<?= APP_URL; ?>/assets/images/PHP%20LOGO/only%20logo.svg">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= APP_URL; ?>/assets/css/user-app.css">
</head>
<body>
<div id="user-app">
