<?php
include '../conn.php';
include '../js/fphp.php';

if ($_SESSION['utilities_personnel'] != 1) {
    echo "❌ Invalid User Rights!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method.";
    exit;
}

$errors  = 0;
$updated = 0;

// Loop through all submitted fields (treq1, trn1, treq2, trn2, ...)
$i = 1;
while (isset($_POST['trn' . $i])) {
    $trn     = intval($_POST['trn' . $i]);
    $request = trim($_POST['treq' . $i] ?? '');

    if ($trn === 0 || $request === '') { 
        $i++; 
        continue; 
    }

    $params = [$request, $trn];
    $stmt = sqlsrv_query($conn,
        "UPDATE tbl_request SET request = ? WHERE trn = ?",
        $params
    );

    if ($stmt === false) {
        $errors++;
    } else {
        $updated++;
        sqlsrv_free_stmt($stmt);
    }

    $i++;
}

sqlsrv_close($conn);

if ($errors > 0) {
    echo "❌ Done with errors. Updated: $updated, Failed: $errors";
} else {
    echo "OK";
}
?>