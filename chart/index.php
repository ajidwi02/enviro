<?php 
	include '../config_andon.php';
	
	$select_mekanik = "SELECT * FROM andon_mekanik WHERE tanggal_off is not null ORDER BY tanggal_off DESC, time_off DESC";
	$result_select_mekanik = mysqli_query($conn, $select_mekanik);
	
	
	
;?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../favicon.ico">
    <title>Machine Control & Monitoring</title>
    <!-- Simple bar CSS -->
    <link rel="stylesheet" href="../css/simplebar.css">
    <!-- Fonts CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100;0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Icons CSS -->
    <link rel="stylesheet" href="../css/feather.css">
	<link rel="stylesheet" href="../css/dataTables.bootstrap4.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="../css/app-light.css" id="lightTheme" disabled>
    <link rel="stylesheet" href="../css/app-dark.css" id="darkTheme">
	<script src="../js/jquery.min.js"></script>
  </head>
  <body class="vertical  dark">
    <div class="wrapper">
	  <!-- Start Nav Bar -->
      <nav class="topnav navbar navbar-light">
		  <span class="avatar avatar-sm mt-2">
            <?php if(session_status == 1){echo $user_name.", Sebagai ".$role_name ;}else{;}?>     
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
			  <a href="#Report" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-edit fe-16"></i>
				<span class="ml-3 item-text">Report</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id=Report>
				<!-- Start Sub Menu -->
					<li class="nav-item">
					  <a class="nav-link pl-3" href=../report_mekanik><span class="ml-1 item-text">Report Andon Mekanik</span></a>
					</li>
					<li class="nav-item">
					  <a class="nav-link pl-3" href=../report_elektrik><span class="ml-1 item-text">Report Andon Elektrik</span></a>
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
                  <h2 class="h5 page-title">Transaction Report <?php echo $factory ;?></h2>
                </div>
              </div>
              <div class="mb-2 align-items-center">
                <div class="card shadow mb-4">
                  <div class="card-body">
                    
					<!-- Start Data -->
					<div class="row">
                    <div class="col-md-12">
						
						<?php if ($session_status == 2){ ?>
						<form action="http://smartagv.aoi.co.id/machine_control/">
							<input type="submit" value="Untuk melihat data, silahkan login dulu !" class="btn btn-outline-secondary"/>
						</form>
						<?php ;} else { ?>
						<!-- Start Table -->
						<table class="table datatables" id="dataTable-1">
							<thead>
							  <tr>
								<th>No</th>
								<th>Machine ID</th>
								<th>Machine Name</th>
								<th>Style</th>
								<th>Component</th>
								<th>Temperature</th>
								<th>Timer</th>
								<th>Factory</th>
								<th>User</th>
								<th>Input Time</th>
							  </tr>
							</thead>
							<tbody>
							<?php while($row_select_transaction = mysqli_fetch_assoc($result_select_transaction))
									{	$nik = $row_select_transaction["nik"];
										$id_transaction = $row_select_transaction["id_transaction"];
										$id_master_machine = $row_select_transaction["id_master_machine"];
										$style = $row_select_transaction["style"];
										$component = $row_select_transaction["component"];
										$temperature_value = $row_select_transaction["temperature_value"];
										$timer_value = $row_select_transaction["timer_value"];
										$user = $row_select_transaction["user"];
										$input_time2 = $row_select_transaction["input_time"];
										$timestamp = strtotime($input_time2);
										$input_time = date("d M Y H:i:s", $timestamp);
										
										$machine = $row_select_transaction["machine"];
										$id_device = $row_select_transaction["id_device"];
										$factory_data = $row_select_transaction["factory_data"];
										?>
										<tr>
											<td></td>
											<td><?php echo $id_device;?></td>
											<td><?php echo $machine;?></td>
											<td><?php echo $style;?></td>
											<td><?php echo $component;?></td>
											<td><?php echo $temperature_value;?></td>
											<td><?php echo $timer_value;?></td>
											<td><?php echo $factory_data;?></td>
											<td><?php echo $user;?></td>
											<td><?php echo $input_time;?></td>
										</tr>
							<?php 	};?>
							</tbody>
						</table>
						<!-- End Table -->
						<?php ;}?>
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
    <script src="../js/popper.min.js"></script>
    <script src="../js/moment.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/simplebar.min.js"></script>
    <script src='../js/jquery.stickOnScroll.js'></script>
    <script src="../js/tinycolor-min.js"></script>
    <script src="../js/config.js"></script>
    <script src='../js/select2.min.js'></script>
    <script src="../js/apps.js"></script>
	<script src="../js/config.js"></script>
	<script src='../js/jquery.dataTables.min.js'></script>
    <script src='../js/dataTables.bootstrap4.min.js'></script>
	<script>
    $(document).ready(function () {
		var t = $('#dataTable-1').DataTable({
			columnDefs: [
				{
					searchable: false,
					orderable: false,
					targets: 0,
				},
			],
			
		});
	 
		t.on('order.dt search.dt', function () {
			let i = 1;
	 
			t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
				this.data(i++);
			});
		}).draw();
	});
    </script>
  </body>
</html>