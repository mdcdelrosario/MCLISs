<?php 
include 'conn.php';

if ($_SESSION['edit_locations'] == 1) {
    include 'js/fphp.php';

    $message = "Saving Error. Please Check Data.";

    if (isset($_POST["t_accountno"])) {
        $accountno = $_POST["t_accountno"];
        $btag = $_POST["t_btag"];

        // MSSQL Parameterized Query
        $usql = "UPDATE tbl_cust SET [BTAG] = ? WHERE ACCOUNTNO = ?";
        $params = array($btag, $accountno);

        $stmt = sqlsrv_query($conn, $usql, $params);

        if ($stmt) {
            $message = "Tagging Data Updated";
        } else {
            $message = "Error: " . print_r(sqlsrv_errors(), true); // Debugging only
        }

        sqlsrv_close($conn);
    }

    echo $message;
}
else {
    echo "You do not have permission to edit tagging data.";
}
?>
