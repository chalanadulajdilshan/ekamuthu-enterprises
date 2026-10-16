<?php
// Shared settings for the hourly local -> cloud DB backup.
// Used by backup-upload.php (client PC) and receive.php (cloud server).

return [
    // Shared secret - must be identical on the client PC and the cloud server
    'token' => '65a427253f706df946d23f4ffc637a42955ee709e12c0b0a',

    // ---------- Client PC (local XAMPP) ----------
    'mysqldump'   => 'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'local_db'    => [
        'host'     => 'localhost',
        'name'     => 'ekamuthu-enterprises',
        'user'     => 'root',
        'password' => '',
    ],
    'upload_url'  => 'https://ekamuthu-enterprises.sourcecode.lk/backup/receive.php',
    'local_keep'  => 72,   // number of backup files to keep on the client PC (3 days)

    // ---------- Cloud server ----------
    'cloud_db'    => [
        'host'     => 'localhost',
        'name'     => 'chalcepi_ekamuthu-enterprises',
        'user'     => 'chalcepi_ekamuthu-enterprises',
        'password' => '!}}c~bOdZR#g',
    ],
    // true  = restore each uploaded backup into the cloud DB (cloud becomes a mirror of the local system)
    // false = only store the .sql.gz files on the cloud server
    'import_to_cloud_db' => true,
    'cloud_keep'  => 168,  // number of backup files to keep on the cloud server (7 days)
];
