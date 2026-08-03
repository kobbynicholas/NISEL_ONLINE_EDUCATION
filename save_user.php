<?php
// Database connection settings
$servername = "localhost";
$username = "root"; // default XAMPP MySQL username
$password = "";     // default is empty
$dbname = "mydb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate and sanitize input
$name = trim($_POST['name']);
$email = trim($_POST['email']);

if (!empty($name) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $email);

    if ($stmt->execute()) {
        echo "User saved successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Invalid input.";
}

$conn->close();
?>
