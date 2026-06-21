<?php
include '../conn.php';
include '../js/fphp.php';

if ($_SESSION['utilities_personnel'] != 1) {
    echo "Invalid User Rights!";
    exit;
}else {
$raw = trim($_POST['trn'] ?? $_GET['trn'] ?? '');

if ($raw === '') {
    echo "No records selected.";
    exit;
}

// Split and sanitize — keep only numeric values
$trns = array_filter(array_map('intval', explode(',', $raw)));

if (empty($trns)) {
    echo "Invalid record IDs.";
    exit;
}

// Build parameterized query with one ? per ID
$placeholders = implode(',', array_fill(0, count($trns), '?'));
$params       = array_values($trns);

$stmt = sqlsrv_query($conn,
    "DELETE FROM tbl_person WHERE trn IN ($placeholders)",
    $params
);

if ($stmt === false) {
    echo "Error: " . print_r(sqlsrv_errors(), true);
    exit;
}

echo "Successfully deleted " . sqlsrv_rows_affected($stmt) . " record(s).";
    
sqlsrv_close($conn);
}

?>