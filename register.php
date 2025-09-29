<?php
// Start session to pass success message
session_start();

// Database connection
$host = 'localhost';
$dbname = 'mdrrjvhm_user_registration'; // Replace with your database name
$dbuser = 'mdrrjvhm_xanxus47';      // Replace with your database username
$dbpass = 'oneLASTsong32';      // Replace with your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables for error message
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into database
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);
                // Set success message in session
                $_SESSION['success'] = 'Registration successful! Please log in.';
                // Redirect to login.php
                header('Location: login.php');
                exit();
            } catch (PDOException $e) {
                $error = 'Error saving user: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .btn-hover {
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-hover:hover {
            transform: translateY(-2px);
        }
        .toggle-password {
            transition: color 0.3s ease;
        }
        .toggle-password:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 flex items-center justify-center p-4">
    <div class="container bg-white rounded-2xl shadow-xl p-8 w-full max-w-md fade-in">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Create Your Account</h2>
        <?php if ($error): ?>
            <p class="error text-red-500 text-center mb-4 bg-red-50 p-3 rounded-lg"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success text-green-600 text-center mb-4 bg-green-50 p-3 rounded-lg border border-green-100"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" action="register.php" class="space-y-6">
            <div class="form-group">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus focus:outline-none"
                       placeholder="Enter your username"
                       aria-required="true" aria-describedby="username-error">
            </div>
            <div class="form-group">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus focus:outline-none"
                       placeholder="Enter your email"
                       aria-required="true" aria-describedby="email-error">
            </div>
            <div class="form-group relative">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus focus:outline-none"
                       placeholder="Enter your password"
                       aria-required="true" aria-describedby="password-error">
                <span class="toggle-password absolute right-3 top-10 text-gray-500 cursor-pointer" onclick="togglePassword('password')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm0 0c0 3.18-4.477 6-9 6s-9-2.82-9-6 4.477-6 9-6 9 2.82 9 6z"></path>
                    </svg>
                </span>
            </div>
            <div class="form-group relative">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg input-focus focus:outline-none"
                       placeholder="Confirm your password"
                       aria-required="true" aria-describedby="confirm-password-error">
                <span class="toggle-password absolute right-3 top-10 text-gray-500 cursor-pointer" onclick="togglePassword('confirm_password')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm0 0c0 3.18-4.477 6-9 6s-9-2.82-9-6 4.477-6 9-6 9 2.82 9 6z"></path>
                    </svg>
                </span>
            </div>
            <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg font-semibold btn-hover hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Register
            </button>
        </form>
        <p class="text-center mt-4 text-sm text-gray-600">
            Already have an account? <a href="login.php" class="text-blue-600 hover:underline">Log in</a>
        </p>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('svg');
            if (field.type === 'password') {
                field.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm0 0c0 3.18-4.477 6-9 6s-9-2.82-9-6 4.477-6 9-6 9 2.82 9 6z M4 12h16"/>';
            } else {
                field.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm0 0c0 3.18-4.477 6-9 6s-9-2.82-9-6 4.477-6 9-6 9 2.82 9 6z"/>';
            }
        }
    </script>
</body>
</html>