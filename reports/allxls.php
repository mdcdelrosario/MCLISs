<?php
ob_start();
include '../js/fphp.php';
include '../conn.php';
ob_end_clean();

set_time_limit(0);

$route = trim($_REQUEST['sroute'] ?? '');
$town  = trim($_REQUEST['stown']  ?? '');
$year  = trim($_REQUEST['syear']  ?? '');
$month = trim($_REQUEST['smonth'] ?? '');
$day   = trim($_REQUEST['sday']   ?? '');
$qtag  = trim($_GET['qtag']       ?? '');

$mm = $month ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
$dd = $day   ? str_pad($day,   2, '0', STR_PAD_LEFT) : '';

// ── Columns ───────────────────────────────────────────────────────────────────
$cols = [];
$res  = sqlsrv_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_cust' ORDER BY ORDINAL_POSITION");
while ($r = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) $cols[] = $r['COLUMN_NAME'];
sqlsrv_free_stmt($res);

// ── WHERE ─────────────────────────────────────────────────────────────────────
$where  = 'WHERE 1=1';
$params = [];

if ($route) { $where .= ' AND ACCOUNTNO LIKE ?'; $params[] = $route . '%'; }
if ($town)  { $where .= ' AND AREA = ?';         $params[] = $town; }

if (!in_array($qtag, ['allwpo','nogps','disco']))
    $where .= ' AND RDG_DTIME > DATEADD(DAY,-1095,GETDATE())';

if      ($qtag == 'nogps') $where .= " AND (GPS_LAT IS NULL OR GPS_LAT='') AND (GPS_LONG IS NULL OR GPS_LONG='')";
elseif  ($qtag == 'disco') $where .= " AND [status]='D'";
elseif  ($qtag == 'btag')  $where .= " AND btag IS NOT NULL AND btag!=''";

$d = '';
if ($year && $mm && $dd) { $d = ' AND CONVERT(DATE,CONNDATE)=?'; $params[] = "$year-$mm-$dd"; }
elseif ($year && $mm)    { $d = ' AND YEAR(CONNDATE)=? AND MONTH(CONNDATE)=?'; $params = array_merge($params,[$year,(int)$mm]); }
elseif ($year)           { $d = ' AND YEAR(CONNDATE)=?';  $params[] = $year; }
elseif ($mm && $dd)      { $d = ' AND MONTH(CONNDATE)=? AND DAY(CONNDATE)=?'; $params = array_merge($params,[(int)$mm,(int)$dd]); }
elseif ($mm)             { $d = ' AND MONTH(CONNDATE)=?'; $params[] = (int)$mm; }
elseif ($dd)             { $d = ' AND DAY(CONNDATE)=?';   $params[] = (int)$dd; }
$where .= $d;

if (!$route && !$town && !$qtag) $where .= ' AND 1=2';

// ── Query ─────────────────────────────────────────────────────────────────────
$res = sqlsrv_query($conn, "SELECT * FROM tbl_cust $where ORDER BY ACCOUNTNO", $params);

// ── Output ────────────────────────────────────────────────────────────────────
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Export' . $qtag . '_' . $town . '_' . $route . '.xls"');
header('Pragma: no-cache');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head><meta charset="UTF-8">
<style>
  table { border-collapse: collapse; font-family: Calibri, sans-serif; font-size: 11pt; }
  th { background: #92D050; color: #111; font-weight: bold; text-align: center; border: 1px solid #334155; padding: 6px 10px; }
  td { border: 1px solid #334155; padding: 6px 10px; mso-number-format: "\@"; }
  tr:nth-child(even) td { background: #F8FBF5; }
</style>
</head>
<body><table>
<thead><tr>
<?php foreach ($cols as $c): ?>
  <th><?= htmlspecialchars($c) ?></th>
<?php endforeach; ?>
</tr></thead>
<tbody>
<?php while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)): ?>
<tr>
  <?php foreach ($cols as $c):
    $v = $row[$c] ?? '';
    if ($v instanceof DateTime) $v = $v->format('Y-m-d H:i:s');
  ?>
  <td><?= htmlspecialchars((string)$v) ?></td>
  <?php endforeach; ?>
</tr>
<?php endwhile; ?>
</tbody>
</table></body></html>
<?php
sqlsrv_free_stmt($res);
sqlsrv_close($conn);