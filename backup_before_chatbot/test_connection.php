<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hr";

echo "<h2>Database Connection Test</h2>";

// Test mysqli connection
echo "<h3>Testing MySQLi Connection:</h3>";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error . "<br>";
} else {
    echo "✅ Connected successfully with MySQLi<br>";
    echo "Server info: " . $conn->server_info . "<br>";
    echo "Host info: " . $conn->host_info . "<br>";
}

// Test PDO connection
echo "<h3>Testing PDO Connection:</h3>";
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully with PDO<br>";
    
    // Get server version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "Server version: " . $version . "<br>";
    
} catch(PDOException $e) {
    echo "❌ PDO Connection failed: " . $e->getMessage() . "<br>";
}

// Test if database exists
echo "<h3>Testing Database Access:</h3>";
if (isset($conn) && !$conn->connect_error) {
    $result = $conn->query("SELECT DATABASE() as db_name");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Currently connected to database: " . $row['db_name'] . "<br>";
    }
    
    // List tables
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "Tables in database:<br>";
        while($row = $result->fetch_assoc()) {
            echo "- " . current($row) . "<br>";
        }
    }
}
?>