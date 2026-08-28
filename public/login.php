<?php
/**
 * SpaceShare — Temporary Public Login Handler
 */
require_once __DIR__ . '/../config/database.php';

header("Location: " . APP_URL . "/admin/login.php");
exit;
