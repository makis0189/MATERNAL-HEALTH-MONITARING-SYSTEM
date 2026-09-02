<?php
/**
 * Loads key=value pairs from a .env file into getenv()/$_ENV/$_SERVER.
 * Silently does nothing if the file isn't present (e.g. local XAMPP
 * setups that don't use one) so the defaults below still apply.
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue; // skip blank lines and comments
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, "\"'");

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnv(__DIR__ . '/.env');

$host   = getenv('DB_HOST') ?: 'mysql.railway.internal';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: 'iYbjrCXVEjmVaXcpJvwsHxEJsTSxodpS';
$dbname = getenv('DB_NAME') ?: 'maternal_health_db';
$port   = getenv('DB_PORT') ?: 3306;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}
