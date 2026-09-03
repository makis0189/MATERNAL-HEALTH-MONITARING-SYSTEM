<?php
require_once __DIR__ . '/session_config.php';
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
header("Location: dashboard.php");
exit();
}

$rawToken = $_GET['token'] ?? ($_POST['token'] ?? '');
$rawToken = trim($rawToken);
$tokenValid = false;
$resetUserId = null;
$errorMsg = '';
$successMsg = '';

function findValidReset($conn, $rawToken) {
if ($rawToken === '') return null;
$tokenHash = hash('sha256', $rawToken);
$stmt = $conn->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()");
$stmt->bind_param("s", $tokenHash);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
return $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$password = trim($_POST['password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');
$reset = findValidReset($conn, $rawToken);

if (!$reset) {
$errorMsg = "This reset link is invalid or has expired. Please request a new one.";
} elseif (strlen($password) < 8) {
$errorMsg = "Password must be at least 8 characters long.";
$tokenValid = true;
} elseif ($password !== $confirmPassword) {
$errorMsg = "Passwords do not match.";
$tokenValid = true;
} else {
$hashed = password_hash($password, PASSWORD_BCRYPT);
$upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$upd->bind_param("si", $hashed, $reset['user_id']);
$upd->execute();
$upd->close();

// Token hii imetumika - isiweze kutumika tena (hata ikiwa bado
// haijaisha muda wake).
$mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
$mark->bind_param("i", $reset['id']);
$mark->execute();
$mark->close();

header("Location: index.php?reset=success");
exit();
}
} else {
$reset = findValidReset($conn, $rawToken);
$tokenValid = (bool) $reset;
if (!$tokenValid) {
$errorMsg = "This reset link is invalid or has expired. Please request a new one.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password | MHS</title>
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

<?php if (!$tokenValid): ?>
<h2>Link Invalid</h2>
<p class="paragraph"><?php echo htmlspecialchars($errorMsg); ?></p>
<p class="donthave" style="margin-top: 20px;"><a href="forgot-password.php">Request a new link</a></p>
<?php else: ?>
<h2>Set a New Password</h2>
<p class="paragraph">Choose a new password for your account</p>

<?php if ($errorMsg): ?>
<p style="color: #d33; background: #fdeaea; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
<?php echo htmlspecialchars($errorMsg); ?>
</p>
<?php endif; ?>

<form class="loginform" action="reset-password.php" method="POST">
<input type="hidden" name="token" value="<?php echo htmlspecialchars($rawToken); ?>">

<div class="input">
<label for="password"><strong>New Password</strong></label>
<i class="fa-solid fa-lock"></i>
<input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" required>
<i class="fa-solid fa-eye toggle-password" data-target="password" title="Show password"></i>
</div>

<div class="input">
<label for="confirm_password"><strong>Confirm New Password</strong></label>
<i class="fa-solid fa-lock"></i>
<input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" minlength="8" required>
<i class="fa-solid fa-eye toggle-password" data-target="confirm_password" title="Show password"></i>
</div>

<button type="submit">Reset Password</button>
</form>
<?php endif; ?>

<footer><i class="fa-regular fa-copyright"></i> 2026 Maternal Health System. All rights reserved.</footer>
</div>
</div>
</div>
<script src="auth.js"></script>
</body>
</html>
