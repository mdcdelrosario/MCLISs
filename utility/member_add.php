<?php
include '../js/fphp.php';
include '../conn.php';

    if($_SESSION['utilities_personnel'] != 1 ) {
            echo "❌ Invalid User Rights!";
    } 
    else {
        $ralarm = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = strtoupper(trim($_POST['tname'] ?? ''));
            $crew = $_POST['tcrew'] ?? '';

            if ($name === '' ) {
                $ralarm = "❌ Please fill out Name";
            } else {
                $sql = "INSERT INTO tbl_person (name, crew) VALUES (?, ?)";
                $params = [$name, $crew];
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt === false) {
                    $ralarm = "❌ Error Adding Record: " . print_r(sqlsrv_errors(), true);
                } else {
                    $ralarm = "✅ Record Added Successfully";
                }
            }
        } else {
            $ralarm = "❌ Error: No POST data";
        }

        echo $ralarm;
        sqlsrv_close($conn);
    }
?>
