<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php

    if($_SESSION['utilities_user'] != 1 ) {
        echo "❌ Invalid User Rights!";
    } 
    else {

        $ralarm = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $t_level = $_POST['t_level'];

            $sql = "INSERT INTO tbl_userrights (level) VALUES (?)";
            $params = array($t_level);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $ralarm = "Error Adding record: " . print_r(sqlsrv_errors(), true);
            } else {
                $ralarm = "Record Added Successfully";
            }
        } else {
            $ralarm = "Error: No POST data";
        }

        echo $ralarm;
        sqlsrv_close($conn);
    }
?>
