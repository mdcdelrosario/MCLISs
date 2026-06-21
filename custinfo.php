<?php include 'conn.php';?>
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
        
        if ($stmt !== false && sqlsrv_has_rows($stmt)) {
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

                $facct = substr($row["ACCOUNTNO"], 0, 2) . "-" . substr($row["ACCOUNTNO"], 2, 2) . "-" . substr($row["ACCOUNTNO"], 4, 4) . "-" . substr($row["ACCOUNTNO"], 8, 4);
                echo "<form id='frmcInfoData'>";
                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";                
                echo    "<input hidden type='text' id='t_accountno' name='t_accountno' value='" . trim($row["ACCOUNTNO"]) . "'>";
                echo    "<input hidden type='text' id='t_name' name='t_name' value='" . trim($row["NAME"]) . "'>";
                echo    "<input hidden type='text' id='t_address' name='t_address' value='" . trim($row["ADDRESS"]) . "'>";
                echo    "<input hidden type='text' id='t_lat' name='t_lat' value='" . $row["GPS_LAT"] . "'>";
                echo    "<input hidden type='text' id='t_lng' name='t_lng' value='" . $row["GPS_LONG"] . "'>";           
                echo    "<tr><td> ACCOUNT NO </td><td>" .  $facct . "</td></tr>";
                echo    "<tr><td> NAME </td><td>" . $row["NAME"] . "</td></tr>";
                echo    "<tr><td> ADDRESS </td><td>" . $row["ADDRESS"] . "</td></tr>" ;
                echo    "<tr><td> CONTACT # </td><td>" . "" . "</td></tr>" ;
                echo "</table>";
                echo  "<br>";

                echo "</form>";


            }
        }
    }
?>
