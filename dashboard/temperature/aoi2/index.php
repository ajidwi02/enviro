<?php
include '../../../config_temp_humid.php';

$select_data = "SELECT * FROM realtime_th WHERE factory = 'AOI2' ORDER BY sequence ASC";
$result_data = mysqli_query($conn, $select_data);

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
   
    <title>Dashboard Temperature & Humidity</title>
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
	<style>
		/* LED Indicator Styles */
		.led-indicator {
			width: 20px;
			height: 20px;
			border-radius: 50%;
			display: inline-block;
			margin: 0 5px;
			box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
			background-color: #999999;
			transition: all 0.3s ease;
		}

		.led-indicator.active {
			background-color: #ff0000;
			box-shadow: 0 0 15px rgba(255, 0, 0, 0.8), inset 0 0 5px rgba(255, 100, 100, 0.8);
		}

		.gauge-label-container {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
		}
	</style>
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
	timeout = 30*60*1000;
	setTimeout(() => {
		document.location.reload();
	}, timeout);
	
	//define gauge
	gauge_temp = [];
	gauge_hum = [];
	
	// Store standard values for red zone detection
	standard_values = {};

	// Function to check if temperature is in red zone
	function isTempInRedZone(temp_val, id_loc) {
		var std = standard_values[id_loc];
		if (!std) return false;
		// Red zone: below max_red_temp or above max_yellow2_temp
		return (temp_val < std.max_red_temp || temp_val > std.max_yellow2_temp);
	}

	// Function to check if humidity is in red zone
	function isHumInRedZone(hum_val, id_loc) {
		var std = standard_values[id_loc];
		if (!std) return false;
		// Red zone: above max_yellow2_hum
		return (hum_val > std.max_yellow2_hum);
	}

	// Function to update LED indicator
	function updateLED(element_id, is_in_red_zone) {
		var led_elem = document.getElementById(element_id);
		if (!led_elem) return;
		if (is_in_red_zone) {
			led_elem.classList.add('active');
		} else {
			led_elem.classList.remove('active');
		}
	}
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
						<h2 class="page-title mb-0">Temperature and Humidity -AOI2-</h2>
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
		<div class="row my-3">
			<!-- Start Card-->
			<?php while($row_data = mysqli_fetch_assoc($result_data))
					{ 
						$temp = $row_data['temp'];
						$humidity = $row_data['humidity'];
						$location = $row_data['location'];
						$id_location = $row_data['id_location'];
						
						//query standard value gauge
						$select_standard = "SELECT * FROM master_standard WHERE id_location = '$id_location' ";
						$result_standard = mysqli_query($conn, $select_standard);
						$row_standard = mysqli_fetch_assoc($result_standard);
						
						//query chart temperature-> call temperature
						$select_chart_temp = "SELECT temp FROM chart_th WHERE id_location = '$id_location' ";
						$result_chart_temp = mysqli_query($conn, $select_chart_temp);
						//query chart temperature-> call date
						$select_chart_temp_date = "SELECT date_only FROM chart_th WHERE id_location = '$id_location' ";
						$result_chart_temp_date = mysqli_query($conn, $select_chart_temp_date);
						
						//query chart humidity-> call humidity
						$select_chart_hum = "SELECT humidity FROM chart_th WHERE id_location = '$id_location' ";
						$result_chart_hum = mysqli_query($conn, $select_chart_hum);
						//query chart humidity--> call date
						$select_chart_hum_date = "SELECT date_only FROM chart_th WHERE id_location = '$id_location' ";
						$result_chart_hum_date = mysqli_query($conn, $select_chart_hum_date);
						
						//query min and max value temperature chart
						$select_m_temp = "SELECT MIN(temp) AS min_temp, MAX(temp) AS max_temp FROM chart_th WHERE id_location = '$id_location'";
						$result_m_temp = mysqli_query($conn, $select_m_temp);
						$row_m_temp = mysqli_fetch_assoc($result_m_temp);
						
						//query min and max value humidity chart
						$select_m_hum = "SELECT MIN(humidity) AS min_hum, MAX(humidity) AS max_hum FROM chart_th WHERE id_location = '$id_location'";
						$result_m_hum = mysqli_query($conn, $select_m_hum);
						$row_m_hum = mysqli_fetch_assoc($result_m_hum);
						
						?>
						<div class="col-md-3">
							<div class="card shadow eq-card mb-4">
								<div class="card-body">
									<div class="card-title">
										<strong><?php echo $location ;?></strong>
										<a class="float-right small text-muted" href="#!"></a>
									</div>
									<div class="row mt-b">
										<div class="col-6 text-center mb-3 border-right" style="padding:0;">
										<div class="gauge-label-container">
											<p class="text-muted mb-0">Temperature</p>
											<span class="led-indicator" id="led_temp_<?php echo $id_location ;?>"></span>
										</div>
										<div id="gauge_temp_<?php echo $id_location ;?>"></div>
										
									</div>
									<div class="col-6 text-center mb-3">
										<div class="gauge-label-container">
											<p class="text-muted mb-0">Humidity</p>
											<span class="led-indicator" id="led_hum_<?php echo $id_location ;?>"></span>
										</div>
											<div id="gauge_hum_<?php echo $id_location ;?>"></div>
											
										</div>
									</div>
								</div> <!-- .card-body -->
							</div> <!-- .card -->
						</div> <!-- .col -->
						
						<!-- Gauge Temperature -->
						<script>
						// Store standard values globally
						standard_values[<?php echo $id_location ;?>] = {
							'min_value_temp': <?php echo floatval($row_standard['min_value_temp']); ?>,
							'max_red_temp': <?php echo floatval($row_standard['max_red_temp']); ?>,
							'max_yellow1_temp': <?php echo floatval($row_standard['max_yellow1_temp']); ?>,
							'max_green_temp': <?php echo floatval($row_standard['max_green_temp']); ?>,
							'max_yellow2_temp': <?php echo floatval($row_standard['max_yellow2_temp']); ?>,
							'max_value_temp': <?php echo floatval($row_standard['max_value_temp']); ?>,
							'min_value_hum': <?php echo floatval($row_standard['min_value_hum']); ?>,
							'max_green_hum': <?php echo floatval($row_standard['max_green_hum']); ?>,
							'max_yellow2_hum': <?php echo floatval($row_standard['max_yellow2_hum']); ?>,
							'max_value_hum': <?php echo floatval($row_standard['max_value_hum']); ?>
						};

						let temp_now_<?php echo $id_location ;?> = <?php echo $temp ;?>;
						// Update LED on initial load
						updateLED('led_temp_<?php echo $id_location ;?>', isTempInRedZone(temp_now_<?php echo $id_location ;?>, <?php echo $id_location ;?>));
						
						$('#gauge_temp_<?php echo $id_location ;?>').highcharts( 
						{
							chart: 
							{
								type: 'gauge',
								plotBackgroundColor: null,
								plotBackgroundImage: null,
								plotBorderWidth: 0,
								plotShadow: false,
								height: '73%'
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
								min: 10,
								max: 40,
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
										fontSize: '8px'
									}
								},
								plotBands: [
								{
									from: <?php echo $row_standard['min_value_temp'];?>,
									to: <?php echo $row_standard['max_red_temp'];?>,
									color: 'red', // red
									thickness: 20
								}, 
								{
									from: <?php echo $row_standard['max_red_temp'];?>,
									to: <?php echo $row_standard['max_yellow1_temp'];?>,
									color: 'yellow', // yellow
									thickness: 20
								}, 
								{
									from: <?php echo $row_standard['max_yellow1_temp'];?>,
									to: <?php echo $row_standard['max_green_temp'];?>,
									color: 'green', // green
									thickness: 20
								}, 
								{
									from: <?php echo $row_standard['max_green_temp'];?>,
									to: <?php echo $row_standard['max_yellow2_temp'];?>,
									color: 'yellow', // yellow
									thickness: 20
								}, 
								{
									from: <?php echo $row_standard['max_yellow2_temp'];?>,
									to: <?php echo $row_standard['max_value_temp'];?>,
									color: 'red', // red
									thickness: 20
								}]
							},

							series: [
							{
								name: 'Temperature',
								data: [temp_now_<?php echo $id_location ;?>],
								tooltip: 
								{
									valueSuffix: ' &#8451;'
								},
								dataLabels: 
								{
									format: '{y} &#8451;',
									borderWidth: 0,
									color: '#333333',
									style: 
									{
										fontSize: '18px'
									},
									zIndex: 3
								},
								dial: 
								{
									radius: '110%',
									backgroundColor: 'black',
									borderColor: 'white',
									borderWidth: 1,
									baseWidth: 7,
									baseLength: '5%',
									rearLength: '-10%',
									zIndex: 2
								},
								pivot: 
								{
									backgroundColor: 'black',
									borderColor: 'white',
									borderWidth: 1,
									radius: 0,
									zIndex: 1
								}
							}]
						});
						
						gauge_temp[<?php echo $id_location ;?>] = function (newvalue)
						{
							var temp_now = $('#gauge_temp_<?php echo $id_location ;?>').highcharts().series[0].points[0];
							temp_now.update(newvalue);
							// Update LED indicator
							updateLED('led_temp_<?php echo $id_location ;?>', isTempInRedZone(newvalue, <?php echo $id_location ;?>));
						}; 
						</script>
						
						<!-- Gauge Humidity -->
						<script>
						let hum_now_<?php echo $id_location ;?> = <?php echo $humidity ;?>;
						// Update LED on initial load
						updateLED('led_hum_<?php echo $id_location ;?>', isHumInRedZone(hum_now_<?php echo $id_location ;?>, <?php echo $id_location ;?>));
						
						$('#gauge_hum_<?php echo $id_location ;?>').highcharts( 
						{
							chart: 
							{
								type: 'gauge',
								plotBackgroundColor: null,
								plotBackgroundImage: null,
								plotBorderWidth: 0,
								plotShadow: false,
								height: '88%'
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
								min: 30,
								max: 100,
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
										fontSize: '8px'
									}
								},
								plotBands: [
								// {
									// from: <?php echo $row_standard['min_value_hum'];?>,
									// to: <?php echo $row_standard['max_red_hum'];?>,
									// color: 'red', // green
									// thickness: 20
								// }, 
								// {
									// from: <?php echo $row_standard['max_red_hum'];?>,
									// to: <?php echo $row_standard['max_yellow1_hum'];?>,
									// color: 'yellow', // yellow
									// thickness: 20
								// }, 
								{
									from: <?php echo $row_standard['min_value_hum'];?>,
									to: <?php echo $row_standard['max_green_hum'];?>,
									color: 'green', // red
									thickness: 20
								}, 
								{
									from: <?php echo $row_standard['max_green_hum'];?>,
									to: <?php echo $row_standard['max_yellow2_hum'];?>,
									color: 'yellow', // red
									thickness: 20
								}, 
								{
									from: <?php echo $row_standard['max_yellow2_hum'];?>,
									to: <?php echo $row_standard['max_value_hum'];?>,
									color: 'red', // red
									thickness: 20
								}]
							},

							series: [
							{
								name: 'Humidity',
								data: [hum_now_<?php echo $id_location ;?>],
								tooltip: 
								{
									valueSuffix: ' %'
								},
								dataLabels: 
								{
									format: '{y} %',
									borderWidth: 0,
									color: '#333333',
									style: 
									{
										fontSize: '18px'
									},
									zIndex: 3
								},
								dial: 
								{
									radius: '110%',
									backgroundColor: 'black',
									borderColor: 'white',
									borderWidth: 1,
									baseWidth: 7,
									baseLength: '5%',
									rearLength: '-10%',
									zIndex: 2
								},
								pivot: 
								{
									backgroundColor: 'black',
									borderColor: 'white',
									borderWidth: 1,
									radius: 0,
									zIndex: 1
								}
							}]
						});
						

						gauge_hum[<?php echo $id_location ;?>] = function (newvalue)
						{
							var hum_now = $('#gauge_hum_<?php echo $id_location ;?>').highcharts().series[0].points[0];
							hum_now.update(newvalue);
							// Update LED indicator
							updateLED('led_hum_<?php echo $id_location ;?>', isHumInRedZone(newvalue, <?php echo $id_location ;?>));
						}; 
						</script>
			<?php	} ?>
			<!-- End Card -->
		
			<!-- Start Footer -->
			<div class="w-100 mx-auto text-center" style="width:100%; position: fixed;bottom: 0; display:none;">	
				<div id="status">Connection Status: Not Connected</div>
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
		window.location.reload();
	}
	function onFailure(message) 
	{
		console.log("Failed");
		document.getElementById("messages").innerHTML = "MQTT Connection Failed- Retrying";
        setTimeout(MQTTconnect, reconnectTimeout);
    }
	function onMessageArrived(r_message)
	{		
		const parse_mess_all = JSON.parse(r_message.payloadString);
		const mess_payload = parse_mess_all.payload;
		
		const string_mess_payload = JSON.stringify(mess_payload);
		const parse_mess_payload = JSON.parse(string_mess_payload);
		
		var_publish = parse_mess_payload.kirim_publish;
		
		const array_publish = var_publish.split(",");
		
		let i = 0;
		let text = "";
		let content_text = "";
		while (array_publish[i]) 
		{
			text = array_publish[i]+"<br>";

			text_mess = array_publish[i];
			const array_content = text_mess.split("_");

			var content_loc = array_content[0];
			var content_temp = array_content[1];
			var content_hum = array_content[2];
			
			content_text += "Loc:"+content_loc+" temp:"+content_temp+" hum:"+content_hum+" - ";
			
			var id_temp = "btn_temp";
			var id_hum = "btn_hum";
			
			var val_temp = Math.round(parseFloat(content_temp)* 10) / 10;
			var val_hum = Math.round(parseFloat(content_hum)* 10) / 10;
			
			gauge_temp[content_loc](val_temp);
			gauge_hum[content_loc](val_hum);
			
			i++;
		};
		
		const var_message = "Last Transaction is: "+content_text;
		document.getElementById("messages").innerHTML = var_message;

	}
	function onConnected(recon,url)
	{
		console.log(" in onConnected " +reconn);
	}
	
	function onConnect() 
	{
		document.getElementById("messages").innerHTML ="Connected to "+host +" on port "+port;
		connected_flag=1
		document.getElementById("status").innerHTML = "Connected";
		console.log("on Connect "+connected_flag);
		
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
		var cname="enviro_Dashboard_AOI2_"+x;
		mqtt = new Paho.MQTT.Client(host,port,cname);
		var options = 
		{
			timeout: 3,
			onSuccess: onConnect,
			onFailure: onFailure,
		};
	
        mqtt.onConnectionLost = onConnectionLost;
        mqtt.onMessageArrived = onMessageArrived;

		mqtt.connect(options);
		return false; 
	}
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
		var stopic= "+/enviro/aoi2/temp_humid";
		console.log("Subscribing to topic = "+stopic);
		mqtt.subscribe(stopic);
		return false;
	}

	</script>
  </body>
</html>