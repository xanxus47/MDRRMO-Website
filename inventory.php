<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'mdrrjvhm_xanxus47'); // Change to your MySQL username
define('DB_PASSWORD', 'oneLASTsong32'); // Change to your MySQL password
define('DB_NAME', 'mdrrjvhm_food_inventory');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch transaction history
$sql = "SELECT t.*, i.name as item_name 
        FROM transactions t 
        JOIN inventory_items i ON t.item_id = i.id 
        ORDER BY t.created_at DESC 
        LIMIT 50";
$result = $conn->query($sql);

session_start();

// Handle transaction history export or display
if (isset($_GET['get_history'])) {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($_GET['get_history'] == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transaction_history_' . date('Y-m-d') . '.csv"');
        
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, [
            'Transaction Date',
            'Item Name', 
            'Category', 
            'Transaction Type', 
            'Quantity', 
            'User', 
            'Names'
        ], ',', '"');
        
        $historySql = "SELECT t.*, i.name as item_name, i.category 
                      FROM transactions t 
                      JOIN inventory_items i ON t.item_id = i.id 
                      ORDER BY t.created_at DESC";
        
        $historyResult = $conn->query($historySql);
        
        if ($historyResult && $historyResult->num_rows > 0) {
            while($row = $historyResult->fetch_assoc()) {
                $cleanData = array_map(function($value) {
                    return str_replace(["\r", "\n"], ' ', trim($value));
                }, [
                    $row['created_at'],
                    $row['item_name'],
                    $row['category'],
                    $row['type'] == 'in' ? 'Stock In' : 'Stock Out',
                    $row['quantity'],
                    $row['user'],
                    $row['names']
                ]);
                
                fputcsv($output, $cleanData, ',', '"');
            }
        } else {
            fputcsv($output, ['Info', 'No transaction history found', '', '', '', '', ''], ',', '"');
        }
        
        fclose($output);
        exit();
    } else {
        $historySql = "SELECT t.*, i.name as item_name, i.category 
                      FROM transactions t 
                      JOIN inventory_items i ON t.item_id = i.id 
                      ORDER BY t.created_at DESC";
        $historyResult = $conn->query($historySql);
        
        if ($historyResult->num_rows > 0) {
            echo '<table style="width:100%; border-collapse:collapse;">';
            echo '<thead><tr>
                    <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Date</th>
                    <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Item</th>
                    <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Category</th>
                    <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Type</th>
                    <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Quantity</th>
                    <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">User</th>
                    <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Names</th>
                  </tr></thead>';
            echo '<tbody>';
            
            while($row = $historyResult->fetch_assoc()) {
                echo '<tr>';
                echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['created_at']) . '</td>';
                echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['item_name']) . '</td>';
                echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['category']) . '</td>';
                echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . ($row['type'] == 'in' ? '➕ Stock In' : '➖ Stock Out') . '</td>';
                echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['quantity']) . '</td>';
                echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['user']) . '</td>';
                echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['names']) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>No transaction history found.</p>';
        }
        
        exit();
    }
}

// Handle CSV export for inventory
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.csv"');
    
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Item Name', 'Category', 'Quantity', 'Unit', 'Expiration Date', 'Status']);
    
    $sql = "SELECT * FROM inventory_items WHERE 1=1";
    $exportSql = $sql;
    
    $searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $categoryFilter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
    $statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
    $quantityFilter = isset($_GET['quantity']) ? $conn->real_escape_string($_GET['quantity']) : '';
    $startDate = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';
    
    if (!empty($searchQuery)) {
        $exportSql .= " AND (name LIKE '%$searchQuery%' OR category LIKE '%$searchQuery%')";
    }
    
    if (!empty($categoryFilter)) {
        $exportSql .= " AND category = '$categoryFilter'";
    }
    
    if (!empty($statusFilter)) {
        $today = date('Y-m-d');
        if ($statusFilter === 'expired') {
            $exportSql .= " AND expiration_date IS NOT NULL AND expiration_date < '$today'";
        } elseif ($statusFilter === 'expiring') {
            $nextWeek = date('Y-m-d', strtotime('+7 days'));
            $exportSql .= " AND expiration_date IS NOT NULL AND expiration_date BETWEEN '$today' AND '$nextWeek'";
        } elseif ($statusFilter === 'fresh') {
            $nextWeek = date('Y-m-d', strtotime('+7 days'));
            $exportSql .= " AND (expiration_date IS NULL OR expiration_date > '$nextWeek')";
        } elseif ($statusFilter === 'na') {
            $exportSql .= " AND expiration_date IS NULL";
        }
    }
    
    if (!empty($quantityFilter)) {
        $thresholds = [
            'Food Inventory' => ['low' => 10, 'medium' => 15],
            'Admin Office Inventory' => ['low' => 10, 'medium' => 20],
            'Planning Office Inventory' => ['low' => 10, 'medium' => 20],
            'Operation Inventory' => ['low' => 10, 'medium' => 20],
            'Construction Materials' => ['low' => 10, 'medium' => 20],
            'Family Kit' => ['low' => 10, 'medium' => 20],
            'Hygiene Kit' => ['low' => 10, 'medium' => 20]
        ];
        
        $conditions = [];
        foreach ($thresholds as $cat => $levels) {
            if ($quantityFilter === 'low') {
                $conditions[] = "(category = '$cat' AND quantity <= {$levels['low']})";
            } elseif ($quantityFilter === 'medium') {
                $conditions[] = "(category = '$cat' AND quantity > {$levels['low']} AND quantity <= {$levels['medium']})";
            } elseif ($quantityFilter === 'high') {
                $conditions[] = "(category = '$cat' AND quantity > {$levels['medium']})";
            }
        }
        
        if (!empty($conditions)) {
            $exportSql .= " AND (" . implode(" OR ", $conditions) . ")";
        }
    }
    
    if (!empty($startDate) && !empty($endDate)) {
        $exportSql .= " AND (expiration_date IS NULL OR expiration_date BETWEEN '$startDate' AND '$endDate')";
    } elseif (!empty($startDate)) {
        $exportSql .= " AND (expiration_date IS NULL OR expiration_date >= '$startDate')";
    } elseif (!empty($endDate)) {
        $exportSql .= " AND (expiration_date IS NULL OR expiration_date <= '$endDate')";
    }
    
    $exportResult = $conn->query($exportSql);
    
    if ($exportResult && $exportResult->num_rows > 0) {
        while($row = $exportResult->fetch_assoc()) {
            $status = 'N/A';
            if ($row['expiration_date']) {
                $today = new DateTime();
                $expDate = new DateTime($row['expiration_date']);
                $diff = $today->diff($expDate);
                
                if ($today > $expDate) {
                    $status = 'Expired';
                } elseif ($diff->days <= 7 && $diff->invert == 0) {
                    $status = 'Expiring Soon';
                } else {
                    $status = 'Fresh';
                }
            }
            
            fputcsv($output, [
                $row['name'],
                $row['category'],
                $row['quantity'],
                $row['unit'],
                $row['expiration_date'] ? $row['expiration_date'] : 'N/A',
                $status
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'inventory_items';
    
    try {
        switch ($action) {
            case 'add':
            case 'edit':
                $name = $conn->real_escape_string(trim($_POST['name']));
                $category = $conn->real_escape_string(trim($_POST['category']));
                $quantity = intval($_POST['quantity']);
                $unit = $conn->real_escape_string(trim($_POST['unit']));
                $expiration_date = !empty($_POST['expiration_date']) ? "'" . $conn->real_escape_string(trim($_POST['expiration_date'])) . "'" : "NULL";
                
                if (empty($name) || empty($category) || empty($unit) || $expiration_date === "NULL") {
                    throw new Exception("All fields are required");
                }
                
                if ($quantity <= 0) {
                    throw new Exception("Quantity must be positive");
                }
                
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO inventory_items (name, category, quantity, unit, expiration_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssiss", $name, $category, $quantity, $unit, $expiration_date);
                } else {
                    $id = intval($_POST['id']);
                    $stmt = $conn->prepare("UPDATE inventory_items SET name=?, category=?, quantity=?, unit=?, expiration_date=? WHERE id=?");
                    $stmt->bind_param("ssissi", $name, $category, $quantity, $unit, $expiration_date, $id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Error saving item: " . $stmt->error);
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id=?");
                $stmt->bind_param("i", $id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error deleting item: " . $stmt->error);
                }
                break;
                
            case 'record_transaction':
                $item_id = intval($_POST['item_id']);
                $type = $conn->real_escape_string($_POST['type']);
                $quantity = intval($_POST['quantity']);
                $names = $conn->real_escape_string($_POST['names']);
                $user = $conn->real_escape_string($_SESSION['username'] ?? 'System');
                
                $sql = "INSERT INTO transactions (item_id, type, quantity, user, names) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isiis", $item_id, $type, $quantity, $user, $names);
                
                if ($stmt->execute()) {
                    $operator = $type === 'in' ? '+' : '-';
                    $updateSql = "UPDATE inventory_items SET quantity = quantity $operator ? WHERE id = ?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("ii", $quantity, $item_id);
                    $stmt->execute();
                } else {
                    throw new Exception("Error recording transaction: " . $conn->error);
                }
                break;
        }
        
        header("Location: index.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Initialize pagination variables
$recordsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $recordsPerPage;

$sql = "SELECT * FROM inventory_items WHERE 1=1";
$params = [];
$types = "";
$searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$quantityFilter = isset($_GET['quantity']) ? $conn->real_escape_string($_GET['quantity']) : '';
$startDate = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

if (!empty($searchQuery)) {
    $sql .= " AND (name LIKE ? OR category LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $types .= "ss";
}

if (!empty($categoryFilter)) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

if (!empty($statusFilter)) {
    $today = date('Y-m-d');
    if ($statusFilter === 'expired') {
        $sql .= " AND expiration_date IS NOT NULL AND expiration_date < ?";
        $params[] = $today;
        $types .= "s";
    } elseif ($statusFilter === 'expiring') {
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $sql .= " AND expiration_date IS NOT NULL AND expiration_date BETWEEN ? AND ?";
        $params[] = $today;
        $params[] = $nextWeek;
        $types .= "ss";
    } elseif ($statusFilter === 'fresh') {
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $sql .= " AND (expiration_date IS NULL OR expiration_date > ?)";
        $params[] = $nextWeek;
        $types .= "s";
    } elseif ($statusFilter === 'na') {
        $sql .= " AND expiration_date IS NULL";
    }
}

if (!empty($quantityFilter)) {
    $thresholds = [
        'Food Inventory' => ['low' => 10, 'medium' => 15],
        'Admin Office Inventory' => ['low' => 10, 'medium' => 20],
        'Planning Office Inventory' => ['low' => 10, 'medium' => 20],
        'Operation Inventory' => ['low' => 10, 'medium' => 20],
        'Construction Materials' => ['low' => 10, 'medium' => 20],
        'Family Kit' => ['low' => 10, 'medium' => 20],
        'Hygiene Kit' => ['low' => 10, 'medium' => 20]
    ];
    
    $conditions = [];
    foreach ($thresholds as $cat => $levels) {
        if ($quantityFilter === 'low') {
            $conditions[] = "(category = ? AND quantity <= ?)";
            $params[] = $cat;
            $params[] = $levels['low'];
            $types .= "sd";
        } elseif ($quantityFilter === 'medium') {
            $conditions[] = "(category = ? AND quantity > ? AND quantity <= ?)";
            $params[] = $cat;
            $params[] = $levels['low'];
            $params[] = $levels['medium'];
            $types .= "sdd";
        } elseif ($quantityFilter === 'high') {
            $conditions[] = "(category = ? AND quantity > ?)";
            $params[] = $cat;
            $params[] = $levels['medium'];
            $types .= "sd";
        }
    }
    
    if (!empty($conditions)) {
        $sql .= " AND (" . implode(" OR ", $conditions) . ")";
    }
}

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND (expiration_date IS NULL OR expiration_date BETWEEN ? AND ?)";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
} elseif (!empty($startDate)) {
    $sql .= " AND (expiration_date IS NULL OR expiration_date >= ?)";
    $params[] = $startDate;
    $types .= "s";
} elseif (!empty($endDate)) {
    $sql .= " AND (expiration_date IS NULL OR expiration_date <= ?)";
    $params[] = $endDate;
    $types .= "s";
}

$totalSql = $sql;
$sql .= " LIMIT ? OFFSET ?";
$params[] = $recordsPerPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$totalStmt = $conn->prepare($totalSql);
if (!empty($params)) {
    $totalStmt->bind_param($types, ...$params);
}
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalItems = $totalResult->num_rows;
$totalPages = ceil($totalItems / $recordsPerPage);

// Calculate stats
$stats = [
    'totalItems' => 0,
    'freshItems' => 0,
    'expiringItems' => 0,
    'expiredItems' => 0,
    'naItems' => 0
];

$statSql = "SELECT expiration_date FROM inventory_items WHERE 1=1" . (empty($params) ? "" : " AND " . substr($sql, strpos($sql, "WHERE 1=1") + 9, strpos($sql, "LIMIT") - strpos($sql, "WHERE 1=1") - 9));
$statStmt = $conn->prepare($statSql);
if (!empty($params)) {
    $statStmt->bind_param($types, ...$params);
}
$statStmt->execute();
$statResult = $statStmt->get_result();

while ($row = $statResult->fetch_assoc()) {
    $stats['totalItems']++;
    $expiration = $row['expiration_date'];
    if ($expiration) {
        $today = new DateTime();
        $expDate = new DateTime($expiration);
        $diff = $today->diff($expDate);
        if ($today > $expDate) {
            $stats['expiredItems']++;
        } elseif ($diff->days <= 7 && $diff->invert == 0) {
            $stats['expiringItems']++;
        } else {
            $stats['freshItems']++;
        }
    } else {
        $stats['naItems']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <style>
        :root {
            --bg: #f3f4f6;
            --text: #111827;
            --border: #e5e7eb;
            --primary: #3b82f6;
            --secondary: #10b981;
        }
        [data-theme="dark"] {
            --bg: #1f2937;
            --text: #f9fafb;
            --border: #374151;
            --primary: #60a5fa;
            --secondary: #34d399;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 1rem;
            background-color: var(--bg);
            color: var(--text);
            transition: all 0.3s;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .stat-card {
            background-color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border: 1px solid var(--border);
        }
        th, td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }
        th {
            background-color: #f9fafb;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 0.25rem;
        }
        button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            background-color: var(--primary);
            color: white;
        }
        button.secondary {
            background-color: var(--secondary);
        }
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        #loadingOverlay.show {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Inventory Management</h1>
            <button id="themeToggle">Toggle Theme</button>
            <div id="currentDateTime"></div>
        </div>
        <div class="stats">
            <div class="stat-card">Total Items: <span id="totalItems">0</span></div>
            <div class="stat-card">Fresh: <span id="freshItems">0</span></div>
            <div class="stat-card">Expiring Soon: <span id="expiringItems">0</span></div>
            <div class="stat-card">Expired: <span id="expiredItems">0</span></div>
            <div class="stat-card">N/A: <span id="naItems">0</span></div>
        </div>
        <form id="filterForm" method="get">
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <input type="text" id="searchInput" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="form-group">
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <option value="Food Inventory" <?php echo $categoryFilter === 'Food Inventory' ? 'selected' : ''; ?>>Food Inventory</option>
                        <option value="Admin Office Inventory" <?php echo $categoryFilter === 'Admin Office Inventory' ? 'selected' : ''; ?>>Admin Office Inventory</option>
                        <option value="Planning Office Inventory" <?php echo $categoryFilter === 'Planning Office Inventory' ? 'selected' : ''; ?>>Planning Office Inventory</option>
                        <option value="Operation Inventory" <?php echo $categoryFilter === 'Operation Inventory' ? 'selected' : ''; ?>>Operation Inventory</option>
                        <option value="Construction Materials" <?php echo $categoryFilter === 'Construction Materials' ? 'selected' : ''; ?>>Construction Materials</option>
                        <option value="Family Kit" <?php echo $categoryFilter === 'Family Kit' ? 'selected' : ''; ?>>Family Kit</option>
                        <option value="Hygiene Kit" <?php echo $categoryFilter === 'Hygiene Kit' ? 'selected' : ''; ?>>Hygiene Kit</option>
                    </select>
                </div>
                <div class="form-group">
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="fresh" <?php echo $statusFilter === 'fresh' ? 'selected' : ''; ?>>Fresh</option>
                        <option value="expiring" <?php echo $statusFilter === 'expiring' ? 'selected' : ''; ?>>Expiring Soon</option>
                        <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="na" <?php echo $statusFilter === 'na' ? 'selected' : ''; ?>>N/A</option>
                    </select>
                </div>
                <div class="form-group">
                    <select name="quantity" class="filter-select">
                        <option value="">All Quantities</option>
                        <option value="low" <?php echo $quantityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $quantityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $quantityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="date" name="start_date" class="date-input" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="form-group">
                    <input type="date" name="end_date" class="date-input" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <button type="submit">Filter</button>
                <button type="button" id="exportBtn">Export to CSV</button>
                <button type="button" onclick="openModal()">Add Item</button>
                <button type="button" onclick="openHistoryModal()">View History</button>
            </div>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Expiration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $status = 'N/A';
                        if ($row['expiration_date']) {
                            $today = new DateTime();
                            $expDate = new DateTime($row['expiration_date']);
                            $diff = $today->diff($expDate);
                            if ($today > $expDate) {
                                $status = 'Expired';
                            } elseif ($diff->days <= 7 && $diff->invert == 0) {
                                $status = 'Expiring Soon';
                            } else {
                                $status = 'Fresh';
                            }
                        }
                        echo '<tr data-id="' . $row['id'] . '">';
                        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                        echo '<td>' . $row['quantity'] . ' ' . htmlspecialchars($row['unit']) . '</td>';
                        echo '<td>' . ($row['expiration_date'] ? date('M j, Y', strtotime($row['expiration_date'])) : 'N/A') . ' (' . $status . ')</td>';
                        echo '<td>
                                <button onclick="openModal(' . $row['id'] . ')">Edit</button>
                                <button onclick="openRecordTransactionModal(' . $row['id'] . ', \'' . addslashes($row['name']) . '\', \'' . addslashes($row['category']) . '\', ' . $row['quantity'] . ', \'' . ($row['expiration_date'] ? addslashes($row['expiration_date']) : 'N/A') . '\')">Record Transaction</button>
                                <button onclick="deleteItem(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')">Delete</button>
                              </td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No items found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <div id="paginationInfo">
            <?php
            echo "Page $page of $totalPages | Total: $totalItems items";
            if ($page > 1) {
                echo ' <a href="?page=' . ($page - 1) . '&' . http_build_query($_GET) . '">Previous</a>';
            }
            if ($page < $totalPages) {
                echo ' <a href="?page=' . ($page + 1) . '&' . http_build_query($_GET) . '">Next</a>';
            }
            ?>
        </div>
    </div>

    <!-- Item Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle"></h2>
            <form id="itemForm" method="post">
                <input type="hidden" id="formAction" name="action">
                <input type="hidden" id="itemId" name="id">
                <div class="form-group">
                    <label for="itemName">Item Name</label>
                    <input type="text" id="itemName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="itemCategory">Category</label>
                    <select id="itemCategory" name="category" required>
                        <option value="Food Inventory">Food Inventory</option>
                        <option value="Admin Office Inventory">Admin Office Inventory</option>
                        <option value="Planning Office Inventory">Planning Office Inventory</option>
                        <option value="Operation Inventory">Operation Inventory</option>
                        <option value="Construction Materials">Construction Materials</option>
                        <option value="Family Kit">Family Kit</option>
                        <option value="Hygiene Kit">Hygiene Kit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="itemQuantity">Quantity</label>
                    <input type="number" id="itemQuantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="itemUnit">Unit</label>
                    <input type="text" id="itemUnit" name="unit" required>
                </div>
                <div class="form-group">
                    <label for="itemExpiration">Expiration Date</label>
                    <input type="date" id="itemExpiration" name="expiration_date">
                    <label><input type="checkbox" id="noExpiration" onclick="toggleExpirationDate()"> No Expiration</label>
                </div>
                <button type="submit">Save</button>
                <button type="button" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <h2>Transaction History</h2>
            <button type="button" onclick="exportHistoryToCSV()">Export to CSV</button>
            <div id="historyContent"></div>
            <button type="button" onclick="closeHistoryModal()">Close</button>
        </div>
    </div>

    <!-- Record Transaction Modal -->
    <div id="recordTransactionModal" class="modal">
        <div class="modal-content">
            <h2 id="transactionModalTitle"></h2>
            <form method="post">
                <input type="hidden" id="transactionItemId" name="item_id">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" id="transactionItemName" name="item_name" readonly>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" id="transactionItemCategory" name="category" readonly>
                </div>
                <div class="form-group">
                    <label>Current Quantity</label>
                    <input type="text" id="transactionItemQuantity" name="quantity" readonly>
                </div>
                <div class="form-group">
                    <label>Expiration Date</label>
                    <input type="text" id="transactionItemExpiration" name="expiration_date" readonly>
                </div>
                <div class="form-group">
                    <label>Transaction Type</label>
                    <select name="type" required>
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" id="transactionQuantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" id="transactionNotes" name="names">
                </div>
                <button type="submit" name="action" value="record_transaction">Record</button>
                <button type="button" onclick="closeRecordTransactionModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div>Loading...</div>
    </div>

    <script>
        function initTheme() {
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        function performSearch(query) {
            const params = new URLSearchParams(window.location.search);
            params.set('search', query);
            window.location.search = params.toString();
        }

        function setupAutoSearch() {
            const searchInput = document.getElementById('searchInput');
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => performSearch(this.value.trim()), 300);
            });
        }

        function openModal(itemId = null) {
            const modal = document.getElementById('itemModal');
            const modalTitle = document.getElementById('modalTitle');
            
            if (itemId) {
                modalTitle.textContent = 'Edit Item';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('itemId').value = itemId;
                
                const row = document.querySelector(`tr[data-id="${itemId}"]`);
                if (row) {
                    document.getElementById('itemName').value = row.cells[0].textContent.trim();
                    document.getElementById('itemCategory').value = row.cells[1].textContent.trim();
                    
                    const quantityText = row.cells[2].textContent.trim();
                    const quantityMatch = quantityText.match(/(\d+)/);
                    if (quantityMatch) {
                        document.getElementById('itemQuantity').value = quantityMatch[1];
                    }
                    
                    const unitMatch = quantityText.match(/\d+\s+(.+)/);
                    if (unitMatch) {
                        document.getElementById('itemUnit').value = unitMatch[1];
                    }
                    
                    const dateText = row.cells[3].textContent.trim().replace(/ \(.*\)/, '');
                    if (dateText === 'N/A') {
                        document.getElementById('noExpiration').checked = true;
                        document.getElementById('itemExpiration').disabled = true;
                        document.getElementById('itemExpiration').value = '';
                    } else {
                        const dateParts = dateText.split(/[\s,]+/);
                        const months = {
                            'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06',
                            'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
                        };
                        const formattedDate = `${dateParts[2]}-${months[dateParts[0]]}-${dateParts[1].padStart(2, '0')}`;
                        document.getElementById('itemExpiration').value = formattedDate;
                        document.getElementById('noExpiration').checked = false;
                        document.getElementById('itemExpiration').disabled = false;
                    }
                }
            } else {
                modalTitle.textContent = 'Add New Item';
                document.getElementById('formAction').value = 'add';
                document.getElementById('itemId').value = '';
                document.getElementById('itemForm').reset();
                document.getElementById('noExpiration').checked = false;
                document.getElementById('itemExpiration').disabled = false;
            }
        
            modal.style.display = 'flex';
        }

        function toggleExpirationDate() {
            const expirationInput = document.getElementById('itemExpiration');
            if (document.getElementById('noExpiration').checked) {
                expirationInput.disabled = true;
                expirationInput.value = '';
            } else {
                expirationInput.disabled = false;
            }
        }

        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
        }

        function openHistoryModal() {
            const modal = document.getElementById('historyModal');
            modal.style.display = 'flex';
            document.getElementById('historyContent').innerHTML = '<p>Loading transaction history...</p>';
            
            fetch('index.php?get_history=1')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('historyContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('historyContent').innerHTML = `<p>Error loading history: ${error}</p>`;
                });
        }

        function closeHistoryModal() {
            document.getElementById('historyModal').style.display = 'none';
        }

        function exportHistoryToCSV() {
            window.location.href = 'index.php?get_history=csv';
        }

        function openRecordTransactionModal(itemId, itemName, itemCategory, itemQuantity, expirationDate) {
            const modal = document.getElementById('recordTransactionModal');
            document.getElementById('transactionModalTitle').textContent = `Record Transaction for ${itemName}`;
            document.getElementById('transactionItemId').value = itemId;
            document.getElementById('transactionItemName').value = itemName;
            document.getElementById('transactionItemCategory').value = itemCategory;
            document.getElementById('transactionItemQuantity').value = itemQuantity;
            document.getElementById('transactionItemExpiration').value = expirationDate;
            document.getElementById('transactionQuantity').value = 1;
            document.getElementById('transactionNotes').value = '';
            modal.style.display = 'flex';
        }

        function closeRecordTransactionModal() {
            document.getElementById('recordTransactionModal').style.display = 'none';
        }

        function deleteItem(itemId, itemName) {
            if (confirm(`Are you sure you want to delete "${itemName}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = itemId;
                form.appendChild(idInput);
                
                const searchInput = document.createElement('input');
                searchInput.type = 'hidden';
                searchInput.name = 'search';
                searchInput.value = '<?php echo htmlspecialchars($searchQuery); ?>';
                form.appendChild(searchInput);
                
                const categoryInput = document.createElement('input');
                categoryInput.type = 'hidden';
                categoryInput.name = 'category';
                categoryInput.value = '<?php echo htmlspecialchars($categoryFilter); ?>';
                form.appendChild(categoryInput);
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = '<?php echo htmlspecialchars($statusFilter); ?>';
                form.appendChild(statusInput);
                
                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = 'quantity';
                quantityInput.value = '<?php echo htmlspecialchars($quantityFilter); ?>';
                form.appendChild(quantityInput);
                
                const startDateInput = document.createElement('input');
                startDateInput.type = 'hidden';
                startDateInput.name = 'start_date';
                startDateInput.value = '<?php echo htmlspecialchars($startDate); ?>';
                form.appendChild(startDateInput);
                
                const endDateInput = document.createElement('input');
                endDateInput.type = 'hidden';
                endDateInput.name = 'end_date';
                endDateInput.value = '<?php echo htmlspecialchars($endDate); ?>';
                form.appendChild(endDateInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('exportBtn').addEventListener('click', function() {
            const params = new URLSearchParams();
            params.append('export', 'csv');
            
            if ('<?php echo $searchQuery; ?>') {
                params.append('search', '<?php echo $searchQuery; ?>');
            }
            if ('<?php echo $categoryFilter; ?>') {
                params.append('category', '<?php echo $categoryFilter; ?>');
            }
            if ('<?php echo $statusFilter; ?>') {
                params.append('status', '<?php echo $statusFilter; ?>');
            }
            if ('<?php echo $quantityFilter; ?>') {
                params.append('quantity', '<?php echo $quantityFilter; ?>');
            }
            if ('<?php echo $startDate; ?>') {
                params.append('start_date', '<?php echo $startDate; ?>');
            }
            if ('<?php echo $endDate; ?>') {
                params.append('end_date', '<?php echo $endDate; ?>');
            }
            
            window.location.href = 'index.php?' + params.toString();
        });

        initTheme();
        document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        setupAutoSearch();

        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch(document.getElementById('searchInput').value.trim());
        });

        document.querySelectorAll('.filter-select, .date-input').forEach(element => {
            element.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value.trim());
            }
        });

        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Initialize stats
        document.getElementById('totalItems').textContent = '<?php echo $stats['totalItems']; ?>';
        document.getElementById('freshItems').textContent = '<?php echo $stats['freshItems']; ?>';
        document.getElementById('expiringItems').textContent = '<?php echo $stats['expiringItems']; ?>';
        document.getElementById('expiredItems').textContent = '<?php echo $stats['expiredItems']; ?>';
        document.getElementById('naItems').textContent = '<?php echo $stats['naItems']; ?>';
    </script>
</body>
</html>