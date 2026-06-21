<?php 
include 'conn.php';

if ($_SESSION['edit_locations'] == 1) {
    include 'js/fphp.php';

    $message = "Saving Error. Please Check Data.";

    if (isset($_POST["t_accountno"])) {
        $accountno = $_POST["t_accountno"];
        $lat = $_POST["t_latc"];
        $long = $_POST["t_lngc"];

        // MSSQL Parameterized Query
        $usql = "UPDATE tbl_cust SET GPS_LAT = ?, GPS_LONG = ? WHERE ACCOUNTNO = ?";
        $params = array($lat, $long, $accountno);
        
        $stmt = sqlsrv_query($conn, $usql, $params);

        if ($stmt) {
            $message = "Location Data Updated";
        } else {
            $message = "Error: " . print_r(sqlsrv_errors(), true); // Debugging only
        }

        sqlsrv_close($conn);
    }

    echo $message;
}
else {
    echo "You do not have permission to edit locations.";
}
?>
