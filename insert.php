<?php
include 'includes/db_connect.php';

session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Database configuration
$db_host = 'localhost';
$db_user = 'mdrrjvhm_xanxus47';
$db_pass = 'oneLASTsong32';

// Process database selection if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_db = isset($_POST['selected_database']) ? sanitize_input($_POST['selected_database']) : 'mdrrjvhm_2025_records';
    $selected_table = isset($_POST['selected_table']) ? sanitize_input($_POST['selected_table']) : 'georisk';

    // Check if creating new table
    if (isset($_POST['new_table_name']) && !empty($_POST['new_table_name'])) {
        $selected_table = sanitize_input($_POST['new_table_name']);
    }

    // Create database connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $selected_db);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS `$selected_table` (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        province VARCHAR(100) NOT NULL,
        municipality VARCHAR(100) NOT NULL,
        organization_name VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        contact_number VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        trainings_attended TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($create_table)) {
        die("Error creating table: " . $conn->error);
    }

    // Process form submission if all fields are present
    if (isset($_POST['province']) && isset($_POST['municipality']) && isset($_POST['organization_name']) &&
        isset($_POST['name']) && isset($_POST['contact_number']) && isset($_POST['address']) &&
        isset($_POST['trainings_attended'])) {

        // Sanitize and validate input data
        $province = sanitize_input($_POST['province']);
        $municipality = sanitize_input($_POST['municipality']);
        $organization_name = sanitize_input($_POST['organization_name']);
        $name = sanitize_input($_POST['name']);
        $contact_number = sanitize_input($_POST['contact_number']);
        $address = sanitize_input($_POST['address']);
        $trainings_attended = sanitize_input($_POST['trainings_attended']);

        // Insert data into database
        $stmt = $conn->prepare("INSERT INTO `$selected_table` (province, municipality, organization_name, name, contact_number, address, trainings_attended) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $province, $municipality, $organization_name, $name, $contact_number, $address, $trainings_attended);

        if ($stmt->execute()) {
            $success_message = "Attendee details saved successfully to <strong>$selected_db.$selected_table</strong>!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
    $conn->close();
}

// Get available databases and tables
$temp_conn = new mysqli($db_host, $db_user, $db_pass);
if ($temp_conn->connect_error) {
    die("Connection failed: " . $temp_conn->connect_error);
}

// Get available databases
$dbs_result = $temp_conn->query("SHOW DATABASES LIKE '%_records'");
$available_dbs = [];
while ($row = $dbs_result->fetch_array()) {
    $available_dbs[] = $row[0];
}

// Set default selected database
$selected_db = isset($_POST['selected_database']) ? $_POST['selected_database'] : (isset($selected_db) ? $selected_db : 'mdrrjvhm_2023_records');

// Get tables for selected database
$temp_conn->select_db($selected_db);
$tables_result = $temp_conn->query("SHOW TABLES");
$available_tables = [];
while ($row = $tables_result->fetch_array()) {
    $available_tables[] = $row[0];
}

$temp_conn->close();

// Set default selected table
$selected_table = isset($_POST['selected_table']) ? $_POST['selected_table'] : (isset($selected_table) ? $selected_table : 'georisk');

// Function to sanitize form inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendee Details Form</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --primary-light: #eef2ff;
      --primary-dark: #3a56d4;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --error: #f72585;
      --text: #2b2d42;
      --text-light: #6c757d;
      --light: #f8f9fa;
      --border: #e9ecef;
      --radius: 12px;
      --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s ease;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      line-height: 1.6;
      color: var(--text);
      background-color: #f5f7ff;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background-image: url('https://images.unsplash.com/photo-1518655048521-f130df041f66?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      background-blend-mode: overlay;
      background-color: rgba(245, 247, 255, 0.9);
    }

    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(63, 55, 201, 0.1) 100%);
      z-index: -1;
    }

    .header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 20px 0;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      position: relative;
      z-index: 10;
    }

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 10px;
    }

    .logo i {
      font-size: 32px;
      color: white;
    }

    .logo-text {
      font-size: 24px;
      font-weight: 600;
    }

    .event-title {
      font-size: 18px;
      font-weight: 400;
      opacity: 0.9;
      margin-bottom: 5px;
    }

    .event-date {
      font-size: 14px;
      opacity: 0.8;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 20px;
      width: 100%;
      position: relative;
    }

    .card {
      background: white;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 40px;
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      border: 1px solid rgba(67, 97, 238, 0.1);
      backdrop-filter: blur(5px);
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    h2 {
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 30px;
      color: var(--primary);
      text-align: center;
      position: relative;
      padding-bottom: 15px;
    }

    h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      border-radius: 3px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-group.full-width {
      grid-column: span 2;
    }

    .address-row {
      grid-column: span 2;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
    }

    .float-label {
      position: relative;
    }

    .float-label input,
    .float-label select,
    .float-label textarea {
      width: 100%;
      padding: 16px 18px;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 15px;
      transition: var(--transition);
      background-color: var(--light);
      color: var(--text);
      text-align: left;
    }

    .float-label input:focus,
    .float-label select:focus,
    .float-label textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      background-color: white;
    }

    .float-label label {
      position: absolute;
      top: 16px;
      left: 18px;
      font-size: 15px;
      color: var(--text-light);
      background: white;
      padding: 0 5px;
      pointer-events: none;
      transition: all 0.2s ease;
      z-index: 1;
    }

    .float-label input:focus + label,
    .float-label input:not(:placeholder-shown) + label,
    .float-label select:focus + label,
    .float-label select:not(:placeholder-shown) + label,
    .float-label select:not([value=""]) + label,
    .float-label textarea:focus + label,
    .float-label textarea:not(:placeholder-shown) + label {
      top: -10px;
      left: 15px;
      font-size: 12px;
      color: var(--primary);
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
      font-size: 16px;
      pointer-events: none;
    }

    .uppercase {
      text-transform: uppercase;
    }

    textarea {
      min-height: 60px;
      resize: vertical;
      line-height: 1.5;
    }

    .form-footer {
      margin-top: auto;
      padding-top: 20px;
    }

    button {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 16px 24px;
      border-radius: var(--radius);
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      width: 100%;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    button:hover {
      background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
    }

    button:active {
      transform: translateY(0);
    }

    .required::after {
      content: " *";
      color: var(--error);
    }

    .note {
      font-size: 12px;
      color: var(--text-light);
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 5px;
      padding-left: 5px;
    }

    .note i {
      font-size: 14px;
    }

    .alert {
      padding: 16px 20px;
      margin-bottom: 25px;
      border-radius: var(--radius);
      display: flex;
      align-items: center;
      gap: 12px;
      animation: fadeIn 0.3s ease;
    }

    .alert i {
      font-size: 20px;
    }

    .alert-success {
      background-color: rgba(76, 201, 240, 0.1);
      color: #0a6c74;
      border-left: 4px solid var(--success);
    }

    .alert-error {
      background-color: rgba(247, 37, 133, 0.1);
      color: #a4133c;
      border-left: 4px solid var(--error);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .footer {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 20px 0;
      text-align: center;
      font-size: 14px;
      margin-top: auto;
    }

    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
    }

    .social-links {
      display: flex;
      gap: 15px;
      margin-top: 10px;
    }

    .social-links a {
      color: white;
      font-size: 18px;
      transition: var(--transition);
    }

    .social-links a:hover {
      transform: translateY(-3px);
      opacity: 0.8;
    }

    /* Custom Select Arrow */
    select {
      appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 16px center;
      background-size: 16px;
      padding-right: 40px;
    }

    /* Particles Background */
    .particles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      pointer-events: none;
    }

    .particle {
      position: absolute;
      background: rgba(67, 97, 238, 0.2);
      border-radius: 50%;
      animation: float linear infinite;
    }

    @keyframes float {
      0% {
        transform: translateY(0) rotate(0deg);
      }
      100% {
        transform: translateY(-100vh) rotate(360deg);
      }
    }

    /* MOBILE RESPONSIVE STYLES */
    @media (max-width: 768px) {
      .container {
        margin: 20px auto;
        padding: 0 15px;
      }

      .card {
        padding: 25px 15px;
      }

      .form-grid,
      .address-row {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .form-group.full-width {
        grid-column: span 1;
      }

      .address-row {
        display: flex;
        flex-direction: column;
        gap: 15px;
      }

      h2 {
        font-size: 22px;
        margin-bottom: 20px;
      }

      .logo-text {
        font-size: 18px;
      }

      .event-title {
        font-size: 14px;
      }

      .event-date {
        font-size: 12px;
      }

      .note {
        font-size: 11px;
      }

      .float-label input,
      .float-label select,
      .float-label textarea {
        padding: 14px 16px;
        font-size: 15px;
      }

      .float-label label {
        font-size: 14px;
        top: 14px;
        left: 16px;
      }

      .float-label input:focus + label,
      .float-label input:not(:placeholder-shown) + label,
      .float-label select:focus + label,
      .float-label select:not(:placeholder-shown) + label,
      .float-label select:not([value=""]) + label,
      .float-label textarea:focus + label,
      .float-label textarea:not(:placeholder-shown) + label {
        top: -8px;
        left: 12px;
        font-size: 11px;
      }

      button {
        padding: 14px 20px;
        font-size: 15px;
      }

      .alert {
        padding: 12px 15px;
        font-size: 14px;
      }

      .alert i {
        font-size: 18px;
      }
      
      .form-group {
        margin-bottom: 15px;
      }
      
      .input-icon {
        right: 12px;
        font-size: 15px;
      }
      
      select {
        background-position: right 12px center;
        padding-right: 36px;
      }
      
      .header-content {
        padding: 0 15px;
      }
      
      .logo {
        gap: 10px;
      }
      
      .logo i {
        font-size: 28px;
      }
    }

    /* Extra small devices (phones, 400px and down) */
    @media (max-width: 400px) {
      .float-label input,
      .float-label select,
      .float-label textarea {
        padding: 12px 14px;
        font-size: 14px;
      }
      
      .float-label label {
        font-size: 13px;
        top: 12px;
        left: 14px;
      }
      
      .float-label input:focus + label,
      .float-label input:not(:placeholder-shown) + label,
      .float-label select:focus + label,
      .float-label select:not(:placeholder-shown) + label,
      .float-label select:not([value=""]) + label,
      .float-label textarea:focus + label,
      .float-label textarea:not(:placeholder-shown) + label {
        top: -7px;
        left: 10px;
        font-size: 10px;
      }
      
      button {
        padding: 12px 16px;
      }
      
      .note {
        font-size: 10px;
      }
      
      .logo-text {
        font-size: 16px;
      }
      
      .logo i {
        font-size: 24px;
      }
    }
  </style>
</head>
<body>
  <!-- Background Particles -->
  <div class="particles" id="particles-js"></div>

  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="logo">
        <i class="fas fa-user-graduate"></i>
        <div class="logo-text">MDRRMO Attendees System</div>
      </div>
      <div class="event-title">Municipal Disaster Risk Reduction & Management Office</div>
      <div class="event-date">
        <i class="far fa-calendar-alt"></i>
        <span>June 15-17, 2023 | Magsaysay, Occidental Mindoro</span>
      </div>
    </div>
  </header>

  <!-- Main Form -->
  <div class="container">
    <div class="card">
      <h2><i class="fas fa-user-plus"></i> Attendee Registration</h2>

      <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <div><?php echo $success_message; ?></div>
        </div>
      <?php endif; ?>

      <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <div><?php echo $error_message; ?></div>
        </div>
      <?php endif; ?>

      <form id="attendeeForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <!-- Database & Table Selection -->
        <div class="form-grid">
          <div class="form-group float-label">
            <select id="selected_database" name="selected_database" required onchange="this.form.submit()">
              <option value="" disabled>Select Database</option>
              <?php foreach ($available_dbs as $db): ?>
                <option value="<?php echo $db; ?>" <?php echo ($selected_db == $db) ? 'selected' : ''; ?>>
                  <?php echo $db; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <label for="selected_database" class="required">Database</label>
          </div>

          <div class="form-group float-label">
            <select id="selected_table" name="selected_table" required onchange="this.form.submit()">
              <option value="" disabled>Select Table</option>
              <?php foreach ($available_tables as $table): ?>
                <option value="<?php echo $table; ?>" <?php echo ($selected_table == $table) ? 'selected' : ''; ?>>
                  <?php echo $table; ?>
                </option>
              <?php endforeach; ?>
              <option value="new_table">+ Create New Table</option>
            </select>
            <label for="selected_table" class="required">Table</label>
          </div>
        </div>

        <!-- Form Fields -->
        <div class="form-grid">
          <div class="form-group float-label">
            <select id="province" name="province" required>
              <option value="" selected disabled></option>
              <option value="OCCIDENTAL MINDORO">OCCIDENTAL MINDORO</option>
              <option value="ORIENTAL MINDORO">ORIENTAL MINDORO</option>
              <option value="MARINDUQUE">MARINDUQUE</option>
              <option value="ROMBLON">ROMBLON</option>
              <option value="PALAWAN">PALAWAN</option>
            </select>
            <label for="province" class="required">Province</label>
          </div>

          <div class="form-group float-label">
            <select id="municipality" name="municipality" required>
              <option value="" selected disabled></option>
              <option value="ABRA DE ILOG">ABRA DE ILOG</option>
              <option value="CALINTAAN">CALINTAAN</option>
              <option value="LOOC">LOOC</option>
              <option value="LUBANG">LUBANG</option>
              <option value="MAGSAYSAY">MAGSAYSAY</option>
              <option value="MAMBURAO">MAMBURAO</option>
              <option value="PALUAN">PALUAN</option>
              <option value="RIZAL">RIZAL</option>
              <option value="SABLAYAN">SABLAYAN</option>
              <option value="SAN JOSE">SAN JOSE</option>
              <option value="SANTA CRUZ">SANTA CRUZ</option>
            </select>
            <label for="municipality" class="required">Municipality</label>
          </div>

          <div class="form-group full-width float-label">
            <input type="text" id="organization" name="organization_name" class="uppercase" placeholder=" " required>
            <label for="organization" class="required">Organization Name</label>
            <div class="note">
              <i class="fas fa-info-circle"></i>
              <span>Please enter in uppercase letters</span>
            </div>
          </div>

          <div class="form-group float-label">
            <input type="text" id="name" name="name" class="uppercase" placeholder=" " required>
            <label for="name" class="required">Full Name</label>
            <div class="note">
              <i class="fas fa-info-circle"></i>
              <span>Please enter in uppercase letters</span>
            </div>
          </div>

          <div class="form-group float-label">
            <div class="input-wrapper">
              <input type="tel" id="contact" name="contact_number" pattern="[0-9]{10,15}" placeholder=" " required>
              <label for="contact" class="required">Contact Number</label>
              <i class="fas fa-phone input-icon"></i>
            </div>
            <div class="note">
              <i class="fas fa-info-circle"></i>
              <span>Format: 09123456789 (10-15 digits)</span>
            </div>
          </div>

          <div class="address-row">
            <div class="form-group float-label">
              <textarea id="address" name="address" class="uppercase" placeholder=" " required></textarea>
              <label for="address" class="required">Complete Address</label>
              <div class="note">
                <i class="fas fa-info-circle"></i>
                <span>Please enter in uppercase letters</span>
              </div>
            </div>

            <div class="form-group float-label">
              <select id="trainings_attended" name="trainings_attended" required>
                <option value="" selected disabled></option>
                <option value="ALL HAZARD INCIDENT MANAGEMENT">ALL HAZARD INCIDENT MANAGEMENT</option>
                <option value="BICS">BICS</option>
                <option value="BLS">BLS</option>
                <option value="BLS">BLS SFA</option>
                <option value="CBDRRM">CBDRRM</option>
                <option value="CCCM">CCCM</option>
                <option value="GEORISK">GEORISK</option>
                <option value="BASIC GIS">GIS</option>
                <option value="ICS_EXE">ICS_EXE</option>
                <option value="ICS INTEGRATED PLANNING">ICS INTEGRATED PLANNING</option>
                <option value="ICS POSITION COURSE">ICS POSITION COURSE</option>
                <option value="MCI">MCI</option>
                <option value="SFA TOT">SFA TOT</option>
                <option value="LDRRMP">LDRRMP</option>
                <option value="MHPSS">MHPSS</option>
                <option value="WASAR">WASAR</option>
              </select>
              <label for="trainings_attended" class="required">Trainings Attended</label>
            </div>
          </div>
        </div>

        <div class="form-footer">
          <button type="submit">
            <i class="fas fa-paper-plane"></i>
            Submit Registration
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Auto Uppercase
    document.querySelectorAll('.uppercase').forEach(el => {
      el.addEventListener('input', function () {
        this.value = this.value.toUpperCase();
      });
      el.addEventListener('paste', function (e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        this.value = text.toUpperCase();
      });
    });

    // Page Load: Animate form groups
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.uppercase').forEach(el => {
        if (el.value) el.value = el.value.toUpperCase();
      });

      const formGroups = document.querySelectorAll('.form-group');
      formGroups.forEach((group, i) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(20px)';
        group.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        setTimeout(() => {
          group.style.opacity = '1';
          group.style.transform = 'translateY(0)';
        }, i * 100);
      });

      // Create Particles
      const container = document.getElementById('particles-js');
      const count = 25;
      for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.classList.add('particle');
        const s = Math.random() * 8 + 4;
        p.style.width = `${s}px`;
        p.style.height = `${s}px`;
        p.style.left = `${Math.random() * 100}%`;
        p.style.top = `${Math.random() * 100}%`;
        p.style.opacity = Math.random() * 0.5 + 0.2;
        p.style.animationDuration = `${Math.random() * 20 + 10}s`;
        p.style.animationDelay = `${Math.random() * 5}s`;
        container.appendChild(p);
      }
    });

    // Handle New Table Creation
    document.getElementById('selected_table').addEventListener('change', function () {
      if (this.value === 'new_table') {
        const name = prompt('Enter name for new table:');
        if (name && name.trim() !== '') {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'new_table_name';
          input.value = name.trim();
          this.form.appendChild(input);

          // Update option
          const opt = this.querySelector('option[value="new_table"]');
          opt.value = name.trim();
          opt.textContent = name.trim();

          this.value = name.trim();
          this.form.submit();
        } else {
          this.value = '';
        }
      }
    });
  </script>
</body>
</html>