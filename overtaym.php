<?php
session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}


// === DATABASE SAVE HANDLER (DO NOT REMOVE) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_form'])) {
    // 🔧 CONFIGURE THESE VALUES
    $host = 'localhost';           // Usually 'localhost'
    $username = 'mdrrjvhm_xanxus47';            // ← CHANGE ME
    $password = 'oneLASTsong32';                // ← CHANGE ME
    $database = 'mdrrjvhm_overtime';        // ← CHANGE ME

    // Create connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        // Log error or redirect to error page
        header('Location: error.php?msg=connection');
        $conn->close();
        exit;
    }

    // Sanitize and get form data
    $name = $conn->real_escape_string($_POST['mainBox'] ?? '');
    $date = $conn->real_escape_string($_POST['Box1'] ?? '');
    $year = $conn->real_escape_string($_POST['Box2'] ?? '');
    $job_title = $conn->real_escape_string($_POST['Box3'] ?? '');
    $division = $conn->real_escape_string($_POST['Box4'] ?? '');
    $start_time = $conn->real_escape_string($_POST['Box5'] ?? '');
    $end_time = $conn->real_escape_string($_POST['Box6'] ?? '');
    $details = $conn->real_escape_string($_POST['area1'] ?? '');

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO accident_reports (name, date, year, job_title, division, start_time, end_time, details, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssss", $name, $date, $year, $job_title, $division, $start_time, $end_time, $details);

    if ($stmt->execute()) {
        // ✅ Success: Close connections and redirect
        $stmt->close();
        $conn->close();
        header('Location: success.html'); // ← Change to your success page
        exit;
    } else {
        // ❌ Failure: Handle database error
        $stmt->close();
        $conn->close();
        header('Location: error.php?msg=db_error'); // Optional error page
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <script>
  window.onload = function () {
    const now = new Date();
    // Format to hh:mm AM/PM
    let hours = now.getHours();
    let minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12; // Convert 0 to 12
    minutes = minutes < 10 ? '0' + minutes : minutes;
    const timeString = `${hours}:${minutes} ${ampm}`;
    document.getElementById("timeBox").value = timeString;
  };
</script>
<head>
  <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="reportOT.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>Accident Report Form</title>
  <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        :root {
            --primary: #dc2626;
            --primary-dark: #b91c1c;
            --success: #10b981;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            width: 100%;
        }
        .demo-section {
            text-align: center;
            margin-bottom: 3rem;
        }
        .demo-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-text-fill-color: transparent;
        }
        .demo-section p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        .content-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }
        .content-preview {
            background: var(--background);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }
        .content-preview h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .content-preview p {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        .export-button {
            position: relative;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
            background: var(--primary);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            min-height: 48px;
            min-width: 200px;
        }
        .export-button:hover:not(.loading):not(.success) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .export-button:active:not(.loading):not(.success) {
            transform: translateY(0);
        }
        .export-button.loading {
            cursor: not-allowed;
            background: var(--primary);
        }
        .export-button.success {
            background: var(--success);
            cursor: default;
        }
        /* Button Content */
        .button-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: opacity 0.3s ease;
        }
        .export-button.loading .button-content,
        .export-button.success .button-content {
            opacity: 0;
        }
        /* Loading Spinner */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .export-button.loading .loading-spinner {
            opacity: 1;
        }
        .spinner {
            width: 100%;
            height: 100%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Success Checkmark */
        .success-checkmark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .export-button.success .success-checkmark {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        .checkmark-circle {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: draw-circle 0.6s ease-out forwards;
            animation-delay: 0.3s;
        }
        .checkmark-check {
            stroke-dasharray: 50;
            stroke-dashoffset: 50;
            animation: draw-check 0.3s ease-out forwards;
            animation-delay: 0.9s;
        }
        @keyframes draw-circle {
            to { stroke-dashoffset: 0; }
        }
        @keyframes draw-check {
            to { stroke-dashoffset: 0; }
        }
        /* Ripple Effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
        }
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        /* Progress Bar */
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.5);
            width: 0;
            transition: width 0.3s ease;
        }
        .export-button.loading .progress-bar {
            width: 100%;
            animation: progress-pulse 1.5s ease-in-out infinite;
        }
        @keyframes progress-pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        /* Success Message */
        .success-message {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--success);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .success-message.show {
            transform: translateX(0);
        }
        .success-message-icon {
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* PDF Icon Animation */
        .pdf-icon {
            width: 24px;
            height: 24px;
            transition: transform 0.3s ease;
        }
        .export-button:hover:not(.loading):not(.success) .pdf-icon {
            transform: translateY(-2px);
        }
        /* Button Variants */
        .button-variants {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 3rem;
        }
        .variant-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            text-align: center;
            min-width: 200px;
        }
        .variant-section h3 {
            margin-bottom: 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        /* Outline Button */
        .export-button.outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        .export-button.outline:hover:not(.loading):not(.success) {
            background: var(--primary);
            color: white;
        }
        .export-button.outline.loading {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        /* Gradient Button */
        .export-button.gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }
        .export-button.gradient:hover:not(.loading):not(.success) {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #991b1b 100%);
        }
        /* Rounded Button */
        .export-button.rounded {
            border-radius: 9999px;
        }
        /* Icon Button */
        .export-button.icon-only {
            width: 56px;
            height: 56px;
            padding: 0;
            border-radius: 50%;
        }
        /* Export Preview */
        .export-preview {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--background);
            border-radius: 12px;
            border: 2px dashed var(--border);
            display: none;
        }
        .export-preview.show {
            display: block;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .preview-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .preview-icon {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 768px) {
            .button-variants {
                flex-direction: column;
                align-items: center;
            }
            .success-message {
                right: 1rem;
                left: 1rem;
                transform: translateY(-100px);
            }
            .success-message.show {
                transform: translateY(0);
            }
            .content-card {
                padding: 2rem;
            }
        }
  </style>
  <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 500px;
            width: 100%;
        }
        .demo-section {
            text-align: center;
            margin-bottom: 3rem;
        }
        .demo-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-text-fill-color: transparent;
        }
        .demo-section p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        .form-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
        }
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .form-header p {
            color: var(--text-secondary);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            background: var(--surface);
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        /* Submit Button Styles */
        .submit-button {
            position: relative;
            width: 10%;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            min-height: 48px;
            margin-top: 1rem;
        }
        .submit-button:hover:not(.loading):not(.success) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .submit-button:active:not(.loading):not(.success) {
            transform: translateY(0);
        }
        .submit-button.loading {
            cursor: not-allowed;
            background: var(--primary);
        }
        .submit-button.success {
            background: var(--success);
            cursor: default;
        }
        /* Button Content */
        .button-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: opacity 0.3s ease;
        }
        .submit-button.loading .button-content,
        .submit-button.success .button-content {
            opacity: 0;
        }
        /* Loading Spinner */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .submit-button.loading .loading-spinner {
            opacity: 1;
        }
        .spinner {
            width: 100%;
            height: 100%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Success Checkmark */
        .success-checkmark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .submit-button.success .success-checkmark {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        .checkmark-circle {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: draw-circle 0.6s ease-out forwards;
            animation-delay: 0.3s;
        }
        .checkmark-check {
            stroke-dasharray: 50;
            stroke-dashoffset: 50;
            animation: draw-check 0.3s ease-out forwards;
            animation-delay: 0.9s;
        }
        @keyframes draw-circle {
            to { stroke-dashoffset: 0; }
        }
        @keyframes draw-check {
            to { stroke-dashoffset: 0; }
        }
        /* Ripple Effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
        }
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        /* Progress Bar */
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.5);
            width: 0;
            transition: width 0.3s ease;
        }
        .submit-button.loading .progress-bar {
            width: 100%;
            animation: progress-pulse 1.5s ease-in-out infinite;
        }
        @keyframes progress-pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        /* Success Message */
        .success-message {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--success);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .success-message.show {
            transform: translateX(0);
        }
        .success-message-icon {
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Submit Icon Animation */
        .submit-icon {
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }
        .submit-button:hover:not(.loading):not(.success) .submit-icon {
            transform: translateX(3px);
        }
        /* Button Variants */
        .button-variants {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 3rem;
        }
        .variant-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            text-align: center;
            min-width: 200px;
        }
        .variant-section h3 {
            margin-bottom: 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        /* Outline Button */
        .submit-button.outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        .submit-button.outline:hover:not(.loading):not(.success) {
            background: var(--primary);
            color: white;
        }
        .submit-button.outline.loading {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        /* Gradient Button */
        .submit-button.gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }
        .submit-button.gradient:hover:not(.loading):not(.success) {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1d4ed8 100%);
        }
        /* Rounded Button */
        .submit-button.rounded {
            border-radius: 9999px;
        }
        /* Icon Button */
        .submit-button.icon-only {
            width: 56px;
            height: 56px;
            padding: 0;
            border-radius: 50%;
        }
        /* Form Success State */
        .form-success {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        .form-success.show {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success-icon-large {
            width: 64px;
            height: 64px;
            background: var(--success);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        @media (max-width: 768px) {
            .button-variants {
                flex-direction: column;
                align-items: center;
            }
            .success-message {
                right: 1rem;
                left: 1rem;
                transform: translateY(-100px);
            }
            .success-message.show {
                transform: translateY(0);
            }
            .form-card {
                padding: 2rem;
            }
        }
    </style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
</script>
</head>
<body>
  <form action="" method="POST" enctype="multipart/form-data">
<div class="" style="margin-left: 1300px;">
   <!-- <button type="button" id="exportBtn" class="pdf-btn">
  <i class="fa fa-file-pdf-o"></i> Export as PDF
</button>-->
                <button  class="export-button" type="button" id="exportBtn" onclick="exportToPDF(this)" style="margin-left: 300px;">
                <div class="button-content">
                    <svg class="pdf-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M12,19L8,15H10.5V12H13.5V15H16L12,19Z"/>
                    </svg>
                    Export to PDF
                </div>
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
                <svg class="success-checkmark" width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <circle class="checkmark-circle" cx="24" cy="24" r="22" stroke="white" stroke-width="3" fill="none"/>
                    <path class="checkmark-check" d="M14 24l6 6 14-14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
                <div class="progress-bar"></div>
            </button>
                 <script>
        function handleSubmit(event) {
            event.preventDefault();
            const button = document.getElementById('submitButton');
            const form = document.getElementById('contactForm');
            const formSuccess = document.getElementById('formSuccess');
            submitForm(button, () => {
                // Hide form and show success message
                form.style.display = 'none';
                formSuccess.classList.add('show');
            });
        }
        function submitDemo(button) {
            submitForm(button);
        }
        function submitForm(button, callback) {
            // Prevent multiple clicks
            if (button.classList.contains('loading') || button.classList.contains('success')) {
                return;
            }
            // Add ripple effect
            createRipple(button, event);
            // Start loading state
            button.classList.add('loading');
            // Create form data
            const form = button.closest('form');
            const formData = new FormData(form);
            formData.append('submit_form', '1');
            // Send AJAX request
            fetch('', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                // Show success state
                button.classList.remove('loading');
                button.classList.add('success');
                // Show success message
                showSuccessMessage();
                // Execute callback if provided
                if (callback) {
                    callback();
                }
                // Reset form after successful submission
                if (data.status === 'success') {
                    // Clear all input fields
                    form.reset();
                    // Also clear readonly duplicate fields
                    document.getElementById('box1').value = '';
                    document.getElementById('box2').value = '';
                    document.getElementById('box3').value = '';
                    document.getElementById('area2').value = '';
                    document.getElementById('dup1').value = '';
                    document.getElementById('dup2').value = '';
                    document.getElementById('dup3').value = '';
                    document.getElementById('dup4').value = '';
                    document.getElementById('dup5').value = '';
                    document.getElementById('dup6').value = '';
                    // Auto-hide success message after 3 seconds
                    setTimeout(() => {
                        const successMsg = document.getElementById('successMessage');
                        successMsg.classList.remove('show');
                    }, 3000);
                }
                // Reset button after delay
                setTimeout(() => {
                    button.classList.remove('success');
                }, 3000);
            })
            .catch(error => {
                button.classList.remove('loading');
                console.error('Error:', error);
                // Handle error state
                button.classList.add('error');
                setTimeout(() => {
                    button.classList.remove('error');
                }, 3000);
            });
        }
        function createRipple(button, event) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            button.appendChild(ripple);
            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
        function showSuccessMessage() {
            const message = document.getElementById('successMessage');
            message.classList.add('show');
            // Auto hide after 3 seconds
            setTimeout(() => {
                message.classList.remove('show');
            }, 3000);
        }
        // Add keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const activeElement = document.activeElement;
                if (activeElement.classList.contains('submit-button')) {
                    e.preventDefault();
                    activeElement.click();
                }
            }
        });
    </script>
                <script>
           function exportToPDF(button) {
            // Prevent multiple clicks
            if (button.classList.contains('loading') || button.classList.contains('success')) {
                return;
            }
            // Add ripple effect
            createRipple(button, event);
            // Start loading state
            button.classList.add('loading');
            // Simulate PDF generation
            setTimeout(() => {
                // Show success state
                button.classList.remove('loading');
                button.classList.add('success');
                // Show success message
                showSuccessMessage();
                // Show export preview
                showExportPreview();
                // Simulate download
                simulateDownload();
                // Reset button after delay
                setTimeout(() => {
                    button.classList.remove('success');
                }, 3000);
            }, 2500);
        }
        function createRipple(button, event) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            button.appendChild(ripple);
            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
        function showSuccessMessage() {
            const message = document.getElementById('successMessage');
            message.classList.add('show');
            setTimeout(() => {
                message.classList.remove('show');
            }, 4000);
        }
        function showExportPreview() {
            const preview = document.getElementById('exportPreview');
            preview.classList.add('show');
            setTimeout(() => {
                preview.classList.remove('show');
            }, 5000);
        }
        function simulateDownload() {
            // Create a blob to simulate PDF download
            const pdfContent = `%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
Sections <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj
4 0 obj
<<
/Length 200
>>
stream
BT
/F1 24 Tf
100 700 Td
(Sample PDF Document) Tj
0 -30 Td
/F1 12 Tf
(This is a sample PDF file generated by the export button.) Tj
ET
endstream
endobj
5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000274 00000 n 
0000000531 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
621
%%EOF`;
            const blob = new Blob([pdfContent], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'report.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        // Add keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const activeElement = document.activeElement;
                if (activeElement.classList.contains('export-button')) {
                    e.preventDefault();
                    exportToPDF(activeElement);
                }
            }
        });
    </script></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
 <!--<button type="button" class="save-btn">
  <i class="fa fa-save"></i>
  <span>Save</span>
</button>-->  
</div>
 <div class="success-message" id="successMessage">
        <div class="success-message-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="white">
                <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
            </svg>
        </div>
        <div>
            <strong>Success!</strong>
            <p style="font-size: 0.875rem; margin-top: 0.25rem;">Your report has been saved successfully.</p>
        </div>
    </div>
<div>
<!--id="form-to-image" id="form-area"-->
    <div class="form-section" id="form1" style="width:1050px; height: 1600px; border-color:#ffffff;">
 <div class="form-section"  style="width:1000px; height: 710px;border-color:#ffffff;">
<img src="okinana.png" class="flot-left" width="100%" height="100%">
<div class="box1" style="border-color:black;">
<div class="" style="margin-left: 2px;  width:1000px; height: 178px;"></div>
<div class="" style="margin-left: 2px; width:1000px; height: 18px;">
<input type="text" style="width:100px; height: 20px;margin-left: 230px; font-size:15px;"id="Box1" placeholder="M/DD/YYYY" name="Box1">
<input type="text"  style="width:100px; height: 20px;margin-left: 440px; font-size:18px"id="Box2" value="2025-" name="Box2">
</div>
<div class="" style="margin-left: 2px; width:1000px; height: 18px;margin-top:4px;">
<input type="text" style="width:320px; height: 18px;margin-left: 230px;font-size:15px;"id="mainBox" placeholder="Name" name="mainBox">
</div>
<div class="" style="margin-left: 2px; width:1000px; height: 17px;margin-top:1.5px;">
<input type="text" style="width:260px; height: 18px;margin-left: 230px;font-size:16px;"id="Box3" placeholder="Communication Equipment Operator" name="Box3">
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 17px;margin-top:4px;">
<input type="text" style="width:220px; height: 17px;margin-left: 230px;font-size:16px;border-color:#ffffff;font-family:'Times New Roman', Times, serif; "id="Box4" placeholder="Operations and Warning" name="Box4">
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 16px;margin-top:3px;">
<input type="text"id="Box5" style="width:160px; height: 17px;margin-left: 230px;font-size:15px;border-color:#ffffff;font-family:'Times New Roman', Times, serif; " placeholder="08-18-25(8:00AM)" name="Box5">
<input id="Box6"type="text" style="width:160px; height: 17px;margin-left: 20px;font-size:15px;border-color:#ffffff;font-family:'Times New Roman', Times, serif; " placeholder="08-18-25(8:00PM)" name="Box6">
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 55px;margin-top:45px;">
<textarea name="area1" type="text"id="area1" rows="3" cols="10" style="resize:none;  width: 820px; height:55px;font-size:15px; font-family:'Times New Roman', Times, serif;margin-left:65px;text-align:center;align-items: center;justify-content:center;"></textarea>
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 57px;margin-top:11px;">
<input type="text" style="width:300px; height: 18px;margin-left: 70px;font-size:15px; font-weight:bold;"id="box1" placeholder="Name" name="box1">
</div>
      <!--other form-->
<div class="" style="margin-left: 2px; width:1000px; height: 18px;margin-top:442px">
<input type="text" style="width:100px; height: 17px;margin-left: 230px; font-size:15px;"id="dup1" placeholder="M/DD/YYYY"readonly>
<input type="text"readonly  style="width:100px; height: 20px;margin-left: 440px; font-size:18px"id="dup2" value="2025-"readonly>
</div>
<div class="" style="margin-left: 2px; width:1000px; height: 17px;margin-top:4.5px;">
<input type="text"readonly style="width:320px; height: 17px;margin-left: 230px;font-size:14.5px;"id="box2" placeholder="Name"readonly>
</div>
<div class="" style="margin-left: 2px; width:1000px; height: 17px;margin-top:3px;">
<input type="text"readonly style="width:260px; height: 17px;margin-left: 230px;font-size:15.5px;"id="dup3" placeholder="Communication Equipment Operator"readonly>
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 17px;margin-top:2.5px;">
<input type="text"readonly style="width:220px; height: 17px;margin-left: 230px;font-size:16px;border-color:#ffffff;font-family:'Times New Roman', Times, serif; " id="dup4"placeholder="Operations and Warning">
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 16px;margin-top:3px;">
<input id="dup5" type="text" readonly style="width:160px; height: 17px;margin-left: 230px;font-size:15px;border-color:#ffffff;font-family:'Times New Roman', Times, serif; " placeholder="08-18-25(8:00AM)">
<input id="dup6"type="text" readonly style="width:160px; height: 17px;margin-left: 20px;font-size:15px;border-color:#ffffff;font-family:'Times New Roman', Times, serif; " placeholder="08-18-25(8:00PM)">
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 57px;margin-top:44px;">
  <textarea type="text" id="area2" rows="3" cols="10" style="resize:none;width: 820px; height:57px;font-size:15px; font-family:'Times New Roman', Times, serif;margin-left:65px;text-align:center;align-items: center;justify-content:center; " readonly></textarea>
</div>
<div class="" style="margin-left: 4px; width:1000px; height: 57px;margin-top:10px;">
  <input type="text" style="width:300px; height: 18px;margin-left: 70px;font-size:15px;font-weight:bold;"id="box3" placeholder="Name"readonly>
</div>
      </div>
 <div class="form-section" style="width:1000px; height: 710px;border-color:#ffffff;">
<img src="okinana.png" class="flot-left" width="100%" height="100%">
      </div>
</div>
<button class="submit-button rounded" style="
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);" 
            type="submit" name="submit_form">
                    <div class="button-content">Submit</div>
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                    <svg class="success-checkmark" width="48" height="48" viewBox="0 0 48 48" fill="none">
                        <circle class="checkmark-circle" cx="24" cy="24" r="22" stroke="white" stroke-width="3" fill="none"/>
                        <path class="checkmark-check" d="M14 24l6 6 14-14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </svg>
                    <div class="progress-bar"></div>
                </button>
  </div>
</form>
<script>
{
    const mainBox = document.getElementById("mainBox");
    const boxes = [
      document.getElementById("box1"),
      document.getElementById("box2"),
      document.getElementById("box3")
    ];
    // Whenever user types in mainBox, copy to all others
    mainBox.addEventListener("input", () => {
      boxes.forEach(box => box.value = mainBox.value);
    });}
  </script>
<script>
    // Map each original box to its duplicate
    const pairs = [
      ["Box1", "dup1"],
      ["Box2", "dup2"],
      ["Box3", "dup3"],
      ["Box4", "dup4"],
      ["Box5", "dup5"],
      ["Box6", "dup6"],
      ["Box7", "dup7"]
    ];
    // Attach listeners
    pairs.forEach(([originalId, duplicateId]) => {
      const original = document.getElementById(originalId);
      const duplicate = document.getElementById(duplicateId);
      original.addEventListener("input", () => {
        duplicate.value = original.value;
      });
    });
  </script>
<script>
    window.onload = function () {
      // Get label text
      document.getElementById("input1").value = document.getElementById("label1").textContent;
      document.getElementById("input2").value = document.getElementById("label2").textContent;
      document.getElementById("input3").value = document.getElementById("label3").textContent;
    };
  </script>
  <script>
    const area1 = document.getElementById("area1");
    const area2 = document.getElementById("area2");
    // Copy text live
    area1.addEventListener("input", () => {
      area2.value = area1.value;
    });
  </script>
<script>
 function updateClock() {
      const now = new Date();
      let hours = now.getHours();
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12;
      hours = hours ? hours : 12; // 0 becomes 12
      hours = String(hours).padStart(2, '0');
      document.getElementById("clock44").textContent = `${hours}:${minutes} ${ampm}`;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
<script>
{
  function updateInput() {
    const checkbox = document.getElementById('myCheckbox');
    const target = document.getElementById('targetInput');
    target.value = checkbox.checked ? "true" : "false";
  }
}
{
  function updateInput1() {
    const checkbox = document.getElementById('myCheckbox1');
    const target = document.getElementById('targetInput1');
    target.value = checkbox.checked ? "true" : "false";
  }
}
{
  function updateInput2() {
    const checkbox = document.getElementById('myCheckbox2');
    const target = document.getElementById('targetInput2');
    target.value = checkbox.checked ? "true" : "false";
  }
}
{
  function updateInput3() {
    const checkbox = document.getElementById('myCheckbox3');
    const target = document.getElementById('targetInput3');
    target.value = checkbox.checked ? "true" : "false";
  }
}
{
  function updateInput4() {
    const checkbox = document.getElementById('myCheckbox4');
    const target = document.getElementById('targetInput4');
    target.value = checkbox.checked ? "true" : "false";
  }
}
{
  function updateInput5() {
    const checkbox = document.getElementById('myCheckbox5');
    const target = document.getElementById('targetInput5');
    target.value = checkbox.checked ? "true" : "false";
  }
}
</script>
 <script>
  // Run when the page is fully loaded
/*  {
    window.onload = function () {
    const labelText = document.getElementById("myLabel").textContent;
    document.getElementById("myInput").value = labelText;
  };
}*/
</script> 
<script>
    document.getElementById('textbox1').addEventListener('input', function() {
        let value = this.value;
        document.getElementById('textbox2').value = value;
        document.getElementById('textbox3').value = value;
    });
</script>
<script>src="fun.js"</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.0/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
  const { jsPDF } = window.jspdf;
  document.getElementById('exportBtn').addEventListener('click', () => {
    const doc = new jsPDF('p', 'mm', [220, 320] );
    const ids = ['form1'];
    const margin = 0;
    ids.reduce((promise, id, idx) => {
      return promise.then(() =>
        html2canvas(document.getElementById(id), { scale: 2 })
          .then(canvas => {
            const img = canvas.toDataURL('image/png');
            const pdfW = doc.internal.pageSize.getWidth() - margin * 2;
            const pdfH = (canvas.height * pdfW) / canvas.width;
            if (idx > 0) doc.addPage();
            doc.addImage(img, 'PNG', margin, margin, pdfW, pdfH);
          })
      );
    }, Promise.resolve())
    .then(() => doc.save('Overtime Request Form.pdf'));
  });
</script>
<script>
document.getElementById('downloadPdfBtn').addEventListener('click', () => {
  const element = document.getElementById('formContainer');
  html2pdf().set({
    margin: [-11,0,0,0],
    filename: 'form.pdf',
    image: { type: 'jpeg', quality: 1.98 },
    html2canvas: { scale: 2, logging: true, dpi: 0 },
    jsPDF: { unit: 'mm', format: [265, 397], orientation: 'portrait' }
  }).from(element).save();
});
</script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
   {
 function updateClock() {
      const now = new Date();
      let hours = now.getHours();
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12;
      hours = hours ? hours : 12; // 0 becomes 12
      hours = String(hours).padStart(2, '0');
      document.getElementById("clock66").textContent = `${hours}:${minutes} ${ampm}`;
    }
    setInterval(updateClock, 1000);
    updateClock();
  }
{ function formatDate(date) {
      const month = date.getMonth() + 1; // Months are 0-indexed
      const day = String(date.getDate()).padStart(2, '0');
      const year = date.getFullYear();
      return `${month}/${day}/${year}`;
    }
    const today = new Date();
    document.getElementById("dateDisplay5").textContent = formatDate(today);
  }
{ function formatDate(date) {
      const month = date.getMonth() + 1; // Months are 0-indexed
      const day = String(date.getDate()).padStart(2, '0');
      const year = date.getFullYear();
      return `${month}/${day}/${year}`;
    }
    const today = new Date();
    document.getElementById("dateDisplay4").textContent = formatDate(today);
  }
  { function formatDate(date) {
      const month = date.getMonth() + 1; // Months are 0-indexed
      const day = String(date.getDate()).padStart(2, '0');
      const year = date.getFullYear();
      return `${month}/${day}/${year}`;
    }
    const today = new Date();
    document.getElementById("dateDisplay3").textContent = formatDate(today);
  }
   {
function updateDate() {
      const now = new Date();
      const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
      const formatted = now.toLocaleDateString(undefined, options);
      document.getElementById('liveDateLabel').textContent = formatted;
    }
    // Update on page load
    updateDate();
    // Refresh every minute (60000ms)
    setInterval(updateDate, 60000);
    }
  {
function updateDate() {
      const now = new Date();
      const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
      const formatted = now.toLocaleDateString(undefined, options);
      document.getElementById('liveDateLabel1').textContent = formatted;
    }
    // Update on page load
    updateDate();
    // Refresh every minute (60000ms)
    setInterval(updateDate, 60000);
    } 
  {
function updateDate() {
      const now = new Date();
      const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
      const formatted = now.toLocaleDateString(undefined, options);
      document.getElementById('liveDateLabel2').textContent = formatted;
    }
    // Update on page load
    updateDate();
    // Refresh every minute (60000ms)
    setInterval(updateDate, 60000);
    }
{
    const fileInput = document.getElementById('fileInput1');
  const previewBox = document.getElementById('previewBox1');
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Uploaded Image">`;
      };
      reader.readAsDataURL(file);
    }
  });
  }
   {
    const fileInput = document.getElementById('fileInput2');
  const previewBox = document.getElementById('previewBox2');
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Uploaded Image">`;
      };
      reader.readAsDataURL(file);
    }
  });
  }
  {
    const fileInput = document.getElementById('fileInput3');
  const previewBox = document.getElementById('previewBox3');
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Uploaded Image">`;
      };
      reader.readAsDataURL(file);
    }
  });
  }
    {
    const fileInput = document.getElementById('fileInput4');
  const previewBox = document.getElementById('previewBox4');
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Uploaded Image">`;
      };
      reader.readAsDataURL(file);
    }
  });
  }
    {
    const fileInput = document.getElementById('fileInput5');
  const previewBox = document.getElementById('previewBox5');
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Uploaded Image">`;
      };
      reader.readAsDataURL(file);
    }
  });
  }
    {
    const fileInput = document.getElementById('fileInput6');
  const previewBox = document.getElementById('previewBox6');
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Uploaded Image">`;
      };
      reader.readAsDataURL(file);
    }
  });
  }
    {
    const fileInput = document.getElementById('fileInput7');
  const previewBox = document.getElementById('previewBox7');
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Uploaded Image">`;
      };
      reader.readAsDataURL(file);
    }
  });
  }
  {const input = document.getElementById('timeInput');
const display = document.getElementById('formattedTime');
input.addEventListener('change', () => {
  const [hour, minute] = input.value.split(':').map(Number);
  const suffix = hour >= 12 ? 'PM' : 'AM';
  const hr = ((hour + 11) % 12 + 1); // convert 0–23 to 1–12
  display.textContent = `${hr}:${minute.toString().padStart(2,'0')} ${suffix}`;
});}
  {
    function exportAsImage() {
      const element = document.getElementById('form-to-image');
      html2canvas(element).then(canvas => {
        const link = document.createElement('a');
        link.download = 'form.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
      });
    }
    }
     {
    function exportAsImage1() {
      const element = document.getElementById('form-to-image1');
      html2canvas(element).then(canvas => {
        const link = document.createElement('a');
         link.download = 'form_capture.png';
         link.style.display = 'inline';
         link.textContent = 'Download Image';
         link.href = canvas.toDataURL('image/png');
         link.click();
      });
    }
    }
     {
    function exportAsImage2() {
      const element = document.getElementById('form-to-image2');
      html2canvas(element).then(canvas => {
        const link = document.createElement('a');
        link.download = 'form.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
      });
    }
    }
{
  const btn1 = document.getElementById('btn1');
const btn2 = document.getElementById('btn2');
const btn3 = document.getElementById('btn3');
btn1.addEventListener('click', () => {
  btn2.removeAttribute('disabled');
});
btn2.addEventListener('click', () => {
  btn3.removeAttribute('disabled');
});
  btn1.addEventListener('click', () => {
  btn2.disabled = false;
});
btn2.addEventListener('click', () => {
  btn3.disabled = false;
});
}
</script>
 </body>
 </html>