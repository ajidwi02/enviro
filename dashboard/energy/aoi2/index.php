<?php
include '../../../config_electricity.php';

//standard 20101
$select_standard_20101 = "SELECT * FROM standard_power WHERE id_location = '20101'";
$result_standard_20101 = mysqli_query($mysqli, $select_standard_20101);
$row_standard_20101 = mysqli_fetch_assoc($result_standard_20101);
$min_20101 = $row_standard_20101['min_value'];
$green_20101 = $row_standard_20101['max_green'];
$yellow_20101 = $row_standard_20101['max_yellow'];
$red_20101 = $row_standard_20101['max_red'];

//CO2 emission
$select_kwh = "SELECT sum(consumpt) as tot_consumpt FROM electricity WHERE id_location = '20101' and cast(datenow as date) >= (cast(now() as date) - interval 30 day) GROUP BY id_location";
$result_kwh = mysqli_query($mysqli, $select_kwh);
$row_kwh = mysqli_fetch_assoc($result_kwh);
$tot_consumpt = $row_kwh['tot_consumpt'];
$emission = $tot_consumpt*0.891/1000;
$rec = $emission/2;

//chart RTS Production
$select_rts_date = "SELECT DISTINCT date_name FROM chart_rts";
$result_rts_date = mysqli_query($mysqli, $select_rts_date);

//Select yesterday kwh
$select_yesterday_kwh = "Select kwh AS kwh from electricity Where id_location = '20101' AND (cast(datenow as date) = (cast(now() as date) - interval 1 day)) Order By datenow DESC LIMIT 1";
$result_yesterday_kwh = mysqli_query($mysqli, $select_yesterday_kwh);
$row_yesterday_kwh = mysqli_fetch_assoc($result_yesterday_kwh);
$last_kwh = $row_yesterday_kwh['kwh'];

//Select today kwh
$select_today_kwh = "Select kwh AS kwh from electricity Where id_location = '20101' AND (cast(datenow as date) = (cast(now() as date))) Order By datenow DESC LIMIT 1";
$result_today_kwh = mysqli_query($mysqli, $select_today_kwh);
$row_today_kwh = mysqli_fetch_assoc($result_today_kwh);
$today_kwh = $row_today_kwh['kwh']; 

//gauge per location
$select_gauge = "Select * from realtime_power WHERE id_location = '20105' OR id_location = '20101'";
$result_gauge = mysqli_query($mysqli, $select_gauge);

//consumption total
$select_gauge_pln = "Select * from realtime_power WHERE id_location = '20101' ";
$result_gauge_pln = mysqli_query($mysqli, $select_gauge_pln);
$row_gauge_pln = mysqli_fetch_assoc($result_gauge_pln);
$pln_supply = $row_gauge_pln['power'];

$select_gauge_rts = "Select * from realtime_power WHERE id_location = '20105' ";
$result_gauge_rts = mysqli_query($mysqli, $select_gauge_rts);
$row_gauge_rts = mysqli_fetch_assoc($result_gauge_rts);
$rts_production = $row_gauge_rts['power'];

if ($pln_supply > $rts_production)
{
	$consumption_gauge = round($pln_supply + $rts_production);
} else {
	$consumption_gauge = round($pln_supply);
};


//RTS yesterday
$select_rts_yesterday = "Select SUM(consumpt) AS consumpt from electricity Where id_location = '20105' AND (cast(enviro.electricity.datenow as date) >= (cast(now() as date) - interval 30 day))";
$result_rts_yesterday = mysqli_query($mysqli, $select_rts_yesterday);
$row_rts_yesterday = mysqli_fetch_assoc($result_rts_yesterday);
$rts_yesterday = $row_rts_yesterday['consumpt'];

//consumpt yesterday
$select_consumpt_yesterday = "Select SUM(consumpt) AS consumpt from electricity Where id_location = '20101' AND (cast(enviro.electricity.datenow as date) >= (cast(now() as date) - interval 30 day))";
$result_consumpt_yesterday = mysqli_query($mysqli, $select_consumpt_yesterday);
$row_consumpt_yesterday = mysqli_fetch_assoc($result_consumpt_yesterday);
$consumpt_yesterday = $row_consumpt_yesterday['consumpt'];

$saving = $rts_yesterday/($consumpt_yesterday+$rts_yesterday);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
   
    <title>Dashboard Energy</title>
    <!-- Simple bar CSS -->
    <link rel="stylesheet" href="../../../css/simplebar.css">
	<link rel="stylesheet" href="../../../css/feather.css">
    <!-- Fonts CSS -->
    <!-- App CSS -->
    <link rel="stylesheet" href="../../../css/app-light.css" id="lightTheme">
	<script src="../../../js/jquery.min.js" type = "text/javascript"></script>
	<script src="../../../js/highcharts.js" type = "text/javascript"></script>
	<script src="../../../js/highcharts-more.js" type = "text/javascript"></script>
	<script src="../../../mqtt/mqttws31.js" type="text/javascript"></script>
	<script src="../../../js/grouped-categories.js" type="text/javascript"></script>
	<script>
	// start showtime
	function showTime() 
	{
		var d = new Date();
		document.getElementById("clock").innerHTML = d.toLocaleTimeString();
	}
	setInterval(showTime, 1000);
	//end showtime
	
	//refresh page after 30 Minutes
	timeout = 15*60*1000;
	setTimeout(() => {
		document.location.reload();
	}, timeout);
	
	//define gauge
	gauge_power = [];
	</script>
	
  </head>
  <body onload="return MQTTconnect();" class="horizontal light">
    <div class="wrapper">
      <main role="main" class="main-content">
		<table style="width:100%">
			<tr>
				<td style="width: 10%;text-align: center;vertical-align: sub;"><img src="../../../logo_aoi.png" width="110"></td>
				<td style="width: 80%;vertical-align: sub;">
					<div class="w-50 mx-auto text-center">	
						<h2 class="page-title mb-0">Energy Dashboard -AOI2-</h2>
						<p class="mb-1 small text-muted"><span id="datenow"></span> / <span id="clock"></span></p>
						<script>
						// start date
						const months = ["Jan", "Feb", "Mar","Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
						let current_datetime = new Date()
						let formatted_date = current_datetime.getDate() + "-" + months[current_datetime.getMonth()] + "-" + current_datetime.getFullYear()
						console.log(formatted_date);
						document.getElementById("datenow").innerHTML = formatted_date;
						// finish date
						</script>
					</div>
				</td>
				<td style="width: 10%;text-align:center;vertical-align: sub;"><img src="../../../logo_bbi.png" width="130"></td>
			</tr>
		</table>
		<div class="col-12">
			<!-- Start Card-->
			<div class="card shadow my-4">
                <div class="card-body" style="padding:0;">
					<div class="row align-items-center my-4">						
						<div class="col-md-4" style="padding:1;">
							<!-- CO2 Emission -->
							<div class="mx-4">
								<strong class="mb-0 text-uppercase">CO2 Emission</strong><br/>
								<h1><?php echo "PLN: ".number_format((float)$emission, 2, '.', '')." Ton, <br>REC: ".number_format((float)$rec, 2, '.', '')." Ton";?></h1>
								<p class="text-muted">The carbon dioxide that produced by electricity consumption in AOI2 for last 30 days</p>

							</div>
							<hr>
							<!-- Solar Panel Production -->
							<div class="mx-4">
								<strong class="mb-0 text-uppercase">Solar Panel Energy</strong><br/>
								<h1><?php echo round($rts_yesterday, 0)." kwh <br>Saving ".round($saving*100, 1)." %";?></h1>
								<p class="text-muted">Energy that produced by solar panel in AOI2 (Last 30 days)</p>
							</div>
						</div>
						<div class="col-md-8" style="padding:0;">
							<div class="mr-4">
								<div id="chart_rts" style="border-style: groove;"></div>
								
							</div>
						</div> <!-- .col-md-8 -->
						<script>
						Highcharts.chart('chart_rts', 
						{
							chart: {
								type: 'area',
								height: (9 / 30 * 100) + '%' // ratio
							},
							title: 
							{
								text: 'AOI2 Consumption vs RTS Production (Kw)',
								x: 0, //center,
								style: {
									fontSize: '16px' 
								},
								
							},
							credits: 
							{
								enabled: false
							},
							tooltip: 
							{
								formatter: function () 
								{
									return 'Kw in ' + this.x +
										'</b> is <b>' + this.y + '</b>';
								}
							},
							xAxis: 
							{
								categories:
								[
									<?php 
									while ($row_rts_date = mysqli_fetch_assoc($result_rts_date))
										{
											$chart_date = $row_rts_date['date_name'];
											echo "{name:'".$chart_date."',";
											
											$select_rts_time = "SELECT time_name FROM chart_rts WHERE date_name = '$chart_date' ";
											$result_rts_time = mysqli_query($mysqli, $select_rts_time);
											
											echo "categories:[";
											
											while ($row_rts_time = mysqli_fetch_assoc($result_rts_time))
											{
												$time_name = $row_rts_time['time_name'];
												echo $time_name.",";
											};
											
											echo "]},";
										}
									?>
									
								],
								tickLength:0,
								crosshair: true
							},
							legend: 
							{
								enabled: true,
								layout: 'horizontal',
								align: 'center',
								verticalAlign: 'top',
								padding: 0,
								itemMarginTop: 1,
								itemMarginBottom: 1,
								itemStyle: 
								{
								  "color": "#333333",
								  "cursor": "pointer",
								  "fontSize": "10px",
								  "fontWeight": "bold",
								  "textOverflow": "ellipsis"
								}
							},
							yAxis: 
							{
								startOnTick: false,
								endOnTick: false,
								//maxPadding: 0.8,
								//tickInterval: 1000,
								minorTickInterval: 0,
								tickPositions: [0, 200, 400, 600, 800, 1000],
								title: 
								{
									text: 'Kw'
								},
								style: {
									fontSize: '12px' 
								},
								
							},
							series: [
							{
								name: 'AOI2 Consumption',
								dataLabels:
								{
									enabled: true,
									style: 
									{
										color: 'black',
										fontSize: '8px' 
									}
								}, 
								color: 'grey',
								data: 
								[
								<?php 
									$select_rts_power = "SELECT total FROM chart_rts";
									$query_rts_power = mysqli_query($mysqli, $select_rts_power);
									while ($row_rts_power = mysqli_fetch_assoc($query_rts_power))
										{
											$chart_power = $row_rts_power ['total'];
											echo round(abs($chart_power),0).",";
										};
									?>
								//consumpt_now
								]
							},
							{
								name: 'RTS Production',
								dataLabels:
								{
									enabled: false,
									style: 
									{
										color: 'black',
										fontSize: '8px' 
									}
								}, 
								color: 'blue',
								data: 
								[
								<?php 
									$select_rts_power = "SELECT rts FROM chart_rts";
									$query_rts_power = mysqli_query($mysqli, $select_rts_power);
									while ($row_rts_power = mysqli_fetch_assoc($query_rts_power))
										{
											$chart_power = $row_rts_power ['rts'];
											echo round(abs($chart_power),0).",";
										};
									?>
								//consumpt_now
								]
							}
							],
							plotOptions: 
							{
								series: 
								{
									shadow: true,
									color: 'rgb(0,0,0, 0.7)',
									pointPadding: 0,
									groupPadding: 0,
									marker: {radius: 1}
								}
							}
						});
						
						
						</script>
						
					</div> <!-- end section -->
                </div> <!-- .card-body -->
            </div> <!-- .card -->
			
			<div class="card shadow my-4" style="padding:0;">
                <div class="card-body" style="padding:0;">
					<div class="row align-items-center my-4">
					
					<!-- Start Looping gauge -->
						<?php while($row_gauge = mysqli_fetch_assoc($result_gauge))
							{ 
								$id_location = $row_gauge['id_location'];
								
								if ($id_location == '20101')
								{
									$location = 'PLN Supply';
								} else if ($id_location == '20105')
								{
									$location = 'RTS Production';
								};
								//$location = $row_gauge['location'];
								$power_gauge = round(abs($row_gauge['power']));
								
								//call standard value
								$select_standard_power = "Select * From standard_power Where id_location='$id_location'";
								$result_standard_power = mysqli_query($mysqli, $select_standard_power);
								$row_standard_power = mysqli_fetch_assoc($result_standard_power);
								$min_standard = $row_standard_power['min_value'];
								$max_green_standard = $row_standard_power['max_green'];
								$max_yellow_standard = $row_standard_power['max_yellow'];
								$max_red_standard = $row_standard_power['max_red'];
								
								?>
								<div class="col-md-3" style="padding:0;">
									<div class="card-body">				
										<p class="small text-uppercase mb-0 text-center"><strong><?php echo $location ;?></strong></p>
										<p class="small text-uppercase text-muted mb-0 text-center">Active Power</p>
										<div id="gauge_power_<?php echo $id_location ;?>" style="padding:0;"></div>
									</div> <!-- .card-body -->
									<script>
										let power_now_<?php echo $id_location?> = <?php echo $power_gauge ;?>;
										$('#gauge_power_<?php echo $id_location?>').highcharts( 
										{
											chart: 
											{
												type: 'gauge',
												plotBackgroundColor: null,
												plotBackgroundImage: null,
												plotBorderWidth: 0,
												plotShadow: false,
												height: '60%'
											},

											title: 
											{
												text: ''
											},
											credits: 
											{
												enabled: false
											},
											pane: 
											{
												startAngle: -120,
												endAngle: 120,
												background: null,
												center: ['50%', '75%'],
												size: '140%'
											},

											// the value axis
											yAxis: 
											{
												min: <?php echo $min_standard ;?>,
												max: <?php echo $max_red_standard;?>,
												tickPixelInterval: 5,
												tickPosition: 'inside',
												tickColor: Highcharts.defaultOptions.chart.backgroundColor || '#FFFFFF',
												tickLength: 10,
												tickWidth: 1,
												minorTickInterval: 5,
												labels: 
												{
													distance: 5,
													style: 
													{
														fontSize: '11px'
													}
												},
												plotBands: [
												{
													from: <?php echo $min_standard ;?>,
													to: <?php echo $max_green_standard ;?>,
													color: 'green', // green
													thickness: 20
												}, 
												{
													from: <?php echo $max_green_standard;?>,
													to: <?php echo $max_yellow_standard;?>,
													color: 'yellow', // yellow
													thickness: 20
												}, 
												{
													from: <?php echo $max_yellow_standard;?>,
													to: <?php echo $max_red_standard;?>,
													color: 'red', // red
													thickness: 20
												}]
											},

											series: [
											{
												name: 'Active Power',
												data: [power_now_<?php echo $id_location?>],
												tooltip: 
												{
													valueSuffix: ' KW'
												},
												dataLabels: 
												{
													format: '{y} KW',
													borderWidth: 0,
													color: '#333333',
													style: 
													{
														fontSize: '24px'
													},
													zIndex: 3
												},
												dial: 
												{
													radius: '110%',
													backgroundColor: 'black',
													borderColor: 'white',
													borderWidth: 1,
													baseWidth: 9,
													baseLength: '5%',
													rearLength: '-8%',
													zIndex: 2
												},
												pivot: 
												{
													backgroundColor: 'black',
													borderColor: 'white',
													borderWidth: 2,
													radius: 1,
													zIndex: 1
												}
											}]
										});
										
										gauge_power[<?php echo $id_location ;?>] = function (newvalue)
										{
											var power_now = $('#gauge_power_<?php echo $id_location ;?>').highcharts().series[0].points[0];
											power_now.update(newvalue);
										}; 
									</script>
								</div> <!-- .col-md-4 -->	
						<?php ;}?>	
						<!-- End Looping gauge -->
						<div class="col-md-3" style="padding:0;">
							<div class="card-body">				
								<p class="small text-uppercase mb-0 text-center"><strong>AOI2 Consumption</strong></p>
								<p class="small text-uppercase text-muted mb-0 text-center">Active Power</p>
								<div id="gauge_power_consumption" style="padding:0;"></div>
							</div> <!-- .card-body -->
							
							<script>
								let power_now_consumption = <?php echo $consumption_gauge ;?>;
								$('#gauge_power_consumption').highcharts( 
								{
									chart: 
									{
										type: 'gauge',
										plotBackgroundColor: null,
										plotBackgroundImage: null,
										plotBorderWidth: 0,
										plotShadow: false,
										height: '60%'
									},

									title: 
									{
										text: ''
									},
									credits: 
									{
										enabled: false
									},
									pane: 
									{
										startAngle: -120,
										endAngle: 120,
										background: null,
										center: ['50%', '75%'],
										size: '140%'
									},

									// the value axis
									yAxis: 
									{
										min: 0,
										max: 2100,
										tickPixelInterval: 5,
										tickPosition: 'inside',
										tickColor: Highcharts.defaultOptions.chart.backgroundColor || '#FFFFFF',
										tickLength: 10,
										tickWidth: 1,
										minorTickInterval: 5,
										labels: 
										{
											distance: 5,
											style: 
											{
												fontSize: '11px'
											}
										},
										plotBands: [
										{
											from: 0,
											to: 1600,
											color: 'green', // green
											thickness: 20
										}, 
										{
											from: 1600,
											to: 1850,
											color: 'yellow', // yellow
											thickness: 20
										}, 
										{
											from: 1850,
											to: 2100,
											color: 'red', // red
											thickness: 20
										}]
									},

									series: [
									{
										name: 'Active Power',
										data: [power_now_consumption],
										tooltip: 
										{
											valueSuffix: ' KW'
										},
										dataLabels: 
										{
											format: '{y} KW',
											borderWidth: 0,
											color: '#333333',
											style: 
											{
												fontSize: '24px'
											},
											zIndex: 3
										},
										dial: 
										{
											radius: '110%',
											backgroundColor: 'black',
											borderColor: 'white',
											borderWidth: 1,
											baseWidth: 9,
											baseLength: '5%',
											rearLength: '-8%',
											zIndex: 2
										},
										pivot: 
										{
											backgroundColor: 'black',
											borderColor: 'white',
											borderWidth: 2,
											radius: 1,
											zIndex: 1
										}
									}]
								});
								
								gauge_power["consumption"] = function (newvalue)
								{
									var power_now = $('#gauge_power_consumption').highcharts().series[0].points[0];
									power_now.update(newvalue);
								}; 
							</script>
						</div>
						
						<div class="col-md-3" style="padding:0;">
							<div class="card-body">				
								<div id="chart_kwh" style="padding:0;"></div>
								
							</div> <!-- .card-body -->
						</div>
					</div>
				</div>
			</div>
			
			<!-- End gauge -->
			<!-- End Card -->
		
			<!-- Start Footer -->
			<div class="w-100 mx-auto text-center" style="width:100%; position: fixed;bottom: 0; display:none;">	
				<div id="status">Connection Status: cek mqtt</div>
				<a id="messages"></a>
			</div>
			<!-- End Footer -->
			
		</div>
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    <script src="../../../js/popper.min.js"></script>
    <script src="../../../js/moment.min.js"></script>
    <script src="../../../js/bootstrap.min.js"></script>
    <script src="../../../js/jquery.sparkline.min.js"></script>
	
	<!-- MQTT -->
	<script type = "text/javascript">
	function onConnectionLost()
	{
		console.log("connection lost");
		document.getElementById("status").innerHTML = "MQTT Connection Lost";
		document.getElementById("messages").innerHTML ="MQTT Connection Lost";
		connected_flag=0;
		<!-- window.location.reload(); -->
	}
	function onFailure(message) 
	{
		console.log("Failed");
		document.getElementById("messages").innerHTML = "MQTT Connection Failed- Retrying";
        setTimeout(MQTTconnect, reconnectTimeout);
    }
	
	//function onMessageArrived
	function onMessageArrived(r_message)
	{		
		const parse_mess_all = JSON.parse(r_message.payloadString);
		const mess_payload = parse_mess_all.payload;
		
		const string_mess_payload = JSON.stringify(mess_payload);
		const parse_mess_payload = JSON.parse(string_mess_payload);
		
		var_publish = parse_mess_payload.listrik;
		
		const array_publish = var_publish.split(",");
		
		let i = 0;
		let text = "";
		let content_text = "";
		let power_consumpt =0;
		while (array_publish[i]) 
		{
			text = array_publish[i]+"<br>";

			text_mess = array_publish[i];
			const array_content = text_mess.split("_");

			var content_loc = array_content[0];
			var content_current = array_content[1];
			var content_voltage = array_content[2];
			var content_power = array_content[3];
			var content_energy = array_content[4];
			
			//content_text += "Loc:"+content_loc+" current:"+content_current+" voltage:"+content_voltage+" power:"+content_power+" energy:"+content_energy+" <br>";
			
			var val_power = Math.round(parseFloat(content_power)* 10) / 10;
			var abs_val_power = Math.round(Math.abs(val_power));
			
			//var new_energy_today = content_energy-<?php echo $last_kwh;?>;
			//var val_energy = Math.round(parseFloat(new_energy_today)* 10) / 10;
			
			if(content_loc == "20101")
			{
				//chart_kwh_today(val_energy);
				gauge_power[content_loc](abs_val_power);
				power_consumpt = Math.round(power_consumpt + abs_val_power);
			} else 
				if(content_loc == "20105")
				{
					gauge_power[content_loc](abs_val_power);
					power_consumpt = Math.round(power_consumpt + abs_val_power);
				} else
				{
				};
			
			i++;
		};
		
		gauge_power["consumption"](power_consumpt);
		
		content_text = power_consumpt + "<br>";
		
		const var_message = "Last Transaction is: <br>" + content_text;
		document.getElementById("messages").innerHTML = var_message;
	}
	
	function onConnected(recon,url)
	{
		console.log(" in onConnected " +reconn);
	}
	
	function onConnect() 
	{
	  // Once a connection has been made, make a subscription and send a message.
		document.getElementById("messages").innerHTML ="Connected to "+host +" on port "+port;
		connected_flag=1
		document.getElementById("status").innerHTML = "Connected";
		console.log("on Connect "+connected_flag);
		
		//subscribe message	
		return sub_topics();
		
	}

    function MQTTconnect() 
	{
		document.getElementById("messages").innerHTML ="";
		var s = "192.168.51.40";
		var p = "8083";
		if (p!="")
		{
			console.log("ports");
			port=parseInt(p);
			console.log("port" +port);
		}
		if (s!="")
		{
			host=s;
			console.log("host");
		}
		console.log("connecting to "+ host +" "+ port);
		var x=Math.floor(Math.random() * 10000); 
		var cname="energy_Dashboard_AOI2_"+x;
		mqtt = new Paho.MQTT.Client(host,port,cname);
		//document.write("connecting to "+ host);
		var options = 
		{
			timeout: 3,
			onSuccess: onConnect,
			onFailure: onFailure,
		};
	
        mqtt.onConnectionLost = onConnectionLost;
        mqtt.onMessageArrived = onMessageArrived;
		//mqtt.onConnected = onConnected;

		mqtt.connect(options);
		return false; 
	}
	
	//function sub topic
	function sub_topics()
	{
		document.getElementById("messages").innerHTML ="";
		if (connected_flag==0)
		{
			out_msg="<b>Not Connected so can't subscribe</b>"
			console.log(out_msg);
			document.getElementById("messages").innerHTML = out_msg;
			return false;
		}
		var stopic= "+/listrikiot/group1/123";
		console.log("Subscribing to topic = "+stopic);
		mqtt.subscribe(stopic);
		return false;
	}
	</script>
  </body>
</html>