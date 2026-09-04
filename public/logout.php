<?php
/**
 * AVASTRA — Logout Handler
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::logout();
header("Location: " . APP_URL . "/public/login.php");
exit;
