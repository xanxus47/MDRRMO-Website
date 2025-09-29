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

if (!isset($_GET['db']) || !isset($_GET['table'])) {
    header("Location: index.php");
    exit();
}

$dbName = $_GET['db'];
$tableName = $_GET['table'];
$pdo = connectToDatabase($dbName);

// Get table structure
$stmt = $pdo->query("DESCRIBE $tableName");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get records (with pagination)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$totalStmt = $pdo->query("SELECT COUNT(*) FROM $tableName");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->query("SELECT * FROM $tableName LIMIT $perPage OFFSET $offset");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Viewer - <?php echo htmlspecialchars($tableName); ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="container">
        <a href="tables.php?db=<?php echo urlencode($dbName); ?>" class="back-btn">← Back to Tables</a>
        <h1>
            Database: <?php echo htmlspecialchars($dbName); ?> 
            <span class="table-name">/ <?php echo htmlspecialchars($tableName); ?></span>
        </h1>
        
        <div class="record-info">
            <span>Total Records: <?php echo number_format($total); ?></span>
            <span>Showing: <?php echo min($perPage, $total - $offset); ?> records</span>
        </div>
        
        <input type="text" id="record-search" placeholder="Search records..." class="search-box">
        
        <div class="table-container">
            <table id="records-table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th><?php echo htmlspecialchars($column); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <td><?php echo htmlspecialchars($record[$column] ?? 'NULL'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="records.php?db=<?php echo urlencode($dbName); ?>&table=<?php echo urlencode($tableName); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>
                
                <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="records.php?db=<?php echo urlencode($dbName); ?>&table=<?php echo urlencode($tableName); ?>&page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('record-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#records-table tbody tr');
            
            rows.forEach(row => {
                let rowText = '';
                row.querySelectorAll('td').forEach(cell => {
                    rowText += cell.textContent.toLowerCase() + ' ';
                });
                
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>