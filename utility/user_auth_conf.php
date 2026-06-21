<?php

    //THIS IS CALLED IF AUTHENTICATION IS CORRECT!
    include '../js/fphp.php';
    include '../conn.php';

    if($_SESSION['utilities_user'] != 1 ) {
                ?>
                    <script>
                        showmsgbox('Error!', 'Invalid User Rights!');
                    </script>
                <?php
    } 
    else {
        $p_id = '';
        $p_inppass = '';
        $p_fname = '';
        $p_lname = '';
        $p_user = '';
        $p_ini = '';
        $p_pass = '';
        $dbacctlvl = '';
        $p_restrict = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['passAuth']))
                $p_inppass = $_POST['passAuth'];
            if (isset($_POST['fname']))
                $p_fname = substr($_POST['fname'], 2, -2);
            if (isset($_POST['lname']))
                $p_lname = substr($_POST['lname'], 2, -2);
            if (isset($_POST['user']))
                $p_user = substr($_POST['user'], 2, -2);
            if (isset($_POST['urestrict']))
                $p_restrict = substr($_POST['urestrict'], 2, -2);
            if (isset($_POST['ini']))
                $p_ini = substr($_POST['ini'], 2, -2);
            if (isset($_POST['pass']))
                $p_pass = substr($_POST['pass'], 2, -2);

            $p_id = $_POST['dat'];
            $acctlvl = '';
            $sql = "SELECT * FROM tbl_users WHERE id = '$p_id'";
            $result = $conn->query($sql);
            $match = false;
            
            while ($row = $result->fetch_assoc()) {
                $decrypted = safeDecrypt($row['pass']);
                $temp = explode('^', $decrypted);
                $dbpass = $temp[0];
                $dbacctlvl = explode('=', $temp[1])[1];
                if ($dbpass == $p_inppass) {
                    $match = true;
                    break;
                }
            }

            if (!$match) {
                ?>
                    <script>
                        showmsgbox('Error!', 'Authentication Failed! Password is invalid!');
                    </script>
                <?php
            }
            else {
                $sql = "UPDATE tbl_users SET ";
                if ($p_fname != '')
                    $sql .= "fname = '$p_fname', ";
                if ($p_lname != '')
                    $sql .= "lname = '$p_lname', ";
                if ($p_user != '')
                    $sql .= "user = '$p_user', ";
                if ($p_restrict != '')
                    $sql .= "`restrict` = '$p_restrict', ";
                if ($p_ini != '')
                    $sql .= "ini = '$p_ini', ";
                if ($p_pass != '' && strlen($p_pass) > 7){
                    $p_pass = safeEncrypt($p_pass . "^acctlvl=" . $dbacctlvl);
                    $sql .= "pass = '$p_pass', ";
                }
                $sql = substr($sql, 0, -2);
                $sql .= " WHERE id = '$p_id'";
                $result = $conn->query($sql);
                ?>
                    <script>
                        showmsgbox('Success!', 'Authentication Complete! Changes were saved!');
                        PageLink('utility/user_mgmt.php','main');
                        closeEdit();
                    </script>
                <?php
            }
        }
    }
?>