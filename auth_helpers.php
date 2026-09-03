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
echo json_encode(["status" => "error", "message" => "Session imeisha. Tafadhali ingia tena."]);
exit();
}
if (!in_array(currentRole(), $allowedRoles, true)) {
http_response_code(403);
echo json_encode(["status" => "error", "message" => "Huna ruhusa ya kufanya kitendo hiki."]);
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
echo "Huna ruhusa ya kufikia ukurasa huu.";
exit();
}
}

function roleCan($allowedRoles) {
return in_array(currentRole(), $allowedRoles, true);
}
