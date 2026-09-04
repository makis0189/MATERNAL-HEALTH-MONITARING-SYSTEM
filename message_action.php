<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse', 'CHW'];

// Doctor is view-only system-wide now — no role currently assigned to write here.
$writeRoles = ['Nurse'];
requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createMessage($conn);
break;
case 'list':
listMessages($conn);
break;
case 'get':
getMessage($conn);
break;
case 'update':
updateMessage($conn);
break;
case 'delete':
deleteMessage($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function createMessage($conn) {
$receiver = trim($_POST['receiver'] ?? '');
$subject  = trim($_POST['subject'] ?? '');
$message  = trim($_POST['message'] ?? '');
$status   = 'Sent';

if ($receiver === '' || $subject === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza mpokeaji na kichwa cha ujumbe."]);
return;
}

$stmt = $conn->prepare("INSERT INTO messages (receiver, subject, message, status) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $receiver, $subject, $message, $status);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Ujumbe umetumwa."]);
} else {
error_log("createMessage error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kutuma ujumbe."]);
}
$stmt->close();
}

function listMessages($conn) {
$result = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getMessage($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
echo json_encode(["status" => "success", "record" => $row]);
} else {
echo json_encode(["status" => "error", "message" => "Ujumbe haukupatikana."]);
}
}

function updateMessage($conn) {
$id       = intval($_POST['id'] ?? 0);
$receiver = trim($_POST['receiver'] ?? '');
$subject  = trim($_POST['subject'] ?? '');
$message  = trim($_POST['message'] ?? '');

if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
if ($receiver === '' || $subject === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza mpokeaji na kichwa cha ujumbe."]);
return;
}

$stmt = $conn->prepare("UPDATE messages SET receiver=?, subject=?, message=? WHERE id=?");
$stmt->bind_param("sssi", $receiver, $subject, $message, $id);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Ujumbe umesasishwa."]);
} else {
error_log("updateMessage error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
}
$stmt->close();
}

function deleteMessage($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Ujumbe umefutwa."]);
} else {
error_log("deleteMessage error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}
