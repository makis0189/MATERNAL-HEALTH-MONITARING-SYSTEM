<?php
require_once __DIR__ . '/session_config.php';
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

$countResult = $conn->query("SELECT COUNT(*) AS total FROM users");
$userCount = $countResult ? (int) $countResult->fetch_assoc()['total'] : 1;

if ($userCount > 0) {
header("Location: index.php?error=registration_closed");
exit();
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = trim($_POST['password'] ?? '');

if ($full_name === '' || $email === '' || $password === '') {
header("Location: register.php?error=empty_fields");
exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
header("Location: register.php?error=invalid_email");
exit();
}

if (strlen($password) < 8) {
header("Location: register.php?error=weak_password");
exit();
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
$checkStmt->close();
header("Location: register.php?error=email_exists");
exit();
}
$checkStmt->close();

$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$role = 'Admin';
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

if ($stmt->execute()) {
$newUserId = $stmt->insert_id;
$stmt->close();
session_regenerate_id(true);
$_SESSION['user_id'] = $newUserId;
$_SESSION['user_name'] = $full_name;
$_SESSION['role'] = $role;

header("Location: dashboard.php");
exit();
} else {
error_log("register_process error: " . $conn->error);
$stmt->close();
header("Location: register.php?error=failed");
exit();
}
}

header("Location: register.php");
exit();
