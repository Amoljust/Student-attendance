<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $servername = "localhost"; 
    $username = "root"; 
    $password = ""; 
    $dbname = "face"; 

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and bind SQL statement to insert student details
    $stmt = $conn->prepare("INSERT INTO students (username, year, department) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $year, $department);

    // Set parameters and execute SQL statement
    $username = $_POST['username'];
    $year = $_POST['year'];
    $department = $_POST['Department'];
    $stmt->execute();

    // Get the last inserted student ID
    $student_id = $conn->insert_id;

    // Prepare and bind SQL statement to insert 50 photos
    $stmt_photos = $conn->prepare("INSERT INTO photos (student_id, photo_data) VALUES (?, ?)");
    $stmt_photos->bind_param("is", $student_id, $photo_data);

    // Set parameters and execute SQL statement for each photo
    $photos_data = explode('|', $_POST['photo_data']);
    foreach ($photos_data as $photo_data) {
        $stmt_photos->execute();
    }

    // Close statements and connection
    $stmt->close();
    $stmt_photos->close();
    $conn->close();

    // Redirect to a success page with a success message
    header("Location: index.html?registration_success=1");
    exit();
}
?>

