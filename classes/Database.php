<?php
/**
 * SpaceShare — Flexible Rental Marketplace
 * Database Wrapper Singleton (PDO)
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Get Singleton PDO Instance
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die("<div style='font-family:sans-serif; padding:20px; background:#fff3f3; border:1px solid #ffcdd2; color:#b71c1c; border-radius:8px;'>
                        <h3>Database Connection Failed</h3>
                        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                        <p>Please make sure XAMPP MySQL server is running and database <code>" . DB_NAME . "</code> exists.</p>
                     </div>");
            }
        }
        return self::$instance;
    }
}
