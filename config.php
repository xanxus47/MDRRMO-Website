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

// Add this near the top of your PHP code, after the session_start()
if (isset($_GET['get_history']) && $_GET['get_history'] == '1') {
    // Return transaction history as HTML
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    $sql = "SELECT t.*, i.name as item_name 
            FROM transactions t
            JOIN inventory_items i ON t.item_id = i.id
            ORDER BY t.timestamp DESC
            LIMIT 100";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo '<table style="width:100%; border-collapse:collapse;">';
        echo '<thead><tr>
                <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Date</th>
                <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Item</th>
                <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Type</th>
                <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Quantity</th>
                <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">User</th>
                <th style="padding:0.75rem; text-align:left; border-bottom:1px solid var(--border);">Name</th>
              </tr></thead>';
        echo '<tbody>';
        
        while($row = $result->fetch_assoc()) {
            $typeBadge = $row['type'] == 'in' ? 
                '<span style="background-color:rgba(16, 185, 129, 0.1); color:#10b981; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem;">IN</span>' : 
                '<span style="background-color:rgba(239, 68, 68, 0.1); color:#ef4444; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem;">OUT</span>';
            
            echo '<tr>';
            echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . date('M j, Y H:i', strtotime($row['timestamp'])) . '</td>';
            echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['item_name']) . '</td>';
            echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . $typeBadge . '</td>';
            echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . $row['quantity'] . '</td>';
            echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['user']) . '</td>';
            echo '<td style="padding:0.75rem; border-bottom:1px solid var(--border);">' . htmlspecialchars($row['names']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No transaction history found.</p>';
    }

    $conn->close();
    
    exit();
}