<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php
    if($_SESSION['utilities_vehicle'] != 1 ) {
            echo "❌ Invalid User Rights!";
    } 
    else {

        $ralarm = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['trn'])) {
            foreach ($_POST['trn'] as $index => $trn) {
                $crew = $_POST['crew'][$index];
                $plateno = $_POST['plateno'][$index];
                $tracker_id = $_POST['tracker_id'][$index];

                $fields = [
                    'main_area_office',
                    'central_area_office',
                    'northern_area_office',
                    'eastern_area_office',
                    'southeastern_area_office',
                    'southern_area_office'
                ];

                // 'engineering',
                // 'general_manager',
                // 'admin'
                
                $updateFields = [];
                $params = [];

                foreach ($fields as $field) {
                    $value = isset($_POST[$field][$index]) ? 1 : 0;
                    $updateFields[] = "$field = ?";
                    $params[] = $value;
                }

                // Update crew name
                $updateFields[] = "crew = ?";
                $updateFields[] = "plateno = ?";
                $updateFields[] = "tracker_id = ?";
                $params[] = $crew;
                $params[] = $plateno;
                $params[] = $tracker_id;

                // WHERE condition
                $params[] = $trn;

                $sql = "UPDATE tbl_crew SET " . implode(', ', $updateFields) . " WHERE trn = ?";
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt === false) {
                    $ralarm .= "❌ Failed on TRN: $trn<br>" . print_r(sqlsrv_errors(), true);
                }
            }

            if ($ralarm == "") {
                $ralarm = "✅ All Records Updated Successfully";
            }
        } else {
            $ralarm = "❌ No POST data";
        }

        echo $ralarm;
        sqlsrv_close($conn);
    }
?>
