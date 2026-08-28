<?php
/**
 * SpaceShare Admin Index Redirect
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::initSession();

if (Auth::isAdmin()) {
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: " . APP_URL . "/public/login.php?redirect=" . urlencode(APP_URL . "/admin/dashboard.php"));
    exit;
}
