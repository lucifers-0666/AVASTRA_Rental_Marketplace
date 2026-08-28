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
    header("Location: login.php");
    exit;
}
