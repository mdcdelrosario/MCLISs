<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?> 

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $p_fr_date = isset($_POST['frcnrdate']) ? htmlspecialchars($_POST['frcnrdate'], ENT_QUOTES, 'UTF-8') : $p_fr_date;
    $p_to_date = isset($_POST['tocnrdate']) ? htmlspecialchars($_POST['tocnrdate'], ENT_QUOTES, 'UTF-8') : $p_to_date;
}
?>

    <table width="100%" border="1px">
        <thead>
            <tr>
            <?php 
                $col_arr = [];
                $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_cnr'";
                $stmt = sqlsrv_query($conn, $sql);
                $colno = 0;

                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $col_arr[] = $row["COLUMN_NAME"];
                    echo "<th>" . $row["COLUMN_NAME"] . "</th>";
                    $colno++;
                }
                sqlsrv_free_stmt($stmt);
            ?>
            </tr>
        </thead>
        <tbody>
            <?php
                $sql = "SELECT * FROM tbl_cnr WHERE req_dt BETWEEN ? AND ?";

                //echo $sql . " " . $p_fr_date. " " . $p_to_date ;
                $params = [$p_fr_date, $p_to_date];
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        echo "<tr>";
                        for ($x = 0; $x < $colno; $x++) {
                            //echo "<td>" . ($row[$col_arr[$x]] ?? '') . "</td>";

                            for ($x = 0; $x < $colno; $x++) {
                                $val = $row[$col_arr[$x]] ?? '';
                                if ($val instanceof DateTime) {
                                    echo "<td>" . $val->format('Y-m-d H:i:s') . "</td>";
                                } else {
                                    echo "<td>" . $val . "</td>";
                                }
                            }

                        }
                        echo "</tr>";
                    }
                    sqlsrv_free_stmt($stmt);
                }
            ?>
        </tbody>
    </table>
    <hr>
