<?php
require_once 'config.php';
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


// Initialize pagination variables
$recordsPerPage = 10;
$offset = 0;
$sql = "SELECT * FROM inventory_items WHERE 1=1";

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.csv"');
    
    // Add UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Write CSV header
    fputcsv($output, ['Item Name', 'Category', 'Quantity', 'Unit', 'Expiration Date', 'Status']);
    
    // Get all filtered items without pagination
    $exportSql = $sql;
    
    // Apply filters if they exist
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
    
    // Execute the query
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

// Handle transaction history export
if (isset($_GET['get_history'])) {
    if ($_GET['get_history'] == 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transaction_history_' . date('Y-m-d') . '.csv"');
        
        // Add UTF-8 BOM for Excel compatibility
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Write CSV header with Excel-friendly formatting
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
        
        if (!$historyResult) {
            error_log("Database error: " . $conn->error);
            fputcsv($output, ['Error', 'Failed to retrieve transaction history', '', '', '', '', ''], ',', '"');
        } 
        elseif ($historyResult->num_rows > 0) {
            while($row = $historyResult->fetch_assoc()) {
                // Clean data for CSV
                $cleanData = array_map(function($value) {
                    // Remove any line breaks and extra spaces
                    $value = str_replace(["\r", "\n"], ' ', $value);
                    $value = trim($value);
                    return $value;
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
        // Display history in HTML
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $name = $conn->real_escape_string($_POST['name']);
                $category = $conn->real_escape_string($_POST['category']);
                $quantity = intval($_POST['quantity']);
                $unit = $conn->real_escape_string($_POST['unit']);
                $expiration_date = !empty($_POST['expiration_date']) ? "'" . $conn->real_escape_string($_POST['expiration_date']) . "'" : "NULL";
                
                if ($_POST['action'] === 'add') {
                    $sql = "INSERT INTO inventory_items (name, category, quantity, unit, expiration_date) 
                            VALUES ('$name', '$category', $quantity, '$unit', $expiration_date)";
                } else {
                    $id = intval($_POST['id']);
                    $sql = "UPDATE inventory_items SET 
                            name = '$name', 
                            category = '$category', 
                            quantity = $quantity, 
                            unit = '$unit', 
                            expiration_date = $expiration_date 
                            WHERE id = $id";
                }
                
                if ($conn->query($sql) === TRUE) {
                    header("Location: html.php");
                    exit();
                } else {
                    $error = "Error: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $sql = "DELETE FROM inventory_items WHERE id = $id";
                if ($conn->query($sql) === TRUE) {
                    header("Location: html.php");
                    exit();
                } else {
                    $error = "Error: " . $conn->error;
                }
                break;
                
            case 'record_transaction':
                $item_id = intval($_POST['item_id']);
                $type = $conn->real_escape_string($_POST['type']);
                $quantity = intval($_POST['quantity']);
                $names = $conn->real_escape_string($_POST['names']);
                $user = $conn->real_escape_string($_SESSION['username'] ?? 'System');
                
                // Record the transaction
                $sql = "INSERT INTO transactions (item_id, type, quantity, user, names) 
                        VALUES ($item_id, '$type', $quantity, '$user','$names')";
                
                if ($conn->query($sql) === TRUE) {
                    // Update inventory quantity
                    $operator = $type === 'in' ? '+' : '-';
                    $updateSql = "UPDATE inventory_items SET quantity = quantity $operator $quantity WHERE id = $item_id";
                    $conn->query($updateSql);
                    
                    header("Location: html.php");
                    exit();
                } else {
                    $error = "Error recording transaction: " . $conn->error;
                }
                break;
        }
    }
}

// Get filter parameters from GET request
$categoryFilter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$quantityFilter = isset($_GET['quantity']) ? $conn->real_escape_string($_GET['quantity']) : '';
$startDate = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';
$searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build SQL query with filters
$sql = "SELECT * FROM inventory_items WHERE 1=1";

if (!empty($searchQuery)) {
    $sql .= " AND (name LIKE '%$searchQuery%' OR category LIKE '%$searchQuery%')";
}

if (!empty($categoryFilter)) {
    $sql .= " AND category = '$categoryFilter'";
}

if (!empty($statusFilter)) {
    $today = date('Y-m-d');
    if ($statusFilter === 'expired') {
        $sql .= " AND expiration_date IS NOT NULL AND expiration_date < '$today'";
    } elseif ($statusFilter === 'expiring') {
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $sql .= " AND expiration_date IS NOT NULL AND expiration_date BETWEEN '$today' AND '$nextWeek'";
    } elseif ($statusFilter === 'fresh') {
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $sql .= " AND (expiration_date IS NULL OR expiration_date > '$nextWeek')";
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
            $conditions[] = "(category = '$cat' AND quantity <= {$levels['low']})";
        } elseif ($quantityFilter === 'medium') {
            $conditions[] = "(category = '$cat' AND quantity > {$levels['low']} AND quantity <= {$levels['medium']})";
        } elseif ($quantityFilter === 'high') {
            $conditions[] = "(category = '$cat' AND quantity > {$levels['medium']})";
        }
    }
    
    if (!empty($conditions)) {
        $sql .= " AND (" . implode(" OR ", $conditions) . ")";
    }
}

// Handle date range filter
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND (expiration_date IS NULL OR expiration_date BETWEEN '$startDate' AND '$endDate')";
} elseif (!empty($startDate)) {
    $sql .= " AND (expiration_date IS NULL OR expiration_date >= '$startDate')";
} elseif (!empty($endDate)) {
    $sql .= " AND (expiration_date IS NULL OR expiration_date <= '$endDate')";
}

// Get total records count for pagination
$totalRecordsQuery = str_replace('SELECT *', 'SELECT COUNT(*) as total', $sql);
$totalRecordsResult = $conn->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];

// Pagination settings
$recordsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;
$totalPages = ceil($totalRecords / $recordsPerPage);

// Add LIMIT to your main query
$sql .= " LIMIT $offset, $recordsPerPage";

// Get filtered items from database
$result = $conn->query($sql);
$items = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Calculate statistics (we need to run this on the full dataset, not just the paginated results)
$statsSql = str_replace(" LIMIT $offset, $recordsPerPage", "", $sql);
$statsResult = $conn->query($statsSql);
$allItemsForStats = [];
if ($statsResult->num_rows > 0) {
    while($row = $statsResult->fetch_assoc()) {
        $allItemsForStats[] = $row;
    }
}

$totalItems = count($allItemsForStats);
$freshItems = 0;
$expiringItems = 0;
$expiredItems = 0;
$naItems = 0;

$today = new DateTime();
$today->setTime(0, 0, 0);
$nextWeek = clone $today;
$nextWeek->modify('+7 days');

foreach ($allItemsForStats as $item) {
    if (!$item['expiration_date']) {
        $naItems++;
        continue;
    }
    
    $expDate = new DateTime($item['expiration_date']);
    $expDate->setTime(0, 0, 0);
    
    if ($expDate < $today) {
        $expiredItems++;
    } elseif ($expDate <= $nextWeek) {
        $expiringItems++;
    } else {
        $freshItems++;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDRRMO Inventory Management System</title>
    <style>
        :root {
            /* Light theme colors */
            --primary: #059669;
            --primary-dark: #047857;
            --secondary: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --na: #8b5cf6;
            --background: #f9fafb;
            --surface: #ffffff;
            --text: #111827;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        [data-theme="dark"] {
            /* Dark theme colors */
            --primary: #34d399;
            --primary-dark: #10b981;
            --secondary: #9ca3af;
            --success: #34d399;
            --warning: #fbbf24;
            --danger: #f87171;
            --info: #60a5fa;
            --na: #a78bfa;
            --background: #0f172a;
            --surface: #1e293b;
            --text: #f1f5f9;
            --text-light: #94a3b8;
            --border: #334155;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--background);
            color: var(--text);
            line-height: 1.6;
            transition: var(--transition);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.125rem;
            opacity: 0.9;
        }

        /* Theme Toggle */
        .theme-toggle {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .theme-icon {
            width: 24px;
            height: 24px;
            color: white;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: var(--info); }
        .stat-card.na { border-left-color: var(--na); }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-card.primary h3 { color: var(--primary); }
        .stat-card.success h3 { color: var(--success); }
        .stat-card.warning h3 { color: var(--warning); }
        .stat-card.danger h3 { color: var(--danger); }
        .stat-card.info h3 { color: var(--info); }
        .stat-card.na h3 { color: var(--na); }

        .stat-card p {
            color: var(--text-light);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Controls */
        .controls {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .controls-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.875rem;
            transition: var(--transition);
            background: var(--background);
            color: var(--text);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            width: 20px;
            height: 20px;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.3);
        }

        .btn-secondary {
            background-color: var(--surface);
            color: var(--text);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            background-color: var(--background);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Filters */
        .filters {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .filters-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text);
        }

        .clear-filters {
            font-size: 0.875rem;
            color: var(--primary);
            cursor: pointer;
            text-decoration: underline;
            transition: var(--transition);
        }

        .clear-filters:hover {
            color: var(--primary-dark);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            background-color: var(--surface);
            color: var(--text);
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .date-range {
            display: flex;
            gap: 0.5rem;
        }

        .date-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            background-color: var(--surface);
            color: var(--text);
            transition: var(--transition);
        }

        .date-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        /* Table */
        .table-wrapper {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem;
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-light);
        }

        .action-btn:hover {
            background: var(--border);
            color: var(--text);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: var(--background);
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
            user-select: none;
            cursor: pointer;
            position: relative;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        th:hover {
            background-color: rgba(5, 150, 105, 0.05);
        }

        .sortable {
            padding-right: 2rem;
        }

        .sort-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.3;
            transition: var(--transition);
        }

        th.sorted-asc .sort-icon::after {
            content: '↑';
            opacity: 1;
            color: var(--primary);
        }

        th.sorted-desc .sort-icon::after {
            content: '↓';
            opacity: 1;
            color: var(--primary);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: var(--background);
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-fresh {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-expiring {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-expired {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .status-na {
            background-color: rgba(139, 92, 246, 0.1);
            color: var(--na);
        }

        /* Quantity Badge */
        .quantity-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .quantity-low {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .quantity-medium {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .quantity-high {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .action-icon-btn {
            padding: 0.375rem;
            background: var(--background);
            border: 2px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .action-icon-btn svg {
            width: 25px;
            height: 25px;
        }

        .action-icon-btn:hover {
            background: var(--border);
            color: var(--text);
        }

        .action-icon-btn.edit:hover {
            border-color: var(--info);
            color: var(--info);
        }

        .action-icon-btn.delete:hover {
            border-color: var(--danger);
            color: var(--danger);
        }

        .action-icon-btn.record:hover {
            border-color: var(--success);
            color: var(--success);
        }

        /* Pagination */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .pagination-info {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .page-btn {
            padding: 0.625rem 1rem;
            border: 1px solid var(--border);
            background-color: var(--surface);
            color: var(--text);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .page-btn:hover:not(:disabled) {
            background-color: var(--background);
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-state svg {
            width: 5rem;
            height: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        /* Loading State */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            display: none;
        }

        [data-theme="dark"] .loading-overlay {
            background-color: rgba(15, 23, 42, 0.8);
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 2rem 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .controls-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-direction: column;
                gap: 1rem;
            }

            .table-container {
                font-size: 0.75rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }

            .date-range {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
            }
            .pagination-controls {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
            }
            
            .page-btn {
                padding: 0.625rem 1rem;
                border: 1px solid var(--border);
                background-color: var(--surface);
                color: var(--text);
                border-radius: 8px;
                cursor: pointer;
                transition: var(--transition);
                font-size: 0.875rem;
                font-weight: 500;
                text-decoration: none;
                display: inline-block;
            }
            
            .page-btn:hover:not(:disabled) {
                background-color: var(--background);
                border-color: var(--primary);
                color: var(--primary);
            }
            
            .page-btn:disabled, .page-btn.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            .page-btn.active {
                background-color: var(--primary);
                color: white;
                border-color: var(--primary);
            }
            .pagination-controls {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .page-btn {
                padding: 0.625rem 1rem;
                border: 1px solid var(--border);
                background-color: var(--surface);
                color: var(--text);
                border-radius: 8px;
                cursor: pointer;
                transition: var(--transition);
                font-size: 0.875rem;
                font-weight: 500;
                text-decoration: none;
                display: inline-block;
            }

            .page-btn:hover:not(:disabled) {
                background-color: var(--background);
                border-color: var(--primary);
                color: var(--primary);
            }

            .page-btn:disabled, .page-btn.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .page-btn.active {
                background-color: var(--primary);
                color: white;
                border-color: var(--primary);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>MDRRMO Inventory Management</h1>
                <p>Track expiration dates and manage your food inventory efficiently</p>
                <div id="currentDateTime" style="margin-top: 5px; font-size: 1.2rem;"></div>
            </div>
            <div class="theme-toggle" id="themeToggle">
                <svg class="theme-icon" id="sunIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
                <svg class="theme-icon" id="moonIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <h3 id="totalItems"><?php echo $totalItems; ?></h3>
                <p>Total Items</p>
            </div>
            <div class="stat-card success">
                <h3 id="freshItems"><?php echo $freshItems; ?></h3>
                <p>Fresh Items</p>
            </div>
            <div class="stat-card warning">
                <h3 id="expiringItems"><?php echo $expiringItems; ?></h3>
                <p>Expiring Soon</p>
            </div>
            <div class="stat-card danger">
                <h3 id="expiredItems"><?php echo $expiredItems; ?></h3>
                <p>Expired Items</p>
            </div>
            <div class="stat-card na">
                <h3 id="naItems"><?php echo $naItems; ?></h3>
                <p>Items No Expiration</p>
            </div>
        </div>

        <!-- Search and Export Controls -->
        <div class="controls">
            <form id="searchForm" method="GET" action="html.php">
                <div class="controls-grid">
                    <div class="search-container">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" class="search-input" name="search" id="searchInput" placeholder="Search by item name or category..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" id="searchBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                    <button type="button" class="btn btn-secondary" id="exportBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Export CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- Filters Section -->
        <div class="filters">
            <div class="filters-header">
                <h3>INVENTORY FILTERS</h3>
                <a href="html.php" class="clear-filters" id="clearFilters">Clear all filters</a>
            </div>
            <form id="filterForm" method="GET" action="html.php">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select class="filter-select" name="category" id="categoryFilter">
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
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" name="status" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="fresh" <?php echo $statusFilter === 'fresh' ? 'selected' : ''; ?>>Fresh</option>
                            <option value="expiring" <?php echo $statusFilter === 'expiring' ? 'selected' : ''; ?>>Expiring Soon</option>
                            <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="na" <?php echo $statusFilter === 'na' ? 'selected' : ''; ?>>No Expiration</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Quantity Level</label>
                        <select class="filter-select" name="quantity" id="quantityFilter">
                            <option value="">All Levels</option>
                            <option value="low" <?php echo $quantityFilter === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="medium" <?php echo $quantityFilter === 'medium' ? 'selected' : ''; ?>>Medium Stock</option>
                            <option value="high" <?php echo $quantityFilter === 'high' ? 'selected' : ''; ?>>High Stock</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Expiration Range</label>
                        <div class="date-range">
                            <input type="date" class="date-input" name="start_date" id="startDate" value="<?php echo htmlspecialchars($startDate); ?>">
                            <input type="date" class="date-input" name="end_date" id="endDate" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                    </div>
                </div>
                <input type="submit" style="display: none;" id="filterSubmit">
            </form>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <div class="table-header">
                <h2 class="table-title">INVENTORY</h2>
                <div class="table-actions">
                    <button class="action-btn" id="refreshBtn" title="Refresh" onclick="window.location.reload()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </button>
                    <button class="action-btn" id="historyBtn" title="Transaction History" onclick="openHistoryModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18"></path>
                            <path d="M18 17V9"></path>
                            <path d="M13 17V5"></path>
                            <path d="M8 17v-3"></path>
                        </svg>
                    </button>
                    <button class="action-btn" id="addItemBtn" title="Add Item" onclick="openModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Expiration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 11H3m6 0V5m0 6l-6 6m12-6h6m-6 0v6m0-6l6-6"/>
                                        </svg>
                                        <h3>No items found</h3>
                                     <p>Try adjusting your search or filters</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): 
                                $status = 'na';
                                $statusText = 'N/A';
                                
                                if ($item['expiration_date']) {
                                    $today = new DateTime();
                                    $expDate = new DateTime($item['expiration_date']);
                                    $diff = $today->diff($expDate);
                                    
                                    if ($today > $expDate) {
                                        $status = 'expired';
                                        $statusText = 'Expired';
                                    } elseif ($diff->days <= 7 && $diff->invert == 0) {
                                        $status = 'expiring';
                                        $statusText = 'Expiring Soon';
                                    } else {
                                        $status = 'fresh';
                                        $statusText = 'Fresh';
                                    }
                                }
                                
                                // Determine quantity level
                                $quantity = $item['quantity'];
                                $category = $item['category'];
                                $thresholds = [
                                    'Food Inventory' => ['low' => 10, 'medium' => 15],
                                    'Admin Office Inventory' => ['low' => 10, 'medium' => 20],
                                    'Planning Office Inventory' => ['low' => 10, 'medium' => 20],
                                    'Operation Inventory' => ['low' => 10, 'medium' => 20],
                                    'Construction Materials' => ['low' => 10, 'medium' => 20],
                                    'Family Kit' => ['low' => 10, 'medium' => 20],
                                    'Hygiene Kit' => ['low' => 10, 'medium' => 20]
                                ];
                                $threshold = $thresholds[$category] ?? ['low' => 5, 'medium' => 15];
                                
                                if ($quantity <= $threshold['low']) {
                                    $quantityLevel = 'low';
                                } elseif ($quantity <= $threshold['medium']) {
                                    $quantityLevel = 'medium';
                                } else {
                                    $quantityLevel = 'high';
                                }
                            ?>
                            <tr data-id="<?php echo $item['id']; ?>">
                                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>
                                    <span class="quantity-badge quantity-<?php echo $quantityLevel; ?>">
                                        <?php echo $item['quantity'] . ' ' . htmlspecialchars($item['unit']); ?>
                                    </span>
                                </td>
                                <td><?php echo $item['expiration_date'] ? date('M j, Y', strtotime($item['expiration_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-icon-btn record" title="Record Transaction" onclick="openRecordTransactionModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>', '<?php echo htmlspecialchars(addslashes($item['category'])); ?>', <?php echo $item['quantity']; ?>, '<?php echo $item['expiration_date'] ? $item['expiration_date'] : 'N/A'; ?>')">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                            </svg>
                                        </button>
                                        <button class="action-icon-btn edit" title="Edit" onclick="openModal(<?php echo $item['id']; ?>)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button class="action-icon-btn delete" title="Delete" onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="spinner"></div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <div class="pagination-info" id="paginationInfo">
                    Showing <?php echo min($offset + 1, $totalRecords); ?> to <?php echo min($offset + count($items), $totalRecords); ?> of <?php echo $totalRecords; ?> records
                </div>
                <div class="pagination-controls">
                    <a href="?page=1<?php echo isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.htmlspecialchars($_GET['category']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['quantity']) ? '&quantity='.htmlspecialchars($_GET['quantity']) : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date='.htmlspecialchars($_GET['start_date']) : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date='.htmlspecialchars($_GET['end_date']) : ''; ?>" class="page-btn<?php echo $page == 1 ? ' disabled' : ''; ?>">First</a>
                    <a href="?page=<?php echo max(1, $page - 1); ?><?php echo isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.htmlspecialchars($_GET['category']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['quantity']) ? '&quantity='.htmlspecialchars($_GET['quantity']) : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date='.htmlspecialchars($_GET['start_date']) : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date='.htmlspecialchars($_GET['end_date']) : ''; ?>" class="page-btn<?php echo $page == 1 ? ' disabled' : ''; ?>">Previous</a>
                    
                    <?php
                    // Show page numbers
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1) {
                        echo '<span class="page-btn disabled">...</span>';
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<a href="?page='.$i.(isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '').(isset($_GET['category']) ? '&category='.htmlspecialchars($_GET['category']) : '').(isset($_GET['status']) ? '&status='.htmlspecialchars($_GET['status']) : '').(isset($_GET['quantity']) ? '&quantity='.htmlspecialchars($_GET['quantity']) : '').(isset($_GET['start_date']) ? '&start_date='.htmlspecialchars($_GET['start_date']) : '').(isset($_GET['end_date']) ? '&end_date='.htmlspecialchars($_GET['end_date']) : '').'" class="page-btn'.($i == $page ? ' active' : '').'">'.$i.'</a>';
                    }
                    
                    if ($endPage < $totalPages) {
                        echo '<span class="page-btn disabled">...</span>';
                    }
                    ?>
                    
                    <a href="?page=<?php echo min($totalPages, $page + 1); ?><?php echo isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.htmlspecialchars($_GET['category']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['quantity']) ? '&quantity='.htmlspecialchars($_GET['quantity']) : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date='.htmlspecialchars($_GET['start_date']) : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date='.htmlspecialchars($_GET['end_date']) : ''; ?>" class="page-btn<?php echo $page == $totalPages ? ' disabled' : ''; ?>">Next</a>
                    <a href="?page=<?php echo $totalPages; ?><?php echo isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.htmlspecialchars($_GET['category']) : ''; ?><?php echo isset($_GET['status']) ? '&status='.htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['quantity']) ? '&quantity='.htmlspecialchars($_GET['quantity']) : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date='.htmlspecialchars($_GET['start_date']) : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date='.htmlspecialchars($_GET['end_date']) : ''; ?>" class="page-btn<?php echo $page == $totalPages ? ' disabled' : ''; ?>">Last</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Modal -->
    <div id="itemModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:var(--surface); padding:2rem; border-radius:12px; width:100%; max-width:500px;">
            <h2 id="modalTitle">Add New Item</h2>
            <form id="itemForm" method="POST" action="html.php">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="itemId" value="">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Item Name</label>
                    <input type="text" id="itemName" name="name" required style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Category</label>
                    <select id="itemCategory" name="category" required style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;">
                        <option value="">Select Category</option>
                        <option value="Food Inventory">Food Inventory</option>
                        <option value="Admin Office Inventory">Admin Office Inventory</option>
                        <option value="Planning Office Inventory">Planning Office Inventory</option>
                        <option value="Operation Inventory">Operation Inventory</option>
                        <option value="Construction Materials">Construction Materials</option>
                        <option value="Family Kit">Family Kit</option>
                        <option value="Hygiene Kit">Hygiene Kit</option>
                    </select> 
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Quantity</label>
                    <input type="number" id="itemQuantity" name="quantity" required min="0" style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Unit</label>
                    <input type="text" id="itemUnit" name="unit" required style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Expiration Date (optional)</label>
                    <input type="date" id="itemExpiration" name="expiration_date" style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;">
                    <div style="margin-top:0.5rem;">
                        <input type="checkbox" id="noExpiration" onchange="toggleExpirationDate()">
                        <label for="noExpiration">No expiration date</label>
                    </div>
                </div>
                <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                    <button type="button" onclick="closeModal()" style="padding:0.75rem 1.5rem; background:var(--danger); color:white; border:none; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:0.75rem 1.5rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer;">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- History Modal -->
    <div id="historyModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:var(--surface); padding:2rem; border-radius:12px; width:100%; max-width:800px; max-height:80vh; overflow-y:auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>Transaction History</h2>
                <button onclick="exportHistoryToCSV()" style="padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer;">
                    Export CSV
                </button>
            </div>
            <div id="historyContent" style="margin:1rem 0;">
                <p>Loading transaction history...</p>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="closeHistoryModal()" style="padding:0.75rem 1.5rem; background:var(--danger); color:white; border:none; border-radius:8px; cursor:pointer;">Close</button>
            </div>
        </div>
    </div>

    <!-- Record Transaction Modal -->
    <div id="recordTransactionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div style="background:var(--surface); padding:2rem; border-radius:12px; width:100%; max-width:500px;">
            <h2 id="transactionModalTitle">Record Transaction</h2>
            <form id="transactionForm" method="POST" action="html.php">
                <input type="hidden" name="action" value="record_transaction">
                <input type="hidden" name="item_id" id="transactionItemId" value="">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Item</label>
                    <input type="text" id="transactionItemName" readonly style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px; background-color: var(--background);">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Category</label>
                    <input type="text" id="transactionItemCategory" readonly style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px; background-color: var(--background);">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Current Quantity</label>
                    <input type="text" id="transactionItemQuantity" readonly style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px; background-color: var(--background);">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Expiration Date</label>
                    <input type="text" id="transactionItemExpiration" readonly style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px; background-color: var(--background);">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Transaction Type</label>
                    <select id="transactionType" name="type" required style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;">
                        <option value="in">➕ Stock In </option>
                        <option value="out">➖ Stock Out</option>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Quantity</label>
                    <input type="number" id="transactionQuantity" name="quantity" required min="1" style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Name</label>
                    <textarea id="transactionNotes" name="names" style="width:100%; padding:0.75rem; border:2px solid var(--border); border-radius:8px;"></textarea>
                </div>
                <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                    <button type="button" onclick="closeRecordTransactionModal()" style="padding:0.75rem 1.5rem; background:var(--danger); color:white; border:none; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:0.75rem 1.5rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer;">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Theme toggle functionality
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            if (savedTheme === 'dark') {
                document.getElementById('sunIcon').style.display = 'none';
                document.getElementById('moonIcon').style.display = 'block';
            } else {
                document.getElementById('sunIcon').style.display = 'block';
                document.getElementById('moonIcon').style.display = 'none';
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (newTheme === 'dark') {
                document.getElementById('sunIcon').style.display = 'none';
                document.getElementById('moonIcon').style.display = 'block';
            } else {
                document.getElementById('sunIcon').style.display = 'block';
                document.getElementById('moonIcon').style.display = 'none';
            }
        }

        // Automatic search functionality
        function setupAutoSearch() {
            const searchInput = document.getElementById('searchInput');
            let searchTimer;
            
            searchInput.addEventListener('input', function(e) {
                // Clear previous timer
                clearTimeout(searchTimer);
                
                // Set a new timer to trigger the search after 0ms of inactivity
                searchTimer = setTimeout(function() {
                    performSearch(searchInput.value.trim());
                }, 0); // 0ms delay after typing stops
            });
        }

        function performSearch(searchTerm) {
            // Get current filter parameters
            const params = new URLSearchParams();
            
            if (searchTerm) {
                params.append('search', searchTerm);
            }
            
            // Add existing filter parameters
            const categoryFilter = document.getElementById('categoryFilter').value;
            if (categoryFilter) {
                params.append('category', categoryFilter);
            }
            
            const statusFilter = document.getElementById('statusFilter').value;
            if (statusFilter) {
                params.append('status', statusFilter);
            }
            
            const quantityFilter = document.getElementById('quantityFilter').value;
            if (quantityFilter) {
                params.append('quantity', quantityFilter);
            }
            
            const startDate = document.getElementById('startDate').value;
            if (startDate) {
                params.append('start_date', startDate);
            }
            
            const endDate = document.getElementById('endDate').value;
            if (endDate) {
                params.append('end_date', endDate);
            }
            
            // Show loading overlay
            document.getElementById('loadingOverlay').classList.add('show');
            
            // Submit the form via AJAX
            fetch('html.php?' + params.toString())
                .then(response => response.text())
                .then(html => {
                    // Parse the response to extract the table body
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTableBody = doc.getElementById('tableBody');
                    const newPaginationInfo = doc.getElementById('paginationInfo');
                    const newStats = {
                        totalItems: doc.getElementById('totalItems')?.textContent,
                        freshItems: doc.getElementById('freshItems')?.textContent,
                        expiringItems: doc.getElementById('expiringItems')?.textContent,
                        expiredItems: doc.getElementById('expiredItems')?.textContent,
                        naItems: doc.getElementById('naItems')?.textContent
                    };
                    
                    // Update the page content
                    if (newTableBody) {
                        document.getElementById('tableBody').innerHTML = newTableBody.innerHTML;
                    }
                    
                    if (newPaginationInfo) {
                        document.getElementById('paginationInfo').innerHTML = newPaginationInfo.innerHTML;
                    }
                    
                    // Update stats cards
                    if (newStats.totalItems) {
                        document.getElementById('totalItems').textContent = newStats.totalItems;
                    }
                    if (newStats.freshItems) {
                        document.getElementById('freshItems').textContent = newStats.freshItems;
                    }
                    if (newStats.expiringItems) {
                        document.getElementById('expiringItems').textContent = newStats.expiringItems;
                    }
                    if (newStats.expiredItems) {
                        document.getElementById('expiredItems').textContent = newStats.expiredItems;
                    }
                    if (newStats.naItems) {
                        document.getElementById('naItems').textContent = newStats.naItems;
                    }
                    
                    // Hide loading overlay
                    document.getElementById('loadingOverlay').classList.remove('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loadingOverlay').classList.remove('show');
                });
        }

        // Modal functions
        function openModal(itemId = null) {
            const modal = document.getElementById('itemModal');
            const modalTitle = document.getElementById('modalTitle');
            
            if (itemId) {
                // Edit mode
                modalTitle.textContent = 'Edit Item';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('itemId').value = itemId;
                
                // Find the item in the table
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
                    
                    const dateText = row.cells[3].textContent.trim();
                    if (dateText === 'N/A') {
                        document.getElementById('noExpiration').checked = true;
                        document.getElementById('itemExpiration').disabled = true;
                        document.getElementById('itemExpiration').value = '';
                    } else {
                        const dateParts = dateText.split(/[\s,]+/);
                        if (dateParts.length === 3) {
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
                }
            } else {
                // Add mode
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

        // History modal functions
        function openHistoryModal() {
            const modal = document.getElementById('historyModal');
            modal.style.display = 'flex';
            
            // Show loading state
            document.getElementById('historyContent').innerHTML = '<p>Loading transaction history...</p>';
            
            // Load history via AJAX
            fetch('html.php?get_history=1')
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

        // Export history to CSV
        function exportHistoryToCSV() {
            window.location.href = 'html.php?get_history=csv';
        }

        // Record transaction modal functions
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

        // Delete item function
        function deleteItem(itemId, itemName) {
            if (confirm(`Are you sure you want to delete "${itemName}"?`)) {
                // Create a form and submit it to handle the deletion
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'html.php';
                
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
                
                // Add current filter parameters
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

        // Export to CSV function
        document.getElementById('exportBtn').addEventListener('click', function() {
            // Get current filter parameters
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
            
            window.location.href = 'html.php?' + params.toString();
        });

        // Initialize the page
        initTheme();
        document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        
        // Set up automatic search
        setupAutoSearch();

        // Prevent default form submission for search
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch(document.getElementById('searchInput').value.trim());
        });

        // Auto-submit filters when changed
        document.querySelectorAll('.filter-select, .date-input').forEach(element => {
            element.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        // Handle search form submission on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value.trim());
            }
        });

        // Real-time date and time display
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

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>
</html>