<?php include 'includes/db_connect.php'; 

session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Viewer - Select Database</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">← Back to Homepage</a>
        <h1>Select Database</h1>
        <div class="database-grid">
            <a href="tables.php?db=mdrrjvhm_2023_records" class="database-card">
                <h2>2023 Records</h2>
                <p>View 2023 data records</p>
            </a>
            <a href="tables.php?db=mdrrjvhm_2024_records" class="database-card">
                <h2>2024 Records</h2>
                <p>View 2024 data records</p>
            </a>
            <a href="tables.php?db=mdrrjvhm_2025_records" class="database-card">
                <h2>2025 Records</h2>
                <p>View 2025 data records</p>
            </a>
            <a href="tables.php?db=mdrrjvhm_user_registration" class="database-card">
                <h2>User Registration</h2>
                <p>View user registration data</p>
            </a>
        </div>
    </div>
</body>
</html>