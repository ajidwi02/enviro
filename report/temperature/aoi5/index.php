<?php
	include '../../../config_temp_humid.php';

	if(isset($_GET["submit"]))
	{
		$aRanges = explode(' to ', $_GET['range']);
		$range1 = $aRanges[0];
		$range2 = isset($aRanges[1]) ? $aRanges[1] : $range1;
		$from = date("d M Y", strtotime($range1));
		$to = date("d M Y", strtotime($range2));

		$safe_range1 = mysqli_real_escape_string($conn, $range1);
		$safe_range2 = mysqli_real_escape_string($conn, $range2);

		$report = "SELECT t.id_transaction, t.id_location, t.temp, t.humidity, t.record_time, 
		                  DATE_FORMAT(t.record_time, '%W') AS day_name,
		                  DATE(t.record_time) AS date_only,
		                  w.weather_status,
		                  w.temp_outdoor
		           FROM temp_humid t
		           LEFT JOIN weather_aoi5 w 
		               ON w.id_transaction = CONCAT('AOI5_WTR_', DATE_FORMAT(t.record_time, '%Y%m%d_%H'), '0000')
		           WHERE (t.id_location IN ('501', '502', 'area501', 'area502', 'Area_1', 'Area_2', 'temp1', 'temp2')) 
		             AND DATE(t.record_time) BETWEEN '$safe_range1' AND '$safe_range2' 
		           ORDER BY t.record_time DESC";
		$result_report = mysqli_query($conn, $report);

	} else
	{
		$report = "SELECT t.id_transaction, t.id_location, t.temp, t.humidity, t.record_time, 
		                  DATE_FORMAT(t.record_time, '%W') AS day_name,
		                  DATE(t.record_time) AS date_only,
		                  w.weather_status,
		                  w.temp_outdoor
		           FROM temp_humid t
		           LEFT JOIN weather_aoi5 w 
		               ON w.id_transaction = CONCAT('AOI5_WTR_', DATE_FORMAT(t.record_time, '%Y%m%d_%H'), '0000')
		           WHERE (t.id_location IN ('501', '502', 'area501', 'area502', 'Area_1', 'Area_2', 'temp1', 'temp2')) 
		             AND t.record_time >= NOW() - INTERVAL 1 WEEK 
		           ORDER BY t.record_time DESC";
		$result_report = mysqli_query($conn, $report);
	}

	// 1. Koleksi Seluruh Record untuk Ekspor ExcelJS & Grafik
	$report_rows = array();
	if ($result_report && mysqli_num_rows($result_report) > 0) {
		while ($r = mysqli_fetch_assoc($result_report)) {
			$report_rows[] = $r;
		}
		mysqli_data_seek($result_report, 0); // Kembalikan pointer untuk perulangan tabel HTML di bawah
	}

	// 2. Siapkan Logo AOI Base64 untuk Disematkan ke Header Excel
	$logo_path = __DIR__ . '/../../../logo_aoi.png';
	$logo_base64 = '';
	if (file_exists($logo_path)) {
		$logo_base64 = base64_encode(file_get_contents($logo_path));
	}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../../favicon.ico">
    <title>Temperature and Humidity Monitoring AOI 5</title>
    <!-- Simple bar CSS -->
    <link rel="stylesheet" href="../../../css/simplebar.css">
    <!-- Fonts CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100;0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Icons CSS -->
    <link rel="stylesheet" href="../../../css/feather.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="../../../css/app-light.css" id="lightTheme">
	<link rel="stylesheet" href="../../../css/daterangepicker.css">

	<script src="../../../js/jquery.min.js"></script>
	<script src="../../../js/bootstrap.min.js"></script>
	<!-- datatable -->
	<link href='../../../css/datatable.min.css' rel='stylesheet' type='text/css'>
    <link href='../../../css/buttons.datatable.min.css' rel='stylesheet' type='text/css'>
	<style type="text/css">
            .dt-buttons{
                width: 100%;
                margin-bottom: 12px;
            }
            .btn-excel-custom {
                background-color: #107c41 !important;
                border-color: #107c41 !important;
                color: #ffffff !important;
                font-weight: 600 !important;
                transition: all 0.2s ease-in-out;
            }
            .btn-excel-custom:hover {
                background-color: #0c5c30 !important;
                border-color: #0c5c30 !important;
                color: #ffffff !important;
                box-shadow: 0 2px 8px rgba(16, 124, 65, 0.4);
            }
            .badge-safe {
                background-color: #d1fae5;
                color: #065f46;
                font-weight: 600;
                padding: 4px 8px;
                border-radius: 4px;
            }
            .badge-alert {
                background-color: #fee2e2;
                color: #991b1b;
                font-weight: 700;
                padding: 4px 8px;
                border-radius: 4px;
            }
    </style>
	<script src="../../../js/jquery.dataTables.min.js"></script>
	<script src="../../../js/datatables.buttons.min.js"></script>
	<script src="../../../js/jszip.min.js"></script>
	<script src="../../../js/buttons.html5.min.js"></script>

	<!-- ExcelJS & Mesin Ekspor Multi-Granularity Charts -->
	<script src="../../../js/exceljs.min.js"></script>
	<script src="export_excel_charts.js"></script>

	<script>
        var REPORT_DATA = <?php echo json_encode($report_rows); ?>;
        var AOI_LOGO_BASE64 = "<?php echo $logo_base64; ?>";
        var REPORT_FILTER_INFO = {
            range_text: "<?php echo isset($_GET['range']) ? addslashes($_GET['range']) : 'Last 1 Week'; ?>",
            from: "<?php echo isset($from) ? addslashes($from) : date('d M Y', strtotime('-1 week')); ?>",
            to: "<?php echo isset($to) ? addslashes($to) : date('d M Y'); ?>"
        };

        $(document).ready(function(){
            var empDataTable = $('#dataTable-2').DataTable({
                dom: 'Blfrtip',
				lengthMenu: [100, 500, 1000],
                buttons: [
                    {
                        extend: 'csv',
                        text: '<i class="fe fe-file-text mr-1"></i> CSV'
                    },
                    {
                        text: '<i class="fe fe-download mr-1"></i> Excel (+ 4 Grafik Tren)',
                        className: 'btn-excel-custom',
                        attr: {
                            id: 'btnExportExcelChart',
                            title: 'Ekspor Excel Multi-Sheet Lengkap dengan 4 Grafik Tren & Batas Acuan (30°C / 60%)'
                        },
                        action: function (e, dt, node, config) {
                            exportExcelWithCharts(dt);
                        }
                    }
                ] 
            });

			empDataTable.on('order.dt search.dt', function () {
				let i = 1;
				empDataTable.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
					this.data(i++);
				});
			}).draw();

        });
    </script>

  </head>
  <body class="vertical  dark">
    <div class="wrapper">
	  <!-- Start Nav Bar -->
      <nav class="topnav navbar navbar-light">
		  <span class="avatar avatar-sm mt-2">

          </span>
      </nav>
	  <!-- End Nav Bar -->

	  <!-- Start Side Bar -->
      <aside class="sidebar-left border-right bg-white shadow" id="leftSidebar" data-simplebar>
		<a href="#" class="btn collapseSidebar toggle-btn d-lg-none text-muted ml-2 mt-3" data-toggle="toggle">
		  <i class="fe fe-x"><span class="sr-only"></span></i>
		</a>
		<nav class="vertnav navbar navbar-light">
		  <!-- Start Logo -->
		  <div class="w-100 mb-3 text-center d-flex flex-column align-items-center justify-content-center pt-2 pb-2">
			<a href="../../../index.php" class="d-inline-flex flex-column align-items-center text-decoration-none">
			  <img src="../../../logo_aoi.png?v=<?= filemtime(__DIR__ . '/../../../logo_aoi.png') ?>" alt="Logo AOI" class="img-fluid" style="max-height: 95px; width: auto; object-fit: contain;">
			</a>
		  </div>
		  <!-- End Logo -->

		  <!-- Start Menu Bar -->
		  <p class="text-muted nav-heading mt-4 mb-1">
			<span>Menu</span>
		  </p>

			<!-- Start Menu -->
			<ul class="navbar-nav flex-fill w-100 mb-2">
			<li class="nav-item dropdown">
			  <a href="#report" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-edit fe-16"></i>
				<span class="ml-3 item-text">Menu</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id="report">
				<!-- Start Sub Menu -->
					<li class="nav-item">
					  <a class="nav-link pl-3" href="../../../index.php"><span class="ml-1 item-text">Menu Utama</span></a>
					</li>
					<li class="nav-item">
					  <a class="nav-link pl-3" href="../../../dashboard/temperature/aoi5/"><span class="ml-1 item-text">Back to dashboard</span></a>
					</li>
				<!-- End Sub Menu -->
			  </ul>
			</li>
			</ul>
			<!-- End Menu -->


		  <!-- End Menu Bar -->
		</nav>
	</aside>
	  <!-- End Side Bar -->

	  <!-- Start Content -->
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="row justify-content-center">
            <div class="col-12">
              <div class="row align-items-center mb-2">
                <div class="col">
                  <h2 class="h5 page-title">Report Temperature and Humidity AOI 5 <?php if(isset($_GET["submit"])){echo " from ".$from." to ".$to;}; ?></h2>
                </div>
              </div>
              <div class="mb-2 align-items-center">
                <div class="card shadow mb-4">
                  <div class="card-body">

					<!-- Start Data -->
					<div class="row">
                    <div class="col-md-12">

						<div class="form-group">
							<form method="GET" action="">
								<div class="input-group mb-3">
									<input id="reportrange" name="range" type="text" placeholder="Select Range" class="form-control" aria-describedby="button-addon2">
									<div class="input-group-append">
									  <input class="btn btn-primary" type="submit" id="submit" name="submit" value="Filter Range Tanggal">
									</div>
									<span></span>
								</div>
							</form>
						</div>

						<!-- Banner Informasi Standar Acuan Aman -->
						<div class="alert alert-info py-2 px-3 d-flex align-items-center justify-content-between mb-3 shadow-sm" style="font-size: 0.9rem; border-left: 4px solid #107c41;">
							<div>
								<i class="fe fe-shield text-success mr-2 fe-16"></i>
								<strong>Batas Acuan Aman Standar Pabrik:</strong> 
								Suhu Maksimum <span class="badge badge-light text-danger font-weight-bold ml-1">30.0 °C</span> &bull; 
								Kelembapan Maksimum <span class="badge badge-light text-danger font-weight-bold ml-1">60.0 %</span>
							</div>
							<div class="small text-muted d-none d-md-block">
								<i class="fe fe-check-circle text-success mr-1"></i>Ekspor Excel otomatis menyusun <strong>4 Grafik Visual Multi-Granularitas</strong> (Per Jam, Hari, Minggu, Bulan).
							</div>
						</div>

						<!-- Start Table -->
						<table class="table datatables" id="dataTable-2">
							<thead>
							  <tr>
								<th>No</th>
								<th>Factory</th>
								<th>Id Location</th>
								<th>Location</th>
								<th>Temperature (C)</th>
								<th>Humidity (%)</th>
								<th>Weather</th>
								<th>Record Time</th>
								<th>Day Name</th>
							  </tr>
							</thead>
							<tbody>
							<?php while($row_report = mysqli_fetch_assoc($result_report))
									{	$factory = 'AOI 5';
										$id_location = $row_report['id_location'];
										$loc_lower = strtolower($id_location);
										if ($loc_lower == '501' || $loc_lower == 'temp1' || $loc_lower == 'area501' || $loc_lower == 'area_1' || $loc_lower == 'area1') {
											$location_name = 'Folding Area';
										} elseif ($loc_lower == '502' || $loc_lower == 'temp2' || $loc_lower == 'area502' || $loc_lower == 'area_2' || $loc_lower == 'area2') {
											$location_name = 'Dry Room';
										} else {
											$location_name = 'Sensor ' . $id_location;
										}
										$Temp = $row_report['temp'];
										$humi = $row_report['humidity'];
										$record_time = $row_report['record_time'];
										$dayname = $row_report['day_name'];
										$weather = !empty($row_report['weather_status']) ? $row_report['weather_status'] : '-';

										$temp_num = is_numeric($Temp) ? floatval($Temp) : 0.0;
										$humi_num = is_numeric($humi) ? floatval($humi) : 0.0;
										?>
										<tr>
											<td></td>
											<td><?php echo $factory;?></td>
											<td><?php echo $id_location;?></td>
											<td><?php echo $location_name;?></td>
											<td class="<?php echo ($temp_num > 30.0) ? 'text-danger font-weight-bold' : ''; ?>"><?php echo $Temp;?></td>
											<td class="<?php echo ($humi_num > 60.0) ? 'text-danger font-weight-bold' : ''; ?>"><?php echo $humi ;?></td>
											<td><?php echo $weather ;?></td>
											<td><?php echo $record_time ;?></td>
											<td><?php echo $dayname ;?></td>
										</tr>
							<?php 	};?>
							</tbody>
						</table>
						<!-- End Table -->

                    </div> <!-- /.col -->
					</div>
                    <!-- End Data -->

                  </div> <!-- .card-body -->
                </div> <!-- .card -->
              </div>
            </div> <!-- .col-12 -->
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
      </main> <!-- main -->
	  <!-- End Content -->
    </div> <!-- .wrapper -->

	<script src="../../../js/moment.min.js"></script>
	<script src="../../../js/popper.min.js"></script>
	<script src="../../../js/config.js"></script>
    <script src="../../../js/apps.js"></script>
	<script src='../../../js/daterangepicker.js'></script>

	<script>
      var start = <?php echo isset($range1) ? "moment('" . addslashes($range1) . "')" : "moment().subtract(6, 'days')"; ?>;
      var end = <?php echo isset($range2) ? "moment('" . addslashes($range2) . "')" : "moment()"; ?>;

      $('#reportrange').daterangepicker(
      {
		locale: {
            format: 'YYYY-MM-DD',
			separator: " to "
        },
        startDate: start,
        endDate: end,
        ranges:
        {
          'Today': [moment(), moment()],
          'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
      });

	</script>
  </body>
</html>
