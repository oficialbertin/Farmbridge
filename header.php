<?php
require 'session_helper.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
$shouldCompress = !headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression');
if ($shouldCompress) {
    @ob_start('ob_gzhandler');
}
$current = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? null;
$dashboard_link = $role === 'farmer' ? 'farmer.php' : ($role === 'buyer' ? 'buyer.php' : ($role === 'admin' ? 'admin.php' : ''));
// Load profile picture if available
$profilePic = null;
if (isset($_SESSION['user_id'])) {
    // Try to use existing DB connection if present
    if (!isset($conn)) {
        @include_once 'db.php';
    }
    if (isset($conn)) {
        if ($stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ? LIMIT 1")) {
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($u = $res->fetch_assoc())) {
                $profilePic = $u['profile_pic'] ?? null;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmBridge AI Rwanda - Connecting Farmers & Buyers</title>
    <link rel="icon" href="assets/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/style.css">
    
<style>
        :root {
            --primary-green: #2e7d32;
            --secondary-green: #4caf50;
            --light-green: #66bb6a;
            --dark-green: #1b5e20;
            --accent-green: #81c784;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --gray: #6c757d;
            --dark-gray: #343a40;
            --black: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--black);
            background-color: var(--white);
        }

        /* Enhanced Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
            box-shadow: 0 4px 20px rgba(46, 125, 50, 0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-top {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 0;
            font-size: 0.9rem;
        }

        .header-top-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--white);
        }

        .contact-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .contact-info i {
            margin-right: 5px;
            color: var(--accent-green);
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--accent-green);
        }

        .main-navbar {
            padding: 15px 0;
        }

    .navbar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--white);
            font-weight: 700;
        font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            color: var(--white);
            transform: scale(1.05);
        }

    .navbar-brand img {
            height: 45px;
        margin-right: 12px;
        border-radius: 8px;
            background: var(--white);
            padding: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .navbar-nav {
    display: flex; /* Ensure flex display */
    flex-direction: row; /* Set to row for horizontal layout */
    align-items: center; /* Align items vertically centered */
    gap: 5px; /* Adjust spacing between items */
    margin: 0;
    padding: 0;
    list-style: none;
}

        

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--white);
            text-decoration: none;
        font-weight: 500;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .nav-link i {
        margin-right: 8px;
            font-size: 1.1rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--accent-green), var(--light-green));
            border: none;
            color: var(--white);
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--light-green), var(--accent-green));
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--white);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary-green);
            transform: translateY(-2px);
        }

        /* Profile hover dropdown */
        .profile-dropdown { position: relative; }
        .profile-dropdown .profile-menu {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 200px;
            background: var(--white);
            color: var(--black);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            padding: 6px;
            z-index: 1200;
        }
        .profile-dropdown:hover .profile-menu { display: block; }
        .profile-dropdown .profile-menu a {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--black) !important;
            text-decoration: none;
            font-weight: 500;
        }
        .profile-dropdown .profile-menu a:hover {
            background: rgba(76, 175, 80, 0.12);
            color: var(--dark-green) !important;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Enhanced Chatbot Styles */
        #ai-chatbot-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1050;
            border-radius: 50%;
            width: 65px;
            height: 65px;
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.3);
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: var(--white);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        #ai-chatbot-btn:hover {
            background: linear-gradient(135deg, var(--secondary-green), var(--primary-green));
            color: var(--white);
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(46, 125, 50, 0.4);
        }

        #ai-chatbot-window {
            display: none;
            position: fixed;
            bottom: 110px;
            right: 30px;
            width: 380px;
            height: 480px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(46, 125, 50, 0.2);
            z-index: 1100;
            overflow: hidden;
            flex-direction: column;
            border: 1px solid rgba(76, 175, 80, 0.1);
        }

        #ai-chatbot-window .chatbot-header {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: var(--white);
            padding: 18px 20px;
            font-weight: 600;
            font-size: 1.1rem;
            flex: 0 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #ai-chatbot-window .chatbot-header .close-btn {
        background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        #ai-chatbot-window .chatbot-header .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        #ai-chatbot-messages {
            padding: 20px;
            flex: 1 1 auto;
            max-height: 380px;
            min-height: 150px;
            overflow-y: auto;
            font-size: 0.95rem;
            background: var(--white);
            word-break: break-word;
            line-height: 1.6;
        }

        #ai-chatbot-form {
            display: flex;
            padding: 15px;
            border-top: 1px solid #e9ecef;
            gap: 10px;
            align-items: flex-end;
            flex: 0 0 auto;
            background: var(--light-gray);
        }

        #ai-chatbot-input {
            flex: 1 1 0%;
            min-width: 0;
            font-size: 1rem;
            padding: 12px 16px;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            background: var(--white);
            resize: none;
            min-height: 45px;
            max-height: 100px;
            line-height: 1.4;
            box-sizing: border-box;
            margin-bottom: 0;
            overflow-y: auto;
            transition: border-color 0.3s ease;
        }

        #ai-chatbot-input:focus {
            outline: none;
            border-color: var(--secondary-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        #ai-chatbot-form button {
            flex: 0 0 auto;
            width: 45px;
            height: 45px;
            padding: 0;
            border-radius: 50%;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            border: none;
            color: var(--white);
            transition: all 0.3s ease;
        }

        #ai-chatbot-form button:hover {
            background: linear-gradient(135deg, var(--secondary-green), var(--primary-green));
            transform: scale(1.05);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-top {
                display: none;
            }

            .navbar-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--primary-green);
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }

            .navbar-nav.active {
                display: flex;
            }

            .nav-link {
                width: 100%;
                justify-content: center;
                margin: 5px 0;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .user-menu {
                flex-direction: column;
                gap: 10px;
            }

            #ai-chatbot-window {
                width: 90vw;
                right: 5vw;
                left: 5vw;
                height: 60vh;
            }
        }

        /* Main Content Spacing */
        .main-content {
            margin-top: 140px;
            min-height: calc(100vh - 140px);
    }
.navbar-nav .nav-link {
    padding: 7px 10px !important;
    font-size: 0.97em;
    min-width: 0;
}
.navbar-nav .nav-link i {
    font-size: 1em;
    margin-right: 5px;
}
</style>
</head>
<body>
    <!-- Enhanced Header -->
    <header class="main-header">
        <!-- Top Bar -->
        <!-- <div class="header-top">
            <div class="header-container">
                <div class="header-top-content">
                    <div class="contact-info">
                        <span><i class="bi bi-telephone"></i> +250 788 123 456</span>
                        <span><i class="bi bi-envelope"></i> info@farmbridge.rw</span>
                        <span><i class="bi bi-geo-alt"></i> Kigali, Rwanda</span>
                    </div>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Main Navigation -->
        <div class="main-navbar">
            <div class="header-container">
                <nav class="d-flex justify-content-between align-items-center">
                    <a href="index.php" class="navbar-brand">
            <?php if (file_exists('assets/logo.png')): ?>
                            <img src="assets/logo.png" alt="FarmBridge AI Logo">
            <?php endif; ?>
                        FarmBridge AI
                    </a>

                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                        <i class="bi bi-list"></i>
                    </button>

                    <ul class="navbar-nav" id="navbarNav">
                        <li class="nav-item">
                            <a href="index.php" class="nav-link<?= $current == 'index.php' ? ' active' : '' ?>">
                                <i class="bi bi-house"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
        <a href="crops.php" class="nav-link<?= $current == 'crops.php' ? ' active' : '' ?>">
            <i class="bi bi-shop"></i> Marketplace
        </a>
    </li>
                        
                        <?php if ($dashboard_link): ?>
                            <li class="nav-item">
                                <a href="<?= $dashboard_link ?>" class="nav-link<?= $current == $dashboard_link ? ' active' : '' ?>">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                    <?php endif; ?>
                        
                        <?php if ($role === 'farmer'): ?>
                            <!-- <li class="nav-item">
                                <a href="farmer_add_crop.php" class="nav-link<?= $current == 'farmer_add_crop.php' ? ' active' : '' ?>">
                                    <i class="bi bi-plus-circle"></i> List Crop
                                </a>
                            </li> -->
                            <li class="nav-item">
                                <a href="farmer_crops.php" class="nav-link<?= $current == 'farmer_crops.php' ? ' active' : '' ?>">
                                    <i class="bi bi-basket"></i> My Crops
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="farmer_payments.php" class="nav-link<?= $current == 'farmer_payments.php' ? ' active' : '' ?>">
                                    <i class="bi bi-cash"></i> Payments
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="farmer_orders.php" class="nav-link<?= $current == 'farmer_orders.php' ? ' active' : '' ?>">
                                    <i class="bi bi-bag-check"></i> My Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="raise_dispute.php" class="nav-link<?= $current == 'raise_dispute.php' ? ' active' : '' ?>">
                                    <i class="bi bi-exclamation-diamond"></i> Disputes
                                </a>
                            </li>
                <?php endif; ?>

<?php if ($role === 'buyer'): ?>
    <li class="nav-item">
        <a href="crops.php" class="nav-link<?= $current == 'crops.php' ? ' active' : '' ?>">
            <i class="bi bi-shop"></i> Marketplace
        </a>
    </li>
    <li class="nav-item">
        <a href="buyer_orders.php" class="nav-link<?= $current == 'buyer_orders.php' ? ' active' : '' ?>">
            <i class="bi bi-bag-check"></i> My Orders
        </a>
    </li>
    <li class="nav-item">
        <a href="buyer_payments.php" class="nav-link<?= $current == 'buyer_payments.php' ? ' active' : '' ?>">
            <i class="bi bi-credit-card"></i> Payments
        </a>
    </li>
    <li class="nav-item">
        <a href="buyer_disputes.php" class="nav-link<?= $current == 'buyer_disputes.php' ? ' active' : '' ?>">
            <i class="bi bi-exclamation-diamond"></i> Disputes
        </a>
    </li>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
    <li class="nav-item">
        <a href="admin_users.php" class="nav-link<?= $current == 'admin_users.php' ? ' active' : '' ?>">
            <i class="bi bi-people"></i> Users
        </a>
    </li>
    <li class="nav-item">
        <a href="admin_crops.php" class="nav-link<?= $current == 'admin_crops.php' ? ' active' : '' ?>">
            <i class="bi bi-flower2"></i> Crops
        </a>
    </li>
    <li class="nav-item">
        <a href="admin_orders.php" class="nav-link<?= $current == 'admin_orders.php' ? ' active' : '' ?>">
            <i class="bi bi-bag"></i> Orders
        </a>
    </li>
    <li class="nav-item">
        <a href="admin_payments.php" class="nav-link<?= $current == 'admin_payments.php' ? ' active' : '' ?>">
            <i class="bi bi-cash-stack"></i> Payments
        </a>
    </li>
    <li class="nav-item">
        <a href="admin_disputes.php" class="nav-link<?= $current == 'admin_disputes.php' ? ' active' : '' ?>">
            <i class="bi bi-exclamation-diamond"></i> Disputes
        </a>
    </li>
    <li class="nav-item">
        <a href="admin_ai_tools.php" class="nav-link<?= $current == 'admin_ai_tools.php' ? ' active' : '' ?>">
            <i class="bi bi-cpu"></i> AI Tools
        </a>
    </li>
<?php endif; ?>
            </ul>

                    <div class="user-menu">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="profile-dropdown">
                                <a href="profile.php" class="nav-link<?= $current == 'profile.php' ? ' active' : '' ?>">
                                    <?php if (!empty($profilePic)): ?>
                                        <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" class="rounded-circle me-1" style="width:24px;height:24px;object-fit:cover;">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle"></i>
                                    <?php endif; ?>
                                    Profile <i class="bi bi-caret-down-fill" style="font-size: .8rem; margin-left: 6px;"></i>
                                </a>
                                <div class="profile-menu">
                                    <a href="profile.php"><i class="bi bi-person"></i> View Profile</a>
<?php if ($role === 'admin'): ?>
                                    <a href="admin_settings.php"><i class="bi bi-gear"></i> Admin Settings</a>
<?php endif; ?>
                                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="nav-link<?= $current == 'login.php' ? ' active' : '' ?>">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                            <a href="register.php" class="btn-primary-custom">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        <?php endif; ?>
                    </div>

                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                        <i class="bi bi-list"></i>
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content Wrapper -->
    <div class="main-content">

    <!-- AI Chatbot Button -->
    <button id="ai-chatbot-btn" title="Chat with FarmBridge AI Assistant">
        <i class="bi bi-robot"></i>
    </button>
    <div id="ai-chatbot-window">
        <div class="chatbot-header">
            <span><i class="bi bi-robot"></i> FarmBridge AI Assistant</span>
            <button class="close-btn" onclick="document.getElementById('ai-chatbot-window').style.display='none'">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div id="ai-chatbot-messages"><b>🤖 Welcome to FarmBridge AI Assistant!</b><br><br>I can help you with:<br>• 🌾 Crop prices and availability<br>• 📊 Market trends and demand forecasts<br>• 🌱 Farming advice and seasonal tips<br>• 💡 Platform help and guidance<br><br>Just ask me anything about farming, crops, or the platform!</div>
        <form id="ai-chatbot-form">
            <textarea id="ai-chatbot-input" class="form-control" placeholder="Type your question..." autocomplete="off" rows="1"></textarea>
            <button class="btn btn-success" type="submit"><i class="bi bi-send"></i></button>
        </form>
    </div>

    <script>
    // Mobile menu toggle
    function toggleMobileMenu() {
        const navbar = document.querySelector('.navbar-nav');
        navbar.classList.toggle('active');
    }

    // Chatbot functionality
    document.getElementById('ai-chatbot-btn').onclick = function() {
        var win = document.getElementById('ai-chatbot-window');
        if (win.style.display === 'none' || win.style.display === '') {
            win.style.display = 'flex';
        } else {
            win.style.display = 'none';
        }
    };

    const chatbotInput = document.getElementById('ai-chatbot-input');
    const chatbotForm = document.getElementById('ai-chatbot-form');
    
    chatbotForm.onsubmit = function(e) {
        e.preventDefault();
        var msg = chatbotInput.value.trim();
        if (!msg) return;
        
        var messages = document.getElementById('ai-chatbot-messages');
        var userDiv = document.createElement('div');
        userDiv.style.margin = '8px 0';
        userDiv.innerHTML = '<b>You:</b> ' + msg.replace(/\n/g, '<br>');
        messages.appendChild(userDiv);
        
        // Show typing indicator
        var aiDiv = document.createElement('div');
        aiDiv.style.margin = '8px 0';
        aiDiv.style.color = '#888';
        aiDiv.innerHTML = '<b>AI:</b> <em>Typing...</em>';
        messages.appendChild(aiDiv);
        
        chatbotInput.value = '';
        messages.scrollTop = messages.scrollHeight;
        
        // Send request to chatbot API
        const bodyEncoded = new URLSearchParams({message: msg}).toString();
        const bust = Date.now().toString();
        fetch('chatbot_text.php?_=' + bust, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: bodyEncoded
        })
        .then(response => response.text())
        .then(text => {
            let out = text;
            // If a JSON string was returned, extract the response field
            if (text && text.trim().startsWith('{')) {
                try {
                    const data = JSON.parse(text);
                    if (data && typeof data.response === 'string') {
                        out = data.response;
                    }
                } catch(_){ /* ignore parse errors */ }
            }
            aiDiv.innerHTML = '<b>AI:</b> ' + (out || 'Hello!').replace(/\n/g, '<br>');
            messages.scrollTop = messages.scrollHeight;
        })
        .catch(error => {
            aiDiv.innerHTML = '<b>AI:</b> <em>Sorry, I\'m having trouble connecting. Please check your internet connection.</em>';
            messages.scrollTop = messages.scrollHeight;
        });
    };

    // Allow Shift+Enter for new lines in textarea
    chatbotInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatbotForm.dispatchEvent(new Event('submit', {cancelable: true, bubbles: true}));
        }
    });

    // Header scroll effect
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.main-header');
        if (window.scrollY > 100) {
            header.style.background = 'rgba(46, 125, 50, 0.95)';
            header.style.backdropFilter = 'blur(10px)';
        } else {
            header.style.background = 'linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%)';
            header.style.backdropFilter = 'none';
        }
    });

    // Mobile menu toggle
    function toggleMobileMenu() {
        const navbar = document.getElementById('navbarNav');
        navbar.classList.toggle('active');
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const navbar = document.getElementById('navbarNav');
        const toggle = document.querySelector('.mobile-menu-toggle');
        if (!navbar.contains(event.target) && !toggle.contains(event.target)) {
            navbar.classList.remove('active');
        }
    });
    </script>
