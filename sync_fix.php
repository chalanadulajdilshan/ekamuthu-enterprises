<?php
include 'class/include.php';
$rentId = 32393;
$ISSUE_NOTE = new IssueNote();
$ISSUE_NOTE->syncRentInvoiceStats($rentId);
echo "Sync complete for Rent Invoice ID: " . $rentId;
unlink(__FILE__); // Self-delete
?>
