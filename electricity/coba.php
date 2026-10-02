<?php
include '../config_electricity.php';

date_default_timezone_set("asia/jakarta");
$datenow = date('Y-m-d H:i:s');
$datenow2 = date('Ymd_His');

$sql_select = "SELECT id_transaction, datenow, kwh, TIMESTAMPDIFF(MINUTE,datenow,NOW()) AS gap
					FROM electricity
					ORDER BY datenow DESC LIMIT 1";
	$result_select = mysqli_query($mysqli,$sql_select);
	$num_select = mysqli_num_rows($result_select);
	$row_select = mysqli_fetch_assoc($result_select);
	
	// kalau tidak ada datanya --> insert
	if($num_select == 0)
	{
		$consumpt = 0;
		$sql_insert = "INSERT INTO electricity VALUES('$datenow2','lokasi_123','22.3','44.1','22.5','11.6','66.6','77.7','11.2','56.3','12.5','32.2','$consumpt','$datenow')";
		$result = mysqli_query($mysqli,$sql_insert);

		if($result)
		{
			echo "Insert Data Succeed..<br>";
		} else
		{
			echo "Insert Data Failed..<br>";
		};
		
	} else
	{
		$gap = $row_select['gap'];
		if($gap < 0)
		{
			echo "Last record is ".$gap." Minutes Ago..<br>";
		} else
		{
			$kwh = 80.2;
			$last_kwh = $row_select['kwh'];
			$consumpt = $kwh - $last_kwh;
			
			$sql_insert = "INSERT INTO electricity VALUES('$datenow2','lokasi_123','22.3','44.1','22.5','11.6','66.6','77.7','11.2','56.3','12.5','$kwh','$consumpt','$datenow')";
			$result = mysqli_query($mysqli,$sql_insert);

			if($result)
			{
				echo "Insert Data Succeed..<br>";
			} else
			{
				echo "Insert Data Failed..<br>";
			};
		};
	};
	
?>