<?php
// Database connection parameters
$servername = "localhost"; // Change this if your MySQL server is on a different host
$username = "root"; // Change this to your MySQL username
$password = ""; // Change this to your MySQL password
$database = "face"; // Change this to your MySQL database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement to insert data into the users table
    $sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";

    if ($conn->query($sql) === TRUE) {
        // Registration successful, redirect to login page
        header("Location: teacher_login.html?registration_success=1");
        exit(); // Stop further execution of PHP script
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Close the database connection
$conn->close();
?>
