<?php

function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
$fromEmail = getenv('FROM_EMAIL') ?: 'no-reply@example.com';
$fromName  = getenv('FROM_NAME') ?: 'Maternal Health System';

$subject = "Password Reset Request - Maternal Health System";

$safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
$safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

$htmlBody = "
<div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
<h2 style='color:#603fd9;'>Password Reset Request</h2>
<p>Hi {$safeName},</p>
<p>We received a request to reset your Maternal Health System password. Click the button below to choose a new password. This link expires in 1 hour.</p>
<p style='text-align:center; margin: 24px 0;'>
<a href='{$safeLink}' style='background:#603fd9; color:#fff; padding:12px 24px; border-radius:6px; text-decoration:none; display:inline-block;'>Reset Password</a>
</p>
<p>If the button doesn't work, copy and paste this link into your browser:<br>
<a href='{$safeLink}'>{$safeLink}</a></p>
<p>If you didn't request this, you can safely ignore this email — your password will not be changed.</p>
<hr>
<p style='color:#888; font-size:12px;'>Maternal Health System</p>
</div>
";

$boundary = md5(uniqid((string) time()));

$headers  = "From: {$fromName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$fromEmail}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

return @mail($toEmail, $subject, $htmlBody, $headers);
}
