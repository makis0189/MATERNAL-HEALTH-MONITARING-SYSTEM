<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse', 'CHW'];

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
createAppointment($conn);
break;
case 'list':
listAppointments($conn);
break;
case 'get':
getAppointment($conn);
break;
case 'update':
updateAppointment($conn);
break;
case 'delete':
deleteAppointment($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "The action is incomprehensible."]);
}

function createAppointment($conn) {
$patient_name     = trim($_POST['patient_name'] ?? '');
$appointment_date = trim($_POST['appointment_date'] ?? '');
$appointment_time = trim($_POST['appointment_time'] ?? '');
$service          = trim($_POST['service'] ?? '');
$status           = trim($_POST['status'] ?? 'Pending');

if ($patient_name === '' || $appointment_date === '' || $appointment_time === '') {
echo json_encode(["status" => "error", "message" => "Please enter name of patient, Date and Time."]);
return;
}

$stmt = $conn->prepare("INSERT INTO appointments (patient_name, appointment_date, appointment_time, service, status) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $patient_name, $appointment_date, $appointment_time, $service, $status);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Appointment successfully saved."]);
} else {
error_log("createAppointment error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Failed to store appointment."]);
}
$stmt->close();
}

function listAppointments($conn) {
$result = $conn->query("SELECT * FROM appointments ORDER BY appointment_date DESC, appointment_time DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getAppointment($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
echo json_encode(["status" => "success", "appointment" => $row]);
} else {
echo json_encode(["status" => "error", "message" => "Appointment not found."]);
}
}

function updateAppointment($conn) {
$id               = intval($_POST['id'] ?? 0);
$patient_name     = trim($_POST['patient_name'] ?? '');
$appointment_date = trim($_POST['appointment_date'] ?? '');
$appointment_time = trim($_POST['appointment_time'] ?? '');
$service          = trim($_POST['service'] ?? '');
$status           = trim($_POST['status'] ?? 'Pending');

if ($id <= 0 || $patient_name === '' || $appointment_date === '' || $appointment_time === '') {
echo json_encode(["status" => "error", "message" => "Please enter all important detail."]);
return;
}

$stmt = $conn->prepare("UPDATE appointments SET patient_name = ?, appointment_date = ?, appointment_time = ?, service = ?, status = ? WHERE id = ?");
$stmt->bind_param("sssssi", $patient_name, $appointment_date, $appointment_time, $service, $status, $id);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Appointment successfully updated."]);
} else {
error_log("updateAppointment error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Failed to update Appointment."]);
}
$stmt->close();
}

function deleteAppointment($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "Invalid ID."]);
return;
}
$stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Appointment Deleted."]);
} else {
error_log("deleteAppointment error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Failed to delete Appointment."]);
}
$stmt->close();
}
