<?php
require_once __DIR__ . '/session_config.php';
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        header("Location: index.php?error=empty_fields");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, full_name, password, role, is_active FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->num_rows === 1 ? $result->fetch_assoc() : null;
    $stmt->close();

    // Hash "bandia" (haihusiani na mtumiaji yeyote halisi) inayotumika
    // endapo email haipo kwenye database - inazuia "timing attack".
    $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.YeIu8/2Se7EnQ1MZDXO2jTk.RkH0GHFvW';
    $hashToCheck = $user ? $user['password'] : $dummyHash;
    $passwordOk = password_verify($password, $hashToCheck);

    if (!$user || !$passwordOk) {
        header("Location: index.php?error=invalid_credentials");
        exit();
    }

    if ((int) $user['is_active'] !== 1) {
        header("Location: index.php?error=account_disabled");
        exit();
    }

    // Zuia "session fixation" - toa session ID mpya kabisa baada ya
    // login kufanikiwa, kabla ya kuweka taarifa za mtumiaji.
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    header("Location: dashboard.php");
    exit();
}

// Ombi si POST - mrudishe kwenye fomu badala ya kuonyesha ukurasa mtupu.
header("Location: index.php");
exit();