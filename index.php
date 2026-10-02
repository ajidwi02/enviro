<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">
    <title>Environment</title>
    <!-- Simple bar CSS -->
    <link rel="stylesheet" href="css/simplebar.css">
    <!-- Fonts CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100;0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Icons CSS -->
    <link rel="stylesheet" href="css/feather.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="css/app-light.css" id="lightTheme">

	<script src="js/jquery.min.js"></script>
	<script src="js/bootstrap.min.js"></script>



  </head>
  <body class="vertical  light">
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

      <!-- Menu Utama: AOI 1 (Dashboard) -->

			<ul class="navbar-nav flex-fill w-100 mb-2">
			<a class="nav-link pl-3" href="#"><span class="ml-1 item-text">Menu Utama</span></a>
			<li class="nav-item dropdown">

			  <a href="#dashboard" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-edit fe-16"></i>
				<span class="ml-3 item-text">AOI 1</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id="dashboard">

         <!-- Start Sub Menu-->
         <li class="nav-item">
               <a class="nav-link pl-3" href="#tempSubmenuaoi1" data-toggle="collapse" aria-expanded="false">
                 <span class="ml-1 item-text">Temp Humid</span>
               </a>
               <!-- Sub-submenu untuk Temp Humid -->
               <ul class="collapse list-unstyled pl-4" id="tempSubmenuaoi1">
                 <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/dashboard/temperature/aoi1/" target="_blank">
                    <span class="ml-1 item-text">Dashboard</a></li>
                 <!-- <li class="nav-item"><a class="nav-link" href="#">Dashboard</a></li>-->
                  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/report/temperature/aoi1/" target="_blank">
                    <span class="ml-1 item-text">Report</a></li>
                 <!-- <li class="nav-item"><a class="nav-link" href="#">Report</a></li> -->
               </ul>
             </li>
             <li class="nav-item">
                   <a class="nav-link pl-3" href="#energySubmenuaoi1" data-toggle="collapse" aria-expanded="false">
                     <span class="ml-1 item-text">Energy</span>
                   </a>
                   <!-- Sub-submenu untuk Temp Humid -->
                   <ul class="collapse list-unstyled pl-4" id="energySubmenuaoi1">
                     <li class="nav-item"><a class="nav-link" href="#">Dashboard</a></li>
                     <!-- <li class="nav-item"><a class="nav-link" href="#">Report</a></li>-->
                   </ul>
                 </li>



      	<!-- End Sub Menu -->
			  </ul>
			</li>
			<li class="nav-item dropdown">
			  <a href="#report" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-edit fe-16"></i>
				<span class="ml-3 item-text">AOI 2</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id="report">
				<!-- Start Sub Menu -->
        <li class="nav-item">
              <a class="nav-link pl-3" href="#tempSubmenuaoi2" data-toggle="collapse" aria-expanded="false">
                <span class="ml-1 item-text">Temp Humid</span>
              </a>
              <!-- Sub-submenu untuk Temp Humid -->
              <ul class="collapse list-unstyled pl-4" id="tempSubmenuaoi2">
                  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/dashboard/temperature/aoi2/" target="_blank">
                     <span class="ml-1 item-text">Dashboard</a></li>
                  <!-- <li class="nav-item"><a class="nav-link" href="#">Dashboard</a></li>-->
                   <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/report/temperature/aoi2/" target="_blank">
                     <span class="ml-1 item-text">Report</a></li>
              </ul>
            </li>
            <li class="nav-item">
                  <a class="nav-link pl-3" href="#energySubmenuaoi2" data-toggle="collapse" aria-expanded="false">
                    <span class="ml-1 item-text">Energy</span>
                  </a>
                  <!-- Sub-submenu untuk Temp Humid -->
                  <ul class="collapse list-unstyled pl-4" id="energySubmenuaoi2">
                    <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/dashboard/energy/aoi2/" target="_blank">
                       <span class="ml-1 item-text">Dashboard</a></li>
                    <!--<li class="nav-item"><a class="nav-link" href="#">Report</a></li>-->
                  </ul>
                </li>
				<!-- End Sub Menu -->
			  </ul>
			</li>
			<li class="nav-item dropdown">
			  <a href="#menuAoi5" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-edit fe-16"></i>
				<span class="ml-3 item-text">AOI 5</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id="menuAoi5">
				<!-- Start Sub Menu -->
        <li class="nav-item">
              <a class="nav-link pl-3" href="#tempSubmenuaoi5" data-toggle="collapse" aria-expanded="false">
                <span class="ml-1 item-text">Temp Humid</span>
              </a>
              <ul class="collapse list-unstyled pl-4" id="tempSubmenuaoi5">
                  <a class="nav-link pl-3" href="dashboard/temperature/aoi5/" target="_blank">
                     <span class="ml-1 item-text">Dashboard</a></li>
                  <a class="nav-link pl-3" href="report/temperature/aoi5/" target="_blank">
                     <span class="ml-1 item-text">Report</a></li>
              </ul>
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
                  <h2 class="h5 page-title">Menu Utama</h2>
                </div>
              </div>
              <div class="mb-2 align-items-center">
                <div class="card shadow mb-4">
                  <div class="card-body">

					<!-- Start Data -->
					<div class="row">
                    <div class="col-md-12">



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
    <script src="js/popper.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/simplebar.min.js"></script>
    <script src='js/jquery.stickOnScroll.js'></script>
    <script src="js/tinycolor-min.js"></script>
    <script src="js/config.js"></script>
    <script src="js/select2.min.js"></script>
    <script src="js/apps.js"></script>
	<script src="js/config.js"></script>
    <script src="mqtt/mqttws31.js"></script>

    <!-- Background MQTT Listener & Auto-Save to Database (temp_humid) -->
    <script type="text/javascript">
      (function() {
        var mqtt;
        var reconnectTimeout = 5000;
        var host = "192.168.51.40";
        var port = 8083;
        var connected_flag = 0;

        var topic_map = {
          'area501': '501',
          'area502': '502',
          '501': '501',
          '502': '502',
          'temp1': '501',
          'temp2': '502',
          'Area_1': '501',
          'Area_2': '502'
        };

        function onConnectionLost(responseObject) {
          connected_flag = 0;
          console.warn("[Background MQTT] Connection lost:", responseObject ? responseObject.errorMessage : "");
          setTimeout(MQTTconnect, reconnectTimeout);
        }

        function onFailure(message) {
          console.warn("[Background MQTT] Connection failed. Retrying in 5s...");
          setTimeout(MQTTconnect, reconnectTimeout);
        }

        function onConnect() {
          connected_flag = 1;
          console.log("[Background MQTT] Connected to broker (" + host + ":" + port + ") - Auto-save active");
          return sub_topics();
        }

        function sub_topics() {
          if (connected_flag === 0) return false;
          var topics = [
            "data/temperature/area501/+",
            "data/temperature/area501/#",
            "data/temperature/area501",
            "data/temperature/area502/+",
            "data/temperature/area502/#",
            "data/temperature/area502",
            "data/temperature/temp1",
            "data/temperature/temp2",
            "data/temperature/+"
          ];

          topics.forEach(function(tp) {
            mqtt.subscribe(tp);
          });
          return false;
        }

        function onMessageArrived(r_message) {
          try {
            var topic = r_message.destinationName || "";
            var payloadStr = r_message.payloadString || "{}";

            var data = JSON.parse(payloadStr);
            var temp_val = Math.round(parseFloat(data.temper !== undefined ? data.temper : data.temp) * 10) / 10;
            var hum_val  = Math.round(parseFloat(data.humidi !== undefined ? data.humidi : data.humidity) * 10) / 10;
            var time_val = data._terminalTime || data.record_time || new Date().toISOString().slice(0, 19).replace('T', ' ');

            var locId = '501';
            if (topic.indexOf('area502') !== -1 || topic.indexOf('502') !== -1 || topic.indexOf('temp2') !== -1) {
              locId = '502';
            } else if (topic.indexOf('area501') !== -1 || topic.indexOf('501') !== -1 || topic.indexOf('temp1') !== -1) {
              locId = '501';
            } else if (data._groupName && (data._groupName.toLowerCase().indexOf('area_2') !== -1 || data._groupName.toLowerCase().indexOf('area2') !== -1)) {
              locId = '502';
            } else if (data._groupName && (data._groupName.toLowerCase().indexOf('area_1') !== -1 || data._groupName.toLowerCase().indexOf('area1') !== -1)) {
              locId = '501';
            } else if (data.id_location) {
              locId = topic_map[data.id_location] || data.id_location;
            }

            // Simpan otomatis ke server via API api/save_temp_humid.php
            saveToDatabase(locId, temp_val, hum_val, time_val);
          } catch (e) {
            console.error("[Background MQTT] Error parsing payload:", e);
          }
        }

        function saveToDatabase(locId, temp, hum, recTime) {
          var payload = {
            id_location: locId,
            temp: temp,
            humidity: hum,
            record_time: recTime
          };

          fetch('api/save_temp_humid.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          })
          .then(function(res) { return res.json(); })
          .then(function(result) {
            if (result.status === 'success') {
              console.log("[Background Auto-Save] ✓ Saved to temp_humid:", result.data);
            } else {
              console.error("[Background Auto-Save] ✗ DB Error:", result.message);
            }
          })
          .catch(function(err) {
            console.error("[Background Auto-Save] Error calling API:", err);
          });
        }

        function MQTTconnect() {
          var x = Math.floor(Math.random() * 10000);
          var cname = "enviro_Portal_Background_" + x;
          mqtt = new Paho.MQTT.Client(host, port, cname);

          var options = {
            timeout: 5,
            onSuccess: onConnect,
            onFailure: onFailure,
            keepAliveInterval: 30
          };

          mqtt.onConnectionLost = onConnectionLost;
          mqtt.onMessageArrived = onMessageArrived;

          try {
            mqtt.connect(options);
          } catch (err) {
            console.error("[Background MQTT] Error connecting:", err);
            setTimeout(MQTTconnect, reconnectTimeout);
          }
        }

        if (window.addEventListener) {
          window.addEventListener('load', MQTTconnect, false);
        } else if (window.attachEvent) {
          window.attachEvent('onload', MQTTconnect);
        }
      })();
    </script>
  </body>
</html>
