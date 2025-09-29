<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$error = '';
$success = '';
$login_value = '';
$remaining_attempts = 3; // Default value

// Database connection
$host = 'localhost';
$dbname = 'mdrrjvhm_user_registration';
$dbuser = 'mdrrjvhm_xanxus47';
$dbpass = 'oneLASTsong32';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $login_value = htmlspecialchars($login);

    // Initialize brute-force protection
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt'] = time();
    }

    // Calculate remaining attempts
    $remaining_attempts = 3 - $_SESSION['login_attempts'];
    $remaining_attempts = max(0, $remaining_attempts); // Ensure not negative

    // Check if user is locked out
    if ($_SESSION['login_attempts'] >= 3 && (time() - $_SESSION['last_attempt']) < 300) {
        $remaining_time = ceil((300 - (time() - $_SESSION['last_attempt'])) / 60);
        $error = "Account temporarily locked. Try again in $remaining_time minutes.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Successful login - reset attempts
                $_SESSION['login_attempts'] = 0;

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Store role
                $_SESSION['loggedin'] = true;

                // Regenerate session ID for security
                session_regenerate_id(true);

                // --- ROLE-BASED REDIRECT LOGIC ---
                $redirect_page = 'homepage.php'; // fallback

                switch ($user['role']) {
                    case 'super_admin':
                        $redirect_page = 'homepage.php'; // Special dashboard for bosses
                        break;
                    case 'admin_training':
                        $redirect_page = 'admin_dashboard.php';
                        break;
                    case 'operations_warning':
                        $redirect_page = 'ops_dashboard.php';
                        break;
                    default:
                        $redirect_page = 'homepage.php';
                }

                header("Location: $redirect_page");
                exit();
            } else {
                // Failed attempt
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();
                $error = 'Invalid credentials. Remaining attempts: ' . $remaining_attempts;
                sleep(1); // Delay to slow down brute-force attacks
            }
        } else {
            // User not found (count as failed attempt)
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $error = 'Invalid credentials. Remaining attempts: ' . $remaining_attempts;
            sleep(1); // Delay to slow down brute-force attacks
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MDRRMO Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #e0e7ff;
            --secondary: #7209b7;
            --accent: #f72585;
            --error: #ef233c;
            --success: #06ffa5;
            --text: #2b2d42;
            --text-light: #8d99ae;
            --bg: #0f0f1e;
            --card-bg: rgba(255, 255, 255, 0.05);
            --border-radius: 16px;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            overflow: hidden;
        }

        /* Animated gradient background */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 50%, #16213e 100%);
            z-index: -2;
        }

        /* Animated particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: var(--primary-light);
            border-radius: 50%;
            opacity: 0.4;
            filter: blur(2px);
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            25% {
                transform: translateY(-20px) translateX(10px);
            }
            50% {
                transform: translateY(10px) translateX(-10px);
            }
            75% {
                transform: translateY(-10px) translateX(20px);
            }
        }

        /* Login container with glassmorphism */
        .login-container {
            width: 100%;
            max-width: 420px;
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            transition: var(--transition);
            padding: 40px;
            position: relative;
            z-index: 1;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            background-size: 200% 100%;
            animation: gradient-shift 3s ease infinite;
        }

        @keyframes gradient-shift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.4);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(67, 97, 238, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(67, 97, 238, 0);
            }
        }

        .logo i {
            font-size: 36px;
            color: white;
        }

        .login-header h2 {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #fff, #e0e7ff);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: var(--text-light);
            font-weight: 400;
            font-size: 0.95rem;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.error {
            background-color: rgba(239, 35, 60, 0.15);
            color: #ff8a95;
            border-left: 4px solid var(--error);
        }

        .alert.success {
            background-color: rgba(6, 255, 165, 0.15);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #e0e7ff;
        }

        .input-field {
            position: relative;
        }

        .input-field input {
            width: 100%;
            padding: 16px 18px;
            padding-right: 46px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .input-field input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .input-field input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: rgba(255, 255, 255, 0.08);
        }

        .input-field i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: var(--transition);
        }

        .input-field i:hover {
            color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            z-index: -1;
            transition: var(--transition);
            opacity: 0;
        }

        .btn:hover::before {
            opacity: 1;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(67, 97, 238, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .footer-text a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .footer-text a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .loading {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 15, 30, 0.8);
            backdrop-filter: blur(5px);
            z-index: 10;
            border-radius: var(--border-radius);
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px;
                width: calc(100% - 40px);
            }
            .particles { display: none; }
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="particles" id="particles"></div>
    
    <div class="login-container">
        <div class="loading" id="loading">
            <div class="loading-spinner"></div>
        </div>
        
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2>MDRRMO Portal</h2>
            <p>Emergency Management System</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="login">Username or Email</label>
                    <div class="input-field">
                        <input type="text" id="login" name="login" 
                               value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" 
                               placeholder="Enter your credentials" required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-field">
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn">Sign In</button>
            </form>
            
            <div class="footer-text">
                Authorized personnel only • <a href="register.php">Create account</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, icon) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Create floating particles
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size
                const size = Math.random() * 15 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random animation duration
                particle.style.animationDuration = `${Math.random() * 20 + 10}s`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                particlesContainer.appendChild(particle);
            }
            
            // Show loading spinner on form submit
            const loginForm = document.getElementById('loginForm');
            const loading = document.getElementById('loading');
            
            loginForm.addEventListener('submit', function() {
                loading.style.display = 'block';
            });
        });
    </script>
</body>
</html>