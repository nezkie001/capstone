<?php
// Use native mail() for simplicity. For production, use PHPMailer.
function sendNotificationEmail($to, $name, $subject, $message) {
    $from = "noreply@schoolregistrar.edu.ph";
    $headers = [
        "From: $from",
        "Reply-To: $from",
        "Content-Type: text/html; charset=UTF-8"
    ];

    $html_message = "
    <html>
    <head><title>$subject</title></head>
    <body>
        <h3>Hello $name,</h3>
        <p>$message</p>
        <p>Best regards,<br>School Registrar System</p>
    </body>
    </html>";

    return mail($to, $subject, $html_message, implode("\r\n", $headers));
}