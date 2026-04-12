<?php
// Prioritize Railway environment variables, fallback to local XAMPP configuration
$host = getenv('MYSQLHOST') ? getenv('MYSQLHOST') : '127.0.0.1';
$user = getenv('MYSQLUSER') ? getenv('MYSQLUSER') : 'root';
$pass = getenv('MYSQLPASSWORD') ? getenv('MYSQLPASSWORD') : '';
$db   = getenv('MYSQLDATABASE') ? getenv('MYSQLDATABASE') : 'energy_db';
$port = getenv('MYSQLPORT') ? getenv('MYSQLPORT') : 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>