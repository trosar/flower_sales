<?php

// 1. Load .env file if it exists (for local development)
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        // Split by the first '=' found
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

// 2. Session Configuration
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 0); 
ini_set('session.use_trans_sid', 1);

$sid = $_GET['sid'] ?? null;

if (!$sid || $sid === 'new') {
    $sid = bin2hex(random_bytes(16));
    
    if (!isset($is_ajax)) {
        // GET THE CURRENT PAGE NAME (e.g., admin.php or index.php)
        $currentPage = basename($_SERVER['PHP_SELF']);
        
        // Redirect back to the SAME page they requested, just with the new SID
        header("Location: " . $currentPage . "?sid=" . $sid);
        exit;
    }
}

session_id($sid);
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

define('SID_STR', 'sid=' . session_id());

// 3. Database connection using ONLY Environment Variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$charset = 'utf8mb4';

// Basic validation: stop if variables are missing
if (!$host || !$user || !$pass) {
    die("Environment variables are not configured.");
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Error is logged internally, but hidden from the public for security
     error_log($e->getMessage());
     die("Database connection failed.");
}
?>