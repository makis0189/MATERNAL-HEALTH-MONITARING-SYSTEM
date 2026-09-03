<?php
require_once __DIR__ . '/session_config.php';
require_once 'db.php';
require_once __DIR__ . '/mailer.php';

if (isset($_SESSION['user_id'])) {
header("Location: dashboard.php");
exit();
}

$submitted = false;
$devResetLink = null; // inajazwa TU wakati APP_ENV=development, kwa majaribio ya ndani (localhost/XAMPP)
$isDev = (getenv('APP_ENV') === 'development');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$email = trim($_POST['email'] ?? '');

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
$stmt = $conn->prepare("SELECT id, full_name, is_active FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($user && (int) $user['is_active'] === 1) {
$rawToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
$del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
$del->bind_param("i", $user['id']);
$del->execute();
$del->close();

$ins = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
$ins->bind_param("iss", $user['id'], $tokenHash, $expiresAt);
$ins->execute();
$ins->close();

$appUrl = rtrim(getenv('APP_URL') ?: '', '/');
$resetLink = ($appUrl !== '' ? $appUrl : '') . '/reset-password.php?token=' . $rawToken;

$sent = sendPasswordResetEmail($email, $user['full_name'], $resetLink);
if (!$sent) {
error_log("sendPasswordResetEmail failed for user id {$user['id']}");
}
if ($isDev) {
error_log("[DEV] Password reset link for {$email}: {$resetLink}");
$devResetLink = $resetLink;
}
}
}

$submitted = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | MHS</title>
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

<?php if ($submitted): ?>
<h2>Check Your Email</h2>
<p class="paragraph">If an account exists for that email address, we've sent a link to reset your password. The link expires in 1 hour.</p>

<?php if ($isDev && $devResetLink): ?>
<div style="background:#fff3cd; border:1px solid #ffe69c; padding:12px; border-radius:6px; margin-bottom:15px; font-size:13px; word-break:break-all;">
<strong>DEV MODE ONLY</strong> (APP_ENV=development) — email haitumwi kwenye localhost/XAMPP kwa kawaida, hivyo link yako iko hapa kwa ajili ya majaribio:<br>
<a href="<?php echo htmlspecialchars($devResetLink); ?>"><?php echo htmlspecialchars($devResetLink); ?></a>
</div>
<?php endif; ?>

<p class="donthave" style="margin-top: 20px;"><a href="index.php">Back to login</a></p>
<?php else: ?>
<img src="LOGO MHS.jpeg" class="LOGO2" alt="MHS Logo">
<h2>Forgot Password?</h2>
<p class="paragraph">Enter your email and we'll send you a reset link</p>

<form class="loginform" action="forgot-password.php" method="POST">
<div class="input">
<label for="email"><strong>Email Address</strong></label>
<i class="fa-regular fa-envelope"></i>
<input type="email" id="email" name="email" placeholder="Enter your email" required>
</div>

<button type="submit">Send Reset Link</button>
</form>

<p class="donthave" style="margin-top: 20px;">Remembered your password? <a href="index.php">Login here</a></p>
<?php endif; ?>

<footer><i class="fa-regular fa-copyright"></i> 2026 Maternal Health System. All rights reserved.</footer>
</div>
</div>
</div>
</body>
</html>
