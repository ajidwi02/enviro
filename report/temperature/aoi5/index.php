<?php
include '../../../config_temp_humid.php';

date_default_timezone_set("Asia/Jakarta");

$from_display = "";
$to_display = "";

if (isset($_GET["submit"]) && !empty($_GET['range'])) {
    $aRanges = explode(' to ', $_GET['range']);
    $range1 = trim($aRanges[0]);
    $range2 = isset($aRanges[1]) ? trim($aRanges[1]) : $range1;
    
    $from_display = date("d M Y", strtotime($range1));
    $to_display = date("d M Y", strtotime($range2));

    $report_sql = "SELECT id_transaction, id_location, temp, humidity, record_time, 
                          DATE_FORMAT(record_time, '%W') AS day_name,
                          DATE(record_time) AS date_only 
                   FROM temp_humid 
                   WHERE (id_location IN ('501', '502', 'area501', 'area502', 'Area_1', 'Area_2', 'temp1', 'temp2')) 
                     AND DATE(record_time) BETWEEN '$range1' AND '$range2' 
                   ORDER BY record_time DESC";
    $result_report = mysqli_query($conn, $report_sql);
} else {
    $report_sql = "SELECT id_transaction, id_location, temp, humidity, record_time, 
                          DATE_FORMAT(record_time, '%W') AS day_name,
                          DATE(record_time) AS date_only 
                   FROM temp_humid 
                   WHERE (id_location IN ('501', '502', 'area501', 'area502', 'Area_1', 'Area_2', 'temp1', 'temp2')) 
                     AND record_time >= NOW() - INTERVAL 7 DAY 
                   ORDER BY record_time DESC";
    $result_report = mysqli_query($conn, $report_sql);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Report Temperature and Humidity AOI 5">
    <meta name="author" content="">
    <link rel="icon" href="../../../favicon.ico">
    <title>Report Temperature & Humidity AOI 5</title>
    
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
    
    <!-- DataTable CSS & JS -->
    <link href='../../../css/datatable.min.css' rel='stylesheet' type='text/css'>
    <link href='../../../css/buttons.datatable.min.css' rel='stylesheet' type='text/css'>
    <style type="text/css">
        .dt-buttons {
            width: 100%;
            margin-bottom: 12px;
        }
        .badge-temp {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
    </style>
    <script src="../../../js/jquery.dataTables.min.js"></script>
    <script src="../../../js/datatables.buttons.min.js"></script>
    <script src="../../../js/jszip.min.js"></script>
    <script src="../../../js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function(){
            var empDataTable = $('#dataTable-report').DataTable({
                dom: 'Blfrtip',
                lengthMenu: [[25, 50, 100, 500, -1], [25, 50, 100, 500, "All"]],
                pageLength: 50,
                buttons: [
                    {
                        extend: 'csv',
                        text: '<i class="fe fe-file-text mr-1"></i> Export CSV',
                        className: 'btn btn-sm btn-outline-secondary mr-1',
                        filename: 'Report_Temp_Humid_AOI5_' + new Date().toISOString().slice(0,10)
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fe fe-download mr-1"></i> Export Excel',
                        className: 'btn btn-sm btn-outline-success',
                        filename: 'Report_Temp_Humid_AOI5_' + new Date().toISOString().slice(0,10)
                    }
                ],
                order: [[5, 'desc']]
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
  <body class="vertical light">
    <div class="wrapper">
      <!-- Start Nav Bar -->
      <nav class="topnav navbar navbar-light">
          <span class="avatar avatar-sm mt-2"></span>
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
            <img src="http://smartagv.aoi.co.id/machine_control/logo.png" width="160" alt="Logo">
          </div>
          <!-- End Logo -->

          <!-- Start Menu Bar -->
          <p class="text-muted nav-heading mt-4 mb-1">
            <span>Navigasi</span>
          </p>

          <ul class="navbar-nav flex-fill w-100 mb-2">
            <li class="nav-item">
              <a class="nav-link pl-3" href="../../../index.php">
                <i class="fe fe-home fe-16"></i>
                <span class="ml-3 item-text">Menu Utama</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link pl-3" href="../../../dashboard/temperature/aoi5/">
                <i class="fe fe-activity fe-16"></i>
                <span class="ml-3 item-text">Dashboard AOI 5</span>
              </a>
            </li>
            <li class="nav-item active">
              <a class="nav-link pl-3 text-primary font-weight-bold" href="#">
                <i class="fe fe-file-text fe-16"></i>
                <span class="ml-3 item-text">Report AOI 5</span>
              </a>
            </li>
          </ul>
        </nav>
      </aside>
      <!-- End Side Bar -->

      <!-- Start Content -->
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="row justify-content-center">
            <div class="col-12">
              
              <div class="row align-items-center mb-3">
                <div class="col">
                  <h2 class="h4 page-title font-weight-bold">
                    Report Temperature and Humidity AOI 5
                    <?php if(!empty($from_display)): ?>
                        <small class="text-muted">(Periode: <?php echo $from_display . " s/d " . $to_display; ?>)</small>
                    <?php else: ?>
                        <small class="text-muted">(Periode: 7 Hari Terakhir)</small>
                    <?php endif; ?>
                  </h2>
                </div>
              </div>

              <div class="card shadow mb-4">
                <div class="card-body">

                  <!-- Form Filter Range Tanggal -->
                  <form method="GET" action="" class="mb-4">
                    <div class="row align-items-center">
                      <div class="col-md-6 col-lg-5">
                        <label class="font-weight-bold text-muted small">PILIH RENTANG TANGGAL</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fe fe-calendar"></i></span>
                          </div>
                          <input id="reportrange" name="range" type="text" placeholder="Pilih Rentang Tanggal" class="form-control" value="<?php echo isset($_GET['range']) ? htmlspecialchars($_GET['range']) : ''; ?>">
                          <div class="input-group-append">
                            <input class="btn btn-primary" type="submit" id="submit" name="submit" value="Filter Data">
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 col-lg-7 text-md-right mt-3 mt-md-0">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fe fe-refresh-cw mr-1"></i> Reset Filter</a>
                        <a href="../../../dashboard/temperature/aoi5/" class="btn btn-outline-primary btn-sm ml-1"><i class="fe fe-eye mr-1"></i> Live Dashboard</a>
                      </div>
                    </div>
                  </form>

                  <!-- Start Table -->
                  <div class="table-responsive">
                    <table class="table table-hover table-striped datatables" id="dataTable-report">
                      <thead class="thead-dark">
                        <tr>
                          <th style="width: 50px;">No</th>
                          <th>Factory</th>
                          <th>ID Location</th>
                          <th>Location Name</th>
                          <th>Temperature (°C)</th>
                          <th>Humidity (%)</th>
                          <th>Record Time</th>
                          <th>Hari</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                        if ($result_report && mysqli_num_rows($result_report) > 0) {
                            while($row = mysqli_fetch_assoc($result_report)) {
                                $id_loc = $row['id_location'];
                                $temp = floatval($row['temp']);
                                $humi = floatval($row['humidity']);
                                $rec_time = $row['record_time'];
                                $dayname = $row['day_name'];
                                
                                // Tentukan Location Name
                                $loc_lower = strtolower($id_loc);
                                if ($loc_lower == '501' || $loc_lower == 'temp1' || $loc_lower == 'area501' || $loc_lower == 'area_1' || $loc_lower == 'area1') {
                                    $loc_name = 'Sensor 1 - Temp & Humid AOI 5';
                                } elseif ($loc_lower == '502' || $loc_lower == 'temp2' || $loc_lower == 'area502' || $loc_lower == 'area_2' || $loc_lower == 'area2') {
                                    $loc_name = 'Sensor 2 - Temp & Humid AOI 5';
                                } else {
                                    $loc_name = 'Sensor ' . $id_loc . ' - AOI 5';
                                }
                        ?>
                        <tr>
                          <td></td>
                          <td><span class="badge badge-info font-weight-bold">AOI 5</span></td>
                          <td><code><?php echo htmlspecialchars($id_loc); ?></code></td>
                          <td><strong><?php echo htmlspecialchars($loc_name); ?></strong></td>
                          <td>
                            <span class="badge <?php echo ($temp > 30 || $temp < 18) ? 'badge-danger' : 'badge-success'; ?> badge-temp">
                              <?php echo number_format($temp, 1); ?> °C
                            </span>
                          </td>
                          <td>
                            <span class="badge <?php echo ($humi > 75) ? 'badge-danger' : (($humi > 65) ? 'badge-warning' : 'badge-primary'); ?> badge-temp">
                              <?php echo number_format($humi, 1); ?> %
                            </span>
                          </td>
                          <td><?php echo htmlspecialchars($rec_time); ?></td>
                          <td><?php echo htmlspecialchars($dayname); ?></td>
                        </tr>
                        <?php 
                            }
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                  <!-- End Table -->

                </div> <!-- .card-body -->
              </div> <!-- .card -->

            </div> <!-- /.col-12 -->
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
      var start = moment().subtract(6, 'days');
      var end = moment();

      <?php if(isset($_GET["submit"]) && !empty($_GET['range'])): ?>
      var curRange = "<?php echo addslashes($_GET['range']); ?>".split(' to ');
      if (curRange.length === 2) {
          start = moment(curRange[0]);
          end = moment(curRange[1]);
      }
      <?php endif; ?>

      $('#reportrange').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD',
            separator: " to "
        },
        startDate: start,
        endDate: end,
        ranges: {
          'Hari Ini (Today)': [moment(), moment()],
          'Kemarin (Yesterday)': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
          '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
          'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
          'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
      });
    </script>
  </body>
</html>
