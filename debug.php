<?php
// TEMPORARY diagnostic script — delete this file after use.
header('Content-Type: text/plain');

echo "DB_HOST = [" . getenv('DB_HOST') . "]\n";
echo "DB_USER = [" . getenv('DB_USER') . "]\n";
echo "DB_NAME = [" . getenv('DB_NAME') . "]\n";
echo "DB_PASS is set: " . (getenv('DB_PASS') ? 'yes' : 'no') . "\n";

echo "\n--- All DB_ related env vars ---\n";
foreach ($_ENV as $k => $v) {
if (stripos($k, 'DB_') === 0 || stripos($k, 'MYSQL') === 0) {
echo "$k = [$v]\n";
}
}
foreach ($_SERVER as $k => $v) {
if ((stripos($k, 'DB_') === 0 || stripos($k, 'MYSQL') === 0) && !isset($_ENV[$k])) {
echo "$k = [$v] (from \$_SERVER)\n";
}
}
