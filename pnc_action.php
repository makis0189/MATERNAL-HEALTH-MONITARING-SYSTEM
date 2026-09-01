<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
header('Content-Type: application/json');

requireRoleApi(['Admin', 'Doctor', 'Nurse']);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createPnc($conn);
        break;
    case 'list':
        listPnc($conn);
        break;
    case 'get':
        getPnc($conn);
        break;
    case 'update':
        updatePnc($conn);
        break;
    case 'delete':
        deletePnc($conn);
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function createPnc($conn) {
    $mother_name      = trim($_POST['mother_name'] ?? '');
    $father_name      = trim($_POST['father_name'] ?? '');
    $delivery_date    = trim($_POST['delivery_date'] ?? '');
    $delivery_method  = trim($_POST['delivery_method'] ?? '');
    $mother_condition = trim($_POST['mother_condition'] ?? '');
    $blood_pressure   = trim($_POST['blood_pressure'] ?? '');
    $temperature      = trim($_POST['temperature'] ?? '');
    $bleeding         = trim($_POST['bleeding'] ?? '');
    $baby_name        = trim($_POST['baby_name'] ?? '');
    $baby_sex         = trim($_POST['baby_sex'] ?? '');
    $birth_weight     = trim($_POST['birth_weight'] ?? '');
    $feeding          = trim($_POST['feeding'] ?? '');

    if ($mother_name === '' || $delivery_date === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mama na tarehe ya kujifungua."]);
        return;
    }

    $temperature  = $temperature === '' ? null : $temperature;
    $birth_weight = $birth_weight === '' ? null : $birth_weight;

    $stmt = $conn->prepare("INSERT INTO postnatal_care (mother_name, father_name, delivery_date, delivery_method, mother_condition, blood_pressure, temperature, bleeding, baby_name, baby_sex, birth_weight, feeding) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssss", $mother_name, $father_name, $delivery_date, $delivery_method, $mother_condition, $blood_pressure, $temperature, $bleeding, $baby_name, $baby_sex, $birth_weight, $feeding);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Rekodi ya baada ya kujifungua imehifadhiwa."]);
    } else {
        error_log("createPnc error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
    }
    $stmt->close();
}

function listPnc($conn) {
    $result = $conn->query("SELECT * FROM postnatal_care ORDER BY created_at DESC");
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
}

function getPnc($conn) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM postnatal_care WHERE id = ?");
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

function updatePnc($conn) {
    $id               = intval($_POST['id'] ?? 0);
    $mother_name      = trim($_POST['mother_name'] ?? '');
    $father_name      = trim($_POST['father_name'] ?? '');
    $delivery_date    = trim($_POST['delivery_date'] ?? '');
    $delivery_method  = trim($_POST['delivery_method'] ?? '');
    $mother_condition = trim($_POST['mother_condition'] ?? '');
    $blood_pressure   = trim($_POST['blood_pressure'] ?? '');
    $temperature      = trim($_POST['temperature'] ?? '');
    $bleeding         = trim($_POST['bleeding'] ?? '');
    $baby_name        = trim($_POST['baby_name'] ?? '');
    $baby_sex         = trim($_POST['baby_sex'] ?? '');
    $birth_weight     = trim($_POST['birth_weight'] ?? '');
    $feeding          = trim($_POST['feeding'] ?? '');

    if ($id <= 0 || $mother_name === '' || $delivery_date === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mama na tarehe ya kujifungua."]);
        return;
    }

    $temperature  = $temperature === '' ? null : $temperature;
    $birth_weight = $birth_weight === '' ? null : $birth_weight;

    $stmt = $conn->prepare("UPDATE postnatal_care SET mother_name=?, father_name=?, delivery_date=?, delivery_method=?, mother_condition=?, blood_pressure=?, temperature=?, bleeding=?, baby_name=?, baby_sex=?, birth_weight=?, feeding=? WHERE id=?");
    $stmt->bind_param("ssssssssssssi", $mother_name, $father_name, $delivery_date, $delivery_method, $mother_condition, $blood_pressure, $temperature, $bleeding, $baby_name, $baby_sex, $birth_weight, $feeding, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Rekodi imesasishwa."]);
    } else {
        error_log("updatePnc error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
    }
    $stmt->close();
}

function deletePnc($conn) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM postnatal_care WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Rekodi imefutwa."]);
    } else {
        error_log("deletePnc error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
    }
    $stmt->close();
}