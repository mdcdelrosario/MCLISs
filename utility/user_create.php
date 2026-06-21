<?php
include '../js/fphp.php';
include '../conn.php';

if ($_SESSION['utilities_user'] != 1) {
    echo "❌ Invalid User Rights!";
} else {

    // Check required fields exist
    if (!isset($_POST['uname'], $_POST['fname'], $_POST['lname'], $_POST['pass'], $_POST['init'], $_POST['lvl'])) {
        echo "❌ Missing required fields.";
        exit;
    }

    // Sanitize inputs
    $user     = trim($_POST['uname']);
    $restrict = trim($_POST['urestrict'] ?? '');
    $fname    = strtoupper(trim($_POST['fname']));
    $lname    = strtoupper(trim($_POST['lname']));
    $lvl      = trim($_POST['lvl']);
    $ini      = strtoupper(trim($_POST['init']));

    // Encrypt password
    $pass = safeEncrypt(trim($_POST['pass']));
    if (!$pass) {
        echo "❌ Password encryption failed.";
        exit;
    }

    // Check if username already exists
    $checkSql  = "SELECT COUNT(*) as cnt FROM tbl_users WHERE [user] = ?";
    $checkStmt = sqlsrv_query($conn, $checkSql, [[$user, SQLSRV_PARAM_IN]]);

    if ($checkStmt === false) {
        echo "❌ Check query failed: " . print_r(sqlsrv_errors(), true);
        exit;
    }

    $exists = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)['cnt'] ?? 0;
    sqlsrv_free_stmt($checkStmt);

    if ($exists > 0) {
        echo "❌ Username already exists!";
    } else {

        $sql = "INSERT INTO tbl_users ([user], [restrict], [pass], fname, lname, ini, acctlvl)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $params = [
            [$user,     SQLSRV_PARAM_IN],
            [$restrict, SQLSRV_PARAM_IN],
            [$pass,     SQLSRV_PARAM_IN],
            [$fname,    SQLSRV_PARAM_IN],
            [$lname,    SQLSRV_PARAM_IN],
            [$ini,      SQLSRV_PARAM_IN],
            [$lvl,      SQLSRV_PARAM_IN],
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            echo "❌ Error: " . $errors[0]['message'];
        } else {
            echo "✅ User created successfully!";
            sqlsrv_free_stmt($stmt);
        }
    }

    sqlsrv_close($conn);
}
?>