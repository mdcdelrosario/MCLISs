<?php
include '../conn.php';

$trn_raw = isset($_POST['trn']) ? $_POST['trn'] : '';

if (empty($trn_raw)) {
    echo "Error: No records selected.";
    exit;
}

// Split, cast each to int, filter out any zeros/invalid
$ids = array_filter(array_map('intval', explode(',', $trn_raw)));

if (empty($ids)) {
    echo "Error: Invalid record IDs.";
    exit;
}

// Build IN (?, ?, ...) placeholders
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// sqlsrv expects params as array of arrays
$params = array_map(function($id) {
    return array($id, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT);
}, $ids);

$sql  = "DELETE FROM tbl_request WHERE trn IN ($placeholders)";
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    echo "Error Deleting Record: " . print_r($errors, true);
} else {
    echo "success";
}

sqlsrv_close($conn);
?>