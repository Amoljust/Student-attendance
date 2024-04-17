<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the username and password are set and not empty
    if (isset($_POST['username']) && isset($_POST['password']) && !empty($_POST['username']) && !empty($_POST['password'])) {
        // Retrieve username and password from the form
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Database connection parameters
        $servername = "localhost"; 
        $db_username = "root"; 
        $db_password = ""; 
        $database = "face"; 

        // Create connection
        $conn = new mysqli($servername, $db_username, $db_password, $database);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Prepare SQL statement to fetch user data
        $sql = "SELECT * FROM users WHERE username='$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // User found, verify password
            $row = $result->fetch_assoc();
            $hashed_password = $row['password'];
            if (password_verify($password, $hashed_password)) {
                // Password is correct, redirect to the dashboard
                session_start();
                $_SESSION['username'] = $username; // Store username in session
                header("Location: teacher_page.html");
                exit();
            } else {
                // Password is incorrect
                echo "Invalid password";
            }
        } else {
            // User not found
            echo "Invalid username";
        }

        // Close the database connection
        $conn->close();
    } else {
        // Username or password not provided
        echo "Please provide both username and password";
    }
}
?>
