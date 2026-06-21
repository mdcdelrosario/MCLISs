<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php
    if($_SESSION['utilities_complaints_request'] != 1 ) {
                echo "❌ Invalid User Rights!";
    } 
    else {
        $ralarm = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $t_request = $_POST['t_request'] ?? '';

            if (!empty($t_request)) {
                // Proper MSSQL INSERT
                $sql = "INSERT INTO tbl_request (request) VALUES (?)";
                $params = [$t_request];

                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt) {
                    $ralarm = "Record Added Successfully";
                    sqlsrv_free_stmt($stmt);
                } else {
                    $ralarm = "Error Adding Record: " . print_r(sqlsrv_errors(), true);
                }
            } else {
                $ralarm = "Error: Request field is empty.";
            }
        } else {
            $ralarm = "Error: No POST data.";
        }

        echo $ralarm;
        sqlsrv_close($conn);
    
    }
?>
