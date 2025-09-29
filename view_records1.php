<?php
// ========================
// 🕒 SET TIMEZONE TO MANILA (PHT - UTC+8)
// ========================
date_default_timezone_set('Asia/Manila');

// ========================
// 🔧 CONFIGURATION
// ========================
$host = 'localhost';
$username = 'mdrrjvhm_xanxus47';
$password = 'oneLASTsong32';
$database = 'mdrrjvhm_overtime';
// Initialize variables
$message = '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;
// ========================
// 🔒 HANDLE DELETE (if POST request)
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $conn_temp = new mysqli($host, $username, $password, $database);
    if ($conn_temp->connect_error) {
        die("Connection failed: " . $conn_temp->connect_error);
    }
    $id = (int)$_POST['delete_id']; // Sanitize as integer
    $stmt = $conn_temp->prepare("DELETE FROM accident_reports WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Record deleted successfully.";
    } else {
        $message = "Error deleting record.";
    }
    $stmt->close();
    $conn_temp->close();
    // Redirect to avoid re-deletion on refresh
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=$page&msg=" . urlencode($message));
    exit;
}
// ========================
// 📦 DATABASE CONNECTION & DATA FETCH
// ========================
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Pagination
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;
// Total records
$total_sql = "SELECT COUNT(*) AS total FROM accident_reports";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);
// Enforce page bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $records_per_page;
}
// Fetch records
$sql = "SELECT * FROM accident_reports ORDER BY submitted_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        :root {
            --primary: #dc2626;
            --primary-dark: #b91c1c;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            margin: 0;
            padding: 1rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        header p {
            color: var(--text-secondary);
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: var(--primary-dark);
        }
        /* Desktop Table Styles */
        .desktop-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .desktop-table thead {
            background: var(--primary);
            color: white;
        }
        .desktop-table th, .desktop-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .desktop-table tbody tr:hover {
            background-color: #fef2f2;
        }
        .desktop-table th {
            font-weight: 600;
        }
        
        /* Mobile Card Styles */
        .mobile-cards {
            display: none;
        }
        .card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .card-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .card-body {
            padding: 1rem;
        }
        .card-row {
            display: flex;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
        }
        .card-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .card-label {
            font-weight: 600;
            width: 40%;
            color: var(--text-secondary);
        }
        .card-value {
            width: 60%;
            word-break: break-word;
        }
        .no-records {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
            font-style: italic;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin: 2rem 0;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-primary);
            min-width: 44px;
            text-align: center;
        }
        .pagination a:hover {
            background-color: #fef2f2;
            border-color: var(--primary);
        }
        .pagination span {
            background: var(--primary);
            color: white;
            font-weight: bold;
        }
        .timestamp {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .delete-btn:hover {
            background: #dc2626;
        }
        .message {
            text-align: center;
            padding: 1rem;
            margin-bottom: 1.5rem;
            background: #dc2626;
            color: white;
            border-radius: 8px;
        }
        
        /* Mobile-first responsive design */
        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }
            header h1 {
                font-size: 1.5rem;
            }
            .desktop-table {
                display: none;
            }
            .mobile-cards {
                display: block;
            }
            .card-header h3 {
                font-size: 1rem;
            }
            .card-row {
                flex-direction: column;
            }
            .card-label, .card-value {
                width: 100%;
            }
            .card-label {
                margin-bottom: 0.25rem;
            }
            .delete-btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            .pagination a, .pagination span {
                padding: 0.5rem 0.75rem;
                margin-bottom: 0.25rem;
            }
        }
        
        /* For larger screens */
        @media (min-width: 769px) {
            .mobile-cards {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fa fa-file-text"></i> Overtime Records</h1>
            <p>View all submitted reports (<?= $total_records ?> total)</p>
        </header>
        <!-- Display Message -->
        <?php
        if (isset($_GET['msg'])) {
            $message = htmlspecialchars($_GET['msg']);
            echo "<div class='message'>$message</div>";
        }
        ?>
        
        <?php if ($result->num_rows > 0): ?>
            <!-- Desktop Table View -->
            <table class="desktop-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Year</th>
                        <th>Job Title</th>
                        <th>Division</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Reason</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['year']) ?></td>
                            <td><?= htmlspecialchars($row['job_title']) ?></td>
                            <td><?= htmlspecialchars($row['division']) ?></td>
                            <td><?= htmlspecialchars($row['start_time']) ?></td>
                            <td><?= htmlspecialchars($row['end_time']) ?></td>
                            <td style="white-space: pre-wrap;"><?= htmlspecialchars($row['details']) ?></td>
                            <td class="timestamp"><?= date('M j, Y g:i A', strtotime($row['submitted_at'])) ?></td>
                            <td>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- Mobile Card View -->
            <div class="mobile-cards">
                <?php 
                // Reset the result pointer to loop through records again
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()): 
                ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="delete-btn"><i class="fa fa-trash"></i> Delete</button>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="card-row">
                                <div class="card-label">Date:</div>
                                <div class="card-value"><?= htmlspecialchars($row['date']) ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">Year:</div>
                                <div class="card-value"><?= htmlspecialchars($row['year']) ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">Job Title:</div>
                                <div class="card-value"><?= htmlspecialchars($row['job_title']) ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">Division:</div>
                                <div class="card-value"><?= htmlspecialchars($row['division']) ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">Start Time:</div>
                                <div class="card-value"><?= htmlspecialchars($row['start_time']) ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">End Time:</div>
                                <div class="card-value"><?= htmlspecialchars($row['end_time']) ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">Reason:</div>
                                <div class="card-value"><?= htmlspecialchars($row['details']) ?></div>
                            </div>
                            <div class="card-row">
                                <div class="card-label">Submitted At:</div>
                                <div class="card-value timestamp"><?= date('M j, Y g:i A', strtotime($row['submitted_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination Controls -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">Previous</a>
                <?php else: ?>
                    <span>Previous</span>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $start + 4);
                if ($end - $start < 4) {
                    $start = max(1, $end - 4);
                }
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">Next</a>
                <?php else: ?>
                    <span>Next</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="no-records">No records found. Start by submitting a report.</p>
        <?php endif; ?>
        <?php
        $stmt->close();
        $conn->close();
        ?>
    </div>
    <!-- Auto-hide message after 3 seconds -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const message = document.querySelector('.message');
            if (message) {
                setTimeout(() => {
                    message.style.transition = "opacity 0.5s";
                    message.style.opacity = "0";
                    setTimeout(() => message.remove(), 600);
                }, 3000);
            }
        });
    </script>
</body>
</html>