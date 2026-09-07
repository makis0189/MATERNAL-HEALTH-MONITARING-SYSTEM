<?php
require_once __DIR__ . '/auth_helpers.php';
header('Content-Type: application/json');
require_once 'db.php';
// Roles allowed to VIEW (list/get) patient records.
$viewRoles  = ['Admin', 'Doctor', 'Nurse', 'CHW'];
// Roles allowed to ADD/EDIT/DELETE patient records — Nurse only.
$writeRoles = ['Nurse'];

// Baseline check: must at least be allowed to view this module.
requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

// Extra check for write actions — only Nurse passes this.
$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$patient_id = generatePatientID($conn);
$full_name         = trim($_POST['full_name'] ?? '');
$dob               = trim($_POST['dob'] ?? '');
$phone             = trim($_POST['phone'] ?? '');
$address           = trim($_POST['address'] ?? '');
$blood_group       = trim($_POST['blood_group'] ?? '');
$pregnancy_status  = trim($_POST['pregnancy_status'] ?? '');
$emergency_contact = trim($_POST['emergency_contact'] ?? '');

if ($full_name === '' || $phone === '') {
echo json_encode(["status" => "error", "message" => "Please enter the patient ID, full name, and phone number."]);
exit();
}

$stmt = $conn->prepare("INSERT INTO patients (patient_id, full_name, dob, phone, address, blood_group, pregnancy_status, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $patient_id, $full_name, $dob, $phone, $address, $blood_group, $pregnancy_status, $emergency_contact);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Patient registered succefully."]);
} else {
error_log("patient create error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Failed to register patient."]);
}
$stmt->close();

} elseif ($action === 'list') {
$result = $conn->query("SELECT * FROM patients WHERE status='active' ORDER BY id DESC");
$patients = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode(["status" => "success", "data" => $patients]);

} elseif ($action === 'delete') {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "Invalid ID."]);
exit();
}

$stmt = $conn->prepare("UPDATE patients SET status='deleted' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Record of patient Deleted."]);
} else {
error_log("patient delete error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Failed to Delete Record."]);
}
$stmt->close();

} elseif ($action === 'get') {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "Invalid ID."]);
exit();
}

$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($patient = $result->fetch_assoc()) {
echo json_encode(["status" => "success", "patient" => $patient]);
} else {
echo json_encode(["status" => "error", "message" => "Patient not Found."]);
}
$stmt->close();

} elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$id = intval($_POST['id'] ?? 0);
$patient_id = trim($_POST['patient_id'] ?? '');
$full_name         = trim($_POST['full_name'] ?? '');
$dob               = trim($_POST['dob'] ?? '');
$phone             = trim($_POST['phone'] ?? '');
$address           = trim($_POST['address'] ?? '');
$blood_group       = trim($_POST['blood_group'] ?? '');
$pregnancy_status  = trim($_POST['pregnancy_status'] ?? '');
$emergency_contact = trim($_POST['emergency_contact'] ?? '');

if ($id <= 0 || $full_name === '' || $phone === '') {
echo json_encode(["status" => "error", "message" => "Please enter the patient ID, full name, and phone number."]);
exit();
}

$stmt = $conn->prepare("UPDATE patients SET patient_id=?, full_name=?, dob=?, phone=?, address=?, blood_group=?, pregnancy_status=?, emergency_contact=? WHERE id=?");
$stmt->bind_param("ssssssssi", $patient_id, $full_name, $dob, $phone, $address, $blood_group, $pregnancy_status, $emergency_contact, $id);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Patient information successfully updated."]);
} else {
error_log("patient update error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Failed to update information."]);
}
$stmt->close();

} else {
echo json_encode(["status" => "error", "message" => "The action is incomprehensible."]);
}
