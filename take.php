<?php

// Function to connect to MySQL database
function connect_to_database() {
    $host = "localhost";
    $database = "face";
    $user = "root";
    $password = "";

    try {
        $connection = new PDO("mysql:host=$host;dbname=$database", $user, $password);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected to MySQL database\n";
        return $connection;
    } catch(PDOException $e) {
        echo "Error while connecting to MySQL: " . $e->getMessage() . "\n";
        return null;
    }
}

// Function to create a table to store CSV data if not exists
function create_csv_table($connection) {
    try {
        $query = "CREATE TABLE IF NOT EXISTS csv_data (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lecture_name VARCHAR(255),
                    file_content TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                  )";
        $connection->exec($query);
        echo "CSV table created or already exists\n";
    } catch(PDOException $e) {
        echo "Error creating CSV table: " . $e->getMessage() . "\n";
    }
}

// Function to store CSV data in the database
function store_csv_data($connection, $lecture_name, $file_content) {
    try {
        $query = "INSERT INTO csv_data (lecture_name, file_content) VALUES (:lecture_name, :file_content)";
        $statement = $connection->prepare($query);
        $statement->bindParam(':lecture_name', $lecture_name);
        $statement->bindParam(':file_content', $file_content);
        $statement->execute();
        echo "CSV data stored successfully\n";
    } catch(PDOException $e) {
        echo "Error storing CSV data: " . $e->getMessage() . "\n";
    }
}

// Function to retrieve student information and photos from the database
function retrieve_student_data($connection) {
    try {
        $query = "SELECT students.id, students.username, photos.photo_data FROM students INNER JOIN photos ON students.id = photos.student_id";
        $statement = $connection->prepare($query);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    } catch(PDOException $e) {
        echo "Error while retrieving data from MySQL: " . $e->getMessage() . "\n";
        return null;
    }
}

// Function for face recognition
function recognize_faces($student_data, $lecture_name) {
    $faceCascade = "haarcascade_frontalface_default.xml"; // Path to the Haar cascade file

    // Load the cascade
    $faceCascadePath = dirname(__FILE__) . '/' . $faceCascade;
    if (!file_exists($faceCascadePath)) {
        echo "Error: Haar cascade file not found\n";
        return;
    }

    $xml = simplexml_load_file($faceCascadePath);
    $faceCascade = new CvHaarClassifierCascade($xml);

    // Initialize video capture
    $capture = new CvCapture(0); // 0 for default camera

    // Initialize CSV content
    $csvContent = [];

    $recognizedIds = []; // Array to keep track of recognized student IDs

    while (true) {
        $frame = $capture->queryFrame();
        if ($frame === false) break;

        // Convert to grayscale
        $gray = $frame->convertColor(CV_BGR2GRAY);

        // Detect faces
        $faces = $faceCascade->detectMultiScale($gray);

        foreach ($faces as $face) {
            // Perform face recognition
            // Here, we're just comparing face sizes for simplicity
            $faceSize = $face->width * $face->height;
            $recognized = false;
            foreach ($student_data as $student) {
                // Compare face sizes
                if (abs(strlen($student['photo_data']) - $faceSize) < 1000) {
                    $studentId = $student['id'];
                    if (!in_array($studentId, $recognizedIds)) {
                        $recognizedIds[] = $studentId;
                        $recognized = true;
                        $studentName = $student['username'];
                        $timestamp = date('Y-m-d H:i:s');
                        $csvContent[] = [$studentId, $studentName, $timestamp];
                        echo "Student ID: $studentId, Name: $studentName\n";
                    }
                    break;
                }
            }

            if (!$recognized) {
                echo "Unknown Student\n";
            }

            // Draw rectangle around the face
            $frame->rectangle(
                $face->x, $face->y,
                $face->x + $face->width, $face->y + $face->height,
                new CvScalar(255, 0, 0), 2, CV_AA, 0
            );
        }

        // Display the frame
        $frame->show();

        // Exit when 'q' is pressed
        $c = cvWaitKey(1);
        if ($c == 27 || $c == ord('q')) break;
    }

    // Write CSV content to a string
    $csvString = '';
    $csvFile = fopen('attendance.csv', 'a');
    fputcsv($csvFile, ['Student ID', 'Name', 'Timestamp']);
    foreach ($csvContent as $row) {
        fputcsv($csvFile, $row);
        $csvString .= implode(',', $row) . "\n";
    }
    fclose($csvFile);

    return $csvString;
}

// Main code
$connection = connect_to_database();
if ($connection) {
    create_csv_table($connection);
    $lecture_name = readline("Enter the lecture name: ");
    $student_data = retrieve_student_data($connection);
    if ($student_data) {
        $csv_content = recognize_faces($student_data, $lecture_name);
        store_csv_data($connection, $lecture_name, $csv_content);
    }
    $connection = null;
}

?>
