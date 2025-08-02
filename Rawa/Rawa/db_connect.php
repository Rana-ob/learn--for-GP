<?php
// db_connect.php - Database connection using MySQLi

$host = 'localhost';
$db   = 'rawa_bank_db'; // The name of the database you created
$user = 'root';         // Default XAMPP username
$pass = '';             // Default XAMPP password (usually empty)

// Create a new MySQLi connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    // In case of a connection error
    // Send a 500 Internal Server Error code and a JSON error message
    http_response_code(500);
    echo json_encode(["message" => "خطأ في الاتصال بقاعدة البيانات: " . $conn->connect_error]);
    exit(); // Stop script execution
}

// Set character set to utf8mb4 for proper Arabic character handling
// This is crucial for preventing display issues with Arabic text
$conn->set_charset("utf8mb4");


?>
