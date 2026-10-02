<?php
	include '../../../config_temp_humid.php';

	if(isset($_GET["submit"]))
	{
		$aRanges = explode(' to ', $_GET['range']);
		$range1 = $aRanges[0];
		$range2 = $aRanges[1];
		$from = date("d M Y", strtotime($range1));
		$to = date("d M Y", strtotime($range2));

		//$select_data = "SELECT * FROM realtime_th WHERE factory = 'AOI1' ORDER BY sequence ASC";

		$report = "SELECT * FROM report_temp_humid WHERE date_only is not null AND factory = 'AOI1' AND date_only between '$range1' and '$range2' ORDER BY record_time DESC";
		$result_report = mysqli_query($conn, $report);

	} else
	{
		$report = "SELECT * FROM report_temp_humid WHERE date_only is not null AND factory = 'AOI1' AND date_only >= now()-interval 1 week ORDER BY record_time DESC";
		$result_report = mysqli_query($conn, $report);
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
    <title>Temperature and Humidity Monitoring AOI 1</title>
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
                buttons: [
                    {
                        extend: 'csv'
                    },
                    {
                        extend: 'excel'
                    }
                ] ,


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
		  <div class="w-100 mb-4 d-flex">
			<img src="http://smartagv.aoi.co.id/machine_control/logo.png" width="160">
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
					  <a class="nav-link pl-3" href="../../../dashboard/temperature/aoi1/"><span class="ml-1 item-text">Back to dashboard</span></a>
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
                  <h2 class="h5 page-title">Report Temperature and Humidity AOI 1 <?php if(isset($_GET["submit"])){echo " from ".$from." to ".$to;}; ?></h2>
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
								<th>Record Time</th>
								<th>Day Name</th>
							  </tr>
							</thead>
							<tbody>
							<?php while($row_report = mysqli_fetch_assoc($result_report))
									{	$factory = $row_report['factory'];
										$id_location = $row_report['id_location'];
										$location_name = $row_report['location_name'];
										$Temp = $row_report['temp'];
										$humi = $row_report['humidity'];
										$record_time = $row_report['record_time'];
										$dayname = $row_report['day_name'];

										?>
										<tr>
											<td></td>
											<td><?php echo $factory;?></td>
											<td><?php echo $id_location;?></td>
											<td><?php echo $location_name;?></td>
											<td><?php echo $Temp;?></td>
											<td><?php echo $humi ;?></td>
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
