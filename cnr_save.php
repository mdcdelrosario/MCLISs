<?php 
include 'conn.php';

if ($_SESSION['edit_complaints_request'] == 1) {
    include 'js/fphp.php';

    //$message = "Saving Error. Please Check Data.";
    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';
    // exit;
    //if (isset($_POST["t_name"])) {
        // Collect and sanitize form values
        $accountno  = str_replace("-", "", $_POST["t_accountno"]);
        $name       = $_POST["t_name"];
        $address    = $_POST["t_address"];
        $contactno  = $_POST["t_phone"];
        $gps_lat    = $_POST["t_latCNR"];
        $gps_long   = $_POST["t_lngCNR"];
        $req_dt     = date('Y-m-d H:i:s', strtotime($_POST["t_rdate"]));
        $request    = $_POST["t_cnr"];
        $req_desc   = $_POST["t_cnr_desc"];
        $crew       = $_POST["t_crew"];
        $area       = $_POST["t_area"] ?? '01';
        $encoder    = $_SESSION["cred_user"];
        $member1    = $_POST["t_person1"];
        $member2    = $_POST["t_person2"];
        $member3    = $_POST["t_person3"];
        $member4    = $_POST["t_person4"];
        $member5    = $_POST["t_person5"];
        $findings   = $_POST["t_find"];
        $action     = $_POST["t_action"];
        $restrict   = $_SESSION["user_restrict"];
        $t_accomplished = isset($_POST["t_accomplished"]) ? 1 : 0;
        $aco_dt = (!empty($_POST['t_adate']) && strtotime($_POST['t_adate']) !== false)
            ? date('Y-m-d H:i:s', strtotime($_POST['t_adate']))
            : '1800-01-01 00:00:00';
        $trn        = $_POST["t_trn"];  


        if ($t_accomplished == 0) {
            $aco_dt = '1800-01-01 00:00:00';
        }

        if (!empty($trn)) {
            // UPDATE query for existing record
            $usql = "UPDATE tbl_cnr 
                     SET accountno = ?, [name] = ?, [address] = ?, contactno = ?, gps_lat = ?, gps_long = ?, 
                         req_dt = CONVERT(DATETIME, ?, 120), request = ?, req_desc = ?, crew = ?, area = ?, encoder = ?, 
                         member1 = ?, member2 = ?, member3 = ?, member4 = ?, member5 = ?, 
                         findings = ?, [action] = ?, [restrict] = ?, aco_dt = CONVERT(DATETIME, ?, 120) 
                     WHERE trn = ?";
            $params = array($accountno, $name, $address, $contactno, $gps_lat, $gps_long, 
                            $req_dt, $request, $req_desc, $crew, $area, $encoder, 
                            $member1, $member2, $member3, $member4, $member5, 
                            $findings, $action, $restrict, $aco_dt, $trn);

        } else {

            $checkSql = "SELECT COUNT(*) AS cnt FROM tbl_cnr 
                         WHERE accountno = ? 
                           AND req_dt = CONVERT(DATETIME, ?, 120)
                           AND request = ?
                           AND crew = ?";
            $checkParams = array($accountno, $req_dt, $request, $crew);
            $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);
            $exists = false;
            if ($checkStmt && ($row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC))) {
                if ($row['cnt'] > 0) {
                    $exists = true;
                }
            }
            if ($exists) {
                $message = "Record already exists with the same Account No, Date/Time, Request, and Crew.";
                echo $message;
                sqlsrv_close($conn);
                exit;
            }

            // INSERT query for new record
            $usql = "INSERT INTO tbl_cnr 
                     (accountno, [name], [address], contactno, gps_lat, gps_long, req_dt, request, req_desc, 
                      crew, area, encoder, member1, member2, member3, member4, member5, findings, [action], 
                      [restrict], aco_dt) 
                     VALUES (?, ?, ?, ?, ?, ?, CONVERT(DATETIME, ?, 120), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CONVERT(DATETIME, ?, 120))";
            $params = array($accountno, $name, $address, $contactno, $gps_lat, $gps_long, 
                            $req_dt, $request, $req_desc, $crew, $area, $encoder, 
                            $member1, $member2, $member3, $member4, $member5, 
                            $findings, $action, $restrict, $aco_dt);

        }

        // Execute SQL Query
        $stmt = sqlsrv_query($conn, $usql, $params);

        if ($stmt) {
            $message = "Complaints/Request Added";
        } else {
            $message = "Error: " . print_r(sqlsrv_errors(), true); // Debugging
        }

        sqlsrv_close($conn);
    //}

    echo $message;
}
else {
    echo "You do not have permission to Save CNR.";
}
?>
