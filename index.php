<?php
require_once __DIR__ . '/session_config.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MHS</title>
    <link rel="icon" type="image" href="ICON.jpeg">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <div class="left-image">
            <div class="brand">
                <img src="LOGO MHS.jpeg" class="logo" alt="MHS Logo">
                <h2>Maternal<br>Health System</h2>
            </div>
            <div class="image-content">
                <p>Better care for mothers,<br>brighter future for generations.</p>
            </div>
        </div>
        <div class="login-form">
            <div class="logform">
                 <img src="LOGO MHS.jpeg" class="LOGO2" alt="MHS Logo">
                <h2>Welcome To Maternal Health Monitaring System [MHMS]</h2>
                <p class="paragraph">Sign in to continue to your account</p>

                <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
                    <p style="color: #1e7e34; background: #eafaf0; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                        Your password has been reset successfully. Please sign in with your new password.
                    </p>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <p style="color: #d33; background: #fdeaea; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                        <?php
                            if ($_GET['error'] === 'empty_fields') echo "Please fill in all required fields.";
                            elseif ($_GET['error'] === 'account_disabled') echo "This account has been deactivated. Please contact your administrator.";
                            elseif ($_GET['error'] === 'registration_closed') echo "Self-registration is closed. Please ask your administrator to create an account for you.";
                            else echo "Invalid email address or password.";
                        ?>
                    </p>
                <?php endif; ?>

                <form class="loginform" action="login_process.php" method="POST">
                    <div class="input">
                        <label for="email"><strong>Email Address</strong></label>
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="input">
                        <label for="password"><strong>Password</strong></label>
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fa-solid fa-eye toggle-password" data-target="password" title="Show password"></i>
                    </div>

                    <div class="input-option">
                            <label for="remember"> 
                                <input type="checkbox" id="remember"> Remember me
                            </label>
                            <a href="forgot-password.php">Forgot password?</a>
                    </div>

                    <button type="submit">Sign in</button>
                </form>
                <p class="donthave">Don't have an account? <a href="register.php">Register here</a></p>
                <footer><i class="fa-regular fa-copyright"></i> 2026 Maternal Health System. All rights reserved.</footer>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
    <script src="auth.js"></script>
</body>
</html>