<?php 
include 'conn.php';

if ($_SESSION['edit_metering_data'] == 1) {
    include 'js/fphp.php';

    $message = "Saving Error. Please Check Data.";

    if (isset($_POST["t_accountno"])) {
        // Collect form values
        $accountno  = $_POST["t_accountno"];
        $meter      = $_POST["t_meter"];
        $brand      = $_POST["t_m_brand"];
        $type       = $_POST["t_m_type"];
        $class      = $_POST["t_m_class"];
        $ercseal    = $_POST["t_m_ercseal"];
        $calibseal  = $_POST["t_m_calibseal"];
        $ptratio    = $_POST["t_m_ptratio"];
        $ctratio    = $_POST["t_m_ctratio"];

        // MSSQL Parameterized Query
        $usql = "UPDATE tbl_cust 
                 SET METERNO = ?,  
                     M_BRAND = ?,  
                     M_TYPE = ?,  
                     M_CLASS = ?,             
                     M_ERCSEAL = ?,            
                     M_CALIBSEAL = ?,            
                     M_PTRATIO = ?,            
                     M_CTRATIO = ?             
                 WHERE ACCOUNTNO = ?";

        $params = array($meter, $brand, $type, $class, $ercseal, $calibseal, $ptratio, $ctratio, $accountno);
        $stmt = sqlsrv_query($conn, $usql, $params);

        if ($stmt) {
            $message = "Meter Data Updated";
        } else {
            $message = "Error: " . print_r(sqlsrv_errors(), true); // Debugging
        }

        sqlsrv_close($conn);
    }

    echo $message;
}
else {
    echo "You do not have permission to edit metering data.";
}
?>
