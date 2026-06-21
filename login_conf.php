<?php include 'conn.php';?>
<?php 
    include 'js/fphp.php';
    
    if (isset($_POST["uname"]) && isset($_POST["upass"])) {
        $user = $_POST["uname"];
        $pass = $_POST["upass"];

        $logged = false;

        $err = $user;

        // Prepare and execute the SQL query
        $sql = "SELECT * FROM tbl_users WHERE [user] = ?";
        $params = array(&$user);
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if (!$stmt) {   
            die(print_r(sqlsrv_errors(), true));
        }
        
        if (sqlsrv_execute($stmt) === false) {
            die(print_r(sqlsrv_errors(), true));

        }

        //$err = $sql;
        // Loop through the result set
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            //$errmsspas = safeDecrypt($row['pass']);
            $errmsspas = "";
            $decrypted = safeDecrypt($row['pass']);
            $temp = explode('^', $decrypted);
            $dbpass = $temp[0];
            $dbacctlvl = explode('=', $temp[1])[1];
            // $dbpass == $pass ||
            if ( $dbpass == $pass) {//$user == "admin"
                if (1==1) {//$dbacctlvl == $row['acctlvl']
                    unset($_SESSION['ERR_LOGIN_PASS']);
                    date_default_timezone_set("Asia/Manila");
                    $dateTime = date("Y-m-d H:i:s");

                    // Update log date
                    $sql_update = "UPDATE tbl_users SET logdate = ? WHERE id = ?";
                    $params_update = array(&$dateTime, &$row['id']);
                    $stmt_update = sqlsrv_prepare($conn, $sql_update, $params_update);

                    if ($stmt_update && sqlsrv_execute($stmt_update)) {
                                $_SESSION['cred_user']    = $row['user'];                        
                        $_SESSION['user_level']   = $row['acctlvl'];                        
                        $_SESSION['user_restrict']= $row['restrict'];
                        $_SESSION['user_fullname']= trim($row['fname'] . ' ' . $row['lname']); // ADD THIS
                        $_SESSION["ERR_LOGIN_PASS"] = "Account is Good " . $_SESSION['cred_user'];
                        $logged = true;    

                        // Fetch user rights from tbl_userrights based on acctlvl
                        $sql_rights = "SELECT * FROM tbl_userrights WHERE level = ?";
                        $stmt_rights = sqlsrv_prepare($conn, $sql_rights, array(&$row['acctlvl']));

                        if ($stmt_rights && sqlsrv_execute($stmt_rights)) {
                            $rights = sqlsrv_fetch_array($stmt_rights, SQLSRV_FETCH_ASSOC);
                            if ($rights) {
                                $_SESSION['search_customer'] = $rights['search_customer'];
                                $_SESSION['search_elements'] = $rights['search_elements'];
                                $_SESSION['is_crew'] = $rights['is_crew'];
                                $_SESSION['main_area'] = $rights['main_area'];
                                $_SESSION['central_area'] = $rights['central_area'];
                                $_SESSION['northern_area'] = $rights['northern_area'];
                                $_SESSION['eastern_area'] = $rights['eastern_area'];
                                $_SESSION['southeastern_area'] = $rights['southeastern_area'];
                                $_SESSION['southern_area'] = $rights['southern_area'];
                                $_SESSION['edit_locations'] = $rights['edit_locations'];
                                $_SESSION['edit_metering_data'] = $rights['edit_metering_data'];
                                $_SESSION['edit_complaints_request'] = $rights['edit_complaints_request'];
                                $_SESSION['utilities_user'] = $rights['utilities_user'];
                                $_SESSION['utilities_vehicle'] = $rights['utilities_vehicle'];
                                $_SESSION['utilities_personnel'] = $rights['utilities_personnel'];
                                $_SESSION['utilities_complaints_request'] = $rights['utilities_complaints_request'];
                                $_SESSION['download_master_data'] = $rights['download_master_data'];
                                $_SESSION['download_complaints_request_data'] = $rights['download_complaints_request_data'];
                            }
                            // After fetching rights, also get crew assignment from tbl_person
                            if ($rights['is_crew'] == 1) {
                                $fullname = trim($row['fname'] . ' ' . $row['lname']);
                                $sql_crew = "SELECT crew FROM tbl_person WHERE name LIKE ?";
                                $like_name = '%' . $fullname . '%';
                                $stmt_crew = sqlsrv_prepare($conn, $sql_crew, [&$like_name]);
                                if ($stmt_crew && sqlsrv_execute($stmt_crew)) {
                                    $crew_row = sqlsrv_fetch_array($stmt_crew, SQLSRV_FETCH_ASSOC);
                                    $_SESSION['user_crew'] = $crew_row['crew'] ?? '';
                                }
                            }
                        }


                        break;
                    } else {
                        die(print_r(sqlsrv_errors(), true));
                    }
                } else {
                    $_SESSION["ERR_LOGIN_PASS"] = "Account invalid! Please try again!.." . $errmsspas;
                    $_SESSION['cred_user'] = "";   
                    header("Location: login.php");
                    return;
                }
            } else {
                $_SESSION["ERR_LOGIN_PASS"] = "Account invalid! Please try again!." . $errmsspas;
                $_SESSION['cred_user'] = "";   
                header("Location: login.php");
                return;
            }
        }

        // If no results were found
        if (!$logged) {
            $_SESSION["ERR_LOGIN_PASS"] = "Account invalid! Please try again! " . $err;
            $_SESSION['cred_user'] = "";   
            header("Location: login.php");
            return;
        }
        header("Location: index.php");
    }
?>
