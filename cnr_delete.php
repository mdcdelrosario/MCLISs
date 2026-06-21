<?php 
include 'conn.php';

if ($_SESSION['edit_complaints_request'] == 1) {
    include 'js/fphp.php';

        $trn        = $_POST["t_trn"];
        
    
        if (!empty($trn)) {
            // DELETE query for existing record
            $usql = "DELETE FROM tbl_cnr WHERE trn = ?";
            $params = array($trn);
       
            // Execute SQL Query
            $stmt = sqlsrv_query($conn, $usql, $params);

            if ($stmt) {
                $message = "Complaints/Request Deleted";
            } else {
                $message = "Error: " . print_r(sqlsrv_errors(), true); // Debugging
            }
        }
        sqlsrv_close($conn);
    //}

    echo $message;
}
else {
    echo "You do not have permission to Delete CNR.";
}
?>
