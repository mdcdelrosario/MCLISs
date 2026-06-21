<?php
include '../js/fphp.php';
include '../conn.php';


    if($_SESSION['utilities_user'] != 1 ) {
        echo "❌ Invalid User Rights!";
    } 
    else {
        $p_id = "";
        $p_fname = "";
        $p_lname = "";
        $p_user = "";
        $p_pass = "";
        $p_ini = "";
        $p_acctlvl = "";
        $p_restrict = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['dat'])) {
            $p_id = $_POST['dat'];

            $sql = "SELECT * FROM tbl_users WHERE id = ?";
            $stmt = sqlsrv_query($conn, $sql, [$p_id]);

            if ($stmt !== false && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $p_fname = $row['fname'];
                $p_lname = $row['lname'];
                $p_user = $row['user'];
                $p_pass = $row['pass'];
                $p_ini = $row['ini'];
                $p_acctlvl = $row['acctlvl'];
                $p_restrict = $row['restrict'];
            }

            sqlsrv_free_stmt($stmt);
        }

        sqlsrv_close($conn);
    }
?>
