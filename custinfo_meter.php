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

                $mhiden = $_SESSION['edit_metering_data'] == 1 ? '' : 'readonly';
?>

<form id="frmcInfoDatameter" name="frmcInfoDatameter">
  <input type="hidden" id="t_accountno" name="t_accountno" value="<?= $row['ACCOUNTNO'] ?>">
  <input type="hidden" id="t_name"      name="t_name"      value="<?= $row['NAME'] ?>">
  <input type="hidden" id="t_address"   name="t_address"   value="<?= $row['ADDRESS'] ?>">

  <div class="loc-section">

    <?php
      $fields = [
        't_meter'       => ['label' => 'Meter No',    'value' => $row['METERNO']     ],
        't_m_brand'     => ['label' => 'Brand',       'value' => $row['M_BRAND']     ],
        't_m_type'      => ['label' => 'Type',        'value' => $row['M_TYPE']      ],
        't_m_class'     => ['label' => 'Class',       'value' => $row['M_CLASS']     ],
        't_m_ercseal'   => ['label' => 'ERC Seal',    'value' => $row['M_ERCSEAL']   ],
        't_m_calibseal' => ['label' => 'Calib. Seal', 'value' => $row['M_CALIBSEAL'] ],
        't_m_ptratio'   => ['label' => 'PT Ratio',    'value' => $row['M_PTRATIO']   ],
        't_m_ctratio'   => ['label' => 'CT Ratio',    'value' => $row['M_CTRATIO']   ],
      ];
      foreach ($fields as $id => $f):
    ?>
      <div class="loc-field-row">
        <span class="loc-label"><?= $f['label'] ?></span>
        <input class="loc-input" type="text" id="<?= $id ?>" name="<?= $id ?>"
               value="<?= htmlspecialchars($f['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               <?= $mhiden ?>>
      </div>
    <?php endforeach; ?>

    <?php if ($_SESSION['edit_metering_data'] == 1): ?>
      <button type="button" class="btn-loc-choose"
              onclick="SaveData('meter_save.php','frmcInfoDatameter')">
        <i class="fa fa-floppy-o"></i> Update Meter
      </button>
    <?php endif; ?>

    <hr class="loc-divider">

    <?php
      $info = [
        ['label' => 'Conn. Date', 'value' => $row['CONNDATE']->format('Y-m-d H:i:s')],
        ['label' => 'Book No',    'value' => $row['BOOKNO']  ],
        ['label' => 'Type',       'value' => $row['TYPE']    ],
        ['label' => 'Status',     'value' => $row['STATUS']  ],
        ['label' => 'Ave. Cons',  'value' => $row['AVECONS'] ],
      ];
      foreach ($info as $item):
    ?>
      <div class="loc-field-row">
        <span class="loc-label"><?= $item['label'] ?></span>
        <input class="loc-input" type="text"
               value="<?= htmlspecialchars($item['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               readonly>
      </div>
    <?php endforeach; ?>

  </div>
</form>

<?php
            }
        }
    }
?>