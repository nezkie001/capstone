<?php

function sendEmailNotifications($students, $subject, $message) {
    foreach ($students as $student) {
        // Assuming a function mail() is available
        mail($student['email'], $subject, $message);
    }
}

// Example usage
$students = [
    ['email' => 'student1@example.com'],
    ['email' => 'student2@example.com'],
];
$subject = 'Important Notification';
$message = 'This is a reminder about the upcoming exam.';
sendEmailNotifications($students, $subject, $message);

?>