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
createImmunization($conn);
break;
case 'list':
listImmunizations($conn);
break;
case 'get':
getImmunization($conn);
break;
case 'update':
updateImmunization($conn);
break;
case 'delete':
deleteImmunization($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function createImmunization($conn) {
$patient_name  = trim($_POST['patient_name'] ?? '');
$vaccine       = trim($_POST['vaccine'] ?? '');
$dose          = trim($_POST['dose'] ?? '');
$date_given    = trim($_POST['date_given'] ?? '');
$next_due_date = trim($_POST['next_due_date'] ?? '');
$status        = trim($_POST['status'] ?? 'Given');

if ($patient_name === '' || $vaccine === '' || $date_given === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina, chanjo na tarehe iliyotolewa."]);
return;
}

$dose = $dose === '' ? null : $dose;
$next_due_date = $next_due_date === '' ? null : $next_due_date;

$stmt = $conn->prepare("INSERT INTO immunizations (patient_name, vaccine, dose, date_given, next_due_date, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $patient_name, $vaccine, $dose, $date_given, $next_due_date, $status);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya chanjo imehifadhiwa."]);
} else {
error_log("createImmunization error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
}
$stmt->close();
}

function listImmunizations($conn) {
$result = $conn->query("SELECT * FROM immunizations ORDER BY date_given DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getImmunization($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM immunizations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($row) {
echo json_encode(["status" => "success", "record" => $row]);
} else {
echo json_encode(["status" => "error", "message" => "Rekodi haikupatikana."]);
}
}

function updateImmunization($conn) {
$id            = intval($_POST['id'] ?? 0);
$patient_name  = trim($_POST['patient_name'] ?? '');
$vaccine       = trim($_POST['vaccine'] ?? '');
$dose          = trim($_POST['dose'] ?? '');
$date_given    = trim($_POST['date_given'] ?? '');
$next_due_date = trim($_POST['next_due_date'] ?? '');
$status        = trim($_POST['status'] ?? 'Given');

if ($id <= 0 || $patient_name === '' || $vaccine === '' || $date_given === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina, chanjo na tarehe iliyotolewa."]);
return;
}

$dose = $dose === '' ? null : $dose;
$next_due_date = $next_due_date === '' ? null : $next_due_date;

$stmt = $conn->prepare("UPDATE immunizations SET patient_name=?, vaccine=?, dose=?, date_given=?, next_due_date=?, status=? WHERE id=?");
$stmt->bind_param("ssssssi", $patient_name, $vaccine, $dose, $date_given, $next_due_date, $status, $id);

if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya chanjo imesasishwa."]);
} else {
error_log("updateImmunization error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
}
$stmt->close();
}

function deleteImmunization($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM immunizations WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya chanjo imefutwa."]);
} else {
error_log("deleteImmunization error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}
