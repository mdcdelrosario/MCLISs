<?php
    if (isset($_POST['btnMsgBoxYes'])){
        session_start();
        session_destroy();
        header("Location: ./login.php");
    }
?>