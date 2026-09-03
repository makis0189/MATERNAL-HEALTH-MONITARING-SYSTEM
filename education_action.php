<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse'];

// Doctor is view-only system-wide now — no role currently assigned to write here.
$writeRoles = [];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createEducation($conn);
break;
case 'list':
listEducation($conn);
break;
case 'get':
getEducation($conn);
break;
case 'update':
updateEducation($conn);
break;
case 'delete':
deleteEducation($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function createEducation($conn) {
$title       = trim($_POST['title'] ?? '');
$category    = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($title === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza kichwa cha mada."]);
return;
}

$stmt = $conn->prepare("INSERT INTO health_education (title, category, description) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $title, $category, $description);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Mada imehifadhiwa."]);
} else {
error_log("createEducation error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
}
$stmt->close();
}

function listEducation($conn) {
$result = $conn->query("SELECT * FROM health_education ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getEducation($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM health_education WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
echo json_encode(["status" => "success", "record" => $row]);
} else {
echo json_encode(["status" => "error", "message" => "Mada haikupatikana."]);
}
}

function updateEducation($conn) {
$id          = intval($_POST['id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$category    = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($id <= 0 || $title === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza kichwa cha mada."]);
return;
}

$stmt = $conn->prepare("UPDATE health_education SET title=?, category=?, description=? WHERE id=?");
$stmt->bind_param("sssi", $title, $category, $description, $id);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Mada imesasishwa."]);
} else {
error_log("updateEducation error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
}
$stmt->close();
}

function deleteEducation($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM health_education WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Mada imefutwa."]);
} else {
error_log("deleteEducation error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}
