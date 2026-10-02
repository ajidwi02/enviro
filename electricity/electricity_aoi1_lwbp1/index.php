<?php
include '../config_electricity_aoi1.php';

$message = $_GET["message"];

echo "<br>Message = ".$message."<br>";

date_default_timezone_set("Asia/Jakarta");
$datenow = date('Y-m-d H:i:s');
$datenow2 = date('Ymd_His');

$myArray = explode(",", $message);

foreach ($myArray as $text) {
  $exp = explode("_", $text);
  if (count($exp) != 3) continue;

  $id_location = $exp[0];
  $kwh_awal = floatval($exp[1]);
  $kwh_akhir = floatval($exp[2]);
  $id_transaksi = $id_location . "_" . $datenow2;

  echo "id_location=$id_location - kwh_awal=$kwh_awal - kwh_akhir=$kwh_akhir - Date=$datenow<br>";

  // cek data sebelumnya
  $sql_select = "SELECT id_transaction, datenow, kwh_awal, kwh_akhir, TIMESTAMPDIFF(MINUTE, datenow, NOW()) AS gap
                 FROM electricity_aoi1_lwbp1
                 WHERE id_location = '$id_location'
                 ORDER BY datenow DESC LIMIT 1";

  $result_select = mysqli_query($mysqli, $sql_select);
  $num_select = mysqli_num_rows($result_select);

  if ($num_select != 0) {
    $row_select = mysqli_fetch_assoc($result_select);
    $gap = $row_select['gap'];

    if ($gap < 55) {
      echo "Last record is $gap minutes ago..<br>";
    } else {
      $consumpt = $kwh_akhir - $kwh_awal;
      //if ($consumpt < 0) $consumpt = 0;
      $tarif = $consumpt * 1035.78;

      $sql_insert = "INSERT INTO electricity_aoi1_lwbp1 (id_transaction, id_location, kwh_awal, kwh_akhir, consumpt, tarif, datenow)
                     VALUES('$id_transaksi','$id_location','$kwh_awal','$kwh_akhir','$consumpt','$tarif','$datenow')";
      $result = mysqli_query($mysqli, $sql_insert);
      echo $result ? "Insert Data Succeed..<br>" : "Insert Data Failed1..<br>";
    }
  } else {
    //$consumpt = 0;
    //$tarif = 0;
    $consumpt = $kwh_akhir - $kwh_awal;
    $tarif = $consumpt * 1035.78;
    $sql_insert = "INSERT INTO electricity_aoi1_lwbp1 (id_transaction, id_location, kwh_awal, kwh_akhir, consumpt, tarif, datenow)
                   VALUES('$id_transaksi','$id_location','$kwh_awal','$kwh_akhir','$consumpt','$tarif','$datenow')";
    $result = mysqli_query($mysqli, $sql_insert);
    echo $result ? "Insert Data Succeed..<br>" : "Insert Data Failed2..<br>";
  }
}
?>
