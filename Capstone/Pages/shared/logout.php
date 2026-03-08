<?php
require_once '../../config/db_config.php';

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page
header('Location: ../student/login.php');
exit();
?>