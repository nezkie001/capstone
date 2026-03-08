<nav class="navbar">
    <div class="nav-brand">
        <h3>Teacher Portal</h3>
    </div>
    <ul class="nav-menu">
        <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
        <li><a href="student-search.php" <?php echo basename($_SERVER['PHP_SELF']) == 'student-search.php' ? 'class="active"' : ''; ?>>Students</a></li>
        <li><a href="view-requests.php" <?php echo basename($_SERVER['PHP_SELF']) == 'view-requests.php' ? 'class="active"' : ''; ?>>Requests</a></li>
        <li><a href="schedule-pickup.php" <?php echo basename($_SERVER['PHP_SELF']) == 'schedule-pickup.php' ? 'class="active"' : ''; ?>>Schedule</a></li>
        <li><a href="send-notifications.php" <?php echo basename($_SERVER['PHP_SELF']) == 'send-notifications.php' ? 'class="active"' : ''; ?>>Notifications</a></li>
        <li><a href="transaction-report.php" <?php echo basename($_SERVER['PHP_SELF']) == 'transaction-report.php' ? 'class="active"' : ''; ?>>Reports</a></li>
        <?php if($_SESSION['teacher_role'] == 'admin' || $_SESSION['teacher_role'] == 'registrar'): ?>
        <li><a href="manage-teachers.php" <?php echo basename($_SERVER['PHP_SELF']) == 'manage-teachers.php' ? 'class="active"' : ''; ?>>Manage Teachers</a></li>
        <?php endif; ?>
        <li><a href="../shared/logout.php" class="btn-logout">Logout</a></li>
    </ul>
    <div class="nav-user">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
        <span class="role-badge"><?php echo ucfirst($_SESSION['teacher_role']); ?></span>
    </div>
</nav>
