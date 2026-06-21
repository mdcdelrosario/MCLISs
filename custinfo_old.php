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
                ?>
                    <button class="w3-button w3-white w3-border" style="float: right;" onclick="document.getElementById('id01').style.display='none'">
                        <i class="fa fa-close" style="font-size:16px;"></i>
                    </button>
                    <button class="w3-button w3-white w3-border" style="float: right;" onclick="PageLink('comnreq.php','dvcustinfo','frmcInfoData','&trn=');">
                        <i class="fa fa-phone" style="font-size:16px;"></i>
                    </button> 
                    <button class="w3-button w3-white w3-border" style="float: right;" onclick="PageLink('ledger.php','dvcustinfo','frmcInfoData','&trn=');">
                        <i class="fa fa-bars" style="font-size:16px;"></i>
                    </button> 
                    <br>
                <?php
                
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
                echo    "<tr><td> ACCOUNT_NO </td><td>" .  $facct . "</td></tr>";
                echo    "<tr><td> NAME </td><td>" . $row["NAME"] . "</td></tr>";
                echo    "<tr><td> ADDRESS </td><td>" . $row["ADDRESS"] . "</td></tr>" ;
                $mhiden="readonly";
                if($_SESSION['user_level'] == "2" or $_SESSION['user_level'] == "4" ){$mhiden="";}
                echo    "<tr><td> METER_NO </td><td>
                            <input $mhiden style= 'width: 100%;' type='text' id='t_meter' name='t_meter' value='" . $row["METERNO"] . "'>
                        </td></tr>";
                echo    "<tr><td> METER_BRAND </td><td>
                        <input $mhiden style= 'width: 100%;' type='text' id='t_m_brand' name='t_m_brand' value='" . $row["M_BRAND"] . "'>
                        </td></tr>";
                echo    "<tr><td> METER_TYPE </td><td>
                        <input $mhiden style= 'width: 100%;' type='text' id='t_m_type' name='t_m_type' value='" . $row["M_TYPE"] . "'>
                        </td></tr>";
                echo    "<tr><td> METER_CLASS </td><td>
                        <input $mhiden style= 'width: 100%;' type='text' id='t_m_class' name='t_m_class' value='" . $row["M_CLASS"] . "'>
                        </td></tr>";
                echo    "<tr><td> M. ERC SEAL </td><td>
                        <input $mhiden style= 'width: 100%;' type='text' id='t_m_ercseal' name='t_m_ercseal' value='" . $row["M_ERCSEAL"] . "'>
                        </td></tr>";
                echo    "<tr><td> M. CALIB. SEAL </td><td>
                        <input $mhiden style= 'width: 100%;' type='text' id='t_m_calibseal' name='t_m_calibseal' value='" . $row["M_CALIBSEAL"] . "'>
                        </td></tr>";
                echo    "<tr><td> M. PT RATION </td><td>
                        <input $mhiden style= 'width: 100%;' type='text' id='t_m_ptratio' name='t_m_ptratio' value='" . $row["M_PTRATIO"] . "'>
                        </td></tr>";
                echo    "<tr><td> M. CT RATIO </td><td>
                        <input $mhiden style= 'width: 100%;' type='text' id='t_m_ctratio' name='t_m_ctratio' value='" . $row["M_CTRATIO"] . "'>
                        </td></tr>";
                if($_SESSION['user_level'] == "2" or $_SESSION['user_level'] == "4" ){
                    echo    "<tr><td> </td>   
                                <td>
                                    <button type='button' onclick=\"SaveData('meter_save.php','frmcInfoData');\" style='width: 100%'>Update MeterNo</button>
                                </td>
                            </tr>";
                }
                echo "<tr><td> CONN DATE </td><td>" . $row["CONNDATE"]->format('Y-m-d H:i:s') . "</td></tr>";
                echo    "<tr><td> BOOK_NO </td><td>" . $row["BOOKNO"] . "</td></tr>";
                echo    "<tr><td> TYPE </td><td>" . $row["TYPE"] . "</td></tr>";
                echo    "<tr><td> STATUS </td><td>" . $row["STATUS"] . "</td></tr>";
                echo    "<tr><td> AVE CONS </td><td>" . $row["AVECONS"] . "</td></tr>";
                echo "</table>";
                echo  "<br>";
                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";              
                echo     "<tr><td> BILL_MONTH </td><td>" . $row["MO"] . "</td></tr>";
                echo     "<tr><td> BILL_YEAR </td><td>" . $row["YEAR"] . "</td></tr>";
                echo     "<tr><td> KWHRU </td><td style='text-align:right'>" .$range. $row["KWHU"] . "</td></tr>";
                echo     "<tr><td> DEMAND </td><td style='text-align:right'>" . $row["DEMAND"] . "</td></tr>";
                echo     "<tr><td> BILL </td><td style='text-align:right'>" . number_format($row["BILL"], 2, '.', ',') . "</td></tr>";
                echo     "<tr><td> FIELD FINDINGS </td><td><font color='red'>" . $row["FFDESC"] . "</font></td></tr>";
                echo "</table>";                
                echo  "<br>";
                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";               
                echo      "<tr><td> FEEDER </td><td>" . $row["FEEDER"] . "</td></tr>";
                echo      "<tr><td> TRANSFORMER </td><td>" . $row["XFORMER"] . "</td></tr>";
                echo      "<tr><td> POLE NO </td><td>" . $row["POLENO"] . "</td></tr>";
                echo "</table>";
                echo  "<br>";
                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";               
                echo      "<tr><td> LATITUDE </td><td><input style= 'width: 100%;' type='text' id='t_lat' name='t_lat' value='" . $row["GPS_LAT"] . "'></td></tr>";
                echo      "<tr><td> LONGITUDE </td><td><input style= 'width: 100%;' type='text' id='t_lng' name='t_lng' value='" . $row["GPS_LONG"] . "'></td></tr>";           
                echo      "<tr><td> </td>  "; 
                echo "    <td> ";
                if($_SESSION['user_level'] == "2" or $_SESSION['user_level'] == "3" or $_SESSION['user_level'] == "4" ){
                    echo "<button type='button' onclick=\"ChangeLoc();\" style='width: 100%'>Choose Location</button><br>";
                    echo "    <button type='button' onclick=\"SaveData('location_save.php','frmcInfoData');\" style='width: 100%'>Save Location</button>";
                }
                echo "<a target=\"_blank\" jstcache=\"6\" href=\"https://www.google.com/maps/search/?api=1&query=".
                           $row["GPS_LAT"] . ", " . $row["GPS_LONG"] . "\"><span> View on Google Maps </span> </a>
                </td>";
                echo "</tr>";
                echo "</table>";
                echo  "<br>";

                echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";               
                echo      "<tr><td> TAG </td><td><input style= 'width: 100%;' type='text' id='t_btag' name='t_btag' value='" . $row["BTAG"] . "'></td></tr>";
                echo      "<tr><td> </td>  "; 
                echo "    <td> ";
                if($_SESSION['user_level'] == "5" or $_SESSION['user_level'] == "2" or $_SESSION['user_level'] == "3" or $_SESSION['user_level'] == "4" ){
                    echo "    <button type='button' onclick=\"SaveData('btag_save.php','frmcInfoData');\" style='width: 100%'>Save Tag</button>";
                }
                echo "</td>";
                echo "</tr>";
                echo "</table>";
                echo  "<br>";

                echo "</form>";


            }
        }
    }
?>
