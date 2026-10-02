<?php
include '../config_electricity.php';

$message = $_GET["message"];

echo "<br>Message = ".$message."<br>";

date_default_timezone_set("asia/jakarta");
$datenow = date('Y-m-d H:i:s');
$datenow2 = date('Ymd_His');

$myArray = explode(",", $message);

$i = 0;
while($i < count($myArray))
{
  $text = $myArray[$i];
  $exp = explode("_", $text);
  $id_location = $exp[0];
  $current_r = $exp[1];
  $current_s= $exp[2];
  $current_t= $exp[3];
  $voltage_r= $exp[4];
  $voltage_s= $exp[5];
  $voltage_t= $exp[6];
  $current_average = $exp[7];
  $voltage_average = $exp[8];
  $power = $exp[9];
  $kwh = $exp[10];
  $id_transaksi = $id_location."_".$datenow2;

  echo "id_location=".$id_location."-current_r=".$current_r."-current_s=".$current_s."-current_t=".$current_t."-voltage_r=".$voltage_r."-voltage_s=".$voltage_s."-voltage_t=".$voltage_t."-current_average=".$current_average."-voltage_average=".$voltage_average."-power=".$power."-kwh=".$kwh."-Date=".$datenow."<br>";

	//cek apakah sudah ada datanya
	$sql_select = "SELECT id_transaction, datenow, kwh, TIMESTAMPDIFF(MINUTE,datenow,NOW()) AS gap
					FROM electricity WHERE id_location = '$id_location'
					ORDER BY datenow DESC LIMIT 1";
	$result_select = mysqli_query($mysqli,$sql_select);
	$num_select = mysqli_num_rows($result_select);
	$row_select = mysqli_fetch_assoc($result_select);
	
	// kalau tidak ada datanya --> insert
	if($num_select != 0)
	{
		$gap = $row_select['gap'];
		if($gap < 55)
		{
			echo "Last record is ".$gap." Minutes Ago..<br>";
		} else
		{
			$last_kwh = $row_select['kwh'];
			$consumpt = $kwh - $last_kwh;
			
			$sql_insert = "INSERT INTO electricity VALUES('$id_transaksi','$id_location','$current_r','$current_s','$current_t','$voltage_r','$voltage_s','$voltage_t','$current_average','$voltage_average','$power',DEFAULT,'$kwh','$consumpt','$datenow')";
			$result = mysqli_query($mysqli,$sql_insert);

			if($result)
			{
				echo "Insert Data Succeed..<br>";
			} else
			{
				echo "Insert Data Failed..<br>";
			};
		};
	} else
	{
		$consumpt = 0;
		$sql_insert = "INSERT INTO electricity VALUES('$id_transaksi','$id_location','$current_r','$current_s','$current_t','$voltage_r','$voltage_s','$voltage_t','$current_average','$voltage_average','$power',DEFAULT,'$kwh','$consumpt','$datenow')";
		$result = mysqli_query($mysqli,$sql_insert);

		if($result)
		{
			echo "Insert Data Succeed..<br>";
		} else
		{
			echo "Insert Data Failed..<br>";
		};
	};
    $i++;
}
?>