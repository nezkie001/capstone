<?php
// student-search.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to search for students
function searchStudents($query) {
    // Connect to the database (assuming the use of MySQL)
    $servername = 'localhost';
    $username = 'your_username';
    $password = 'your_password';
    $dbname = 'your_database';

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Prepare statement to avoid SQL Injection
    $stmt = $conn->prepare('SELECT * FROM students WHERE name LIKE ? OR id_number LIKE ?');
    $searchQuery = '%' . $query . '%'; // Use like clause
    $stmt->bind_param('ss', $searchQuery, $searchQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $students;
}

// Code to handle search request
if (isset($_GET['search'])) {
    $query = $_GET['search'];
    $students = searchStudents($query);
} else {
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Search</title>
</head>
<body>
    <h1>Search for Students</h1>
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Enter student name or ID">
        <button type="submit">Search</button>
    </form>

    <h2>Search Results</h2>
    <ul>
        <?php foreach ($students as $student): ?>
            <li><?php echo htmlspecialchars($student['name']) . ' - ID: ' . htmlspecialchars($student['id_number']); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
