<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Session imeisha. Tafadhali ingia tena."]);
    exit();
}

$filter = $_GET['filter'] ?? 'all';

function dateCondition($filter) {
    if ($filter === 'today') {
        return "WHERE DATE(created_at) = CURDATE()";
    } elseif ($filter === 'month') {
        return "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    }
    return "";
}

function countTable($conn, $table, $cond = '') {
    $result = $conn->query("SELECT COUNT(*) AS total FROM $table $cond");
    return $result ? (int) $result->fetch_assoc()['total'] : 0;
}

$cond = dateCondition($filter);

$counts = [
    "patients"      => countTable($conn, "patients"),
    "appointments"  => countTable($conn, "appointments", $cond),
    "anc"           => countTable($conn, "antenatal_care", $cond),
    "pnc"           => countTable($conn, "postnatal_care", $cond),
    "immunizations" => countTable($conn, "immunizations", $cond),
    "high_risk"     => countTable($conn, "high_risk_cases", $cond),
];

echo json_encode(["status" => "success", "data" => $counts]);