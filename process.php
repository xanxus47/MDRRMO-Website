<?php
require_once 'config.php';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'inventory_items';
    
    try {
        switch ($action) {
            case 'add':
            case 'edit':
                // Validate and sanitize inputs
                $name = $conn->real_escape_string(trim($_POST['name']));
                $category = $conn->real_escape_string(trim($_POST['category']));
                $quantity = intval($_POST['quantity']);
                $unit = $conn->real_escape_string(trim($_POST['unit']));
                $expiration_date = $conn->real_escape_string(trim($_POST['expiration_date']));
                
                // Basic validation
                if (empty($name) || empty($category) || empty($unit) || empty($expiration_date)) {
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
                
            default:
                throw new Exception("Invalid action");
        }
        
        // Redirect back to the main page
        header("Location: html.php");
        exit();
        
    } catch (Exception $e) {
        // Store error in session and redirect back
        session_start();
        $_SESSION['error'] = $e->getMessage();
        header("Location: html.php");
        exit();
    }
}

?>