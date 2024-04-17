<?php
// Connect to your database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "face"; // Database name
$tableName = "csv_data"; // Table name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch CSV files from the database
$sql = "SELECT * FROM $tableName";
$result = $conn->query($sql);

// Display CSV files on the teacher dashboard
if ($result->num_rows > 0) {
    echo '<div class="csvRecords">';
    while($row = $result->fetch_assoc()) {
        echo '<div class="csv-record">';
        echo '<a href="data:application/csv;base64,' . base64_encode($row['file_content']) . '" download="' . $row['lecture_name'] . '.csv">' . $row['lecture_name'] . '</a>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo "No CSV files found";
}

$conn->close();
?>
