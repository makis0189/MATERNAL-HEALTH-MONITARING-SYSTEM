<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
require_once __DIR__ . '/risk_assessment.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse'];

$writeRoles = ['Nurse'];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createAnc($conn);
break;
case 'list':
listAnc($conn);
break;
case 'get':
getAnc($conn);
break;
case 'update':
updateAnc($conn);
break;
case 'delete':
deleteAnc($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function flagHighRiskCase($conn, $patient_name, $gestational_weeks, $risk_reasons) {
$followup_date = date('Y-m-d', strtotime('+7 days'));
$risk_factor = implode('; ', $risk_reasons);
if ($risk_factor === '') {
$risk_factor = 'Imegundulika na mfumo kutokana na vipimo vya ANC.';
}

$checkStmt = $conn->prepare("SELECT id FROM high_risk_cases WHERE patient_name = ? AND status != 'Recovered' ORDER BY id DESC LIMIT 1");
$checkStmt->bind_param("s", $patient_name);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
$stmt = $conn->prepare("UPDATE high_risk_cases SET gestational_weeks=?, risk_level='High', risk_factor=?, followup_date=?, auto_flagged=1 WHERE id=?");
$stmt->bind_param("sssi", $gestational_weeks, $risk_factor, $followup_date, $existing['id']);
$stmt->execute();
$stmt->close();
} else {
$status = 'Monitoring';
$stmt = $conn->prepare("INSERT INTO high_risk_cases (patient_name, gestational_weeks, risk_level, risk_factor, followup_date, status, auto_flagged) VALUES (?, ?, 'High', ?, ?, ?, 1)");
$stmt->bind_param("sssss", $patient_name, $gestational_weeks, $risk_factor, $followup_date, $status);
$stmt->execute();
$stmt->close();
}
}

function createAnc($conn) {
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment ----
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("INSERT INTO antenatal_care (patient_name, lmp_date, edd_date, gestational_weeks, blood_pressure, weight, temperature, fetal_heart_rate, risk_level, risk_reasons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssss", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimehifadhiwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("createAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
$stmt->close();
}
}

function listAnc($conn) {
$result = $conn->query("SELECT * FROM antenatal_care ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM antenatal_care WHERE id = ?");
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

function updateAnc($conn) {
$id                = intval($_POST['id'] ?? 0);
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($id <= 0 || $patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("UPDATE antenatal_care SET patient_name=?, lmp_date=?, edd_date=?, gestational_weeks=?, blood_pressure=?, weight=?, temperature=?, fetal_heart_rate=?, risk_level=?, risk_reasons=? WHERE id=?");
$stmt->bind_param("ssssssssssi", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str, $id);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimesasishwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("updateAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
$stmt->close();
}
}

function deleteAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM antenatal_care WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya ANC imefutwa."]);
} else {
error_log("deleteAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
require_once __DIR__ . '/risk_assessment.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse'];

$writeRoles = ['Nurse'];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createAnc($conn);
break;
case 'list':
listAnc($conn);
break;
case 'get':
getAnc($conn);
break;
case 'update':
updateAnc($conn);
break;
case 'delete':
deleteAnc($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function flagHighRiskCase($conn, $patient_name, $gestational_weeks, $risk_reasons) {
$followup_date = date('Y-m-d', strtotime('+7 days'));
$risk_factor = implode('; ', $risk_reasons);
if ($risk_factor === '') {
$risk_factor = 'Imegundulika na mfumo kutokana na vipimo vya ANC.';
}

$checkStmt = $conn->prepare("SELECT id FROM high_risk_cases WHERE patient_name = ? AND status != 'Recovered' ORDER BY id DESC LIMIT 1");
$checkStmt->bind_param("s", $patient_name);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
$stmt = $conn->prepare("UPDATE high_risk_cases SET gestational_weeks=?, risk_level='High', risk_factor=?, followup_date=?, auto_flagged=1 WHERE id=?");
$stmt->bind_param("sssi", $gestational_weeks, $risk_factor, $followup_date, $existing['id']);
$stmt->execute();
$stmt->close();
} else {
$status = 'Monitoring';
$stmt = $conn->prepare("INSERT INTO high_risk_cases (patient_name, gestational_weeks, risk_level, risk_factor, followup_date, status, auto_flagged) VALUES (?, ?, 'High', ?, ?, ?, 1)");
$stmt->bind_param("sssss", $patient_name, $gestational_weeks, $risk_factor, $followup_date, $status);
$stmt->execute();
$stmt->close();
}
}

function createAnc($conn) {
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment ----
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("INSERT INTO antenatal_care (patient_name, lmp_date, edd_date, gestational_weeks, blood_pressure, weight, temperature, fetal_heart_rate, risk_level, risk_reasons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssss", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimehifadhiwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("createAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
$stmt->close();
}
}

function listAnc($conn) {
$result = $conn->query("SELECT * FROM antenatal_care ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM antenatal_care WHERE id = ?");
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

function updateAnc($conn) {
$id                = intval($_POST['id'] ?? 0);
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($id <= 0 || $patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("UPDATE antenatal_care SET patient_name=?, lmp_date=?, edd_date=?, gestational_weeks=?, blood_pressure=?, weight=?, temperature=?, fetal_heart_rate=?, risk_level=?, risk_reasons=? WHERE id=?");
$stmt->bind_param("ssssssssssi", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str, $id);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimesasishwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("updateAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
$stmt->close();
}
}

function deleteAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM antenatal_care WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya ANC imefutwa."]);
} else {
error_log("deleteAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
require_once __DIR__ . '/risk_assessment.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse'];

$writeRoles = ['Nurse'];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createAnc($conn);
break;
case 'list':
listAnc($conn);
break;
case 'get':
getAnc($conn);
break;
case 'update':
updateAnc($conn);
break;
case 'delete':
deleteAnc($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function flagHighRiskCase($conn, $patient_name, $gestational_weeks, $risk_reasons) {
$followup_date = date('Y-m-d', strtotime('+7 days'));
$risk_factor = implode('; ', $risk_reasons);
if ($risk_factor === '') {
$risk_factor = 'Imegundulika na mfumo kutokana na vipimo vya ANC.';
}

$checkStmt = $conn->prepare("SELECT id FROM high_risk_cases WHERE patient_name = ? AND status != 'Recovered' ORDER BY id DESC LIMIT 1");
$checkStmt->bind_param("s", $patient_name);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
$stmt = $conn->prepare("UPDATE high_risk_cases SET gestational_weeks=?, risk_level='High', risk_factor=?, followup_date=?, auto_flagged=1 WHERE id=?");
$stmt->bind_param("sssi", $gestational_weeks, $risk_factor, $followup_date, $existing['id']);
$stmt->execute();
$stmt->close();
} else {
$status = 'Monitoring';
$stmt = $conn->prepare("INSERT INTO high_risk_cases (patient_name, gestational_weeks, risk_level, risk_factor, followup_date, status, auto_flagged) VALUES (?, ?, 'High', ?, ?, ?, 1)");
$stmt->bind_param("sssss", $patient_name, $gestational_weeks, $risk_factor, $followup_date, $status);
$stmt->execute();
$stmt->close();
}
}

function createAnc($conn) {
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment ----
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("INSERT INTO antenatal_care (patient_name, lmp_date, edd_date, gestational_weeks, blood_pressure, weight, temperature, fetal_heart_rate, risk_level, risk_reasons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssss", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimehifadhiwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("createAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
$stmt->close();
}
}

function listAnc($conn) {
$result = $conn->query("SELECT * FROM antenatal_care ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM antenatal_care WHERE id = ?");
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

function updateAnc($conn) {
$id                = intval($_POST['id'] ?? 0);
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($id <= 0 || $patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("UPDATE antenatal_care SET patient_name=?, lmp_date=?, edd_date=?, gestational_weeks=?, blood_pressure=?, weight=?, temperature=?, fetal_heart_rate=?, risk_level=?, risk_reasons=? WHERE id=?");
$stmt->bind_param("ssssssssssi", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str, $id);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimesasishwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("updateAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
$stmt->close();
}
}

function deleteAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM antenatal_care WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya ANC imefutwa."]);
} else {
error_log("deleteAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
require_once __DIR__ . '/risk_assessment.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse'];

$writeRoles = ['Nurse'];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createAnc($conn);
break;
case 'list':
listAnc($conn);
break;
case 'get':
getAnc($conn);
break;
case 'update':
updateAnc($conn);
break;
case 'delete':
deleteAnc($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function flagHighRiskCase($conn, $patient_name, $gestational_weeks, $risk_reasons) {
$followup_date = date('Y-m-d', strtotime('+7 days'));
$risk_factor = implode('; ', $risk_reasons);
if ($risk_factor === '') {
$risk_factor = 'Imegundulika na mfumo kutokana na vipimo vya ANC.';
}

$checkStmt = $conn->prepare("SELECT id FROM high_risk_cases WHERE patient_name = ? AND status != 'Recovered' ORDER BY id DESC LIMIT 1");
$checkStmt->bind_param("s", $patient_name);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
$stmt = $conn->prepare("UPDATE high_risk_cases SET gestational_weeks=?, risk_level='High', risk_factor=?, followup_date=?, auto_flagged=1 WHERE id=?");
$stmt->bind_param("sssi", $gestational_weeks, $risk_factor, $followup_date, $existing['id']);
$stmt->execute();
$stmt->close();
} else {
$status = 'Monitoring';
$stmt = $conn->prepare("INSERT INTO high_risk_cases (patient_name, gestational_weeks, risk_level, risk_factor, followup_date, status, auto_flagged) VALUES (?, ?, 'High', ?, ?, ?, 1)");
$stmt->bind_param("sssss", $patient_name, $gestational_weeks, $risk_factor, $followup_date, $status);
$stmt->execute();
$stmt->close();
}
}

function createAnc($conn) {
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment ----
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("INSERT INTO antenatal_care (patient_name, lmp_date, edd_date, gestational_weeks, blood_pressure, weight, temperature, fetal_heart_rate, risk_level, risk_reasons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssss", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimehifadhiwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("createAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
$stmt->close();
}
}

function listAnc($conn) {
$result = $conn->query("SELECT * FROM antenatal_care ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM antenatal_care WHERE id = ?");
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

function updateAnc($conn) {
$id                = intval($_POST['id'] ?? 0);
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($id <= 0 || $patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("UPDATE antenatal_care SET patient_name=?, lmp_date=?, edd_date=?, gestational_weeks=?, blood_pressure=?, weight=?, temperature=?, fetal_heart_rate=?, risk_level=?, risk_reasons=? WHERE id=?");
$stmt->bind_param("ssssssssssi", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str, $id);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimesasishwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("updateAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
$stmt->close();
}
}

function deleteAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM antenatal_care WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya ANC imefutwa."]);
} else {
error_log("deleteAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
require_once __DIR__ . '/risk_assessment.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse'];

$writeRoles = ['Nurse'];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createAnc($conn);
break;
case 'list':
listAnc($conn);
break;
case 'get':
getAnc($conn);
break;
case 'update':
updateAnc($conn);
break;
case 'delete':
deleteAnc($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function flagHighRiskCase($conn, $patient_name, $gestational_weeks, $risk_reasons) {
$followup_date = date('Y-m-d', strtotime('+7 days'));
$risk_factor = implode('; ', $risk_reasons);
if ($risk_factor === '') {
$risk_factor = 'Imegundulika na mfumo kutokana na vipimo vya ANC.';
}

$checkStmt = $conn->prepare("SELECT id FROM high_risk_cases WHERE patient_name = ? AND status != 'Recovered' ORDER BY id DESC LIMIT 1");
$checkStmt->bind_param("s", $patient_name);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
$stmt = $conn->prepare("UPDATE high_risk_cases SET gestational_weeks=?, risk_level='High', risk_factor=?, followup_date=?, auto_flagged=1 WHERE id=?");
$stmt->bind_param("sssi", $gestational_weeks, $risk_factor, $followup_date, $existing['id']);
$stmt->execute();
$stmt->close();
} else {
$status = 'Monitoring';
$stmt = $conn->prepare("INSERT INTO high_risk_cases (patient_name, gestational_weeks, risk_level, risk_factor, followup_date, status, auto_flagged) VALUES (?, ?, 'High', ?, ?, ?, 1)");
$stmt->bind_param("sssss", $patient_name, $gestational_weeks, $risk_factor, $followup_date, $status);
$stmt->execute();
$stmt->close();
}
}

function createAnc($conn) {
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment ----
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("INSERT INTO antenatal_care (patient_name, lmp_date, edd_date, gestational_weeks, blood_pressure, weight, temperature, fetal_heart_rate, risk_level, risk_reasons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssss", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimehifadhiwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("createAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
$stmt->close();
}
}

function listAnc($conn) {
$result = $conn->query("SELECT * FROM antenatal_care ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM antenatal_care WHERE id = ?");
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

function updateAnc($conn) {
$id                = intval($_POST['id'] ?? 0);
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($id <= 0 || $patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("UPDATE antenatal_care SET patient_name=?, lmp_date=?, edd_date=?, gestational_weeks=?, blood_pressure=?, weight=?, temperature=?, fetal_heart_rate=?, risk_level=?, risk_reasons=? WHERE id=?");
$stmt->bind_param("ssssssssssi", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str, $id);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimesasishwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("updateAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
$stmt->close();
}
}

function deleteAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM antenatal_care WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya ANC imefutwa."]);
} else {
error_log("deleteAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
require_once __DIR__ . '/risk_assessment.php';
header('Content-Type: application/json');

$viewRoles = ['Admin', 'Doctor', 'Nurse'];

$writeRoles = ['Nurse'];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action, $writeActions, true)) {
requireRoleApi($writeRoles);
}

switch ($action) {
case 'create':
createAnc($conn);
break;
case 'list':
listAnc($conn);
break;
case 'get':
getAnc($conn);
break;
case 'update':
updateAnc($conn);
break;
case 'delete':
deleteAnc($conn);
break;
default:
echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function flagHighRiskCase($conn, $patient_name, $gestational_weeks, $risk_reasons) {
$followup_date = date('Y-m-d', strtotime('+7 days'));
$risk_factor = implode('; ', $risk_reasons);
if ($risk_factor === '') {
$risk_factor = 'Imegundulika na mfumo kutokana na vipimo vya ANC.';
}

$checkStmt = $conn->prepare("SELECT id FROM high_risk_cases WHERE patient_name = ? AND status != 'Recovered' ORDER BY id DESC LIMIT 1");
$checkStmt->bind_param("s", $patient_name);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
$stmt = $conn->prepare("UPDATE high_risk_cases SET gestational_weeks=?, risk_level='High', risk_factor=?, followup_date=?, auto_flagged=1 WHERE id=?");
$stmt->bind_param("sssi", $gestational_weeks, $risk_factor, $followup_date, $existing['id']);
$stmt->execute();
$stmt->close();
} else {
$status = 'Monitoring';
$stmt = $conn->prepare("INSERT INTO high_risk_cases (patient_name, gestational_weeks, risk_level, risk_factor, followup_date, status, auto_flagged) VALUES (?, ?, 'High', ?, ?, ?, 1)");
$stmt->bind_param("sssss", $patient_name, $gestational_weeks, $risk_factor, $followup_date, $status);
$stmt->execute();
$stmt->close();
}
}

function createAnc($conn) {
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment ----
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("INSERT INTO antenatal_care (patient_name, lmp_date, edd_date, gestational_weeks, blood_pressure, weight, temperature, fetal_heart_rate, risk_level, risk_reasons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssss", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimehifadhiwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("createAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
$stmt->close();
}
}

function listAnc($conn) {
$result = $conn->query("SELECT * FROM antenatal_care ORDER BY created_at DESC");
$data = [];
if ($result) {
while ($row = $result->fetch_assoc()) {
$data[] = $row;
}
}
echo json_encode(["status" => "success", "data" => $data]);
}

function getAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}

$stmt = $conn->prepare("SELECT * FROM antenatal_care WHERE id = ?");
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

function updateAnc($conn) {
$id                = intval($_POST['id'] ?? 0);
$patient_name      = trim($_POST['patient_name'] ?? '');
$lmp_date          = trim($_POST['lmp_date'] ?? '');
$edd_date          = trim($_POST['edd_date'] ?? '');
$gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
$blood_pressure    = trim($_POST['blood_pressure'] ?? '');
$weight            = trim($_POST['weight'] ?? '');
$temperature       = trim($_POST['temperature'] ?? '');
$fetal_heart_rate  = trim($_POST['fetal_heart_rate'] ?? '');

if ($id <= 0 || $patient_name === '' || $lmp_date === '' || $edd_date === '') {
echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa, LMP na EDD."]);
return;
}

$gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
$weight            = $weight === '' ? null : $weight;
$temperature       = $temperature === '' ? null : $temperature;
$fetal_heart_rate  = $fetal_heart_rate === '' ? null : $fetal_heart_rate;

// ---- Automated Risk Assessment
$assessment = assessAncRisk($blood_pressure, $fetal_heart_rate, $temperature);
$risk_level = $assessment['level'];
$risk_reasons_str = implode('; ', $assessment['reasons']);

$stmt = $conn->prepare("UPDATE antenatal_care SET patient_name=?, lmp_date=?, edd_date=?, gestational_weeks=?, blood_pressure=?, weight=?, temperature=?, fetal_heart_rate=?, risk_level=?, risk_reasons=? WHERE id=?");
$stmt->bind_param("ssssssssssi", $patient_name, $lmp_date, $edd_date, $gestational_weeks, $blood_pressure, $weight, $temperature, $fetal_heart_rate, $risk_level, $risk_reasons_str, $id);

if ($stmt->execute()) {
$stmt->close();

$riskAlert = false;
if ($risk_level === 'High') {
flagHighRiskCase($conn, $patient_name, $gestational_weeks, $assessment['reasons']);
$riskAlert = true;
}

echo json_encode([
"status"       => "success",
"message"      => "Taarifa za ANC zimesasishwa.",
"patient_name" => $patient_name,
"risk_level"   => $risk_level,
"risk_reasons" => $assessment['reasons'],
"risk_alert"   => $riskAlert,
]);
} else {
error_log("updateAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
$stmt->close();
}
}

function deleteAnc($conn) {
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
echo json_encode(["status" => "error", "message" => "ID batili."]);
return;
}
$stmt = $conn->prepare("DELETE FROM antenatal_care WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
echo json_encode(["status" => "success", "message" => "Rekodi ya ANC imefutwa."]);
} else {
error_log("deleteAnc error: " . $conn->error);
echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
}
$stmt->close();
}
