<?php
// Enable error reporting to identify the exact issue instead of a blank 500 error
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Default Local XAMPP settings
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'energy_db';
$port = 3307;

// Check if Railway provided a unified MYSQL_URL string (Very common)
$url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($url) {
    // Parse mysql://user:pass@host:port/dbname
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? $host;
    $user = $parsed['user'] ?? $user;
    $pass = $parsed['pass'] ?? $pass;
    $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $db;
    $port = $parsed['port'] ?? 3306;
} else {
    // Fallback to individual variables if MYSQL_URL isn't used
    $host = getenv('MYSQLHOST') ?: $host;
    $user = getenv('MYSQLUSER') ?: $user;
    $pass = getenv('MYSQLPASSWORD') ?: $pass;
    $db   = getenv('MYSQLDATABASE') ?: $db;
    $port = getenv('MYSQLPORT') ?: $port;
}

// Set MySQLi to throw exceptions instead of fatal PHP errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    // Turn strict mode off after connection so missing tables don't crash the site
    mysqli_report(MYSQLI_REPORT_OFF);
} catch (mysqli_sql_exception $e) {
    // This will print the actual error to the screen instead of a 500 Error
    die("<div style='padding:20px; font-family:sans-serif; background:#ffeb3b;'>
            <h3>Database Connection Failed!</h3>
            <p><strong>Error Details:</strong> " . $e->getMessage() . "</p>
            <p>Make sure your Railway MySQL database is correctly linked to this app.</p>
         </div>");
}
?>