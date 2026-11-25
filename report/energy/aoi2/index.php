<?php
	include '../../../config_electricity.php';

	// Ambil daftar panel untuk dropdown filter (khusus AOI2)
	$query_panels = "SELECT id_location, location_name FROM loc_electricity WHERE factory = 'AOI2' ORDER BY location_name ASC";
	$result_panels = mysqli_query($mysqli, $query_panels);

	// Menangkap input filter
	$selected_panel = isset($_GET['panel']) ? $_GET['panel'] : 'all';
	$range_input    = isset($_GET['range']) ? $_GET['range'] : '';

	// Base kondisi tambahan untuk panel
	$panel_condition = "";
	if ($selected_panel !== 'all' && !empty($selected_panel)) {
		$safe_panel = mysqli_real_escape_string($mysqli, $selected_panel);
		$panel_condition = " AND e.id_location = '$safe_panel'";
	}

	if(isset($_GET["submit"]))
	{
		$aRanges = explode(' to ', $range_input);
		$range1 = isset($aRanges[0]) ? $aRanges[0] : date('Y-m-d');
		$range2 = isset($aRanges[1]) ? $aRanges[1] : $range1;
		$from = date("d M Y", strtotime($range1));
		$to = date("d M Y", strtotime($range2));

		$report = "SELECT l.factory, l.location_name AS location, ABS(e.power) AS power, e.kwh, e.consumpt, DATE_FORMAT(e.datenow, '%d-%b-%y %H:%i:%s') AS datetime, DATE_FORMAT(e.datenow, '%Y-%m-%d %H:%i:%s') AS date_only 
		           FROM electricity e 
		           JOIN loc_electricity l ON l.id_location = e.id_location 
		           WHERE e.datenow IS NOT NULL 
		             AND l.factory = 'AOI2' 
		             AND e.datenow >= '$range1 00:00:00' 
		             AND e.datenow < DATE_ADD('$range2', INTERVAL 1 DAY) 
		             {$panel_condition}
		           ORDER BY e.datenow DESC, e.id_location ASC";
		$result_report = mysqli_query($mysqli, $report);

	} else
	{
		$report = "SELECT l.factory, l.location_name AS location, ABS(e.power) AS power, e.kwh, e.consumpt, DATE_FORMAT(e.datenow, '%d-%b-%y %H:%i:%s') AS datetime, DATE_FORMAT(e.datenow, '%Y-%m-%d %H:%i:%s') AS date_only 
		           FROM electricity e 
		           JOIN loc_electricity l ON l.id_location = e.id_location 
		           WHERE e.datenow IS NOT NULL 
		             AND l.factory = 'AOI2' 
		             AND e.datenow >= NOW() - INTERVAL 1 MONTH 
		             {$panel_condition}
		           ORDER BY e.datenow DESC, e.id_location ASC";
		$result_report = mysqli_query($mysqli, $report);
	}
	;?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../../favicon.ico">
    <title>Electricity Monitoring</title>
    <!-- Simple bar CSS -->
    <link rel="stylesheet" href="../../../css/simplebar.css">
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
            }
            .report-electricity-table {
                table-layout: fixed;
                width: 100%;
                min-width: 900px;
            }
            .report-electricity-table th,
            .report-electricity-table td {
                vertical-align: middle;
            }
            .report-electricity-table th.text-nowrap,
            .report-electricity-table td.text-nowrap {
                white-space: nowrap;
            }
            .report-electricity-table td:nth-child(3) {
                white-space: normal;
                word-break: break-word;
            }
    </style>
	<script src="../../../js/jquery.dataTables.min.js"></script>
	<script src="../../../js/datatables.buttons.min.js"></script>
	<script src="../../../js/jszip.min.js"></script>
	<script src="../../../js/buttons.html5.min.js"></script>

	<script>
        $(document).ready(function(){
            var empDataTable = $('#dataTable-2').DataTable({
                dom: 'Blfrtip',
				lengthMenu: [100, 500, 1000],
				autoWidth: false,
                buttons: [
                    {
                        extend: 'csv'
                    },
                    {
                        extend: 'excel'
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
					  <a class="nav-link pl-3" href="../../../dashboard/energy/aoi2/"><span class="ml-1 item-text">Back to dashboard</span></a>
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
                  <h2 class="h5 page-title">Report Electricity <?php if(isset($_GET["submit"])){echo " from ".$from." to ".$to;}; ?></h2>
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
								<div class="form-row align-items-center">
									<!-- Dropdown Select Panel -->
									<div class="col-md-4 mb-2">
										<select name="panel" class="form-control" id="panelSelect">
											<option value="all">-- Semua Panel (All Panels) --</option>
											<?php 
											// Reset pointer jika diperlukan atau loop ulang hasil query_panels
											mysqli_data_seek($result_panels, 0);
											while($row_p = mysqli_fetch_assoc($result_panels)) {
												$sel = ($selected_panel == $row_p['id_location']) ? 'selected' : '';
												echo '<option value="'.$row_p['id_location'].'" '.$sel.'>'.$row_p['location_name'].'</option>';
											}
											?>
										</select>
									</div>
									<!-- Input Date Range -->
									<div class="col-md-6 mb-2">
										<input id="reportrange" name="range" type="text" value="<?php echo htmlspecialchars($range_input); ?>" placeholder="Select Range" class="form-control">
									</div>
									<!-- Tombol Submit -->
									<div class="col-md-2 mb-2">
										<input class="btn btn-primary btn-block" type="submit" id="submit" name="submit" value="Filter Data">
									</div>
								</div>
							</form>
						</div>

						<!-- Start Table -->
						<div class="table-responsive">
							<table class="table datatables report-electricity-table" id="dataTable-2">
								<colgroup>
									<col style="width: 4%;">
									<col style="width: 9%;">
									<col style="width: 24%;">
									<col style="width: 10%;">
									<col style="width: 16%;">
									<col style="width: 16%;">
									<col style="width: 21%;">
								</colgroup>
								<thead>
								  <tr>
									<th class="text-center">No</th>
									<th>Factory</th>
									<th>Location</th>
									<th class="text-right">Power (KW)</th>
									<th class="text-right">KWH</th>
									<th class="text-right" title="Pemakaian kWh sejak record sebelumnya">Consumpt (KWH)</th>
									<th>Record Time</th>
								  </tr>
								</thead>
								<tbody>
								<?php while($row_report = mysqli_fetch_assoc($result_report))
										{	$factory = $row_report['factory'];
											$location_name = $row_report['location'];
											$power = $row_report['power'];
											$kwh = $row_report['kwh'];
											$consumpt = $row_report['consumpt'];
											$record_time = $row_report['datetime'];
											$consumpt_display = ($consumpt === null || $consumpt === '') ? '-' : number_format((float)$consumpt, 2);

											?>
											<tr>
												<td class="text-center"></td>
												<td><?php echo $factory;?></td>
												<td><?php echo $location_name;?></td>
												<td class="text-right text-nowrap"><?php echo number_format($power) ;?></td>
												<td class="text-right text-nowrap"><?php echo number_format($kwh) ;?></td>
												<td class="text-right text-nowrap"><?php echo $consumpt_display;?></td>
												<td class="text-nowrap"><?php echo $record_time ;?></td>
											</tr>
								<?php 	};?>
								</tbody>
							</table>
						</div>
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
      var start = moment();
      var end = moment();

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