<?php
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

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'mdrrjvhm_xanxus47');
define('DB_PASS', 'oneLASTsong32');

// Create initial connection (without selecting a specific database)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all databases (excluding system databases)
$databases = [];
$result = $conn->query("SHOW DATABASES WHERE `Database` NOT IN ('information_schema', 'mysql', 'performance_schema', 'mdrrjvhm_submissions_db', 'sys')");
if ($result) {
    while ($row = $result->fetch_row()) {
        $databases[] = $row[0];
    }
}

// Initialize variables
$search_results = [];
$search_query = '';
$error = null;
$success = null;
$searched_databases = [];
$searched_tables = [];
$searched_columns = [];

// Process edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $database = sanitize_input($_POST['database']);
    $table = sanitize_input($_POST['table']);
    $id = sanitize_input($_POST['id']);
    
    // Connect to the specific database
    $conn->select_db($database);
    
    // Get all columns in the table
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    
    // Prepare the update statement
    $setClauses = [];
    $params = [];
    $types = '';
    
    foreach ($columns as $column) {
        if ($column === 'id') continue; // Skip ID field
        
        if (isset($_POST[$column])) {
            $setClauses[] = "`$column` = ?";
            $params[] = $_POST[$column];
            $types .= 's';
        }
    }
    
    if (!empty($setClauses)) {
        $set = implode(', ', $setClauses);
        $sql = "UPDATE `$table` SET $set WHERE id = ?";
        $params[] = $id;
        $types .= 'i';
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = "Failed to prepare update statement: " . $conn->error;
        } else {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $success = "Record successfully updated in $database.$table";
            } else {
                $error = "Failed to update record: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Clear search results if a new search is being performed
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $new_search_query = sanitize_input($_GET['search']);
    
    // Only clear results if the search query has changed
    if ($new_search_query !== $search_query) {
        $search_results = [];
        $searched_databases = [];
        $searched_tables = [];
        $searched_columns = [];
    }
    
    $search_query = $new_search_query;
    
    // Search across all databases
    foreach ($databases as $database) {
        $conn->select_db($database);
        $searched_databases[] = $database;
        
        // Get all tables in current database
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
        }
        
        // Search across all tables in current database
        foreach ($tables as $table) {
            $searched_tables[] = "$database.$table";
            
            // Get all columns in current table
            $columns = [];
            $result = $conn->query("SHOW COLUMNS FROM `$table`");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row['Field'];
                }
            }
            
            if (empty($columns)) continue;
            
            // Build dynamic WHERE clause for NAME columns only
            $whereClauses = [];
            $params = [];
            $types = '';
            
            foreach ($columns as $column) {
                // Skip binary/BLOB columns that can't be searched with LIKE
                $column_type = '';
                $type_result = $conn->query("SHOW COLUMNS FROM `$table` WHERE Field = '$column'");
                if ($type_result && $type_row = $type_result->fetch_assoc()) {
                    $column_type = $type_row['Type'];
                }
                
                if (stripos($column_type, 'blob') !== false || stripos($column_type, 'binary') !== false) {
                    continue;
                }
                
                // Only search in columns that contain 'name' in their name (case insensitive)
                if (stripos($column, 'name') !== false) {
                    $whereClauses[] = "`$column` LIKE ?";
                    $params[] = "%$search_query%";
                    $types .= 's';
                    $searched_columns[] = "$database.$table.$column";
                }
            }
            
            if (empty($whereClauses)) continue;
            
            $where = implode(' OR ', $whereClauses);
            $sql = "SELECT *, '$database' AS source_database, '$table' AS source_table FROM `$table` WHERE $where";
            
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $error = "Failed to prepare query for table $database.$table: " . $conn->error;
                continue;
            }
            
            // Bind parameters dynamically
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                $error = "Failed to execute query for table $database.$table: " . $stmt->error;
                $stmt->close();
                continue;
            }
            
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $search_results[] = $row;
            }
            
            $stmt->close();
        }
    }
    
    if (empty($searched_databases)) {
        $error = "No searchable databases found";
    }
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Check if we're viewing a record for editing
$edit_record = null;
if (isset($_GET['edit']) && isset($_GET['database']) && isset($_GET['table']) && isset($_GET['id'])) {
    $database = sanitize_input($_GET['database']);
    $table = sanitize_input($_GET['table']);
    $id = sanitize_input($_GET['id']);
    
    $conn->select_db($database);
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_record = $result->fetch_assoc();
    $stmt->close();
    
    if (!$edit_record) {
        $error = "Record not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDRRMO Records Search</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #0056b3;  /* Emergency blue */
            --primary-light: #1a73e8;
            --secondary: #d32f2f;  /* Alert red */
            --light: #f8f9fa;
            --dark: #212529;
            --warning: #f57c00;  /* Warning orange */
            --success: #388e3c;  /* Safety green */
            --info: #0288d1;  /* Info blue */
            --glass: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            background: 
                linear-gradient(rgba(0, 86, 179, 0.85), rgba(211, 47, 47, 0.85)),
                url('https://images.unsplash.com/photo-1584473457406-6240486418e9?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        h1 {
            font-size: 2.8rem;
            color: white;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .search-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .search-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
            min-width: 300px;
        }
        
        label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.2);
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #2e7d32;
        }
        
        .results-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .results-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, var(--primary), var(--info));
            color: white;
        }
        
        .results-count {
            background-color: white;
            color: var(--primary);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
            padding: 0 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th {
            background-color: #f5f5f5;
            padding: 1.25rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            position: sticky;
            top: 0;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 1.25rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .source-badge {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .alert-danger {
            border-left: 5px solid var(--secondary);
        }
        
        .alert-success {
            border-left: 5px solid var(--success);
        }
        
        .alert-info {
            border-left: 5px solid var(--info);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem;
            color: var(--dark);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        
        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Edit form styles */
        .edit-form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            margin-bottom: 2.5rem;
        }
        
        .edit-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .edit-form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .edit-form-actions {
            display: flex;
            gap: 1rem;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-col {
            flex: 1;
            min-width: 300px;
        }
        
        /* Emergency ribbon */
        .emergency-ribbon {
            position: absolute;
            top: 20px;
            right: -50px;
            background-color: var(--secondary);
            color: white;
            padding: 0.5rem 5rem;
            transform: rotate(45deg);
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 100;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .action-btn.edit {
            background-color: var(--info);
            color: white;
        }
        
        .action-btn.edit:hover {
            background-color: #0277bd;
        }
        
        .action-btn.view {
            background-color: var(--success);
            color: white;
        }
        
        .action-btn.view:hover {
            background-color: #2e7d32;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 2.2rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
                min-width: auto;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .emergency-ribbon {
                right: -70px;
                font-size: 0.7rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-col {
                width: 100%;
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Emergency Ribbon -->
    <div class="emergency-ribbon">MDRRMO OFFICIAL</div>
    
    <div class="container">
        <header>
            <h1><i class="fas fa-shield-alt"></i> MDRRMO Records System</h1>
            <p>Search emergency preparedness and response records</p>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                <div>
                    <strong>Success</strong>
                    <p><?php echo $success; ?></p>
                </div>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle" style="color: var(--secondary);"></i>
                <div>
                    <strong>Emergency Alert</strong>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
        <?php endif; ?>

<?php if ($edit_record): ?>
    <!-- Edit Record Form -->
    <div class="edit-form-container">
        <div class="edit-form-header">
            <h2 class="edit-form-title">
                <i class="fas fa-edit"></i> EDIT RECORD
                <span class="source-badge"><?php echo strtoupper(htmlspecialchars($_GET['table'])); ?></span>
            </h2>
            <div class="edit-form-actions">
                <a href="?search=<?php echo urlencode($search_query); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> BACK TO RESULTS
                </a>
            </div>
        </div>
        
        <form method="POST" style="text-transform: uppercase;">
            <input type="hidden" name="update" value="1">
            <input type="hidden" name="database" value="<?php echo htmlspecialchars($_GET['database']); ?>">
            <input type="hidden" name="table" value="<?php echo htmlspecialchars($_GET['table']); ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
            
            <?php
            // Get columns for the current table
            $conn->select_db($_GET['database']);
            $columns_result = $conn->query("SHOW COLUMNS FROM `{$_GET['table']}`");
            $columns = [];
            if ($columns_result) {
                while ($column = $columns_result->fetch_assoc()) {
                    $columns[] = $column;
                }
            }
            
            // Group columns into rows of 2 for better layout
            $column_chunks = array_chunk($columns, 2);
            
            foreach ($column_chunks as $chunk): ?>
    <div class="form-row">
        <?php foreach ($chunk as $column): 
            // Skip these fields - ID and timestamp fields
            if ($column['Field'] === 'id' || $column['Field'] === 'created_at' || $column['Field'] === 'updated_at') continue;
        ?>
            <div class="form-col">
                <label for="<?php echo $column['Field']; ?>" style="text-transform: uppercase;">
                    <?php echo strtoupper(str_replace('_', ' ', $column['Field'])); ?>
                </label>
                <?php if (strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'varchar') !== false): ?>
                    <input type="text" 
                           id="<?php echo $column['Field']; ?>" 
                           name="<?php echo $column['Field']; ?>" 
                           value="<?php echo strtoupper(htmlspecialchars($edit_record[$column['Field']] ?? '')); ?>" 
                           class="uppercase-input"
                           style="text-transform: uppercase;">
                <?php elseif (strpos($column['Type'], 'int') !== false || strpos($column['Type'], 'decimal') !== false): ?>
                    <input type="number" 
                           id="<?php echo $column['Field']; ?>" 
                           name="<?php echo $column['Field']; ?>" 
                           value="<?php echo htmlspecialchars($edit_record[$column['Field']] ?? ''); ?>">
                <?php elseif (strpos($column['Type'], 'date') !== false): ?>
                    <input type="date" 
                           id="<?php echo $column['Field']; ?>" 
                           name="<?php echo $column['Field']; ?>" 
                           value="<?php echo htmlspecialchars($edit_record[$column['Field']] ?? ''); ?>">
                <?php elseif (strpos($column['Type'], 'time') !== false): ?>
                    <input type="time" 
                           id="<?php echo $column['Field']; ?>" 
                           name="<?php echo $column['Field']; ?>" 
                           value="<?php echo htmlspecialchars($edit_record[$column['Field']] ?? ''); ?>">
                <?php elseif (strpos($column['Type'], 'enum') !== false): ?>
                    <?php
                    // Parse ENUM values
                    preg_match("/enum\('(.*)'\)/", $column['Type'], $matches);
                    $enum_values = explode("','", $matches[1]);
                    ?>
                    <select id="<?php echo $column['Field']; ?>" name="<?php echo $column['Field']; ?>" style="text-transform: uppercase;">
                        <?php foreach ($enum_values as $value): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" 
                                <?php if (($edit_record[$column['Field']] ?? '') === $value) echo 'selected'; ?>>
                                <?php echo strtoupper(htmlspecialchars($value)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" 
                           id="<?php echo $column['Field']; ?>" 
                           name="<?php echo $column['Field']; ?>" 
                           value="<?php echo strtoupper(htmlspecialchars($edit_record[$column['Field']] ?? '')); ?>" 
                           class="uppercase-input"
                           style="text-transform: uppercase;">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
            
            <div class="pt-2 flex space-x-4">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> SAVE CHANGES
                </button>
                <a href="?search=<?php echo urlencode($search_query); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> CANCEL
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Main Search Interface -->
            <div class="search-card">
                <form method="GET" class="search-form">
                    <div class="form-group">
                        <label for="search"><i class="fas fa-search"></i> Search by Name</label>
                        <input type="text" name="search" id="search" placeholder="Enter name to search" 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Search Records
                    </button>
                </form>
            </div>
            
            <?php if (!empty($search_results)): ?>
                <div class="results-container">
                    <div class="results-header">
                        <h2><i class="fas fa-file-alt"></i> Search Results</h2>
                        <div>
                            <span class="results-count"><?php echo count($search_results); ?> records found</span>
                            <a href="homepage.php" class="btn btn-secondary" style="margin-left: 1rem;">
                                <i class="fas fa-home"></i> Back to Home
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <?php 
                                    // Get all unique column names from all results
                                    $all_columns = [];
                                    foreach ($search_results as $row) {
                                        $all_columns = array_merge($all_columns, array_keys($row));
                                    }
                                    $unique_columns = array_unique($all_columns);
                                    
                                    // Always show source_table first
                                    echo '<th>Record Type</th>';
                                    
                                    // Show other columns (excluding specific columns we don't want to display)
                                    foreach ($unique_columns as $column) {
                                        if ($column !== 'source_table' && $column !== 'source_database' && 
                                            $column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                                            echo '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $column))) . '</th>';
                                        }
                                    }
                                    ?>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($search_results as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="source-badge" title="<?php echo htmlspecialchars($row['source_database']); ?>">
                                                <?php echo htmlspecialchars($row['source_table']); ?>
                                            </span>
                                            <div style="font-size: 0.8rem; color: #666; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($row['source_database']); ?>
                                            </div>
                                        </td>
                                        <?php 
                                        foreach ($unique_columns as $column) {
                                            if ($column !== 'source_table' && $column !== 'source_database' && 
                                                $column !== 'id' && $column !== 'created_at' && $column !== 'updated_at') {
                                                echo '<td>' . (isset($row[$column]) ? htmlspecialchars($row[$column]) : '') . '</td>';
                                            }
                                        }
                                        ?>
                                        <td>
                                            <a href="?edit=1&database=<?php echo urlencode($row['source_database']); ?>&table=<?php echo urlencode($row['source_table']); ?>&id=<?php echo urlencode($row['id']); ?>&search=<?php echo urlencode($search_query); ?>" 
                                               class="action-btn edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif (isset($_GET['search'])): ?>
                <div class="empty-state">
                    <i class="fas fa-file-exclamation"></i>
                    <h3>No Records Found</h3>
                    <p>No matching records found for "<?php echo htmlspecialchars($search_query); ?>"</p>
                    <p style="margin-top: 1rem;">Try different search terms or check spelling</p>
                    <a href="?" class="btn" style="margin-top: 1.5rem;">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-database"></i>
                    <h3>MDRRMO Records Portal</h3>
                    <p>Search records of participants</p>
                    <?php if (!empty($tables)): ?>
                        <p style="margin-top: 1.5rem; font-size: 0.9rem; color: #666;">
                            <i class="fas fa-info-circle"></i> System contains <?php echo count($tables); ?> record categories
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Existing search input handling
            const searchInput = document.getElementById('search');
            const searchForm = document.querySelector('.search-form');
            
            let currentValue = searchInput.value;
            
            searchInput.addEventListener('input', function() {
                if (currentValue && !this.value.trim()) {
                    searchForm.submit();
                }
                currentValue = this.value;
            });
            
            searchInput.addEventListener('search', function() {
                if (!this.value.trim()) {
                    searchForm.submit();
                }
            });

            // New code to force uppercase in edit form inputs
            const editForm = document.querySelector('.edit-form-container form');
            if (editForm) {
                // Force uppercase in real-time for text inputs
                const textInputs = editForm.querySelectorAll('input.uppercase-input');
                textInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                    });
                });

                // Ensure all text inputs are uppercase on form submission
                editForm.addEventListener('submit', function(event) {
                    textInputs.forEach(input => {
                        input.value = input.value.toUpperCase();
                    });
                });
            }
        });
    </script>
</body>
</html>