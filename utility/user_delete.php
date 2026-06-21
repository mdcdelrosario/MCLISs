<?php
include '../js/fphp.php';
include '../conn.php';

if ($_SESSION['utilities_user'] != 1) {
    echo "❌ Invalid User Rights!";
    exit;
}

$raw = trim($_GET['userid'] ?? $_POST['userid'] ?? '');

if ($raw === '') {
    echo "❌ User ID not provided.";
    exit;
}

// Split and keep only valid integers
$ids = array_filter(array_map('intval', explode(',', $raw)));

if (empty($ids)) {
    echo "❌ Invalid user IDs.";
    exit;
}

// Prevent deletion of protected account
if (in_array(1, $ids)) {
    echo "❌ Cannot delete protected account.";
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = sqlsrv_query($conn,
    "DELETE FROM tbl_users WHERE id IN ($placeholders)",
    array_values($ids)
);

if ($stmt === false) {
    echo "❌ Delete failed: " . print_r(sqlsrv_errors(), true);
} else {
    echo "✅ User(s) deleted successfully.";
}

sqlsrv_close($conn);
?>