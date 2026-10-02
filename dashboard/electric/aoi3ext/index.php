<?php
include '../../../config_electricity.php';

//standard power
$select_standard_power = "SELECT * FROM standard_power WHERE id_location = '30101'";
$result_standard_power = mysqli_query($mysqli, $select_standard_power);
$row_standard_power = mysqli_fetch_assoc($result_standard_power);
$min_power = $row_standard_power['min_value'];
$green_power = $row_standard_power['max_green'];
$yellow_power = $row_standard_power['max_yellow'];
$red_power = $row_standard_power['max_red'];

//standard voltage
$select_standard_voltage = "SELECT * FROM standard_voltage WHERE id_location = '30101'";
$result_standard_voltage = mysqli_query($mysqli, $select_standard_voltage);
$row_standard_voltage = mysqli_fetch_assoc($result_standard_voltage);
$min_voltage = $row_standard_voltage['min_value'];
$red_voltage1 = $row_standard_voltage['max_red1'];
$yellow_voltage1 = $row_standard_voltage['max_yellow1'];
$green_voltage = $row_standard_voltage['max_green'];
$yellow_voltage2 = $row_standard_voltage['max_yellow2'];
$red_voltage2 = $row_standard_voltage['max_red2'];

//standard current percentage
$select_standard_current = "SELECT * FROM standard_current_percent WHERE id_location = '30101'";
$result_standard_current = mysqli_query($mysqli, $select_standard_current);
$row_standard_current = mysqli_fetch_assoc($result_standard_current);
$min_current = $row_standard_current['min_value'];
$green_current = $row_standard_current['max_green'];
$yellow_current = $row_standard_current['max_yellow'];
$red_current = $row_standard_current['max_red'];

//standard power factor
$select_standard_power_factor = "SELECT * FROM standard_power_factor WHERE id_location = '30101'";
$result_standard_power_factor = mysqli_query($mysqli, $select_standard_power_factor);
$row_standard_power_factor = mysqli_fetch_assoc($result_standard_power_factor);
$min_power_factor = $row_standard_power_factor['min_value'];
$green_power_factor = $row_standard_power_factor['max_green'];
$yellow_power_factor = $row_standard_power_factor['max_yellow'];
$red_power_factor = $row_standard_power_factor['max_red'];

//select electricity 30101
$select_electricity = "Select power, power_factor, voltage_average, current_average from electricity Where id_location = '30101' AND (cast(datenow as date) = (cast(now() as date))) Order By datenow DESC LIMIT 1";
$result_electricity = mysqli_query($mysqli, $select_electricity);
$row_electricity = mysqli_fetch_assoc($result_electricity);
$power = round(abs($row_electricity['power']));
$power_factor = round(abs($row_electricity['power_factor']*100));
$voltage_average = round(abs($row_electricity['voltage_average']));
$current_average = round(abs($row_electricity['current_average']));

$cap_current = 4000;
$current_percent = $current_average/$cap_current*100;

//select chart power date name
$select_date_name = "SELECT DISTINCT date_name from chart_aoi3ext";
$result_date_name = mysqli_query($mysqli, $select_date_name);

$select_date_name2 = "SELECT DISTINCT date_name from chart_aoi3ext";
$result_date_name2 = mysqli_query($mysqli, $select_date_name2);

$select_date_name3 = "SELECT DISTINCT date_name from chart_aoi3ext";
$result_date_name3 = mysqli_query($mysqli, $select_date_name3);

$select_date_name4 = "SELECT DISTINCT date_name from chart_aoi3ext";
$result_date_name4 = mysqli_query($mysqli, $select_date_name4);

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
   
    <title>Dashboard Electricity</title>
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
	
	//refresh page after 15 Minutes
	timeout = 15*60*1000;
	setTimeout(() => {
		document.location.reload();
	}, timeout);
	
	//define gauge
	gauge_power = [];
	gauge_voltage = [];
	gauge_current = [];
	gauge_power_factor = [];
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
						<h2 class="page-title mb-0">Electricity Dashboard -AOI3 Ext-</h2>
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
						<div class="col-md-4">
							<div class="mx-4">
								<strong class="mb-0 text-uppercase">Electricity Consumption</strong><br />
								<p class="text-muted">The amount of power that used by AOI3 Extension LVMDP in Kilowatt</p>
								<div id="gauge_power" style="padding:0;"></div>
							</div>
							<script>
								let power_now = <?php echo $power ;?>;
								$('#gauge_power').highcharts( 
								{
									chart: 
									{
										type: 'gauge',
										plotBackgroundColor: null,
										plotBackgroundImage: null,
										plotBorderWidth: 0,
										plotShadow: false,
										height: '30%'
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
										min: <?php echo $min_power ;?>,
										max: <?php echo $red_power;?>,
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
											from: <?php echo $min_power ;?>,
											to: <?php echo $green_power ;?>,
											color: 'green', // green
											thickness: 20
										}, 
										{
											from: <?php echo $green_power;?>,
											to: <?php echo $yellow_power;?>,
											color: 'yellow', // yellow
											thickness: 20
										}, 
										{
											from: <?php echo $yellow_power;?>,
											to: <?php echo $red_power;?>,
											color: 'red', // red
											thickness: 20
										}]
									},

									series: [
									{
										name: 'Active Power',
										data: [power_now],
										tooltip: 
										{
											valueSuffix: 'Power (KW)'
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
								
								gauge_power = function (newvalue)
								{
									var power_now = $('#gauge_power').highcharts().series[0].points[0];
									power_now.update(newvalue);
								}; 
							</script>
							
						</div>
						<div class="col-md-8" style="padding:0;">
							<div class="mr-4">
								<div id="chart_power"></div>								
							</div>
							<script>
							Highcharts.chart('chart_power', 
							{
								chart: {
									type: 'spline',
									height: (5 / 30 * 100) + '%', // ratio
									events: 
									{
										load: function() 
										{
											const chart = this,
											data = chart.series[0].data;

											data.map((element) => 
											{
												if (element.y <= <?php echo $green_power ;?>) 
												{
													element.update(
													{
														marker: 
														{
															fillColor: 'green'
														}
													})
												} else
												if (element.y <= <?php echo $yellow_power ;?>) 
												{
													element.update(
													{
														marker: 
														{
															fillColor: 'yellow'
														}
													})
												} else 
												{
													element.update(
													{
														marker: 
														{
															fillColor: 'red'
														}
													})
												}
											});
										}
									}
								},
								title: 
								{
									text: 'AOI3 ext Consumption (Kw)',
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
										while ($row_power_date_name = mysqli_fetch_assoc($result_date_name))
											{
												$chart_power_date = $row_power_date_name['date_name'];
												echo "{name:'".$chart_power_date."',";
												
												$select_power_time = "SELECT time_name FROM chart_aoi3ext WHERE date_name = '$chart_power_date' ";
												$result_power_time = mysqli_query($mysqli, $select_power_time);
												
												echo "categories:[";
												
												while ($row_power_time = mysqli_fetch_assoc($result_power_time))
												{
													$time_name_power = $row_power_time['time_name'];
													echo $time_name_power.",";
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
									enabled: false,
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
									minorTickInterval: 0,
									title: 
									{
										text: 'Power (Kw)'
									},
									style: 
									{
										fontSize: '12px' 
									}
								},
								series: [
								{
									name: 'AOI3 Ext Consumption',
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
										$select_chart_power = "SELECT power FROM chart_aoi3ext";
										$query_chart_power = mysqli_query($mysqli, $select_chart_power);
										while ($row_chart_power = mysqli_fetch_assoc($query_chart_power))
											{
												$chart_power = $row_chart_power ['power'];
												echo round(abs($chart_power),0).",";
											};
										?>
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
									}
								}
							});
						</script>
						</div> <!-- .col-md-8 -->
					</div>
					<hr>
					<div class="row align-items-center my-4">						
						<div class="col-md-4" style="padding:1;">
							<!-- CO2 Emission -->
							<div class="mx-4">
								<strong class="mb-0 text-uppercase">Voltage</strong><br />
								<p class="text-muted">The voltage value of AOI3 Extension LVMDP</p>
								<div id="gauge_voltage" style="padding:0;"></div>
							</div>
							<script>
								let voltage_now = <?php echo $voltage_average ;?>;
								$('#gauge_voltage').highcharts( 
								{
									chart: 
									{
										type: 'gauge',
										plotBackgroundColor: null,
										plotBackgroundImage: null,
										plotBorderWidth: 0,
										plotShadow: false,
										height: '30%'
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
										min: <?php echo $min_voltage ;?>,
										max: <?php echo $red_voltage2;?>,
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
											from: <?php echo $min_voltage ;?>,
											to: <?php echo $red_voltage1 ;?>,
											color: 'red', // red1
											thickness: 20
										}, 
										{
											from: <?php echo $red_voltage1;?>,
											to: <?php echo $yellow_voltage1;?>,
											color: 'yellow', // yellow1
											thickness: 20
										}, 
										{
											from: <?php echo $yellow_voltage1;?>,
											to: <?php echo $green_voltage;?>,
											color: 'green', // green
											thickness: 20
										}, 
										{
											from: <?php echo $green_voltage;?>,
											to: <?php echo $yellow_voltage2;?>,
											color: 'yellow', // yellow2
											thickness: 20
										}, 
										{
											from: <?php echo $yellow_voltage2;?>,
											to: <?php echo $red_voltage2;?>,
											color: 'red', // red2
											thickness: 20
										}]
									},

									series: [
									{
										name: 'Active Voltage',
										data: [voltage_now],
										tooltip: 
										{
											valueSuffix: ' V'
										},
										dataLabels: 
										{
											format: '{y} V',
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
								
								gauge_voltage = function (newvalue)
								{
									var voltage_now = $('#gauge_voltage').highcharts().series[0].points[0];
									voltage_now.update(newvalue);
								}; 
							</script>
							
						</div>
						<div class="col-md-8" style="padding:0;">
							<div class="mr-4">
								<div id="chart_voltage"></div>								
							</div>
							<script>
								Highcharts.chart('chart_voltage', 
								{
									chart: {
										type: 'spline',
										height: (5 / 30 * 100) + '%', // ratio
										events: 
										{
											load: function() 
											{
												const chart = this,
												data = chart.series[0].data;

												data.map((element) => 
												{
													if (element.y <= <?php echo $red_voltage1 ;?>) 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'red'
															}
														})
													} else
													if (element.y <= <?php echo $yellow_voltage1 ;?>) 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'yellow'
															}
														})
													} else 
													if (element.y <= <?php echo $green_voltage ;?>)
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'green'
															}
														})
													} else 
													if (element.y <= <?php echo $yellow_voltage2 ;?>)
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'yellow'
															}
														})
													} else 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'red'
															}
														})
													}
												});
											}
										}
									},
									title: 
									{
										text: 'AOI3 ext Voltage (V)',
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
											return 'V in ' + this.x +
												'</b> is <b>' + this.y + '</b>';
										}
									},
									xAxis: 
									{
										categories:
										[
											<?php 
											while ($row_voltage_date_name = mysqli_fetch_assoc($result_date_name2))
												{
													$chart_voltage_date = $row_voltage_date_name['date_name'];
													echo "{name:'".$chart_voltage_date."',";
													
													$select_voltage_time = "SELECT time_name FROM chart_aoi3ext WHERE date_name = '$chart_voltage_date' ";
													$result_voltage_time = mysqli_query($mysqli, $select_voltage_time);
													
													echo "categories:[";
													
													while ($row_voltage_time = mysqli_fetch_assoc($result_voltage_time))
													{
														$time_name_voltage = $row_voltage_time['time_name'];
														echo $time_name_voltage.",";
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
										enabled: false,
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
										minorTickInterval: 0,
										title: 
										{
											text: 'Voltage (V)'
										},
										style: 
										{
											fontSize: '12px' 
										}
									},
									series: [
									{
										name: 'AOI3 Ext Voltage',
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
											$select_chart_voltage = "SELECT voltage_average FROM chart_aoi3ext";
											$query_chart_voltage = mysqli_query($mysqli, $select_chart_voltage);
											while ($row_chart_voltage = mysqli_fetch_assoc($query_chart_voltage))
												{
													$chart_voltage = $row_chart_voltage ['voltage_average'];
													echo round(abs($chart_voltage),0).",";
												};
											?>
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
										}
									}
								});
							</script>
						</div> <!-- .col-md-8 -->
					</div> 
					<hr>
					<div class="row align-items-center my-4">						
						<div class="col-md-4" style="padding:1;">
							<!-- CO2 Emission -->
							<div class="mx-4">
								<strong class="mb-0 text-uppercase">Current Usage</strong><br />
								<p class="text-muted">Comparison of current between actual and capacity of AOI3 Extension LVMDP in percenr (Actual Current / Capacity)</p>
								<div id="gauge_current" style="padding:0;"></div>
							</div>
							<script>
								let current_now = <?php echo $current_percent ;?>;
								$('#gauge_current').highcharts( 
								{
									chart: 
									{
										type: 'gauge',
										plotBackgroundColor: null,
										plotBackgroundImage: null,
										plotBorderWidth: 0,
										plotShadow: false,
										height: '30%'
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
										min: <?php echo $min_current ;?>,
										max: <?php echo $red_current;?>,
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
											from: <?php echo $min_current ;?>,
											to: <?php echo $green_current ;?>,
											color: 'green', // green
											thickness: 20
										}, 
										{
											from: <?php echo $green_current;?>,
											to: <?php echo $yellow_current;?>,
											color: 'yellow', // yellow
											thickness: 20
										}, 
										{
											from: <?php echo $yellow_current;?>,
											to: <?php echo $red_current;?>,
											color: 'red', // red
											thickness: 20
										}]
									},

									series: [
									{
										name: 'Current Percentage',
										data: [current_now],
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
								
								gauge_current = function (newvalue)
								{
									var current_now = $('#gauge_current').highcharts().series[0].points[0];
									current_now.update(newvalue);
								}; 
							</script>
							
						</div>
						<div class="col-md-8" style="padding:0;">
							<div class="mr-4">
								<div id="chart_current"></div>								
							</div>
							<script>
								Highcharts.chart('chart_current', 
								{
									chart: {
										type: 'spline',
										height: (5 / 30 * 100) + '%', // ratio
										events: 
										{
											load: function() 
											{
												const chart = this,
												data = chart.series[0].data;

												data.map((element) => 
												{
													if (element.y <= <?php echo $green_current ;?>) 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'green'
															}
														})
													} else
													if (element.y <= <?php echo $yellow_current ;?>) 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'yellow'
															}
														})
													} else 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'red'
															}
														})
													}
												});
											}
										}
									},
									title: 
									{
										text: 'AOI3 ext Current Usage % (Active Current / Capacity)',
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
											return 'Current Usage in ' + this.x +
												'</b> is <b>' + this.y + '</b>';
										}
									},
									xAxis: 
									{
										categories:
										[
											<?php 
											while ($row_current_date_name = mysqli_fetch_assoc($result_date_name3))
												{
													$chart_current_date = $row_current_date_name['date_name'];
													echo "{name:'".$chart_current_date."',";
													
													$select_current_time = "SELECT time_name FROM chart_aoi3ext WHERE date_name = '$chart_current_date' ";
													$result_current_time = mysqli_query($mysqli, $select_current_time);
													
													echo "categories:[";
													
													while ($row_current_time = mysqli_fetch_assoc($result_current_time))
													{
														$time_name_current = $row_current_time['time_name'];
														echo $time_name_current.",";
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
										enabled: false,
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
										minorTickInterval: 0,
										title: 
										{
											text: 'Current Usage (%)'
										},
										style: 
										{
											fontSize: '12px' 
										}
									},
									series: [
									{
										name: 'AOI3 Ext Current Usage',
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
											$select_chart_current = "SELECT current_average FROM chart_aoi3ext";
											$query_chart_current = mysqli_query($mysqli, $select_chart_current);
											while ($row_chart_current = mysqli_fetch_assoc($query_chart_current))
												{
													$chart_current = $row_chart_current ['current_average'];
													echo round(abs($chart_current/$cap_current*100),0).",";
												};
											?>
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
										}
									}
								});
							</script>
						</div> <!-- .col-md-8 -->
					</div> <!-- end section -->
					<hr>
					<div class="row align-items-center my-4">						
						<div class="col-md-4" style="padding:1;">
							<!-- CO2 Emission -->
							<div class="mx-4">
								<strong class="mb-0 text-uppercase">Power Factor</strong><br />
								<p class="text-muted">Comparison of power between actual and PLN electricity source of AOI3 Extension LVMDP in percenr (Actual Power / PLN Power)</p>
								<div id="gauge_power_factor" style="padding:0;"></div>
							</div>
							<script>
								let power_factor_now = <?php echo $power_factor ;?>;
								$('#gauge_power_factor').highcharts( 
								{
									chart: 
									{
										type: 'gauge',
										plotBackgroundColor: null,
										plotBackgroundImage: null,
										plotBorderWidth: 0,
										plotShadow: false,
										height: '30%'
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
										min: <?php echo $min_power_factor ;?>,
										max: <?php echo $green_power_factor;?>,
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
											from: <?php echo $min_power_factor ;?>,
											to: <?php echo $red_power_factor ;?>,
											color: 'red', // red
											thickness: 20
										}, 
										{
											from: <?php echo $red_power_factor;?>,
											to: <?php echo $yellow_power_factor;?>,
											color: 'yellow', // yellow
											thickness: 20
										}, 
										{
											from: <?php echo $yellow_power_factor;?>,
											to: <?php echo $green_power_factor;?>,
											color: 'green', // green
											thickness: 20
										}]
									},

									series: [
									{
										name: 'Power Factor',
										data: [power_factor_now],
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
								
								gauge_power_factor = function (newvalue)
								{
									var power_factor_now = $('#gauge_power_factor').highcharts().series[0].points[0];
									power_factor_now.update(newvalue);
								}; 
							</script>
							
						</div>
						<div class="col-md-8" style="padding:0;">
							<div class="mr-4">
								<div id="chart_power_factor"></div>								
							</div>
							<script>
								Highcharts.chart('chart_power_factor', 
								{
									chart: {
										type: 'spline',
										height: (5 / 30 * 100) + '%', // ratio
										events: 
										{
											load: function() 
											{
												const chart = this,
												data = chart.series[0].data;

												data.map((element) => 
												{
													if (element.y <= <?php echo $red_power_factor ;?>) 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'red'
															}
														})
													} else
													if (element.y <= <?php echo $yellow_power_factor ;?>) 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'yellow'
															}
														})
													} else 
													{
														element.update(
														{
															marker: 
															{
																fillColor: 'green'
															}
														})
													}
												});
											}
										}
									},
									title: 
									{
										text: 'AOI3 ext Power Factor',
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
											return 'Power Factor in ' + this.x +
												'</b> is <b>' + this.y + '</b>';
										}
									},
									xAxis: 
									{
										categories:
										[
											<?php 
											while ($row_power_factor_date_name = mysqli_fetch_assoc($result_date_name4))
												{
													$chart_power_factor_date = $row_power_factor_date_name['date_name'];
													echo "{name:'".$chart_power_factor_date."',";
													
													$select_power_factor_time = "SELECT time_name FROM chart_aoi3ext WHERE date_name = '$chart_power_factor_date' ";
													$result_power_factor_time = mysqli_query($mysqli, $select_power_factor_time);
													
													echo "categories:[";
													
													while ($row_power_factor_time = mysqli_fetch_assoc($result_power_factor_time))
													{
														$time_name_power_factor = $row_power_factor_time['time_name'];
														echo $time_name_power_factor.",";
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
										enabled: false,
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
										minorTickInterval: 0,
										title: 
										{
											text: 'Power Factor (%)'
										},
										style: 
										{
											fontSize: '12px' 
										}
									},
									series: [
									{
										name: 'AOI3 Ext Power Factor',
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
											$select_chart_power_factor = "SELECT power_factor FROM chart_aoi3ext";
											$query_chart_power_factor = mysqli_query($mysqli, $select_chart_power_factor);
											while ($row_chart_power_factor = mysqli_fetch_assoc($query_chart_power_factor))
												{
													$chart_power_factor = $row_chart_power_factor ['power_factor'];
													echo round(abs($chart_power_factor*100),0).",";
												};
											?>
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
										}
									}
								});
							</script>
						</div> <!-- .col-md-8 -->
					</div> <!-- end section -->
					
                </div> <!-- .card-body -->
            </div> <!-- .card -->			
			<!-- End gauge -->
			<!-- End Card -->
		
			<!-- Start Footer -->
			<div class="w-100 mx-auto text-center" style="width:100%; position: fixed;bottom: 0;display:none;">	
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
		
		var_content = parse_mess_payload.listrik;
		
		const array_content = var_content.split("_");

		var content_loc = array_content[0];
		var content_current = Math.round((array_content[1]/<?php echo $cap_current;?>)*100);
		var content_power_factor = (Math.round(array_content[2]* 100) / 100)*100;
		var content_voltage = Math.round(array_content[3]);
		var content_kwh = Math.round(array_content[4]);
		var content_kw = Math.round(array_content[5]);
		
		content_show = "Lokasi: "+content_loc+", Current: "+content_current+", Powerfactor: "+content_power_factor+", Vol: "+content_voltage+", KWH: "+content_kwh+", KW: "+content_kw
		
		gauge_power(content_kw);
		gauge_current(content_current);
		gauge_voltage(content_voltage);
		gauge_power_factor(content_power_factor);
		
		const var_message = "Last Transaction is: <br>" + content_show;
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
		var stopic= "+/listrikiot/group3/123";
		console.log("Subscribing to topic = "+stopic);
		mqtt.subscribe(stopic);
		return false;
	}
	</script>
  </body>
</html>