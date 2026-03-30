<?php
include 'class/include.php';
$SUPPLIER_MASTER = new SupplierMaster();
$response = $SUPPLIER_MASTER->fetchForDataTable(['start'=>0, 'length'=>10, 'draw'=>1]);
header('Content-Type: application/json');
echo json_encode($response);
