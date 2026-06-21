<?php include 'conn.php';?>
<?php include 'js/fphp.php';?>

<?php
    $p_account = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vaccount'])) {
        $p_account = htmlspecialchars($_POST['vaccount'], ENT_QUOTES, 'UTF-8');
    }
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['vaccount'])) {
        $p_account = htmlspecialchars($_GET['vaccount'], ENT_QUOTES, 'UTF-8');
    }

    if (strlen($p_account) >= 4) {

        $sql    = "SELECT * FROM tbl_cust WHERE accountno = ?";
        $params = [$p_account];
        $stmt   = sqlsrv_query($conn, $sql, $params);

        if ($stmt !== false && sqlsrv_has_rows($stmt)) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
?>

<form id="frmcInfoDataloc">
  <input type="hidden" id="t_accountno" name="t_accountno" value="<?= $row['ACCOUNTNO'] ?>">
  <input type="hidden" id="t_name"      name="t_name"      value="<?= $row['NAME'] ?>">
  <input type="hidden" id="t_address"   name="t_address"   value="<?= $row['ADDRESS'] ?>">

  <div class="loc-section">

    <!-- Coordinates -->
    <div class="loc-field">
      <span class="loc-label">
        <i class="fa fa-map-marker" style="color:var(--g400);margin-right:4px;"></i> Latitude
      </span>
      <input class="loc-input" type="text" id="t_latc" name="t_latc" value="<?= $row['GPS_LAT'] ?>">
    </div>

    <div class="loc-field">
      <span class="loc-label">
        <i class="fa fa-map-marker" style="color:var(--g400);margin-right:4px;"></i> Longitude
      </span>
      <input class="loc-input" type="text" id="t_lngc" name="t_lngc" value="<?= $row['GPS_LONG'] ?>">
    </div>

    <!-- Choose + Save on same row -->
    <?php if ($_SESSION['edit_locations'] == 1): ?>
      <div class="loc-btn-row">
        <button type="button" class="btn-loc-choose" onclick="ChangeLoc()">
          <i class="fa fa-crosshairs"></i> Choose
        </button>
        <button type="button" class="btn-loc-save" onclick="SaveData('location_save.php','frmcInfoDataloc')">
          <i class="fa fa-floppy-o"></i> Save
        </button>
        </div>
    <!-- Google Maps at the bottom -->
    <a class="btn-maps-link" target="_blank"
       href="https://www.google.com/maps/search/?api=1&query=<?= $row['GPS_LAT'] ?>,<?= $row['GPS_LONG'] ?>">
      <i class="fa fa-globe"></i> View on Google Maps
    </a>
    
    <?php endif; ?>

    <hr class="loc-divider">

    <!-- Tag -->
    <div class="loc-field">
      <span class="loc-label">
        <i class="fa fa-tag" style="color:var(--g400);margin-right:4px;"></i> Tag
      </span>
      <select style="width:100%;" id="t_btag" name="t_btag">
        <option value="">None</option>
        <?php
          $tag_sql  = "SELECT id, tag, btag FROM tbl_tag ORDER BY tag ASC";
          $tag_stmt = sqlsrv_query($conn, $tag_sql);
          $has_tags = false;
          if ($tag_stmt !== false) {
            while ($tag_row = sqlsrv_fetch_array($tag_stmt, SQLSRV_FETCH_ASSOC)) {
              $has_tags = true;
              $selected  = ($row["BTAG"] == $tag_row["btag"]) ? 'selected' : '';
              $tag_id    = htmlspecialchars($tag_row["id"], ENT_QUOTES, 'UTF-8');
              $tag_val   = htmlspecialchars($tag_row["btag"], ENT_QUOTES, 'UTF-8');
              $tag_label = htmlspecialchars($tag_row["tag"],  ENT_QUOTES, 'UTF-8');
              echo "<option value='$tag_val' data-id='$tag_id' data-tag='$tag_label' $selected>$tag_label</option>";
            }
          }
          if (!$has_tags) {
            $tag_sql  = "SELECT
                          COALESCE(NULLIF(MIN(CAST([TAG] AS nvarchar(255))), ''), [BTAG]) AS tag,
                          CAST(0 AS int) AS id,
                          [BTAG] AS btag
                         FROM tbl_cust
                         WHERE ISNULL([BTAG], '') <> ''
                         GROUP BY [BTAG]
                         ORDER BY tag ASC";
            $tag_stmt = sqlsrv_query($conn, $tag_sql);
            if ($tag_stmt !== false) {
              while ($tag_row = sqlsrv_fetch_array($tag_stmt, SQLSRV_FETCH_ASSOC)) {
                $selected  = ($row["BTAG"] == $tag_row["btag"]) ? 'selected' : '';
                $tag_id    = htmlspecialchars($tag_row["id"], ENT_QUOTES, 'UTF-8');
                $tag_val   = htmlspecialchars($tag_row["btag"], ENT_QUOTES, 'UTF-8');
                $tag_label = htmlspecialchars($tag_row["tag"],  ENT_QUOTES, 'UTF-8');
                echo "<option value='$tag_val' data-id='$tag_id' data-tag='$tag_label' $selected>$tag_label</option>";
              }
            }
          }
        ?>
      </select>
    </div>

    <?php if ($_SESSION['edit_locations'] == 1): ?>
      <button type="button" class="btn-tag-save" onclick="SaveData('btag_save.php','frmcInfoDataloc')">
        <i class="fa fa-tag"></i> Save Tag
      </button>
    <?php endif; ?>

    

    

  </div>
</form>

<?php
            }
        }
    }
?>
