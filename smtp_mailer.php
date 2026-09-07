<?php
/**
 * smtp_mailer.php - SMTP client rahisi, bila Composer/PHPMailer -
 * inaongea moja kwa moja na SMTP server kupitia socket (fsockopen),
 * ikifuata protocol ya SMTP (RFC 5321) kwa hatua zake za msingi:
 * EHLO -> STARTTLS (kama inahitajika) -> AUTH LOGIN -> MAIL FROM ->
 * RCPT TO -> DATA -> QUIT.
 *
 * Kwa nini si mail() ya PHP: mail() inategemea "sendmail" iliyowekwa
 * sawa kwenye server (mara nyingi HAIPO kwenye XAMPP/localhost, na
 * kwenye baadhi ya hosting pia). SMTP halisi (Gmail au hosting yako)
 * inahakikisha barua ZINAFIKA kweli.
 */

class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption; // 'tls' (STARTTLS, kwa kawaida port 587) au 'ssl' (implicit, kwa kawaida port 465)
    private $socket;
    private $timeout = 15;
    private $lastError = '';

    public function __construct($host, $port, $username, $password, $encryption = 'tls') {
        $this->host = $host;
        $this->port = (int) $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = strtolower($encryption);
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function connect() {
        $prefix = ($this->encryption === 'ssl') ? 'ssl://' : '';
        $this->socket = @fsockopen($prefix . $this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            $this->lastError = "Imeshindikana kuunganisha na SMTP server: {$errstr} ({$errno})";
            return false;
        }
        stream_set_timeout($this->socket, $this->timeout);
        $this->readResponse(); // greeting ya server (220 ...)
        return true;
    }

    private function sendCommand($cmd) {
        fwrite($this->socket, $cmd . "\r\n");
    }

    private function readResponse() {
        $response = '';
        while (!feof($this->socket) && ($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;
            // Mstari wa mwisho wa jibu la SMTP una nafasi baada ya namba ya
            // code (mfano "250 OK"), si "-" (mfano "250-CONTINUES").
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    }

    private function expectCode($response, $expectedCodes) {
        $code = (int) substr($response, 0, 3);
        return in_array($code, (array) $expectedCodes, true);
    }

    /**
     * Inatuma email moja. Inarudisha true ikifanikiwa, false ikishindikana
     * (tumia getLastError() kuona sababu halisi kwa ajili ya error_log).
     */
    public function send($fromEmail, $fromName, $toEmail, $toName, $subject, $htmlBody) {
        if (!$this->connect()) {
            return false;
        }

        $localHost = $_SERVER['SERVER_NAME'] ?? 'localhost';

        $this->sendCommand("EHLO {$localHost}");
        $this->readResponse();

        if ($this->encryption === 'tls') {
            $this->sendCommand("STARTTLS");
            $resp = $this->readResponse();
            if (!$this->expectCode($resp, 220)) {
                $this->lastError = "STARTTLS haikukubaliwa na server: {$resp}";
                fclose($this->socket);
                return false;
            }
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->lastError = "Imeshindikana kuanzisha TLS encryption.";
                fclose($this->socket);
                return false;
            }
            // EHLO lazima itumwe TENA baada ya STARTTLS kufanikiwa.
            $this->sendCommand("EHLO {$localHost}");
            $this->readResponse();
        }

        $this->sendCommand("AUTH LOGIN");
        $resp = $this->readResponse();
        if (!$this->expectCode($resp, 334)) {
            $this->lastError = "AUTH LOGIN haikukubaliwa: {$resp}";
            fclose($this->socket);
            return false;
        }

        $this->sendCommand(base64_encode($this->username));
        $resp = $this->readResponse();
        if (!$this->expectCode($resp, 334)) {
            $this->lastError = "Username imekataliwa: {$resp}";
            fclose($this->socket);
            return false;
        }

        $this->sendCommand(base64_encode($this->password));
        $resp = $this->readResponse();
        if (!$this->expectCode($resp, 235)) {
            $this->lastError = "Uthibitishaji (authentication) umeshindikana - hakiki SMTP_USERNAME/SMTP_PASSWORD kwenye .env: {$resp}";
            fclose($this->socket);
            return false;
        }

        $this->sendCommand("MAIL FROM: <{$fromEmail}>");
        $resp = $this->readResponse();
        if (!$this->expectCode($resp, 250)) {
            $this->lastError = "MAIL FROM imekataliwa: {$resp}";
            fclose($this->socket);
            return false;
        }

        $this->sendCommand("RCPT TO: <{$toEmail}>");
        $resp = $this->readResponse();
        if (!$this->expectCode($resp, [250, 251])) {
            $this->lastError = "RCPT TO imekataliwa: {$resp}";
            fclose($this->socket);
            return false;
        }

        $this->sendCommand("DATA");
        $resp = $this->readResponse();
        if (!$this->expectCode($resp, 354)) {
            $this->lastError = "DATA command imekataliwa: {$resp}";
            fclose($this->socket);
            return false;
        }

        $headers  = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "To: {$toName} <{$toEmail}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        // "Dot-stuffing": kwa mujibu wa SMTP, mstari wenye "." peke yake
        // unaashiria MWISHO wa ujumbe. Mstari wowote wa ndani wa body
        // unaoanza na "." lazima uongezwe "." nyingine mbele yake ili
        // usichanganywe na hiyo alama ya mwisho.
        $bodyLines = explode("\n", str_replace("\r\n", "\n", $htmlBody));
        $stuffedBody = '';
        foreach ($bodyLines as $line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
            $stuffedBody .= rtrim($line, "\r") . "\r\n";
        }

        $message = $headers . "\r\n" . $stuffedBody . ".";
        $this->sendCommand($message);
        $resp = $this->readResponse();
        if (!$this->expectCode($resp, 250)) {
            $this->lastError = "Server imekataa kutuma ujumbe: {$resp}";
            fclose($this->socket);
            return false;
        }

        $this->sendCommand("QUIT");
        $this->readResponse();
        fclose($this->socket);

        return true;
    }
}
