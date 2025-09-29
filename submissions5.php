<?php
// Set the default timezone to your local timezone
date_default_timezone_set('Asia/Manila');

// Database configuration
$dbHost     = 'localhost';
$dbUsername = 'mdrrjvhm_xanxus47';
$dbPassword = 'oneLASTsong32';
$dbName     = 'mdrrjvhm_submissions_db';

// Connect to database
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL connection timezone
$conn->query("SET time_zone = '+08:00'");

// Initialize variables
$tableCreationMessage = '';
$submissionSuccess = false;
$submissionTime = '';
$nameError = '';

// Get current date info
$year = date('Y');
$month = date('F'); // Full month name
$day = (int)date('j'); // Day of the month, no leading zeros
$submissionTime = date('Y-m-d H:i:s');

// Determine table name based on date
if ($day >= 16) {
    $currentTable = "submissions_{$month}_16_{$year}";
} else {
    $currentTable = "submissions_{$month}_1_{$year}";
}

// Function to safely create table
function createTableIfNotExists($conn, $tableName) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($checkTable->num_rows == 0) {
        $createTableSQL = "CREATE TABLE `$tableName` (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            particulars TEXT NOT NULL,
            submission_time DATETIME NOT NULL,
            INDEX idx_name (name),
            INDEX idx_time (submission_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($createTableSQL)) {
            return "Table `$tableName` created successfully.";
        } else {
            return "Error creating table `$tableName`: " . $conn->error;
        }
    }
    return false;
}

// Automatically create table on the 1st or 16th
$tableMessage1 = $tableMessage2 = '';
if ($day == 1 || $day == 16) {
    // Always try to ensure both tables for this month exist
    $table1 = "submissions_{$month}_1_{$year}";        // 1st - 15th
    $table2 = "submissions_{$month}_16_{$year}";       // 16th - end

    $msg1 = createTableIfNotExists($conn, $table1);
    $msg2 = createTableIfNotExists($conn, $table2);

    if ($msg1) $tableCreationMessage .= $msg1 . "<br>";
    if ($msg2) $tableCreationMessage .= $msg2;
} else {
    // Just ensure the current table exists (fallback)
    $msg = createTableIfNotExists($conn, $currentTable);
    if ($msg) $tableCreationMessage = $msg;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strtoupper(trim($_POST['name'])); // Convert to uppercase
    $particulars = trim($_POST['particulars']);

    // Validate required fields
    if (empty($name)) {
        $nameError = "Name is required.";
    } elseif (empty($particulars)) {
        $nameError = "Particulars are required.";
    } else {
        // Double-check table exists before inserting
        $checkTable = $conn->query("SHOW TABLES LIKE '$currentTable'");
        if ($checkTable->num_rows == 0) {
            // Try to create it again if missing
            $createTableSQL = "CREATE TABLE `$currentTable` (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                particulars TEXT NOT NULL,
                submission_time DATETIME NOT NULL,
                INDEX idx_name (name),
                INDEX idx_time (submission_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            if (!$conn->query($createTableSQL)) {
                die("Critical error: Could not create table $currentTable - " . $conn->error);
            }
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO `$currentTable` (name, particulars, submission_time) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $name, $particulars, $submissionTime);
            if ($stmt->execute()) {
                $submissionSuccess = true;
            } else {
                $nameError = "Submission failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $nameError = "Database error: " . $conn->error;
        }
    }
}

// Get existing names (from all relevant tables in current month/year)
$existingNames = [];
$likePattern = "submissions_{$month}_%_{$year}";
$tablesQuery = $conn->query("SHOW TABLES LIKE '$likePattern'");

if ($tablesQuery) {
    while ($tableRow = $tablesQuery->fetch_row()) {
        $tableName = $tableRow[0];
        $nameQuery = $conn->query("SELECT DISTINCT name FROM `$tableName` ORDER BY name");
        if ($nameQuery) {
            while ($row = $nameQuery->fetch_assoc()) {
                $upperName = strtoupper($row['name']);
                if (!in_array($upperName, $existingNames)) {
                    $existingNames[] = $upperName;
                }
            }
        }
    }
    sort($existingNames);
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .alert {
            padding: 10px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success {
            background: #dff0d8;
            color: #3c763d;
            text-align: center;
        }
        .error {
            background: #f2dede;
            color: #a94442;
        }
        .info {
            background: #d9edf7;
            color: #31708f;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button {
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 20px auto 0;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            gap: 10px;
        }
        .submit-btn {
            background-color: #4CAF50;
            flex: 1;
        }
        .view-submissions {
            background-color: #337ab7;
            flex: 1;
        }
        .uppercase-input {
            text-transform: uppercase;
        }
        .uppercase-notice {
            font-size: 0.8em;
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Submission Form</h1>

    <!-- Show table creation message (admin info) -->
    <?php if (!empty($tableCreationMessage)): ?>
        <div class="alert info">
            <?php echo $tableCreationMessage; ?>
        </div>
    <?php endif; ?>

    <!-- Show success message -->
    <?php if ($submissionSuccess): ?>
        <div class="alert success">
            Form submitted successfully at <?php echo date('F j, Y g:i A', strtotime($submissionTime)); ?>
        </div>
        <script>
            // Clear form fields after successful submission
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('submissionForm').reset();
            });
        </script>
    <?php endif; ?>

    <!-- Show error message -->
    <?php if (!empty($nameError)): ?>
        <div class="alert error">
            <?php echo htmlspecialchars($nameError); ?>
        </div>
    <?php endif; ?>

    <form id="submissionForm" method="post">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" class="uppercase-input" value="<?php echo isset($name) && !$submissionSuccess ? htmlspecialchars($name) : ''; ?>" required>
            <div class="uppercase-notice">Name will be automatically converted to uppercase</div>
        </div>

        <div class="form-group">
            <label for="particulars">Particulars:</label>
            <textarea id="particulars" name="particulars" required><?php echo isset($particulars) && !$submissionSuccess ? htmlspecialchars($particulars) : ''; ?></textarea>
        </div>

        <div class="button-container">
            <button type="submit" class="submit-btn">SUBMIT</button>
            <button type="button" class="view-submissions" onclick="window.location.href='print-with-word1.php'">View Submissions</button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            
            // Force uppercase in real-time
            nameInput.addEventListener('input', function() {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.toUpperCase();
                // Preserve cursor position
                this.setSelectionRange(start, end);
            });
            
            // Handle paste events
            nameInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                const upperText = text.toUpperCase();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + upperText + this.value.substring(end);
                // Move cursor to end of pasted text
                this.setSelectionRange(start + upperText.length, start + upperText.length);
            });
        });
    </script>
</body>
</html>