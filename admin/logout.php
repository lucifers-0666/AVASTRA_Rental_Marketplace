<?php
/**
 * AVASTRA — Admin Logout Handler
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::logoutAdmin();
header("Location: " . APP_URL . "/admin/login.php");
exit;
