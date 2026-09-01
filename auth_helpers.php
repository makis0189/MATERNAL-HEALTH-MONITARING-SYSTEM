<?php
/**
 * auth_helpers.php - Role-Based Access Control (RBAC) helpers.
 *
 * Majukumu (roles) yanayotambuliwa na mfumo:
 *   Admin   - kila kitu + Manage Users
 *   Doctor  - taarifa zote za kimatibabu (Patients, Appointments, ANC,
 *             PNC, Immunization, High Risk, Messages, Education, Reports)
 *   Nurse   - sawa na Doctor KIUFANISI wa mfumo (wote ni "clinical staff"),
 *             lakini bila Reports
 *   CHW     - Community Health Worker: Appointments na Messages TU (jina,
 *             namba ya simu, miadi) - HAINA ufikiaji wa rekodi kamili za
 *             kimatibabu (BP, vipimo, historia) - hii inatekeleza NFR 6
 *             (Privacy) ya SRS: "Community Health Workers shall only have
 *             access to the contact and appointment information of
 *             patients assigned to them, and shall not have access to
 *             full clinical records."
 *   Manager - Dashboard na Reports TU (kuangalia takwimu, hakuna kuhariri)
 *
 * MUHIMU: Ukaguzi huu wa PHP ndio ULINZI HALISI (server-side). Kuficha
 * vitufe kwenye dashboard.php ni kwa ajili ya UX nzuri tu - mtu mwenye
 * ujuzi angeweza kupitisha hilo kwa kuita *_action.php moja kwa moja, ndiyo
 * maana kila *_action.php lazima liwe na requireRole() lake pia.
 */

require_once __DIR__ . '/session_config.php';

function currentRole() {
    return $_SESSION['role'] ?? null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Tumia hii mwanzoni mwa kila *_action.php (API) baada ya kuangalia
 * session. Ikiwa role haiendani, inatoa JSON error na 403, kisha exit().
 */
function requireRoleApi(array $allowedRoles) {
    if (!isLoggedIn()) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Session imeisha. Tafadhali ingia tena."]);
        exit();
    }
    if (!in_array(currentRole(), $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Huna ruhusa ya kufanya kitendo hiki."]);
        exit();
    }
}

/**
 * Tumia hii kwenye kurasa za HTML (mfano dashboard.php ikiwa siku moja
 * ikigawanywa) - inaelekeza kwenda login au inaonyesha ukurasa wa
 * "hairuhusiwi" badala ya JSON.
 */
function requireRolePage(array $allowedRoles) {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
    if (!in_array(currentRole(), $allowedRoles, true)) {
        http_response_code(403);
        echo "Huna ruhusa ya kufikia ukurasa huu.";
        exit();
    }
}

function roleCan($allowedRoles) {
    return in_array(currentRole(), $allowedRoles, true);
}