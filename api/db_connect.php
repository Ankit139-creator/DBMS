<?php
// =====================================================
// db_connect.php - Database Connection File
// Canteen Review System - DBMS Mini Project
// =====================================================

// Database configuration
$host = "bschams1l2rgancp72ti-mysql.services.clever-cloud.com";       // Server name (localhost for XAMPP)
$username = "uvgowmuzbwisjawa";        // Default XAMPP MySQL username
$password = "v7iiD24pNcsZh0pFAfqp";    // MySQL root password
$database = "bschams1l2rgancp72ti";  // Our database name
$port = 3306;


// Create connection using MySQLi
$conn = mysqli_connect($host, $username, $password, $database);

// Check if connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// =====================================================
// Function: log_db_operation()
// Purpose: Logs database operations to terminal/console
//          and to a log file for tracking
// =====================================================
function log_db_operation($operation, $details) {
    $timestamp = date("Y-m-d H:i:s");
    $log_message = "[$timestamp] [DB $operation] $details";
    
    // Log to PHP error log (visible in XAMPP Apache error log / terminal)
    error_log($log_message);
    
    // Also write to a custom log file in the project folder
    $log_file = __DIR__ . '/db_operations.log';
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
    
    // Print to console/terminal if running from CLI
    if (php_sapi_name() === 'cli') {
        echo $log_message . PHP_EOL;
    }
}
?>
