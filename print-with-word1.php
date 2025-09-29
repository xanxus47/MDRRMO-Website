<?php
// Set the default timezone
date_default_timezone_set('Asia/Manila');

// Database configuration
$dbHost     = 'localhost';
$dbUsername = 'mdrrjvhm_xanxus47';
$dbPassword = 'oneLASTsong32';
$dbName     = 'mdrrjvhm_submissions_db';

// Connect to database
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+08:00'");

// Initialize variables
$searchTerm = '';
$view = 'first'; // Default: 1st–15th
$year = date('Y');
$month = date('F'); // Full month name, e.g., July
$currentMonth = date('Y-m');

// Table naming logic (must match form script)
$table1 = "submissions_{$month}_1_{$year}";     // 1st - 15th
$table2 = "submissions_{$month}_16_{$year}";    // 16th - end

// Validate view
if (isset($_GET['view']) && in_array($_GET['view'], ['first', 'second'])) {
    $view = $_GET['view'];
}

// Build WHERE clause
$monthCondition = "DATE(submission_time) LIKE '$currentMonth-%'";
$dateClause = '';
if ($view == 'first') {
    $dateClause = "$monthCondition AND DAY(submission_time) <= 15";
    $tables = [$table1];
} else {
    $dateClause = "$monthCondition AND DAY(submission_time) >= 16";
    $tables = [$table2];
}

// Apply search filter
$whereClause = $dateClause;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $conn->real_escape_string($_GET['search']);
    $whereClause .= " AND (name LIKE '%$searchTerm%' OR particulars LIKE '%$searchTerm%')";
}

// Handle Word generation
if (isset($_GET['generate_word'])) {
    if (!isset($_GET['reviewed_approved']) || !isset($_GET['checked_approved'])) {
        die("Error: Both 'Reviewed By' and 'Checked By' must be approved before generating the document.");
    }

    require_once 'PHPWord/src/PhpWord/Autoloader.php';
    \PhpOffice\PhpWord\Autoloader::register();

    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $sectionStyle = [
        'breakType' => 'continuous',
        'marginLeft' => 1000,
        'marginRight' => 1000,
        'marginTop' => 1000,
        'marginBottom' => 1000
    ];
    $section = $phpWord->addSection($sectionStyle);
    $section->addText('Accomplishment Report', ['name' => 'Arial', 'size' => 16, 'bold' => true], ['alignment' => 'center']);
    $section->addText('Generated on: ' . date('F j, Y g:i A'), ['name' => 'Arial', 'size' => 10]);

    $lastDay = date('t');
    $period = $view == 'first' ? "1st - 15th" : "16th - {$lastDay}";
    $section->addText("Reporting Period: {$month} $period", ['name' => 'Arial', 'size' => 10, 'italic' => true]);

    if (!empty($searchTerm)) {
        $section->addText('Search filter: "' . htmlspecialchars(urldecode($searchTerm)) . '"', ['name' => 'Arial', 'size' => 10]);
    }
    $section->addText('Approved by: FROILAN L. FERNANDEZ and JOHN BOSCO G. QUILIT', ['name' => 'Arial', 'size' => 10, 'italic' => true]);
    $section->addTextBreak(1);

    $foundData = false;

    foreach ($tables as $tableName) {
        // Check if table exists
        $check = $conn->query("SHOW TABLES LIKE '$tableName'");
        if ($check->num_rows == 0) continue;

        $query = "SELECT 
                    name,
                    DATE(submission_time) as submission_date,
                    GROUP_CONCAT(particulars SEPARATOR '\n') as particulars,
                    MIN(submission_time) as earliest_time,
                    MAX(submission_time) as latest_time
                  FROM `$tableName` 
                  WHERE $whereClause 
                  GROUP BY name, DATE(submission_time)
                  ORDER BY latest_time DESC";

        $result = $conn->query($query);
        if ($result === false) continue;

        while ($row = $result->fetch_assoc()) {
            $foundData = true;
            $dateStr = date('F j, Y', strtotime($row['submission_date']));
            $timeRange = ($row['earliest_time'] == $row['latest_time']) 
                ? date('g:i A', strtotime($row['earliest_time']))
                : date('g:i A', strtotime($row['earliest_time'])) . ' - ' . date('g:i A', strtotime($row['latest_time']));

            $section->addText($row['name'], ['name' => 'Arial', 'size' => 12, 'bold' => true]);
            $section->addText($dateStr . ' - ' . $timeRange, ['name' => 'Arial', 'size' => 10, 'italic' => true]);
            $section->addText($row['particulars'], ['name' => 'Arial', 'size' => 11]);
            $section->addTextBreak(2);
        }
    }

    if (!$foundData) {
        $section->addText('No submissions found for the selected period.', ['color' => 'red']);
    }

    $section->addTextBreak(3);
    $section->addText('Reviewed By:', ['name' => 'Arial', 'size' => 12], ['alignment' => 'left']);
    $section->addText('FROILAN L. FERNANDEZ', ['name' => 'Arial', 'size' => 12, 'bold' => true], ['alignment' => 'left']);
    $section->addTextBreak(2);
    $section->addText('Certified By:', ['name' => 'Arial', 'size' => 12], ['alignment' => 'left']);
    $section->addText('JOHN BOSCO G. QUILIT', ['name' => 'Arial', 'size' => 12, 'bold' => true], ['alignment' => 'left']);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="submissions_report_' . date('Ymd_His') . '.docx"');
    header('Cache-Control: max-age=0');
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;
}

// Fetch submissions for display
$allResults = [];
foreach ($tables as $tableName) {
    $check = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($check->num_rows == 0) continue;

    $query = "SELECT 
                name,
                DATE(submission_time) as submission_date,
                GROUP_CONCAT(particulars SEPARATOR '\n') as particulars,
                MIN(submission_time) as earliest_time,
                MAX(submission_time) as latest_time
              FROM `$tableName` 
              WHERE $whereClause 
              GROUP BY name, DATE(submission_time)
              ORDER BY latest_time DESC";

    $result = $conn->query($query);
    if ($result === false) continue;

    while ($row = $result->fetch_assoc()) {
        $allResults[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions Preview</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .search-container {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .search-button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .search-button:hover { background-color: #45a049; }
        .reset-button {
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .reset-button:hover { background-color: #d32f2f; }
        .word-button {
            padding: 10px 20px;
            background-color: #2b579a;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .word-button:hover { background-color: #1e3f74; }
        .button-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .submission-count {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.1em;
            color: #555;
        }
        .submission-item {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .submission-name {
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 5px;
            color: #333;
        }
        .submission-date {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .submission-particulars {
            white-space: pre-line;
            line-height: 1.5;
        }
        .no-submissions {
            text-align: center;
            font-size: 1.2em;
            color: #666;
            padding: 40px 0;
        }
        .time-info {
            font-size: 0.9em;
            color: #666;
            text-align: center;
            margin-bottom: 20px;
        }
        .search-info {
            text-align: center;
            font-style: italic;
            color: #666;
            margin-bottom: 20px;
        }
        .approval-section {
            margin: 30px 0;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .approval-row {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .approval-box {
            text-align: center;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 40%;
        }
        .approval-checkbox {
            margin-right: 10px;
            transform: scale(1.5);
        }
        .approval-label {
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover { background-color: #45a049; }
        .toggle-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #ff9800;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .toggle-button:hover { background-color: #e68900; }
    </style>
    <script>
        function validateApproval() {
            const reviewed = document.getElementById('reviewed_approved').checked;
            const checked = document.getElementById('checked_approved').checked;
            if (!reviewed || !checked) {
                alert('Both "Reviewed By" and "Checked By" must be approved before generating the document.');
                return false;
            }
            return true;
        }
        function prepareDownload() {
    if (validateApproval()) {
        const searchTerm = "<?php echo !empty($searchTerm) ? urlencode($searchTerm) : ''; ?>";
        const currentView = "<?php echo $view; ?>"; // Capture current view (first or second)
        let url = 'print-with-word1.php?generate_word=1&view=' + currentView;
        if (searchTerm) {
            url += '&search=' + searchTerm;
        }
        url += '&reviewed_approved=1&checked_approved=1';
        window.location.href = url;
    }
}
    </script>
</head>
<body>
    <h1>Submissions Preview</h1>
    <div class="time-info">
        Current server time: <?php echo date('F j, Y g:i A'); ?>
    </div>

    <form method="GET" action="print-with-word1.php" class="search-container">
        <input type="text" name="search" class="search-input" placeholder="Search by name or particulars..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit" class="search-button">Search</button>
        <?php if (!empty($searchTerm)): ?>
            <a href="print-with-word1.php" class="reset-button">Reset</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($searchTerm)): ?>
        <div class="search-info">Showing results for: "<?php echo htmlspecialchars($searchTerm); ?>"</div>
    <?php endif; ?>

    <!-- Toggle View -->
    <div style="text-align: center; margin: 10px 0 20px;">
        <?php
        $toggleView = ($view == 'first') ? 'second' : 'first';
        $toggleLabel = ($view == 'first') ? 'Switch to 16th–31st' : 'Switch to 1st–15th';
        $params = array_filter(['view' => $toggleView, 'search' => $searchTerm]);
        $urlParams = http_build_query($params);
        ?>
        <a href="?<?php echo $urlParams; ?>" class="toggle-button">🔁 <?php echo $toggleLabel; ?></a>
    </div>

    <!-- Current Period -->
    <div class="search-info">
        <strong>Currently viewing: <?php echo ($view == 'first') ? "$month 1 – 15" : "$month 16 – " . date('t'); ?></strong>
    </div>

    <!-- Submission Count -->
    <div class="submission-count">
        <?php echo count($allResults); ?> day<?php echo count($allResults) != 1 ? 's' : ''; ?> of submission<?php echo count($allResults) != 1 ? 's' : ''; ?>
        <?php if (!empty($searchTerm)) echo ' found'; ?>
    </div>

    <!-- Display Submissions -->
    <?php if (count($allResults) > 0): ?>
        <div class="submissions-list">
            <?php foreach ($allResults as $row): ?>
                <div class="submission-item">
                    <div class="submission-name"><?php echo htmlspecialchars($row['name']); ?></div>
                    <div class="submission-date">
                        <?php 
                        $dateStr = date('F j, Y', strtotime($row['submission_date']));
                        $timeRange = ($row['earliest_time'] == $row['latest_time']) 
                            ? date('g:i A', strtotime($row['earliest_time']))
                            : date('g:i A', strtotime($row['earliest_time'])) . ' - ' . date('g:i A', strtotime($row['latest_time']));
                        echo $dateStr . ' • ' . $timeRange;
                        ?>
                    </div>
                    <div class="submission-particulars"><?php echo nl2br(htmlspecialchars($row['particulars'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-submissions">No submissions found<?php echo !empty($searchTerm) ? ' matching your search' : ''; ?>.</div>
    <?php endif; ?>

    <!-- Approval Section -->
    <div class="approval-section">
        <h3 style="text-align: center; margin-bottom: 20px;">Approval Required</h3>
        <div class="approval-row">
            <div class="approval-box">
                <label class="approval-label">
                    <input type="checkbox" id="reviewed_approved" class="approval-checkbox"> Reviewed and Approved By: FROILAN L. FERNANDEZ
                </label>
            </div>
            <div class="approval-box">
                <label class="approval-label">
                    <input type="checkbox" id="checked_approved" class="approval-checkbox"> Checked and Approved By: JOHN BOSCO G. QUILIT
                </label>
            </div>
        </div>
    </div>

    <!-- Generate Word Button -->
    <div class="button-container">
        <button onclick="prepareDownload()" class="word-button">Generate Word Document</button>
    </div>

    <!-- Back Link -->
    <a href="submissions5.php" class="back-link">Back to Submission Form</a>
</body>
</html>