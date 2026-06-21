<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php
    if($_SESSION['utilities_vehicle'] != 1 ) {
               echo "❌ Invalid User Rights!";
    } 
    else {

        $ralarm = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_POST['trn'])) {
                $p_trn = $_POST['trn'];

                $sql = "DELETE FROM tbl_crew WHERE trn = ?";
                $params = array($p_trn);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt === false) {
                    $ralarm = "Error Deleting Record: " . $p_trn . " >> " . print_r(sqlsrv_errors(), true);
                } else {
                    $ralarm = "Record Deleted Successfully";
                }
            } else {
                $ralarm = "Error: TRN not specified.";
            }
        } else {
            $ralarm = "Error: No POST data";
        }

        echo $ralarm;
        sqlsrv_close($conn);
    }
?>
