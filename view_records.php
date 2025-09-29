<?php
// ========================
// 🕒 SET TIMEZONE TO MANILA (PHT - UTC+8)
// ========================
date_default_timezone_set('Asia/Manila');
// 🔧 CONFIGURE DATABASE
$host = 'localhost';
$username = 'mdrrjvhm_xanxus47';  // ← Change if different
$password = 'oneLASTsong32';      // ← Change if different
$database = 'mdrrjvhm_overtime';  // ← Change if different
// Create connection
$conn = new mysqli($host, $username, $password, $database);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// 🔢 Pagination setup
$records_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;
$offset = ($page - 1) * $records_per_page;
// Get total number of records
$total_sql = "SELECT COUNT(*) AS total FROM accident_reports";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page);
// Fetch records for current page
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
            cursor: pointer;
            position: relative;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1rem;
            padding-right: 30px;
        }
        .card-header .summary {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
        .card-header .toggle-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s;
        }
        .card-header.expanded .toggle-icon {
            transform: translateY(-50%) rotate(180deg);
        }
        .card-body {
            padding: 1rem;
            display: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
        }
        .card-body.expanded {
            display: block;
            max-height: 1000px;
            transition: max-height 0.5s ease-in, padding 0.3s ease-in;
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
                        <div class="card-header" onclick="toggleCard(this)">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <div class="summary">
                                <?= htmlspecialchars($row['date']) ?> | <?= htmlspecialchars($row['job_title']) ?>
                            </div>
                            <i class="fa fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="card-body">
                            <div class="card-row">
                                <div class="card-label">Year:</div>
                                <div class="card-value"><?= htmlspecialchars($row['year']) ?></div>
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
                // Show up to 5 page links (centered around current page)
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

    <script>
        function toggleCard(header) {
            const cardBody = header.nextElementSibling;
            const isExpanded = header.classList.contains('expanded');
            
            // Toggle the expanded class
            header.classList.toggle('expanded');
            cardBody.classList.toggle('expanded');
            
            // Close all other cards
            const allCards = document.querySelectorAll('.card');
            allCards.forEach(card => {
                if (card.querySelector('.card-header') !== header) {
                    card.querySelector('.card-header').classList.remove('expanded');
                    card.querySelector('.card-body').classList.remove('expanded');
                }
            });
        }
    </script>
</body>
</html>