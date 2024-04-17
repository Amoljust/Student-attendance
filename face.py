from flask import Flask, request, render_template,send_file,redirect,url_for
import sys
import cv2
import numpy as np
import mysql.connector
from mysql.connector import Error
import base64
import csv
from datetime import datetime

app = Flask(__name__)

# Function to connect to MySQL database
def connect_to_database():
    try:
        connection = mysql.connector.connect(
            host='localhost',
            database='face',
            user='root',
            password=''
        )
        if connection.is_connected():
            print("Connected to MySQL database")
            return connection
    except Error as e:
        print("Error while connecting to MySQL", e)
        return None

# Function to create a table to store CSV data if not exists
def create_csv_table(connection):
    try:
        cursor = connection.cursor()
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS csv_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lecture_name VARCHAR(255),
                file_content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        connection.commit()
        print("CSV table created or already exists")
    except Error as e:
        print("Error creating CSV table:", e)

# Function to store CSV data in the database
def store_csv_data(connection, lecture_name, file_content):
    try:
        cursor = connection.cursor()
        cursor.execute("INSERT INTO csv_data (lecture_name, file_content) VALUES (%s, %s)", (lecture_name, file_content))
        connection.commit()
        print("CSV data stored successfully")
    except Error as e:
        print("Error storing CSV data:", e)

# Function to retrieve student information and photos from the database
def retrieve_student_data(connection):
    try:
        cursor = connection.cursor()
        cursor.execute("SELECT students.id, students.username, photos.photo_data FROM students INNER JOIN photos ON students.id = photos.student_id")
        rows = cursor.fetchall()
        return rows
    except Error as e:
        print("Error while retrieving data from MySQL", e)
        return None

# Function for face recognition
def recognize_faces(student_data, lecture_name):
    # Initialize face recognition model
    face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

    # Load student photos and IDs
    student_photos = [np.frombuffer(base64.b64decode(row[2]), np.uint8) for row in student_data]
    student_ids = [row[0] for row in student_data]

    # Initialize video capture
    cap = cv2.VideoCapture(0)

    # Initialize CSV file content
    csv_content = []

    recognized_ids = []  # List to keep track of recognized student IDs

    while True:
        ret, frame = cap.read()
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        faces = face_cascade.detectMultiScale(gray, scaleFactor=1.3, minNeighbors=5, minSize=(30, 30))

        for (x, y, w, h) in faces:
            # Here, we're just comparing face sizes for simplicity
            face_size = w * h
            recognized = False
            for i, photo in enumerate(student_photos):
                # Compare face sizes
                if abs(len(photo) - face_size) < 1000:
                    student_id = student_ids[i]
                    if student_id not in recognized_ids:
                        recognized_ids.append(student_id)
                        recognized = True
                        student_name = student_data[i][1]
                        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                        csv_content.append([student_id, student_name, timestamp])
                        print("Student ID:", student_id, "Name:", student_name)
                    break

            if not recognized:
                print("Unknown Student")

            # Draw rectangle around the face
            cv2.rectangle(frame, (x, y), (x + w, y + h), (255, 0, 0), 2)

        cv2.imshow('Face Recognition', frame)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    cap.release()
    cv2.destroyAllWindows()

    # Write CSV content to a string
    csv_string = ""
    with open('attendance.csv', 'a', newline='') as csvfile:
        csv_writer = csv.writer(csvfile)
        csv_writer.writerow(['Student ID', 'Name', 'Timestamp'])
        for row in csv_content:
            csv_writer.writerow(row)
            csv_string += ','.join(map(str, row)) + '\n'

    return csv_string

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/process', methods=['POST'])
def process():
    # Connect to the database
    connection = connect_to_database()
    if connection:
        # Create CSV table if not exists
        create_csv_table(connection)

        # Retrieve student data from the database
        student_data = retrieve_student_data(connection)
        if student_data:
            # Get lecture name from form data
            lecture_name = request.form['lecture_name']

            # Recognize faces and store attendance
            csv_content = recognize_faces(student_data, lecture_name)
            store_csv_data(connection, lecture_name, csv_content)
        
        # Close database connection
        connection.close()
        return redirect('http://localhost/STUDENT%20LOGIN/teacher_page.html')

    return 'Attendance recorded successfully!'

if __name__ == "__main__":
    app.run(debug=True)
