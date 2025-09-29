<?php
include 'includes/db_connect.php';

session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['db'])) {
    header("Location: index.php");
    exit();
}

$dbName = $_GET['db'];
$pdo = connectToDatabase($dbName);

// Get all tables in the database
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Viewer - Select Table</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">← Back to Databases</a>
        <h1>Select Table from <?php echo htmlspecialchars($dbName); ?></h1>
        
        <input type="text" id="table-search" placeholder="Search tables..." class="search-box">
        
        <div class="table-grid" id="table-list">
            <?php foreach ($tables as $table): ?>
                <a href="records.php?db=<?php echo urlencode($dbName); ?>&table=<?php echo urlencode($table); ?>" class="table-card">
                    <h2><?php echo htmlspecialchars($table); ?></h2>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.getElementById('table-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const tables = document.querySelectorAll('.table-card');
            
            tables.forEach(table => {
                const tableName = table.querySelector('h2').textContent.toLowerCase();
                if (tableName.includes(searchTerm)) {
                    table.style.display = 'block';
                } else {
                    table.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>