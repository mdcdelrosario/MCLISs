<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?>

<?php

    if($_SESSION['utilities_user'] != 1 ) {
       echo "❌ Invalid User Rights!";
    } 
    else {
        $ralarm = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['trn'])) {
            foreach ($_POST['trn'] as $index => $trn) {
                $level = $_POST['level'][$index];

                $fields = [
    'search_customer',
    'search_elements',
    'is_crew',

    // Vehicles Viewing
    'main_area',
    'central_area',
    'northern_area',
    'eastern_area',
    'southeastern_area',
    'southern_area',

    // Editing
    'edit_locations',
    'edit_metering_data',
    'edit_complaints_request',
    'edit_elements_pole',        // Poles (All)
    'edit_poles_central',        // ← ADD
    'edit_poles_northern',       // ← ADD
    'edit_poles_eastern',        // ← ADD
    'edit_poles_southeastern',   // ← ADD
    'edit_poles_southern',       // ← ADD

    // Utilities Management
    'utilities_user',
    'utilities_vehicle',
    'utilities_personnel',
    'utilities_complaints_request',

    // Download Reports
    'download_master_data',
    'download_complaints_request_data',
    'download_cust_brgy',
    'download_cust_town'
];

                $updateFields = [];
                $params = [];

                foreach ($fields as $field) {
                    $value = isset($_POST[$field][$index]) ? 1 : 0;
                    $updateFields[] = "$field = ?";
                    $params[] = $value;
                }

                // Update level name
                $updateFields[] = "level = ?";
                $params[] = $level;

                // WHERE condition
                $params[] = $trn;

                $sql = "UPDATE tbl_userrights SET " . implode(', ', $updateFields) . " WHERE trn = ?";
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