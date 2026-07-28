<?php
/**
 * Billard Stream Configuration
 */

session_start();

// Database Configuration
define('DB_HOST', 'mysql33.unoeuro.com');
define('DB_NAME', 'wahl_it_dk_db');
define('DB_USER', 'wahl-it');
define('DB_PASS', 'wahlit2026');

// Hardcoded club credentials (temporary)
$klubber = [
    "fbk" => password_hash("billard2026", PASSWORD_DEFAULT),
    "testklub" => password_hash("test1234", PASSWORD_DEFAULT)
];

/**
 * Returns a PDO database connection.
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, 
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database forbindelse fejlede: " . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * Checks if the user is logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['klub_id']) && !empty($_SESSION['klub_id']);
}

/**
 * Redirects to login.php if the user is not logged in.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}
