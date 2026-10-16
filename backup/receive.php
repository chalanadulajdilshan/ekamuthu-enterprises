<?php
// Runs on the cloud server: receives the hourly backup from the client PC,
// stores it and (optionally) restores it into the cloud DB.

header('Content-Type: text/plain');
date_default_timezone_set('Asia/Colombo');
set_time_limit(0);
ini_set('memory_limit', '512M');

$config = require __DIR__ . '/config.php';
$dir    = __DIR__ . '/storage';

function fail($code, $message)
{
    http_response_code($code);
    exit($message);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'POST only');
}
if (!hash_equals($config['token'], (string) ($_POST['token'] ?? ''))) {
    fail(403, 'Invalid token');
}
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    fail(400, 'Upload error ' . ($_FILES['file']['error'] ?? 'no file'));
}

$tmp = $_FILES['file']['tmp_name'];
if (!hash_equals((string) ($_POST['sha256'] ?? ''), hash_file('sha256', $tmp))) {
    fail(400, 'Checksum mismatch');
}

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$name = basename($_FILES['file']['name']);
if (!preg_match('/^backup_\d{8}_\d{6}\.sql\.gz$/', $name)) {
    fail(400, 'Invalid file name');
}
$target = $dir . '/' . $name;
if (!move_uploaded_file($tmp, $target)) {
    fail(500, 'Could not save file');
}

// Keep only the latest N cloud backups
$files = glob($dir . '/backup_*.sql.gz');
rsort($files);
foreach (array_slice($files, $config['cloud_keep']) as $old) {
    unlink($old);
}

if (!$config['import_to_cloud_db']) {
    exit("Stored {$name}");
}

// Restore into the cloud DB, one statement at a time.
// mysqldump escapes newlines inside values, so a line ending in ';' always ends a statement.
$db  = $config['cloud_db'];
$con = mysqli_connect($db['host'], $db['user'], $db['password'], $db['name']);
if (!$con) {
    fail(500, "Stored {$name}, but cloud DB connect failed: " . mysqli_connect_error());
}
mysqli_set_charset($con, 'utf8mb4');

$gz        = gzopen($target, 'rb');
$statement = '';
$count     = 0;
while (($line = gzgets($gz)) !== false) {
    $trimmed = trim($line);
    // Skip blanks, comments and MariaDB's client-only "/*M!999999\- enable the sandbox mode */" line
    if ($statement === '' && ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*M!999999') === 0)) {
        continue;
    }
    $statement .= $line;
    if (substr($trimmed, -1) === ';') {
        if (!mysqli_query($con, $statement)) {
            gzclose($gz);
            fail(500, "Stored {$name}, but import failed after {$count} statements: " . mysqli_error($con));
        }
        $statement = '';
        $count++;
    }
}
gzclose($gz);
mysqli_close($con);

echo "Stored and imported {$name} ({$count} statements)";
