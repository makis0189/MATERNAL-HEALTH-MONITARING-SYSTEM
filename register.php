<?php
require_once __DIR__ . '/session_config.php';
require_once 'db.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// RBAC bootstrap: usajili wa umma unapatikana TU kama hakuna mtumiaji
// yeyote bado (yaani huu ni usanidi wa kwanza wa mfumo). Baada ya hapo,
// Admin ndiye pekee anayeweza kutengeneza akaunti mpya (kupitia "Manage
// Users" ndani ya dashboard).
$countResult = $conn->query("SELECT COUNT(*) AS total FROM users");
$userCount = $countResult ? (int) $countResult->fetch_assoc()['total'] : 1;
$registrationOpen = ($userCount === 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | MHS</title>
     <link rel="icon" type="image" href="ICON.jpeg">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <div class="left-image">
            <div class="brand">
                <img src="ICON.jpeg" class="logo" alt="MHS Logo">
                <h2>Maternal<br>Health System</h2>
            </div>
            <div class="image-content">
                <p>Better care for mothers,<br>brighter future for generations.</p>
            </div>
        </div>
        <div class="login-form">
            <div class="logform">
                <?php if ($registrationOpen): ?>
                <h2>Create Account</h2>
                <p class="paragraph">First-time setup — create the administrator account</p>

                <?php if (isset($_GET['error'])): ?>
                    <p style="color: #d33; background: #fdeaea; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                        <?php
                            if ($_GET['error'] === 'email_exists') echo "Email is already registered.";
                            elseif ($_GET['error'] === 'empty_fields') echo "Please fill in all required fields.";
                            elseif ($_GET['error'] === 'invalid_email') echo "Please enter a valid email address.";
                            elseif ($_GET['error'] === 'weak_password') echo "Password must be at least 8 characters long.";
                            else echo "Registration failed. Try again.";
                        ?>
                    </p>
                <?php endif; ?>

                <form class="loginform" action="register_process.php" method="POST">
                    <div class="input">
                        <label for="full_name"><strong>Full Name</strong></label>
                        <i class="fa-regular fa-user"></i>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                    </div>

                    <div class="input">
                        <label for="email"><strong>Email Address</strong></label>
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="input">
                        <label for="password"><strong>Password</strong></label>
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" minlength="8" required>
                        <i class="fa-solid fa-eye toggle-password" data-target="password" title="Show password"></i>
                    </div>

                    <button type="submit">Register</button>
                </form>

                <p class="donthave" style="margin-top: 20px;">Already have an account? <a href="index.php">Login here</a></p>
                <?php else: ?>
                <h2>Registration Closed</h2>
                <p class="paragraph">This system already has an administrator. New staff accounts are created by your administrator from the "Manage Users" section of the dashboard — self-registration is disabled to protect patient data.</p>
                <p class="donthave" style="margin-top: 20px;"><a href="index.php">Back to login</a></p>
                <?php endif; ?>
                <footer><i class="fa-regular fa-copyright"></i> 2026 Maternal Health System. All rights reserved.</footer>
            </div>
        </div>
    </div>
    <script src="auth.js"></script>
</body>
</html>