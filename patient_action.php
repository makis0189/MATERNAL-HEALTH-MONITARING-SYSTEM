<?php
require_once __DIR__ . '/auth_helpers.php';
header('Content-Type: application/json');
require_once 'db.php';

requireRoleApi(['Admin', 'Doctor', 'Nurse']);

$action = $_GET['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id        = trim($_POST['patient_id'] ?? '');
    $full_name         = trim($_POST['full_name'] ?? '');
    $dob               = trim($_POST['dob'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $address           = trim($_POST['address'] ?? '');
    $blood_group       = trim($_POST['blood_group'] ?? '');
    $pregnancy_status  = trim($_POST['pregnancy_status'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');

    if ($patient_id === '' || $full_name === '' || $phone === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza Patient ID, jina kamili na namba ya simu."]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO patients (patient_id, full_name, dob, phone, address, blood_group, pregnancy_status, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $patient_id, $full_name, $dob, $phone, $address, $blood_group, $pregnancy_status, $emergency_contact);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Mgonjwa amesajiliwa kikamilifu."]);
    } else {
        error_log("patient create error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kusajili mgonjwa."]);
    }
    $stmt->close();

} elseif ($action === 'list') {
    $result = $conn->query("SELECT * FROM patients ORDER BY id DESC");
    $patients = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(["status" => "success", "data" => $patients]);

} elseif ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Rekodi ya mgonjwa imefutwa."]);
    } else {
        error_log("patient delete error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta rekodi."]);
    }
    $stmt->close();

} elseif ($action === 'get') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($patient = $result->fetch_assoc()) {
        echo json_encode(["status" => "success", "patient" => $patient]);
    } else {
        echo json_encode(["status" => "error", "message" => "Mgonjwa hakupatikana."]);
    }
    $stmt->close();

} elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $patient_id        = trim($_POST['patient_id'] ?? '');
    $full_name         = trim($_POST['full_name'] ?? '');
    $dob               = trim($_POST['dob'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $address           = trim($_POST['address'] ?? '');
    $blood_group       = trim($_POST['blood_group'] ?? '');
    $pregnancy_status  = trim($_POST['pregnancy_status'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');

    if ($id <= 0 || $patient_id === '' || $full_name === '' || $phone === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza Patient ID, jina kamili na namba ya simu."]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE patients SET patient_id=?, full_name=?, dob=?, phone=?, address=?, blood_group=?, pregnancy_status=?, emergency_contact=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $patient_id, $full_name, $dob, $phone, $address, $blood_group, $pregnancy_status, $emergency_contact, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Taarifa za mgonjwa zimesasishwa."]);
    } else {
        error_log("patient update error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha taarifa."]);
    }
    $stmt->close();

} else {
    echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}