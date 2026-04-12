<?php
// Your Railway Database Credentials
$host = "mysql.railway.internal";    // Look under MYSQLHOST on Railway
$user = "root";                      // Look under MYSQLUSER
$password = "GszRJSpivRAOubPUiFHjYvGcvpKBdDFY"; // Look under MYSQLPASSWORD
$dbname = "railway";                 // Look under MYSQLDATABASE
$port = 3306;                       // Look under MYSQLPORT

// Create the connection passing the exact port variable at the end
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Check if it worked
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
