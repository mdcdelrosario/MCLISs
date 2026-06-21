<?php
include '../js/fphp.php';
include '../conn.php';

if ($_SESSION['utilities_personnel'] != 1) {
    echo "❌ Invalid User Rights!";
    exit;
}else {
    // Process updates

$trns  = $_POST['trn']  ?? [];
$names = $_POST['name'] ?? [];
$crews = $_POST['crew'] ?? [];

if (empty($trns)) {
    echo "❌ No records to update.";
    exit;
}

$updated = 0;
$errors  = 0;

foreach ($trns as $i => $trn) {
    $trn  = intval($trn);
    $name = strtoupper(trim($names[$i] ?? ''));
    $crew = trim($crews[$i] ?? '');

    if ($trn === 0 || $name === '') continue;

    $stmt = sqlsrv_query($conn,
        "UPDATE tbl_person SET name = ?, crew = ? WHERE trn = ?",
        [$name, $crew, $trn]
    );

    if ($stmt === false) {
        $errors++;
    } else {
        $updated++;
    }
}

if ($errors > 0) {
    echo "❌ Done with errors. Updated: $updated, Failed: $errors";
} else {
    echo "OK";
}
 
sqlsrv_close($conn);
}

?>