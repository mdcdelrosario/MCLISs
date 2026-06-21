<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php
    if($_SESSION['utilities_vehicle'] != 1 ) {
               echo "❌ Invalid User Rights!";
    } 
    else {
        $ralarm = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $crew = $_POST['t_crew'];
            $plateno = $_POST['t_plateno'];

            $sql = "INSERT INTO tbl_crew (crew, plateno) VALUES (?, ?)";
            $params = array($crew, $plateno);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $ralarm = "Error Adding record: " . print_r(sqlsrv_errors(), true);
            } else {
                $ralarm = "Record Added Successfully";
            }
        } else {
            $ralarm = "Error: No POST data";
        }

        echo $ralarm;
        sqlsrv_close($conn);
    }
?>
