<?php include 'conn.php';?>
<?php include 'conns.php';?>
<?php include 'js/fphp.php';?>

<?php
    $p_account = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vaccount'])) {
        $p_account = htmlspecialchars($_POST['vaccount'], ENT_QUOTES, 'UTF-8');
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['vaccount'])) {
        $p_account = htmlspecialchars($_GET['vaccount'], ENT_QUOTES, 'UTF-8');
    }
    
    if(strlen($p_account)>=4){

        $sql = "SELECT * FROM tbl_cust WHERE accountno = ?";
        $params = [$p_account];
        $stmt = sqlsrv_query($conn, $sql, $params);

 
        $count = 0;
        
        if ($stmt !== false && sqlsrv_has_rows($stmt) && 1==0) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

                
                $count++;
                $range = "";
                if( $row["AVECONS"] > 20){
                    if( (float)$row["KWHU"] > ((float)$row["AVECONS"]*1.5)){
                        $range = "<font color='red'>HIGH : " . (int)(((float)$row["KWHU"]/(float)$row["AVECONS"])*100)."% > </font>"; 
                    }
                    if( (float)$row["KWHU"] < ((float)$row["AVECONS"] *.5)){
                        $range = "<font color='red'>LOW : " . (int)(((float)$row["KWHU"]/(float)$row["AVECONS"])*100)."% > </font>"; 
                    }
                }

                $facct = substr($row["ACCOUNTNO"],0,2) . "-" . substr($row["ACCOUNTNO"],2,4) . "-" . substr($row["ACCOUNTNO"],6,4);
                echo "<form id='frmcInfoData'>";
                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";                
                echo    "<input hidden type='text' id='t_accountno' name='t_accountno' value='" . $row["ACCOUNTNO"] . "'>";
                echo    "<input hidden type='text' id='t_name' name='t_name' value='" . $row["NAME"] . "'>";
                echo    "<input hidden type='text' id='t_address' name='t_address' value='" . $row["ADDRESS"] . "'>";
              
                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";              
                echo     "<tr><td> BILL_MONTH </td><td>" . $row["MO"] . "</td></tr>";
                echo     "<tr><td> BILL_YEAR </td><td>" . $row["YEAR"] . "</td></tr>";
                echo     "<tr><td> KWHRU </td><td style='text-align:right'>" .$range. $row["KWHU"] . "</td></tr>";
                echo     "<tr><td> DEMAND </td><td style='text-align:right'>" . $row["DEMAND"] . "</td></tr>";
                echo     "<tr><td> BILL </td><td style='text-align:right'>" . number_format($row["BILL"], 2, '.', ',') . "</td></tr>";
                echo     "<tr><td> FIELD FINDINGS </td><td><font color='red'>" . $row["FFDESC"] . "</font></td></tr>";
                echo "</table>";                
  
                echo  "<br>";

                echo "</form>";


            }
        }

        if(1==1){ //Skip Condition
            $sql = "SELECT TOP 24
                        Ldgr_Invoice_No AS Invoice_No,
                        Ldgr_Year_Month AS Yr_Mo,
                        Ldgr_Bill_Date AS Bill_Date,
                        Ldgr_Pres_Rdng AS prsRdng,
                        Ldgr_KWH_Used AS kWHr,
                        Ldgr_Doc_Amount AS Amount,
                        Ldgr_Paid AS Status
                    FROM dbo.EBS_Consumer_Ldgr
                    WHERE Ldgr_Account_No LIKE ?
                    ORDER BY Ldgr_Year_Month DESC";
            $params = ['%' . substr($p_account, 2)];
            
            $stmt = sqlsrv_query($conns, $sql, $params);

            if ($stmt !== false && sqlsrv_has_rows($stmt)) {
                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;' border='1px'>";
                echo "<tr style='font-weight: bold; text-align: center;'>";
                echo "<td>YEAR-MO</td>";
                echo "<td>READING</td>";
                echo "<td>KWHU</td>";
                echo "<td>BILL</td>";
                echo "<td>P</td>";
                echo "</tr>";

                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $yearmo = $row["Yr_Mo"];
                    $reading = $row["prsRdng"];
                    $kwhu = $row["kWHr"];
                    $bill = number_format($row["Amount"], 2);
                    $paid = isset($row["Status"]) && $row["Status"] == "Y" ? "Y" : "N";

                    echo "<tr>";
                    echo "<td>$yearmo</td>";
                    echo "<td style='text-align: right;'>$reading</td>";
                    echo "<td style='text-align: right;'>$kwhu</td>";
                    echo "<td style='text-align: right;'>$bill</td>";
                    echo "<td>$paid</td>";
                    echo "</tr>";
                }

                echo "</table><br>";
            }


        //     $accnt_no = $p_account;
        //     $area = "01";
        //    // $url = "http://210.4.125.186:8081/api/getledger2?accnt_no=$accnt_no&area=$area";
            

        //     // Fetch JSON from API
        //     $json = file_get_contents($url);
        //     $data = json_decode($json, true);
            
        //     // Display basic info
        //     if (!empty($data)) {
        //         $formatted_account = substr($accnt_no, 0, 2) . "-" . substr($accnt_no, 2, 4) . "-" . substr($accnt_no, 6, 4);
            
        //         // echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";
        //         // echo "<tr><td> ACCOUNT_NO </td><td><input style='width: 100%;' type='text' value='" . $formatted_account . "'></td></tr>";
        //         // echo "<tr><td> NAME </td><td><input style='width: 100%;' type='text' value=''></td></tr>";
        //         // echo "<tr><td> ADDRESS </td><td><input style='width: 100%;' type='text' value=''></td></tr>";
        //         // echo "<tr><td> Type-Status </td><td><input style='width: 100%;' type='text' value=''></td></tr>";
        //         // echo "</table><br>";
            
        //         // Ledger table
        //         echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;' border='1px'>";
        //         //echo "<tr><td> YEAR-MO </td><td> READING </td><td> KWHU </td><td> DEMAND </td><td> BILL </td><td> PAID </td></tr>";
        //         echo "<tr style='font-weight: bold; text-align: center;'>";
        //         echo "<td>YEAR-MO</td>";
        //         echo "<td>READING</td>";
        //         echo "<td>KWHU</td>";
        //         echo "<td>BILL</td>";
        //         echo "<td>P</td>";
        //         echo "</tr>";
                
            
        //         foreach ($data as $row) {
        //             $yearmo = $row["Yr_Mo"];
        //             $reading = $row["prsRdng"]; // no separate reading in your JSON, using kWHr
        //             $kwhu = $row["kWHr"];
        //             $demand = ""; // demand not in JSON
        //             $amount = is_numeric($row["Amount"]) ? (float)$row["Amount"] : 0;
        //             $bill = number_format($amount, 2);
        //             $paid = ($row["Status"] === "Y") ? "Y" : "N";
            
        //             echo "<tr>";
        //             echo "<td>$yearmo</td>";
        //             echo "<td style='text-align: right;'>$reading</td>";
        //             echo "<td style='text-align: right;'>$kwhu</td>";
        //             // echo "<td style='text-align: right;'>$demand</td>"; // optional
        //             echo "<td style='text-align: right;'>$bill</td>";
        //             echo "<td>$paid</td>";
        //             echo "</tr>";
                    
                    
        //         }
            
                // echo "</table><br>";
            // } else {
            //     echo "<p>No records found or failed to connect to API.</p>";
            // }
        }
        
    }
?>
