<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
header('Content-Type: application/json');
$viewRoles = ['Admin', 'Doctor', 'Nurse'];

$writeRoles = ['Nurse'];

requireRoleApi($viewRoles);

$action = $_GET['action'] ?? '';

$writeActions = ['create', 'update', 'delete'];
if (in_array($action,$writeActions, true)) {
    requireApi($writeRoles);
}

switch ($action) {
    case 'create':
        createHighRisk($conn);
        break;
    case 'list':
        listHighRisk($conn);
        break;
    case 'get':
        getHighRisk($conn);
        break;
    case 'update':
        updateHighRisk($conn);
        break;
    case 'delete':
        deleteHighRisk($conn);
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function createHighRisk($conn) {
    $patient_name      = trim($_POST['patient_name'] ?? '');
    $gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
    $risk_level        = trim($_POST['risk_level'] ?? '');
    $risk_factor       = trim($_POST['risk_factor'] ?? '');
    $followup_date     = trim($_POST['followup_date'] ?? '');
    $status            = trim($_POST['status'] ?? 'Monitoring');

    if ($patient_name === '' || $risk_level === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa na kiwango cha hatari."]);
        return;
    }

    $gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
    $followup_date = $followup_date === '' ? null : $followup_date;

    $stmt = $conn->prepare("INSERT INTO high_risk_cases (patient_name, gestational_weeks, risk_level, risk_factor, followup_date, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $patient_name, $gestational_weeks, $risk_level, $risk_factor, $followup_date, $status);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Kesi ya hatari kubwa imehifadhiwa."]);
    } else {
        error_log("createHighRisk error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kuhifadhi."]);
    }
    $stmt->close();
}

function listHighRisk($conn) {
    $result = $conn->query("SELECT * FROM high_risk_cases ORDER BY created_at DESC");
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
}

function getHighRisk($conn) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM high_risk_cases WHERE id = ?");
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

function updateHighRisk($conn) {
    $id                = intval($_POST['id'] ?? 0);
    $patient_name      = trim($_POST['patient_name'] ?? '');
    $gestational_weeks = trim($_POST['gestational_weeks'] ?? '');
    $risk_level        = trim($_POST['risk_level'] ?? '');
    $risk_factor       = trim($_POST['risk_factor'] ?? '');
    $followup_date     = trim($_POST['followup_date'] ?? '');
    $status            = trim($_POST['status'] ?? 'Monitoring');

    if ($id <= 0 || $patient_name === '' || $risk_level === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza jina la mgonjwa na kiwango cha hatari."]);
        return;
    }

    $gestational_weeks = $gestational_weeks === '' ? null : $gestational_weeks;
    $followup_date = $followup_date === '' ? null : $followup_date;

    $stmt = $conn->prepare("UPDATE high_risk_cases SET patient_name=?, gestational_weeks=?, risk_level=?, risk_factor=?, followup_date=?, status=? WHERE id=?");
    $stmt->bind_param("ssssssi", $patient_name, $gestational_weeks, $risk_level, $risk_factor, $followup_date, $status, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Kesi imesasishwa."]);
    } else {
        error_log("updateHighRisk error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha."]);
    }
    $stmt->close();
}

function deleteHighRisk($conn) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM high_risk_cases WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Kesi imefutwa."]);
    } else {
        error_log("deleteHighRisk error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta."]);
    }
    $stmt->close();
}
