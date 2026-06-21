<?php 
include '../js/fphp.php';
include '../conn.php';

$user_level = $_SESSION['user_type'] ?? 'guest';
$user_id    = (int)($_SESSION['user_id'] ?? 0);

$can_edit_cnr = ($_POST['can_edit'] ?? '0') === '1';
$is_crew      = ($_POST['is_crew']  ?? '0') === '1';

$statusFilter = trim($_POST['status_filter'] ?? '');

/* ── Build WHERE ── */
$where  = [];
$params = [];

$done_condition    = "(aco_dt IS NOT NULL AND YEAR(aco_dt) >= 2020)";
$not_done          = "(aco_dt IS NULL OR YEAR(aco_dt) < 2020)";
$pending_condition = "(crew IS NULL OR crew = '') AND $not_done";
$accepted_condition= "(crew IS NOT NULL AND crew <> '') AND $not_done";

if ($statusFilter === 'done') {
    $where[] = $done_condition;
} elseif ($statusFilter === 'pending') {
    $where[] = $pending_condition;
} elseif ($statusFilter === 'accepted') {
    $where[] = $accepted_condition;
}
// empty = all records

/* ── SQL ── */
$sql = "SELECT trn, req_dt, name, area, address, request, req_desc,
               crew, aco_dt, encoder,
               member1, member2, member3, member4, member5
        FROM tbl_cnr";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY req_dt DESC";

$stmt = empty($params)
    ? sqlsrv_query($conn, $sql)
    : sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    $errMsg = '';
    if (is_array($errors)) {
        foreach ($errors as $e) {
            $errMsg .= '[' . ($e['code'] ?? '') . '] ' . ($e['message'] ?? '') . ' ';
        }
    }
    error_log('complaint_data.php SQL error: ' . $errMsg . ' | SQL: ' . $sql);
    echo '<tr><td colspan="8" class="rpt-empty">
            <p>Query failed.</p>
            <span>' . htmlspecialchars(trim($errMsg)) . '</span>
          </td></tr>';
    exit;
}

/* ── Fetch ── */
$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
}

if (empty($rows)) {
    echo '<tr><td colspan="8" class="rpt-empty">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>No complaints found</p>
            <span>Try a different filter</span>
          </td></tr>';
    exit;
}

/* ── Render ── */
foreach ($rows as $i => $row) {

    $trn  = htmlspecialchars($row['trn']  ?? '', ENT_QUOTES);
    $name = htmlspecialchars($row['name'] ?? '', ENT_QUOTES);
    $area = htmlspecialchars($row['area'] ?? '', ENT_QUOTES);
    $crew = htmlspecialchars($row['crew'] ?? '', ENT_QUOTES);

    $req_desc_raw = $row['req_desc'] ?? '';
    $req_desc     = htmlspecialchars($req_desc_raw, ENT_QUOTES);

    $trn_js  = addslashes($row['trn']  ?? '');
    $name_js = addslashes($row['name'] ?? '');
    $area_js = addslashes($row['area'] ?? '');

    $req_dt = ($row['req_dt'] instanceof DateTime) ? $row['req_dt']->format('M d, Y') : '—';

    /* ── aco_dt: valid only if year >= 2020 ── */
    $aco_dt_raw = $row['aco_dt'];
    $aco_dt_valid = ($aco_dt_raw instanceof DateTime)
        && ((int)$aco_dt_raw->format('Y') >= 2020);

    $hasCrew = !empty(trim($row['crew'] ?? ''));

    /* ── 3-stage status ── */
    if ($aco_dt_valid) {
        $statusLabel = 'Done';
        $statusClass = 'status-done';
        $status      = 'done';
    } elseif ($hasCrew) {
        $statusLabel = 'Accepted';
        $statusClass = 'status-accepted';
        $status      = 'accepted';
    } else {
        $statusLabel = 'Pending';
        $statusClass = 'status-pending';
        $status      = 'pending';
    }

    /* ── Action button based on status + role ── */
    $statusAction = '';
    if ($status !== 'done') {
        if ($is_crew && $status === 'pending') {
            // Crew can accept unassigned complaints
            $statusAction = '
              <button class="action-btn accept-btn" onclick="acceptComplaint(\'' . $trn_js . '\')">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>Accept
              </button>';
        } elseif ($is_crew && $status === 'accepted') {
            // Crew can mark their accepted complaint as done
            $statusAction = '
              <button class="action-btn done-btn" onclick="doneComplaint(\'' . $trn_js . '\')">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>Mark Done
              </button>';
        } elseif ($can_edit_cnr) {
            // Admin can mark any pending/accepted complaint done
            $statusAction = '
              <button class="action-btn done-btn" onclick="doneComplaint(\'' . $trn_js . '\')">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>Mark Done
              </button>';
        }
    } else {
        $statusAction = '<span style="font-size:11px;color:#b8c4ba;font-style:italic;">Completed</span>';
    }

    /* ── Assigned crew column ── */
    $crewDisplay = !empty($crew)
        ? '<span class="crew-chip-sm">' . $crew . '</span>'
        : '<span class="unassigned-sm">Unassigned</span>';

    /* ── Buttons ── */
    $viewBtn = '
      <button class="action-btn detail-btn" onclick="loadComplaintDetails(\'' . $trn_js . '\')">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>View
      </button>';

    $editBtn   = '';
    $deleteBtn = '';

    if ($can_edit_cnr) {
        $editBtn = '
          <button class="action-btn edit-btn" onclick="openEditModal(\'' . $trn_js . '\')">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>Edit
          </button>';

        $deleteBtn = '
          <button class="action-btn done-btn" onclick="deleteComplaint(\'' . $trn_js . '\')">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              <path d="M10 11v6M14 11v6"/>
            </svg>Delete
          </button>';
    }

    /* ── Output row ── */
    echo '<tr>
      <td class="td-id">' . ($i + 1) . '</td>
      <td class="td-date">' . $req_dt . '</td>
      <td class="td-customer">' . $name . '</td>
      <td class="td-complaint">' . $req_desc . '</td>
      <td>
        <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-start;">
          <span class="status-badge ' . $statusClass . '">' . $statusLabel . '</span>
          ' . $statusAction . '
        </div>
      </td>
      <td>' . $crewDisplay . '</td>
      <td>
        <div class="action-group">
          ' . $viewBtn . $editBtn . $deleteBtn . '
        </div>
      </td>
    </tr>';
}
?>