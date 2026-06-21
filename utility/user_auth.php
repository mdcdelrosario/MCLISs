<?php 
    //THIS FORM IS TO AUTHENTICATE ALL
    include '../js/fphp.php';
    include '../conn.php';

    $p_id = '';
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $p_id = $_POST['dat'];
    }
?>

<script>
    function clickAuthOK(){
        var dat = '&dat=<?php echo $p_id; ?>&passAuth=' + $('#txtAuthPass').val() +
            '&fname="' + '<?php echo $_POST['fname']; ?>'  + '"' +
            '&lname="' + '<?php echo $_POST['lname']; ?>'  + '"' +
            '&ini="' + '<?php echo $_POST['ini']; ?>'  + '"' +
            '&uname="' + '<?php echo $_POST['uname']; ?>'  + '"' +
            '&urestrict="' + '<?php echo $_POST['urestrict']; ?>'  + '"' +
            '&pass="' + '<?php echo $_POST['pass']; ?>'  + '"';
        PageLink('utility/user_auth_conf.php', 'divcheck', 'fauth', dat);
    }
</script>

<div style="width: 400px; height: fit-content;" class="card">
    <form id="fauth" method="post" onsubmit="event.preventDefault();">
        <div style="padding: 16px">
            <h3 style="margin: 2px; width: 100%; text-align: center;">Authentication required <span class="fa fa-unlock-alt"></span></h3>
            <h5 style="margin: 2px; margin-top: 20px; width: 100%; text-align: center;">Please enter this user's password to apply changes</h5>
            <label for="psw"><b>Password</b></label>
            <input class="input-style1" id="txtAuthPass" type="password" placeholder="Enter Password" name="passAuth" required select>
            <button class="btn-wide-green" onclick="clickAuthOK();" type="submit">Authenticate</button>
            <button class="btn-wide-red" onclick="$('#divAuth').hide(); $('#divAuth').empty();" type="button">Cancel</button>
        </div>
    </form>
</div>
<div id="divcheck">
</div>