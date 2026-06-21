<?php include '../js/fphp.php'; ?>
<?php include '../conn.php'; ?> 

<?php
$p_route  = trim($_REQUEST['sroute']  ?? '');
$p_town   = trim($_REQUEST['stown']   ?? '');
$p_year   = trim($_REQUEST['syear']   ?? '');
$p_month  = trim($_REQUEST['smonth']  ?? '');
$p_day    = trim($_REQUEST['sday']    ?? '');

// Pad month and day to 2 digits (e.g. "5" → "05")
$p_month_padded = ($p_month != '') ? str_pad($p_month, 2, '0', STR_PAD_LEFT) : '';
$p_day_padded   = ($p_day   != '') ? str_pad($p_day,   2, '0', STR_PAD_LEFT) : '';
?>

<div class="ms-table-wrap">
  <div class="ms-table-scroll">
    <table class="ms-table">
      <thead>
        <tr>
        <?php 
            $col_arr = [];
            $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_cust' ORDER BY ORDINAL_POSITION";
            $stmt = sqlsrv_query($conn, $sql);
            $colno = 0;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $col_arr[] = $row["COLUMN_NAME"];
                echo "<th>" . htmlspecialchars($row["COLUMN_NAME"]) . "</th>";
                $colno++;
            }
            sqlsrv_free_stmt($stmt);
        ?>
        </tr>
      </thead>
      <tbody>
        <?php
            $hasData = false;
            $params  = [];
            $where   = "WHERE 1=1";

            if ($p_route != '') {
                $where   .= " AND ACCOUNTNO LIKE ?";
                $params[] = $p_route . '%';
            }

            if ($p_town != '') {
                $where   .= " AND AREA = ?";
                $params[] = $p_town;
            }

            // Date filter using SUBSTRING on CONVERT(VARCHAR, CONNDATE, 120)
            // Format is always: 2021-03-27 10:35:43
            //   Year  = pos 1, len 4
            //   Month = pos 6, len 2
            //   Day   = pos 9, len 2

            if ($p_year != '' && $p_month_padded != '' && $p_day_padded != '') {
                // Year + Month + Day
                $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
                         .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
                         .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
                $params[] = $p_year;
                $params[] = $p_month_padded;
                $params[] = $p_day_padded;

            } elseif ($p_year != '' && $p_month_padded != '') {
                // Year + Month
                $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?"
                         .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
                $params[] = $p_year;
                $params[] = $p_month_padded;

            } elseif ($p_year != '') {
                // Year only
                $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),1,4) = ?";
                $params[] = $p_year;

            } elseif ($p_month_padded != '' && $p_day_padded != '') {
                // Month + Day (no year)
                $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?"
                         .  " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
                $params[] = $p_month_padded;
                $params[] = $p_day_padded;

            } elseif ($p_month_padded != '') {
                // Month only
                $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),6,2) = ?";
                $params[] = $p_month_padded;

            } elseif ($p_day_padded != '') {
                // Day only
                $where   .= " AND SUBSTRING(CONVERT(VARCHAR,CONNDATE,120),9,2) = ?";
                $params[] = $p_day_padded;
            }

            $sql  = "SELECT TOP 1000 * FROM tbl_cust $where ORDER BY ACCOUNTNO";
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                echo '<tr><td colspan="' . $colno . '">
                        <b>Query Error</b><pre>' . print_r(sqlsrv_errors(), true) . '</pre>
                      </td></tr>';
            } else {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $hasData = true;
                    echo '<tr>';
                    foreach ($col_arr as $col) {
                        $val = $row[$col] ?? '';
                        if ($val instanceof DateTime) {
                            echo '<td>' . $val->format('Y-m-d H:i:s') . '</td>';
                        } else {
                            echo '<td>' . htmlspecialchars((string)$val) . '</td>';
                        }
                    }
                    echo '</tr>';
                }
                sqlsrv_free_stmt($stmt);
            }

            if (!$hasData) {
                $has_filter = ($p_route != '' || $p_town != '' || $p_year != '' || $p_month != '' || $p_day != '');
                $msg = $has_filter ? 'No records matched your filters' : 'No data to display';
                $sub = $has_filter ? 'Try adjusting the Area, Account No., or Connection Date filters'
                                   : 'Set a filter above and click Show Data';
                echo '<tr><td colspan="' . $colno . '">
                        <div class="ms-empty">
                          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                          </svg>
                          <p>' . $msg . '</p>
                          <span>' . $sub . '</span>
                        </div>
                      </td></tr>';
            }

            sqlsrv_close($conn);
        ?>
      </tbody>
    </table>
  </div>
</div>