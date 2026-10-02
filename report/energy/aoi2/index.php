<?php
	include '../../../config_electricity.php';
	
	if(isset($_GET["submit"]))
	{
		$aRanges = explode(' to ', $_GET['range']);
		$range1 = $aRanges[0];
		$range2 = $aRanges[1];
		$from = date("d M Y", strtotime($range1));
		$to = date("d M Y", strtotime($range2));

		$report = "SELECT factory, location, power, kwh, datetime, date_only FROM report_electricity WHERE date_only is not null AND date_only between '$range1' and '$range2' ORDER BY date_only DESC, id_location ASC";
		$result_report = mysqli_query($mysqli, $report);

	} else
	{
		$report = "SELECT factory, location, power, kwh, datetime, date_only FROM report_electricity WHERE date_only is not null AND date_only >= now()-interval 1 month ORDER BY date_only DESC, id_location ASC";
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
		  <div class="w-100 mb-4 d-flex">
			<img src="../../../logo_aoi.png" width="160">
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
						<table class="table datatables" id="dataTable-2" >
							<thead>
							  <tr>
								<th>No</th>
								<th>Factory</th>
								<th>Location</th>
								<th>Power (KW)</th>
								<th>KWH</th>
								<th>Record Time</th>
							  </tr>
							</thead>
							<tbody>
							<?php while($row_report = mysqli_fetch_assoc($result_report))
									{	$factory = $row_report['factory'];
										$location_name = $row_report['location'];
										$power = $row_report['power'];
										$kwh = $row_report['kwh'];
										$record_time = $row_report['datetime'];
										
										?>
										<tr>
											<td></td>
											<td><?php echo $factory;?></td>
											<td><?php echo $location_name;?></td>
											<td><?php echo number_format($power) ;?></td>
											<td><?php echo number_format($kwh) ;?></td>
											<td><?php echo $record_time ;?></td>
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
