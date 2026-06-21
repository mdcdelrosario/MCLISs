<?php 
include 'conn.php';

if ($_SESSION['edit_complaints_request'] == 1) {
    include 'js/fphp.php';


        $crew       = $_POST["t_crew"];
        $member1    = $_POST["t_person1"];
        $member2    = $_POST["t_person2"];
        $member3    = $_POST["t_person3"];
        $member4    = $_POST["t_person4"];
        $member5    = $_POST["t_person5"];
        
    if (empty(trim($crew))) {
    echo "Please select a crew before saving members.";
    exit;
}

    if (!empty($crew)) {

        $usql = "UPDATE tbl_person SET crew = '' WHERE crew = ?";
        $params = array($crew);
        $stmt = sqlsrv_query($conn, $usql, $params);

        $usql = "UPDATE tbl_person SET crew = ? WHERE [name] = ?";
        if (!empty($member1)) {
            $params = array($crew, $member1);
            $stmt = sqlsrv_query($conn, $usql, $params);
        }
        if (!empty($member2)) {
            $params = array($crew, $member2);
            $stmt = sqlsrv_query($conn, $usql, $params);
        }
        if (!empty($member3)) {
            $params = array($crew, $member3);
            $stmt = sqlsrv_query($conn, $usql, $params);
        }
        if (!empty($member4)) {
            $params = array($crew, $member4);
            $stmt = sqlsrv_query($conn, $usql, $params);
        }
        if (!empty($member5)) {
            $params = array($crew, $member5);
            $stmt = sqlsrv_query($conn, $usql, $params);
        }
        if ($stmt) {
            $message = "Crew Updated Successfully";
        } else {
            $message = "Error: " . print_r(sqlsrv_errors(), true); // Debugging
        }

        sqlsrv_close($conn);
    }

    echo $message;
}
else {
    echo "You do not have permission to Save CNR.";
}
?>
