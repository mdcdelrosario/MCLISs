<?php
include '../js/fphp.php';
include '../conn.php';

if ($_SESSION['utilities_user'] != 1) {
    echo "Invalid User Rights!";
} else {

    $userId    = $_POST['userid']  ?? null;
    $newPass   = $_POST['new']     ?? '';
    $confirmPass = $_POST['confirm'] ?? '';

    if (!$userId) {
        die("User ID not provided.");
    }

    if ($newPass !== $confirmPass) {
        die("Passwords do not match.");
    }

    if (empty($newPass)) {
        die("Password cannot be empty.");
    }

    // 1. Check user exists
    $sql  = "SELECT [pass] FROM tbl_users WHERE id = ?";  // removed stray comma after [pass]
    $stmt = sqlsrv_query($conn, $sql, [$userId]);
    $row  = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$row) {
        die("User not found.");
    }

    // 2. Update password
    $newEncrypted = safeEncrypt($newPass);
    $updateSql  = "UPDATE tbl_users SET [pass] = ? WHERE id = ?";  // removed stray comma before WHERE
    $updateStmt = sqlsrv_query($conn, $updateSql, [$newEncrypted, $userId]);

    if ($updateStmt === false) {
        echo "Update failed: " . print_r(sqlsrv_errors(), true);
    } else {
        echo "Password updated successfully.";
    }

    sqlsrv_close($conn);
}
?>