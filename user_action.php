<?php
require_once __DIR__ . '/auth_helpers.php';
require_once 'db.php';
header('Content-Type: application/json');

requireRoleApi(['Admin']);

$VALID_ROLES = ['Admin', 'Doctor', 'Nurse', 'CHW', 'Manager'];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createUser($conn);
        break;
    case 'list':
        listUsers($conn);
        break;
    case 'get':
        getUser($conn);
        break;
    case 'update':
        updateUser($conn);
        break;
    case 'delete':
        deleteUser($conn);
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Kitendo hakieleweki."]);
}

function createUser($conn) {
    global $VALID_ROLES;

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $role      = trim($_POST['role'] ?? '');

    if ($full_name === '' || $email === '' || $password === '' || $role === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza taarifa zote."]);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Barua pepe si sahihi."]);
        return;
    }
    if (strlen($password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password lazima iwe na herufi angalau 8."]);
        return;
    }
    if (!in_array($role, $VALID_ROLES, true)) {
        echo json_encode(["status" => "error", "message" => "Role si sahihi."]);
        return;
    }

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        echo json_encode(["status" => "error", "message" => "Barua pepe hii tayari imesajiliwa."]);
        return;
    }
    $checkStmt->close();

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $full_name, $email, $hashed, $role);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Mtumiaji ameongezwa kikamilifu."]);
    } else {
        error_log("createUser error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kuongeza mtumiaji."]);
    }
    $stmt->close();
}

function listUsers($conn) {
    $result = $conn->query("SELECT id, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
}

function getUser($conn) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        return;
    }
    $stmt = $conn->prepare("SELECT id, full_name, email, role, is_active FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        echo json_encode(["status" => "success", "record" => $row]);
    } else {
        echo json_encode(["status" => "error", "message" => "Mtumiaji hakupatikana."]);
    }
}

function updateUser($conn) {
    global $VALID_ROLES;

    $id        = intval($_POST['id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = trim($_POST['role'] ?? '');
    $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
    $password  = trim($_POST['password'] ?? ''); // hiari - ikiwa tupu, password haibadiliki

    if ($id <= 0 || $full_name === '' || $email === '' || $role === '') {
        echo json_encode(["status" => "error", "message" => "Tafadhali jaza taarifa zote."]);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Barua pepe si sahihi."]);
        return;
    }
    if (!in_array($role, $VALID_ROLES, true)) {
        echo json_encode(["status" => "error", "message" => "Role si sahihi."]);
        return;
    }
    if ($password !== '' && strlen($password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password mpya lazima iwe na herufi angalau 8."]);
        return;
    }

    // Usalama: Admin asijifunge nje ya mfumo mwenyewe - asijibadilishe
    // kuwa role isiyo Admin, wala asijizime (deactivate) mwenyewe.
    if ($id === (int) $_SESSION['user_id']) {
        if ($role !== 'Admin') {
            echo json_encode(["status" => "error", "message" => "Huwezi kubadilisha role ya akaunti yako mwenyewe kutoka Admin."]);
            return;
        }
        if ($is_active !== 1) {
            echo json_encode(["status" => "error", "message" => "Huwezi kuzima (deactivate) akaunti yako mwenyewe."]);
            return;
        }
    }

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $email, $id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        echo json_encode(["status" => "error", "message" => "Barua pepe hii tayari inatumiwa na mtumiaji mwingine."]);
        return;
    }
    $checkStmt->close();

    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, is_active=?, password=? WHERE id=?");
        $stmt->bind_param("sssisi", $full_name, $email, $role, $is_active, $hashed, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role=?, is_active=? WHERE id=?");
        $stmt->bind_param("sssii", $full_name, $email, $role, $is_active, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Mtumiaji amesasishwa kikamilifu."]);
    } else {
        error_log("updateUser error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kusasisha mtumiaji."]);
    }
    $stmt->close();
}

function deleteUser($conn) {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID batili."]);
        return;
    }

    if ($id === (int) $_SESSION['user_id']) {
        echo json_encode(["status" => "error", "message" => "Huwezi kufuta akaunti yako mwenyewe."]);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Mtumiaji amefutwa."]);
    } else {
        error_log("deleteUser error: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Imeshindikana kufuta mtumiaji."]);
    }
    $stmt->close();
}