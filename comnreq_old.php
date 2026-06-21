<?php 
include 'conn.php';
include 'js/fphp.php';

$p_trn = $p_area = $p_account = $p_name = $p_address = $p_contactno = "";
$p_lat = $p_lng = $p_reqdt = $p_request = $p_req_desc = $p_crew = "";
$p_findings = $p_action = $p_aco_dt = $p_encoder = "";
$p_person1 = $p_person2 = $p_person3 = $p_person4 = $p_person5 = $p_restrict = "";

// Handle POST request securely
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["t_accountno"])) {
        $p_account = $_POST['t_accountno'];
        $p_name = $_POST['t_name'];
        $p_address = $_POST['t_address'];
        $p_lat = $_POST['t_lat'];
        $p_lng = $_POST['t_lng'];
    }
    $p_trn = $_POST['trn'];
}

// Fetch data securely using parameterized query
if ($p_trn != '') {
    $sql = "SELECT * FROM tbl_cnr WHERE trn = ?";
    $params = [$p_trn];
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true)); // Debugging output
    }
    
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $p_area = $row["area"];
        $p_account = $row["accountno"];
        $p_name = $row["name"];
        $p_address = $row["address"];
        $p_contactno = $row["contactno"];
        $p_lat = $row["gps_lat"];
        $p_lng = $row["gps_long"];
        $p_reqdt = ($row["req_dt"] instanceof DateTime) ? $row["req_dt"]->format('Y-m-d\TH:i') : "";
        $p_request = $row["request"];
        $p_req_desc = $row["req_desc"];
        $p_crew = $row["crew"];
        $p_findings = $row["findings"];
        $p_action = $row["action"];
        $p_aco_dt = ($row["aco_dt"] instanceof DateTime) ? $row["aco_dt"]->format('Y-m-d\TH:i') : "";
        $p_encoder = $row["encoder"];
        $p_person1 = $row["member1"];
        $p_person2 = $row["member2"];
        $p_person3 = $row["member3"];
        $p_person4 = $row["member4"];
        $p_person5 = $row["member5"];
        $p_restrict = $row["restrict"];
    }
}

// Format account number securely
$p_account = str_pad($p_account, 10, " ");
$facct = substr($p_account, 0, 2) . "-" . substr($p_account, 2, 4) . "-" . substr($p_account, 6, 4);

?>
<button class="w3-button w3-white w3-border" style="float: right;" onclick="document.getElementById('id01').style.display='none'">
    <i class="fa fa-close" style="font-size:16px;"></i>
</button>
<br>

<form id='frmcInfoData'>
    <input type='hidden' id='t_trn' name='t_trn' value='<?php echo htmlspecialchars($p_trn); ?>'>
    
    <table class='w3-table-all tbpad2'>
        <tr><td>ACCOUNT NO</td><td><input style='width: 100%;' type='text' id='t_accountno' name='t_accountno' value='<?php echo htmlspecialchars($facct); ?>'></td></tr>
        <tr><td>NAME</td><td><input style='width: 100%;' type='text' id='t_name' name='t_name' value='<?php echo htmlspecialchars($p_name); ?>'></td></tr>
        <tr><td>ADDRESS</td><td><input style='width: 100%;' type='text' id='t_address' name='t_address' value='<?php echo htmlspecialchars($p_address); ?>'></td></tr>
        <tr><td>CONTACT NO</td><td><input style='width: 100%;' type='text' id='t_phone' name='t_phone' value='<?php echo htmlspecialchars($p_contactno); ?>'></td></tr>
    </table>

    <br>

    <table class='w3-table-all tbpad2'>
        <tr><td>LATITUDE</td><td><input style='width: 100%;' type='text' id='t_lat' name='t_lat' value='<?php echo htmlspecialchars($p_lat); ?>'></td></tr>
        <tr><td>LONGITUDE</td><td><input style='width: 100%;' type='text' id='t_lng' name='t_lng' value='<?php echo htmlspecialchars($p_lng); ?>'></td></tr>
        <tr><td></td><td><button type='button' onclick="ChangeLoc();" style='width: 100%'>Update Location</button></td></tr>
    </table>

    <br>

    <table class='w3-table-all tbpad2'>
        <tr><td>REQUEST DATETIME</td><td><input style='width: 100%;' type='datetime-local' id='t_rdate' name='t_rdate' value='<?php echo htmlspecialchars($p_reqdt); ?>'></td></tr>
        <tr><td>COMPLAINT / REQUEST</td><td><?php echo select_table("tbl_request", "request", "request", "t_cnr", $p_request); ?></td></tr>
        <tr><td>DESCRIPTION</td><td><textarea rows="4" style='width: 100%;' id='t_cnr_desc' name='t_cnr_desc'><?php echo htmlspecialchars($p_req_desc); ?></textarea></td></tr>
        <tr><td>CREW</td><td><?php echo select_table("tbl_crew", "crew", "crew", "t_crew", $p_crew); ?></td></tr>
        <tr><td>MEMBERS</td><td>
            <?php 
                echo select_table("tbl_person", "name", "name", "t_person1", $p_person1);
                echo select_table("tbl_person", "name", "name", "t_person2", $p_person2);
            ?>
        </td></tr>
        <tr><td></td><td>
            <?php 
                echo select_table("tbl_person", "name", "name", "t_person3", $p_person3);
                echo select_table("tbl_person", "name", "name", "t_person4", $p_person4);
            ?>
        </td></tr>
        <tr><td></td><td><?php echo select_table("tbl_person", "name", "name", "t_person5", $p_person5); ?></td></tr>
        <tr><td>FINDINGS</td><td><textarea rows="4" style='width: 100%;' id='t_find' name='t_find'><?php echo htmlspecialchars($p_findings); ?></textarea></td></tr>
        <tr><td>ACTION TAKEN</td><td><textarea rows="4" style='width: 100%;' id='t_action' name='t_action'><?php echo htmlspecialchars($p_action); ?></textarea></td></tr>
        <tr><td>ACCOMPLISHED DATETIME</td><td><input style='width: 100%;' type='datetime-local' id='t_adate' name='t_adate' value='<?php echo htmlspecialchars($p_aco_dt); ?>'></td></tr>
    </table>

    <?php 
    if ($p_restrict == $_SESSION['user_restrict'] && in_array($_SESSION['user_level'], ["1", "4"])) {
        echo "<button type='button' onclick=\"SaveData('cnr_save.php', 'frmcInfoData');\" style='width: 100%'>Add/Update Request/Complain</button><hr>";
    }
    ?>
</form>
