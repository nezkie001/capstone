<?php
session_start();

if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'student') {
        header('Location: pages/student/dashboard.php');
    } elseif ($_SESSION['user_type'] === 'teacher') {
        header('Location: pages/teacher/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Registrar - Document Request System</title>
    <link rel="stylesheet" href="http://localhost/Capstone/css/style.css">
    <link rel="stylesheet" href="http://localhost/Capstone/css/responsive.css">
</head>
<body>
    <div class="container-landing">
        <nav class="navbar">
            <div class="nav-brand">
                <h1>📚 School Registrar</h1>
            </div>
            <div class="nav-links">
                <a href="#features" class="nav-link">Features</a>
                <a href="#about" class="nav-link">About</a>
            </div>
        </nav>

        <header class="hero">
            <div class="hero-content">
                <h2>Document Request System</h2>
                <p>Simple, Fast, and Secure Document Management for Students and Teachers</p>
                <div class="hero-buttons">
                    <a href="http://localhost/Capstone/Pages/student/login.php" class="btn btn-primary">Student Portal</a>
                    <a href="http://localhost/Capstone/Pages/teacher/login.php" class="btn btn-secondary">Teacher Portal</a>
                </div>
            </div>
        </header>

        <section id="features" class="features">
            <h2>Key Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📄</div>
                    <h3>Request Documents</h3>
                    <p>Students can easily request official documents like PSA, Form 137, and TOR</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⏱️</div>
                    <h3>Real-time Status</h3>
                    <p>Track your document requests in real-time with instant notifications</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3>Teacher Management</h3>
                    <p>Teachers can manage requests, schedule pickups, and send notifications</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📧</div>
                    <h3>Email Notifications</h3>
                    <p>Get notified via email about document status and pickup schedules</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Reports & History</h3>
                    <p>Generate detailed reports and view complete transaction history</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Private</h3>
                    <p>Your data is protected with secure authentication and encryption</p>
                </div>
            </div>
        </section>

        <section id="about" class="about">
            <h2>How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Login/Sign Up</h3>
                    <p>Create your account or log in with your credentials</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Request Document</h3>
                    <p>Browse available documents and submit your request</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Get Notified</h3>
                    <p>Receive email updates about your request status</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Pick Up</h3>
                    <p>Collect your document at the scheduled time</p>
                </div>
            </div>
        </section>

        <footer class="footer">
            <p>&copy; 2026 School Registrar Document Request System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>