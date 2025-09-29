<?php
session_start();
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.html");
    exit;
}

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Unset all session variables
    $_SESSION = array();
    // If using cookies to store session ID, delete the cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // Destroy the session
    session_destroy();
    // Redirect to login page after logout
    header("Location: index.html?logout=success");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Collapsible Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --sidebar-width-desktop: 280px;
            --sidebar-width-collapsed: 70px;
            --mobile-breakpoint: 768px;
            --primary-color: #0057d8;
            --background-color: #f5f5f5;
            --light-bg-color: #f8f8f8;
            --border-color: #e0e0e0;
            --text-color: #333;
            --icon-size: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* Ensure full height */
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        /* Full-screen background overlay */
        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('image/GEGE.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
            pointer-events: none; /* Allow interaction with content */
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--background-color);
            transition: margin-left 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        body.no-scroll {
            overflow: hidden;
        }

        .sidebar {
            width: var(--sidebar-width-desktop);
            background: #fff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px 0;
            transition: all 0.3s ease;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }

        .main-content {
            flex: 1;
            padding: 20px;
            transition: margin-left 0.3s ease;
            margin-left: var(--sidebar-width-desktop);
            position: relative;
            z-index: 1;
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
        }

        .main-content h1, .main-content p {
            color: #fff;
        }

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-width-collapsed);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                position: fixed;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar.collapsed {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .mobile-menu-btn {
                display: block !important;
            }
            .toggle-btn {
                display: none;
            }
        }

        .toggle-btn {
            display: flex;
            justify-content: flex-end;
            padding: 10px 20px;
            cursor: pointer;
        }

        .toggle-btn i {
            font-size: 24px;
            color: var(--text-color);
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 24px;
            z-index: 1001;
            background: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .menu-title {
            padding: 15px 25px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 10px;
            white-space: nowrap;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .menu-title {
            opacity: 0;
            visibility: hidden;
        }

        .menu {
            list-style: none;
        }

        .menu-item {
            position: relative;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.2s ease;
            position: relative;
            white-space: nowrap;
            gap: 10px;
        }

        .menu-link:hover {
            background: var(--light-bg-color);
            color: var(--primary-color);
        }

        .menu-link.active {
            color: var(--primary-color);
            background: rgba(0, 87, 216, 0.05);
        }

        .menu-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--primary-color);
        }

        .menu-link i {
            font-size: var(--icon-size);
            width: 24px;
            text-align: center;
        }

        .submenu {
            list-style: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: #fafafa;
        }

        .menu-item.active .submenu {
            max-height: 500px;
        }

        .submenu .menu-link {
            padding-left: 45px;
            font-weight: 400;
            font-size: 14px;
        }

        .sidebar.collapsed .submenu .menu-link {
            padding-left: 25px;
        }

        .submenu .menu-link:hover {
            background: #f0f0f0;
        }

        .has-submenu > .menu-link::after {
            content: '\f054';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%) rotate(0deg);
            transition: transform 0.3s ease;
        }

        .has-submenu.active > .menu-link::after {
            transform: translateY(-50%) rotate(90deg);
        }

        .sidebar.collapsed .menu-link::after {
            display: none;
        }

        .sidebar.collapsed .menu-link {
            justify-content: center;
            padding: 14px 0;
        }

        .sidebar.collapsed .menu-text {
            display: none;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar.open + .sidebar-overlay {
            display: block;
            opacity: 1;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 400px;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-content {
            padding: 25px;
            text-align: center;
        }

        .modal-content h3 {
            margin-bottom: 12px;
            color: var(--text-color);
            font-size: 1.3rem;
        }

        .modal-content p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #0044b0;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #ccc;
        }

        /* Accessibility */
        .menu-link:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        .toggle-btn:focus, .mobile-menu-btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Logout button hover */
        #logoutLink:hover {
            color: #d32f2f !important;
            background-color: rgba(211, 47, 47, 0.1) !important;
        }
    </style>
</head>
<body>
    <!-- Full-screen Background -->
    <div class="background-overlay"></div>

    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle mobile menu">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar" aria-label="Main navigation">
        <button class="toggle-btn" id="toggleSidebar" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="menu-title">Navigation</div>
        <ul class="menu">
            <li class="menu-item">
                <a href="#" class="menu-link active" aria-current="page">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li class="menu-item has-submenu active">
                <a href="#" class="menu-link" aria-expanded="true">
                    <i class="fas fa-users-cog"></i>
                    <span class="menu-text">Admin & Training</span>
                </a>
                <ul class="submenu">
                    <li class="menu-item">
                        <a href="search.php" class="menu-link active" aria-current="page">
                            <i class="fas fa-search"></i>
                            <span class="menu-text">Search Attendees</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="insert.php" class="menu-link">
                            <i class="fas fa-user-plus"></i>
                            <span class="menu-text">Insert Attendees</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="records.php" class="menu-link">
                            <i class="fas fa-table"></i>
                            <span class="menu-text">View All Records</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="absentform.php" class="menu-link">
                            <i class="fas fa-user-times"></i>
                            <span class="menu-text">Absent Form</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="overtaym.php" class="menu-link">
                            <i class="fas fa-clock"></i>
                            <span class="menu-text">Overtime Form</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="view_records1.php" class="menu-link">
                            <i class="fas fa-clock"></i>
                            <span class="menu-text">Overtime Viewer</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="html.php" class="menu-link">
                            <i class="fas fa-boxes"></i>
                            <span class="menu-text">Inventory Form</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="menu-item">
                <a href="#" class="menu-link">
                    <i class="fas fa-chart-line"></i>
                    <span class="menu-text">Analytics</span>
                </a>
            </li>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link" aria-expanded="false">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">Settings</span>
                </a>
                <ul class="submenu">
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="fas fa-user"></i>
                            <span class="menu-text">Account</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="fas fa-shield-alt"></i>
                            <span class="menu-text">Security</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="fas fa-bell"></i>
                            <span class="menu-text">Notifications</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="fas fa-sliders-h"></i>
                            <span class="menu-text">Preferences</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Logout Button -->
            <li class="menu-item">
                <a href="#" class="menu-link" id="logoutLink">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay" role="button" aria-label="Close sidebar"></div>

    

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-content">
                <h3>Confirm Logout</h3>
                <p>Are you sure you want to log out of your account?</p>
                <div class="modal-actions">
                    <button id="cancelLogout" class="btn btn-secondary">Cancel</button>
                    <button id="confirmLogout" class="btn btn-primary">Log Out</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // === Modal Logout Functionality ===
        const logoutLink = document.getElementById('logoutLink');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogout = document.getElementById('cancelLogout');
        const confirmLogout = document.getElementById('confirmLogout');

        // Open modal when logout link is clicked
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            logoutModal.classList.add('active');
        });

        // Close modal on Cancel
        cancelLogout.addEventListener('click', () => {
            logoutModal.classList.remove('active');
        });

        // Confirm logout and redirect
        confirmLogout.addEventListener('click', () => {
            window.location.href = '?logout=true';
        });

        // Close modal if clicking outside
        logoutModal.addEventListener('click', (e) => {
            if (e.target === logoutModal) {
                logoutModal.classList.remove('active');
            }
        });

        // Also close with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && logoutModal.classList.contains('active')) {
                logoutModal.classList.remove('active');
            }
        });

        // === Sidebar Toggle & Mobile Menu ===
        const sidebar = document.querySelector('.sidebar');
        const toggleSidebarBtn = document.getElementById('toggleSidebar');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const hasSubmenuLinks = document.querySelectorAll('.has-submenu > .menu-link');
        const allMenuLinks = document.querySelectorAll('.menu-link');
        const body = document.body;

        // Restore sidebar state
        const sidebarState = localStorage.getItem('sidebarState');
        if (sidebarState === 'collapsed' && window.innerWidth > 768) {
            sidebar.classList.add('collapsed');
        }

        // Close all submenus
        function closeAllSubmenus() {
            document.querySelectorAll('.has-submenu.active').forEach(item => {
                item.classList.remove('active');
                item.querySelector('.menu-link').setAttribute('aria-expanded', 'false');
            });
        }

        // Toggle sidebar collapse (desktop)
        if (toggleSidebarBtn) {
            toggleSidebarBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarState', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
                closeAllSubmenus();
            });
        }

        // Toggle mobile menu
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                body.classList.toggle('no-scroll');
                mobileMenuBtn.setAttribute('aria-expanded', sidebar.classList.contains('open'));
            });
        }

        // Close sidebar with overlay click
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                body.classList.remove('no-scroll');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            });
        }

        // Toggle submenus
        hasSubmenuLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const isDesktopExpanded = !sidebar.classList.contains('collapsed') && window.innerWidth > 768;
                const isMobileOpen = sidebar.classList.contains('open');
                if (isDesktopExpanded || isMobileOpen) {
                    e.preventDefault();
                    const parent = this.parentElement;
                    const isActive = parent.classList.contains('active');
                    closeAllSubmenus();
                    if (!isActive) {
                        parent.classList.add('active');
                        this.setAttribute('aria-expanded', 'true');
                    }
                }
            });
        });

        // Auto-close mobile menu when clicking a non-submenu link
        allMenuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768 && !this.parentElement.classList.contains('has-submenu')) {
                    sidebar.classList.remove('open');
                    body.classList.remove('no-scroll');
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Swipe gestures
        let touchStartX = 0;
        let touchEndX = 0;

        sidebar.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        sidebar.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 50 && window.innerWidth <= 768) {
                sidebar.classList.remove('open');
                body.classList.remove('no-scroll');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchEndX - touchStartX > 50 && window.innerWidth <= 768 && !sidebar.classList.contains('open')) {
                sidebar.classList.add('open');
                body.classList.add('no-scroll');
                mobileMenuBtn.setAttribute('aria-expanded', 'true');
            }
        });

        // Resize handler
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
                body.classList.remove('no-scroll');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
</body>
</html>