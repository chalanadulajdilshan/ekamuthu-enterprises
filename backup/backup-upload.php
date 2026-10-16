<?php
// Runs on the client PC (via Windows Task Scheduler, every hour):
// dumps the local DB, gzips it and uploads it to the cloud server.
//   C:\xampp\php\php.exe C:\xampp\htdocs\ekamuthu-enterprises\backup\backup-upload.php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

date_default_timezone_set('Asia/Colombo');
set_time_limit(0);

$config = require __DIR__ . '/config.php';
$dir    = __DIR__ . '/storage';
$logFile = $dir . '/backup.log';

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

function backupLog($message)
{
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

// 1. Dump local DB
$db      = $config['local_db'];
$sqlFile = $dir . '/backup_' . date('Ymd_His') . '.sql';
$cmd = sprintf(
    '"%s" --host=%s --user=%s %s --single-transaction --quick --default-character-set=utf8mb4 --result-file=%s %s 2>&1',
    $config['mysqldump'],
    escapeshellarg($db['host']),
    escapeshellarg($db['user']),
    $db['password'] !== '' ? '--password=' . escapeshellarg($db['password']) : '',
    escapeshellarg($sqlFile),
    escapeshellarg($db['name'])
);

exec($cmd, $output, $exitCode);
if ($exitCode !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
    backupLog('ERROR mysqldump failed: ' . implode(' | ', $output));
    @unlink($sqlFile);
    exit(1);
}

// 2. Gzip
$gzFile = $sqlFile . '.gz';
$in  = fopen($sqlFile, 'rb');
$out = gzopen($gzFile, 'wb6');
while (!feof($in)) {
    gzwrite($out, fread($in, 1024 * 512));
}
fclose($in);
gzclose($out);
unlink($sqlFile);

// 3. Upload
$ch = curl_init($config['upload_url']);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'token'  => $config['token'],
        'sha256' => hash_file('sha256', $gzFile),
        'file'   => new CURLFile($gzFile, 'application/gzip', basename($gzFile)),
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TIMEOUT        => 600,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

$sizeKb = round(filesize($gzFile) / 1024, 1);
if ($response === false || $httpCode !== 200) {
    backupLog("ERROR upload failed ({$httpCode}) " . basename($gzFile) . " {$sizeKb}KB: " . ($curlErr ?: $response));
    $exit = 1;
} else {
    backupLog('OK ' . basename($gzFile) . " {$sizeKb}KB -> " . trim($response));
    $exit = 0;
}

// 4. Keep only the latest N local backups
$files = glob($dir . '/backup_*.sql.gz');
rsort($files);
foreach (array_slice($files, $config['local_keep']) as $old) {
    unlink($old);
}

exit($exit);
