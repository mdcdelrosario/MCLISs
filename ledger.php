<?php include 'conn.php';?>
<?php include 'js/fphp.php';?>
<button class="w3-button w3-white w3-border" style="float: right;" onclick="document.getElementById('id01').style.display='none'">
    <i class="fa fa-close" style="font-size:16px;"></i>
</button>
<br>
<?php
    $p_account = "";
    $p_name =  "";
    $p_address =  "";
    $p_type =  "";
    $p_status =  "";
    $p_yearmo =  "";
    $p_prsrdng =  "";
    $p_kwhu =  "";
    $p_demand =  "";
    $p_bill =  "";
    $p_paid =  "";


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["t_accountno"])){
            $p_account = $conn->real_escape_string($_POST['t_accountno']);
            $p_name = $conn->real_escape_string($_POST['t_name']);
            $p_address = $conn->real_escape_string($_POST['t_address']);
        }
    }

    echo "<form id='frmcInfoData'>";

    if($p_account != ''){
        $sql = "SELECT * FROM tbl_history WHERE accountno = '" . $p_account . "' GROUP BY yearmo ORDER BY yearmo";
        $result = $conn->query($sql);
        $cntr = 0;

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $p_account = $row["accountno"];
                $p_name = $row["name"];
                $p_address = $row["address"];
                $p_type =  $row["ctype"];
                $p_status =  $row["cstatus"];
                $p_yearmo =  $row["yearmo"];
                $p_prsrdng =  $row["prsrdng"];
                $p_kwhu =  $row["kwhu"];
                $p_demand =  $row["demand"];
                $p_bill =  $row["bill"];
                $p_paid =  $row["paid"];

                if($cntr==0){
                    $p_account = str_pad($p_account,10," ");
                    $facct = substr($p_account,0,2) . "-" . substr($p_account,2,4) . "-" . substr($p_account,6,4);
                    echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;'>";                
                    echo    "<tr><td> ACCOUNT_NO </td><td><input style='width: 100%;' type='text' id='t_accountno' name='t_accountno' value='" .  $facct . "'></td></tr>";
                    echo    "<tr><td> NAME </td><td><input style='width: 100%;' type='text' id='t_name' name='t_name' value='" . $p_name . "'></td></tr>";
                    echo    "<tr><td> ADDRESS </td><td><input style='width: 100%;' type='text' id='t_address' name='t_address' value='" . $p_address . "'></td></tr>";
                    echo    "<tr><td> Type-Status </td><td><input style='width: 100%;' type='text' id='t_phone' name='t_phone' value='" . $p_type . " - " . $p_status . "'></td></tr>";
                    echo "</table>";
                    echo  "<br>";                
                    echo "<table class='w3-table-all tbpad2' style='padding: 1px 1px;' border='1px'>";   
                    echo "<tr><td> YEAR-MO </td><td> READING </td><td> KWHU </td><td>DEMAND</td><td> BILL </td><td> PAID </td></tr>      ";      
                }

                echo      "<tr><td><input style= 'width: 100%;' type='text' id='t_lat' name='t_lat' value='" . $p_yearmo . "'></td>";
                echo      "<td><input style= 'width: 100%;' type='text' id='t_lng' name='t_lng' value='" . $p_prsrdng . "'></td>";
                echo      "<td><input style= 'width: 100%;' type='text' id='t_lng' name='t_lng' value='" . $p_kwhu . "'></td>";
                echo      "<td><input style= 'width: 100%;' type='text' id='t_lng' name='t_lng' value='" . $p_demand . "'></td>";
                echo      "<td><input style= 'width: 100%;' type='text' id='t_lng' name='t_lng' value='" . $p_bill . "'></td>";
                echo      "<td><input style= 'width: 100%;' type='text' id='t_lng' name='t_lng' value='" . $p_paid . "'></td></tr>";

                $cntr++;                
            }
        echo "</table>";
        echo  "<br>";
    }
    }

    ?>

    <?php

    echo "</form>";

?>
