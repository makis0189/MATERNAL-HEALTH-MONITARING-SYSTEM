<?php
/**
 * mailer.php - Kutuma barua pepe ya "reset password".
 *
 * Njia kuu: SMTP halisi (Gmail au hosting yako) kupitia smtp_mailer.php -
 * hii ndiyo njia inayohakikisha barua ZINAFIKA kweli.
 *
 * Kama SMTP_HOST haijawekwa kwenye .env, mfumo unarudi kwenye mail() ya
 * PHP kama "fallback" (kwa server chache ambazo sendmail yao inafanya
 * kazi sawa) - lakini kwa XAMPP/localhost, SMTP ndiyo njia pekee
 * itakayofanya kazi kweli.
 */

require_once __DIR__ . '/smtp_mailer.php';

function buildResetEmailHtml($toName, $resetLink) {
    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    return "
        <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
            <h2 style='color:#603fd9;'>Password Reset Request</h2>
            <p>Hi {$safeName},</p>
            <p>We received a request to reset your Maternal Health System password. Click the button below to choose a new password. This link expires in 1 hour.</p>
            <p style='text-align:center; margin: 24px 0;'>
                <a href='{$safeLink}' style='background:#603fd9; color:#fff; padding:12px 24px; border-radius:6px; text-decoration:none; display:inline-block;'>Reset Password</a>
            </p>
            <p>If the button doesn't work, copy and paste this link into your browser:<br>
            <a href='{$safeLink}'>{$safeLink}</a></p>
            <p>If you didn't request this, you can safely ignore this email - your password will not be changed.</p>
            <hr>
            <p style='color:#888; font-size:12px;'>Maternal Health System</p>
        </div>
    ";
}

function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    $fromEmail = getenv('FROM_EMAIL') ?: 'no-reply@example.com';
    $fromName  = getenv('FROM_NAME') ?: 'Maternal Health System';
    $subject   = "Password Reset Request - Maternal Health System";
    $htmlBody  = buildResetEmailHtml($toName, $resetLink);

    $smtpHost = getenv('SMTP_HOST') ?: '';

    if ($smtpHost !== '') {
        // ---- Njia kuu: SMTP halisi ----
        $smtpPort       = getenv('SMTP_PORT') ?: 587;
        $smtpUsername   = getenv('SMTP_USERNAME') ?: '';
        $smtpPassword   = getenv('SMTP_PASSWORD') ?: '';
        $smtpEncryption = getenv('SMTP_ENCRYPTION') ?: 'tls';

        $smtp = new SimpleSMTP($smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption);
        $sent = $smtp->send($fromEmail, $fromName, $toEmail, $toName, $subject, $htmlBody);

        if (!$sent) {
            error_log("SMTP send failed to {$toEmail}: " . $smtp->getLastError());
        }
        return $sent;
    }

    // ---- Fallback: mail() ya PHP (kama SMTP haijasanidiwa kwenye .env) ----
    $headers  = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $sent = @mail($toEmail, $subject, $htmlBody, $headers);
    if (!$sent) {
        error_log("mail() fallback failed to {$toEmail} - SMTP_HOST haijawekwa kwenye .env, na mail() ya server pia imeshindwa.");
    }
    return $sent;
}
