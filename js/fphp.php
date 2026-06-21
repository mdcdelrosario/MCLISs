<?php

function select_table($stable, $sfield, $sdesc, $sname, $defval = "", $scond = "", $style = "") {
    include 'conn.php';

    $rval = "<select name=\"" . htmlspecialchars($sname) ."\" id=\"" . htmlspecialchars($sname) . "\" style=\"" . htmlspecialchars($style) . "\">";
    $rval .="<option value=\"\"></option>";

    // Ensure valid table and column names (DO NOT use placeholders for them)
    $sql = "SELECT DISTINCT $sfield AS ufield, $sdesc AS udesc FROM $stable";
    
    // Add condition if provided
    if (!empty($scond)) {
        $sql .= " WHERE 1=1 $scond";
    }

    // Execute query
    $stmt = sqlsrv_query($conn, $sql);
    
    // Check for errors
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true)); // Debugging output
    }

    // Fetch results and build the select dropdown
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $issel = ($row["ufield"] == $defval) ? "selected" : "";
        $rval .= "<option $issel value=\"" . htmlspecialchars($row["ufield"]) . "\">" . htmlspecialchars($row["udesc"]) . "&nbsp;</option>";
    }

    $rval .= "</select>";
    
    return $rval;
}

function select_table_attr($stable, $sfield, $sdesc, $sname, $defval = "", $scond = "", $style = "", $attributes = "") {
    include 'conn.php';

    $rval = "<select name=\"" . htmlspecialchars($sname) ."\" id=\"" . htmlspecialchars($sname) . "\" style=\"" . htmlspecialchars($style) . "\" $attributes>";
    $rval .="<option value=\"\"></option>";
    
    $sql = "SELECT DISTINCT $sfield AS ufield, $sdesc AS udesc FROM $stable";
    
    if (!empty($scond)) {
        $sql .= " WHERE 1=1 $scond";
    }

    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $issel = ($row["ufield"] == $defval) ? "selected" : "";
        $rval .= "<option $issel value=\"" . htmlspecialchars($row["ufield"]) . "\">" . htmlspecialchars($row["udesc"]) . "&nbsp;</option>";
    }

    $rval .= "</select>";
    
    return $rval;
}

function select_months($sname,$defval){
    $rval = "";
    $rval = $rval . "<select name=\"" . $sname ."\" id=\"" . $sname . "\">";
    for ($x = 1; $x <= 12; $x++) {
        $d=mktime(0, 0, 0, $x, 1, 2000);
        $issel ="";
        if($x == $defval){ $issel = "selected";}
        $rval = $rval . "<option ". $issel ." value=\"" . $x . "\">" . date("F", $d) . "&nbsp</option>";
    }
    $rval = $rval . "</select>";
    return $rval;
}

function select_year($sname,$defval){
    $rval = "";
    $rval = $rval . "<select name=\"" . $sname ."\" id=\"" . $sname . "\">";
    for ($x = date("Y"); $x >= 2000; $x--) {
        $issel ="";
        if($x == $defval){ $issel = "selected";}
        $rval = $rval . "<option ". $issel ." value=\"" . $x . "\">" . $x . "&nbsp</option>";
    }
    $rval = $rval . "</select>";    
    return $rval;
}

function StQuery($lktable,$lkfield,$lkcond = "1=1"){
    include 'conn.php';
    $rval = "";

    $sql = "SELECT `" . $lkfield . "` as rval FROM " . $lktable . " WHERE " . $lkcond ;

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rval = $row["rval"] ;
        }
    }

    $conn->close();
    return $rval;
}

function safeEncrypt($plaintext) {
    $method = "AES-256-CBC";
    $key = hash('sha256', "Dev@GrinItech2020", true);
    $iv = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
    $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);
    return base64_encode($iv . $hash . $ciphertext);
}

function safeDecrypt($ivHashCiphertext) {
    $ivHashCiphertext=base64_decode($ivHashCiphertext);
    $method = "AES-256-CBC";
    $iv = substr($ivHashCiphertext, 0, 16);
    $hash = substr($ivHashCiphertext, 16, 32);
    $ciphertext = substr($ivHashCiphertext, 48);
    $key = hash('sha256', "Dev@GrinItech2020", true);
   
    return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
}

function copyClip($text) {
    echo "<script> copyClip(\"" . $text ."\"); </script>";
}

?>