<?php

require_once __DIR__ . '/session_config.php';

function currentRole() {
return $_SESSION['role'] ?? null;
}

function isLoggedIn() {
return isset($_SESSION['user_id']);
}

function requireRoleApi(array $allowedRoles) {
if (!isLoggedIn()) {
http_response_code(403);
echo json_encode(["status" => "error", "message" => "Session finished. Please enter again."]);
exit();
}
if (!in_array(currentRole(), $allowedRoles, true)) {
http_response_code(403);
echo json_encode(["status" => "error", "message" => "Access denied."]);
exit();
}
}

function requireRolePage(array $allowedRoles) {
if (!isLoggedIn()) {
header("Location: index.php");
exit();
}
if (!in_array(currentRole(), $allowedRoles, true)) {
http_response_code(403);
echo "You are not authorized to access this page.";
exit();
}
}

function roleCan($allowedRoles) {
return in_array(currentRole(), $allowedRoles, true);
}
