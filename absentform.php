<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absence Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #4cc9f0;
            --secondary: #f72585;
            --accent: #7209b7;
            --text: #1a1a1a;
            --text-light: #6b7280;
            --light: #f9fafb;
            --white: #ffffff;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text);
            position: relative;
            overflow-x: hidden;
        }
        
        /* Modern background pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(at 80% 0%, hsla(189, 100%, 56%, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(355, 100%, 93%, 0.1) 0px, transparent 50%);
            z-index: -1;
        }
        
        .container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-lg);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        
        .container:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        /* Modern card header */
        .card-header {
            text-align: center;
            margin-bottom: 32px;
            position: relative;
        }
        
        .card-header::after {
            content: '';
            display: block;
            width: 48px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            margin: 16px auto 0;
            border-radius: 2px;
        }
        
        h1 {
            color: var(--text);
            font-weight: 700;
            font-size: 28px;
            letter-spacing: -0.025em;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        input, select {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--white);
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius-sm);
            font-size: 14px;
            transition: var(--transition);
            outline: none;
            color: var(--text);
            box-shadow: var(--box-shadow);
        }
        
        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
        }
        
        input::placeholder {
            color: #9ca3af;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--border-radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-transform: none;
            letter-spacing: normal;
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .btn-secondary {
            background-color: var(--white);
            color: var(--primary);
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .btn-secondary:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }
        
        .message {
            padding: 16px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 24px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .message.fade-out {
            animation: fadeOut 0.3s ease forwards;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }
        
        .success-message {
            background-color: #ecfdf5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }
        
        .error-message {
            background-color: #fef2f2;
            color: var(--error);
            border: 1px solid #fecaca;
        }
        
        .error-text {
            color: var(--error); 
            font-size: 13px; 
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            position: relative;
            animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: var(--box-shadow-lg);
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .close-modal {
            position: absolute;
            top: 16px;
            right: 16px;
            color: var(--text-light);
            font-size: 20px;
            cursor: pointer;
            background: none;
            border: none;
            transition: var(--transition);
            padding: 4px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-modal:hover {
            background-color: #f3f4f6;
            color: var(--text);
        }
        
        .modal-title {
            color: var(--text);
            margin-bottom: 20px;
            text-align: center;
            font-size: 20px;
            font-weight: 600;
        }
        
        /* Table styles */
        .records-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 16px;
            font-size: 14px;
        }
        
        .records-table th {
            background-color: #f9fafb;
            color: var(--text);
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .records-table td {
            padding: 12px 16px;
            color: var(--text);
            background: var(--white);
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            transition: var(--transition);
        }
        
        .records-table tr:hover td {
            background-color: #f9fafb;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
        }
        
        .edit-btn {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .edit-btn:hover {
            background-color: #fde68a;
        }
        
        .delete-btn {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .delete-btn:hover {
            background-color: #fecaca;
        }
        
        .no-records {
            text-align: center;
            padding: 32px;
            color: var(--text-light);
            font-size: 14px;
            background-color: #f9fafb;
            border-radius: var(--border-radius-sm);
            margin-top: 16px;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            border-radius: var(--border-radius);
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        
        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(67, 97, 238, 0.1);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 32px;
                max-width: 95%;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 16px;
            }
            
            .container {
                padding: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="loadingOverlay" class="loading-overlay">
            <div class="spinner"></div>
        </div>
        
        <?php
        session_start();
        date_default_timezone_set('Asia/Manila');

        // Database configuration
        $servername = "localhost";
        $username = "mdrrjvhm_xanxus47";
        $password = "oneLASTsong32";
        $dbname = "mdrrjvhm_absent";

        // Initialize variables
        $name = $date = "";
        $nameErr = $dateErr = "";
        $records = [];
        $success_message = "";
        $error_message = "";

        try {
            // Create connection
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if form is submitted
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Handle form submission
                if (isset($_POST["submit_form"])) {
                    // Validate and sanitize inputs
                    $name = trim($_POST["name"] ?? '');
                    $date = $_POST["date"] ?? '';

                    // Name validation
                    if (empty($name)) {
                        $nameErr = "Name is required";
                    } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
                        $nameErr = "Only letters and white space allowed";
                    }

                    // Date validation
                    if (empty($date)) {
                        $dateErr = "Date is required";
                    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
                        $dateErr = "Invalid date format (YYYY-MM-DD required)";
                    }

                    // If no errors, proceed with database insertion
                    if (empty($nameErr) && empty($dateErr)) {
                        try {
                            $stmt = $conn->prepare("INSERT INTO users (name, date_of_absent) VALUES (:name, :date)");
                            $stmt->bindParam(':name', $name);
                            $stmt->bindParam(':date', $date);

                            if ($stmt->execute()) {
                                $_SESSION['success_message'] = "Form submitted successfully!";
                                $name = "";
                                $date = "";
                            } else {
                                $_SESSION['error_message'] = "Error submitting form. Please try again.";
                            }
                        } catch(PDOException $e) {
                            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
                        }
                    }
                }

                // Handle edit submission
                if (isset($_POST["edit_record"])) {
                    $edit_id = $_POST["edit_id"] ?? '';
                    $edit_name = trim($_POST["edit_name"] ?? '');
                    $edit_date = $_POST["edit_date"] ?? '';
                    if (!empty($edit_id) && !empty($edit_name) && !empty($edit_date)) {
                        try {
                            $stmt = $conn->prepare("UPDATE users SET name = :name, date_of_absent = :date WHERE id = :id");
                            $stmt->bindParam(':name', $edit_name);
                            $stmt->bindParam(':date', $edit_date);
                            $stmt->bindParam(':id', $edit_id);
                            if ($stmt->execute()) {
                                $_SESSION['success_message'] = "Record updated successfully!";
                            } else {
                                $_SESSION['error_message'] = "Error updating record. Please try again.";
                            }
                        } catch(PDOException $e) {
                            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
                        }
                    }
                }

                // Handle delete action
                if (isset($_POST["delete_record"])) {
                    $delete_id = $_POST["delete_id"] ?? '';
                    if (!empty($delete_id)) {
                        try {
                            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
                            $stmt->bindParam(':id', $delete_id);
                            if ($stmt->execute()) {
                                $_SESSION['success_message'] = "Record deleted successfully!";
                            } else {
                                $_SESSION['error_message'] = "Error deleting record. Please try again.";
                            }
                        } catch(PDOException $e) {
                            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
                        }
                    }
                }
            }

            // Fetch all records for display
            $stmt = $conn->prepare("SELECT id, name, date_of_absent FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            $_SESSION['error_message'] = "Connection failed: " . $e->getMessage();
        }

        // Close connection
        $conn = null;

        // Display success/error messages
        if (isset($_SESSION['success_message'])) {
            echo '<div class="message success-message"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="message error-message"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>
        
        <div class="card-header">
            <h1>Report Absence</h1>
        </div>
        
        <form id="absentForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="submit_form" value="1">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" 
                       value="<?php echo htmlspecialchars($name); ?>" required>
                <?php if (!empty($nameErr)): ?>
                    <div id="nameError" class="error-text">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($nameErr); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="date">Date of Absence</label>
                <input type="date" id="date" name="date" 
                       value="<?php echo htmlspecialchars($date); ?>" required>
                <?php if (!empty($dateErr)): ?>
                    <div id="dateError" class="error-text">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($dateErr); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit
            </button>
            
            <button type="button" id="viewSubmissionsBtn" class="btn btn-secondary" style="margin-top: 12px;">
                <i class="fas fa-list"></i> View Submissions
            </button>
        </form>
    </div>

    <!-- Records Modal -->
    <div id="recordsModal" class="modal">
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <h2 class="modal-title">Submission Records</h2>
            
            <?php if (empty($records)): ?>
                <div class="no-records">
                    <i class="fas fa-clipboard-list" style="font-size: 32px; margin-bottom: 12px;"></i>
                    <p>No records found</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date of Absence</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['name']); ?></td>
                                <td><?php echo date('F j, Y', strtotime($record['date_of_absent'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" data-id="<?php echo $record['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($record['name']); ?>" 
                                                data-date="<?php echo $record['date_of_absent']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="delete_id" value="<?php echo $record['id']; ?>">
                                            <button type="submit" name="delete_record" class="action-btn delete-btn" 
                                                    onclick="return confirm('Are you sure you want to delete this record?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" id="closeEditModal">&times;</button>
            <h2 class="modal-title">Edit Record</h2>
            
            <form method="post" id="editForm">
                <input type="hidden" name="edit_id" id="editId">
                <input type="hidden" name="edit_record" value="1">
                
                <div class="form-group">
                    <label for="editName">Full Name</label>
                    <input type="text" id="editName" name="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editDate">Date of Absence</label>
                    <input type="date" id="editDate" name="edit_date" class="form-control" required>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelEdit" style="flex: 1;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to today
            const dateField = document.getElementById('date');
            if (dateField && !dateField.value) {
                const today = new Date();
                const formattedDate = today.toISOString().substr(0, 10);
                dateField.value = formattedDate;
            }

            // Focus on name field
            const nameField = document.getElementById('name');
            if (nameField) {
                nameField.focus();
            }

            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.classList.add('fade-out');
                    setTimeout(() => {
                        message.remove();
                    }, 300);
                }, 5000);
            });

            // Records Modal handling
            const recordsModal = document.getElementById('recordsModal');
            const viewBtn = document.getElementById('viewSubmissionsBtn');
            const closeModal = document.querySelector('.close-modal');

            viewBtn.addEventListener('click', function() {
                recordsModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });

            closeModal.addEventListener('click', function() {
                recordsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });

            recordsModal.addEventListener('click', function(e) {
                if (e.target === recordsModal) {
                    recordsModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });

            // Edit Modal handling
            const editModal = document.getElementById('editModal');
            const closeEditModal = document.getElementById('closeEditModal');
            const cancelEdit = document.getElementById('cancelEdit');
            const editForm = document.getElementById('editForm');

            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const date = this.getAttribute('data-date');
                    
                    document.getElementById('editId').value = id;
                    document.getElementById('editName').value = name;
                    document.getElementById('editDate').value = date;
                    
                    recordsModal.style.display = 'none';
                    editModal.style.display = 'flex';
                });
            });

            closeEditModal.addEventListener('click', function() {
                editModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });

            cancelEdit.addEventListener('click', function() {
                editModal.style.display = 'none';
                recordsModal.style.display = 'flex';
            });

            editModal.addEventListener('click', function(e) {
                if (e.target === editModal) {
                    editModal.style.display = 'none';
                    recordsModal.style.display = 'flex';
                }
            });

            // Close modals with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (recordsModal.style.display === 'flex') {
                        recordsModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                    if (editModal.style.display === 'flex') {
                        editModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                }
            });

            // Show loading overlay on form submission
            document.getElementById('absentForm').addEventListener('submit', function() {
                document.getElementById('loadingOverlay').classList.add('active');
            });

            editForm.addEventListener('submit', function() {
                document.getElementById('loadingOverlay').classList.add('active');
            });
        });
    </script>
</body>
</html>