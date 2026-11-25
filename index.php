<!doctype html>
<html lang="id">
  <head>
    <script>document.documentElement.classList.add('mu-js');</script>
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
    <link rel="stylesheet" href="css/menu-utama.css?v=<?= filemtime(__DIR__ . '/css/menu-utama.css') ?>">

	<script src="js/jquery.min.js"></script>
	<script src="js/bootstrap.min.js"></script>



  </head>
  <body class="vertical light mu-page">
    <div class="wrapper">
	  <!-- Start Nav Bar -->
      <nav class="topnav navbar navbar-light" aria-label="Bilah atas">
        <button type="button" class="mu-nav-toggle" id="muNavToggle"
                aria-controls="leftSidebar" aria-expanded="false"
                aria-label="Buka menu navigasi">
          <span class="mu-nav-toggle__box" aria-hidden="true">
            <span class="mu-nav-toggle__line"></span>
            <span class="mu-nav-toggle__line"></span>
            <span class="mu-nav-toggle__line"></span>
          </span>
        </button>

        <a class="mu-brand" href="index.php">
          <span class="mu-brand__dot" aria-hidden="true"></span>
          <span class="mu-brand__name">Environment</span>
        </a>

        <span class="mu-topbar__section">Menu Utama</span>
      </nav>
	  <!-- End Nav Bar -->

	  <!-- Start Side Bar -->
      <aside class="sidebar-left border-right bg-white" id="leftSidebar" data-simplebar aria-label="Navigasi utama">
		<nav class="vertnav navbar navbar-light">
		  <!-- Start Logo -->
		  <div class="w-100 mb-3 text-center d-flex flex-column align-items-center justify-content-center pt-2 pb-2">
			<a href="index.php" class="d-inline-flex flex-column align-items-center text-decoration-none">
			  <img src="logo_aoi.png?v=<?= filemtime(__DIR__ . '/logo_aoi.png') ?>" alt="Logo AOI" class="img-fluid" style="max-height: 95px; width: auto; object-fit: contain;">
			</a>
		  </div>
		  <!-- End Logo -->

		  <!-- Start Menu Bar -->
		  <p class="text-muted nav-heading mt-3 mb-1">
			<span>Menu</span>
		  </p>

		  <!-- Start Menu -->
		  <ul class="navbar-nav flex-fill w-100 mb-2">
			<li class="nav-item active">
			  <a class="nav-link pl-3" href="index.php">
				<i class="fe fe-home fe-16"></i>
				<span class="ml-3 item-text font-weight-bold">Menu Utama</span>
			  </a>
			</li>
			<li class="nav-item dropdown">
			  <a href="#dashboard" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-layers fe-16"></i>
				<span class="ml-3 item-text">AOI 1</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id="dashboard">
				<!-- Start Sub Menu-->
				<li class="nav-item">
				  <a class="nav-link pl-3" href="#tempSubmenuaoi1" data-toggle="collapse" aria-expanded="false">
					<span class="ml-1 item-text">Temp Humid</span>
				  </a>
				  <!-- Sub-submenu untuk Temp Humid -->
				  <ul class="collapse list-unstyled pl-3" id="tempSubmenuaoi1">
					<li class="nav-item">
					  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/dashboard/temperature/aoi1/" target="_blank">
						<span class="ml-1 item-text">Dashboard</span>
					  </a>
					</li>
					<li class="nav-item">
					  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/report/temperature/aoi1/" target="_blank">
						<span class="ml-1 item-text">Report</span>
					  </a>
					</li>
				  </ul>
				</li>
				<li class="nav-item">
				  <a class="nav-link pl-3" href="#energySubmenuaoi1" data-toggle="collapse" aria-expanded="false">
					<span class="ml-1 item-text">Energy</span>
				  </a>
				  <!-- Sub-submenu untuk Energy -->
				  <ul class="collapse list-unstyled pl-3" id="energySubmenuaoi1">
					<li class="nav-item">
					  <a class="nav-link pl-3" href="#">
						<span class="ml-1 item-text">Dashboard</span>
					  </a>
					</li>
				  </ul>
				</li>
				<!-- End Sub Menu -->
			  </ul>
			</li>
			<li class="nav-item dropdown">
			  <a href="#report" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-layers fe-16"></i>
				<span class="ml-3 item-text">AOI 2</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id="report">
				<!-- Start Sub Menu -->
				<li class="nav-item">
				  <a class="nav-link pl-3" href="#tempSubmenuaoi2" data-toggle="collapse" aria-expanded="false">
					<span class="ml-1 item-text">Temp Humid</span>
				  </a>
				  <!-- Sub-submenu untuk Temp Humid -->
				  <ul class="collapse list-unstyled pl-3" id="tempSubmenuaoi2">
					<li class="nav-item">
					  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/dashboard/temperature/aoi2/" target="_blank">
						<span class="ml-1 item-text">Dashboard</span>
					  </a>
					</li>
					<li class="nav-item">
					  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/report/temperature/aoi2/" target="_blank">
						<span class="ml-1 item-text">Report</span>
					  </a>
					</li>
				  </ul>
				</li>
				<li class="nav-item">
				  <a class="nav-link pl-3" href="#energySubmenuaoi2" data-toggle="collapse" aria-expanded="false">
					<span class="ml-1 item-text">Energy</span>
				  </a>
				  <!-- Sub-submenu untuk Energy -->
				  <ul class="collapse list-unstyled pl-3" id="energySubmenuaoi2">
					<li class="nav-item">
					  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/dashboard/energy/aoi2/" target="_blank">
						<span class="ml-1 item-text">Dashboard</span>
					  </a>
					</li>
					<li class="nav-item">
					  <a class="nav-link pl-3" href="http://enviro.bbi-apparel.com/report/energy/aoi2/index.php" target="_blank">
						<span class="ml-1 item-text">Report</span>
					  </a>
					</li>
				  </ul>
				</li>
				<!-- End Sub Menu -->
			  </ul>
			</li>
			<li class="nav-item dropdown">
			  <a href="#menuAoi5" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle nav-link">
				<i class="fe fe-layers fe-16"></i>
				<span class="ml-3 item-text">AOI 5</span>
			  </a>
			  <ul class="collapse list-unstyled pl-4 w-100" id="menuAoi5">
				<!-- Start Sub Menu -->
				<li class="nav-item">
				  <a class="nav-link pl-3" href="#tempSubmenuaoi5" data-toggle="collapse" aria-expanded="false">
					<span class="ml-1 item-text">Temp Humid</span>
				  </a>
				  <ul class="collapse list-unstyled pl-3" id="tempSubmenuaoi5">
					<li class="nav-item">
					  <a class="nav-link pl-3" href="dashboard/temperature/aoi5/" target="_blank">
						<span class="ml-1 item-text">Dashboard</span>
					  </a>
					</li>
					<li class="nav-item">
					  <a class="nav-link pl-3" href="report/temperature/aoi5/" target="_blank">
						<span class="ml-1 item-text">Report</span>
					  </a>
					</li>
				  </ul>
			    <a class="nav-link pl-3" href="#energySubmenuaoi5" data-toggle="collapse" aria-expanded="false">
				    <span class="ml-1 item-text">Energy</span>
				</a>
				<ul class="collapse list-unstyled pl-3" id="energySubmenuaoi5">
					<li class="nav-item">
					    <a class="nav-link pl-3" href="http://iotelectricity.bbi-apparel.com" target="_blank">
						    <span class="ml-1 item-text">Dashboard</span>
					    </a>
					</li>
					<li class="nav-item">
					    <a class="nav-link pl-3" href="http://iotelectricity.bbi-apparel.com/export-data" target="_blank">
							<span class="ml-1 item-text">Report</span>
						</a>
					</li>
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

	  <div class="mu-nav-scrim" data-role="nav-scrim" aria-hidden="true"></div>

<!-- Start Content -->
<?php
$slots_menu = require __DIR__ . '/config/menu_utama.php';

function mu_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<main role="main" class="main-content">
  <div class="mu-board">
    <div class="mu-board__grid">

      <div class="mu-tiles">
        <?php foreach ($slots_menu as $slot): ?>
          <?php $has_url = !empty($slot['url']); ?>
          <article class="mu-card"
                   data-id="<?= mu_e($slot['id']) ?>"
                   data-state="<?= $has_url ? 'loading' : 'empty' ?>"
                   data-design-width="<?= (int) $slot['design_width'] ?>"
                   <?= $has_url ? 'data-src="' . mu_e($slot['url']) . '"' : '' ?>
                   aria-label="<?= mu_e(($has_url ? 'Pratinjau ' : '') . $slot['title'] . ' ' . $slot['area'] . ($has_url ? '' : ' belum terpasang')) ?>">

            <header class="mu-card__head">
              <span class="mu-dot" aria-hidden="true"></span>
              <span class="mu-card__id">
                <span class="mu-card__title"><?= mu_e($slot['title']) ?></span>
                <span class="mu-card__area"><?= mu_e($slot['area']) ?></span>
              </span>
              <?php if ($has_url): ?>
                <a class="mu-card__open"
                   href="<?= mu_e($slot['url']) ?>"
                   target="_blank" rel="noopener"
                   aria-label="Buka <?= mu_e($slot['title'] . ' ' . $slot['area']) ?> di tab baru"
                   title="Buka <?= mu_e($slot['title'] . ' ' . $slot['area']) ?> di tab baru">
                  <i class="fe fe-external-link" aria-hidden="true"></i>
                </a>
              <?php endif; ?>
            </header>

            <div class="mu-card__view">
              <?php if ($has_url): ?>
                <iframe class="mu-frame"
                        title="Pratinjau live <?= mu_e($slot['title'] . ' ' . $slot['area']) ?>"
                        tabindex="-1" aria-hidden="true" scrolling="no"
                        sandbox="allow-same-origin allow-scripts allow-forms"></iframe>
                <div class="mu-state mu-state--loading">
                  <p class="mu-state__title">Memuat dashboard…</p>
                </div>
                <div class="mu-state mu-state--error">
                  <p class="mu-state__title">Dashboard tidak merespons</p>
                  <p class="mu-state__body">Periksa koneksi jaringan, lalu muat ulang.</p>
                  <button type="button" class="mu-btn mu-state__action" data-action="reload">Muat ulang</button>
                </div>
              <?php else: ?>
                <div class="mu-state mu-state--empty">
                  <p class="mu-state__title">Belum terpasang</p>
                  <p class="mu-state__body">Dashboard ini belum dihubungkan ke menu utama.</p>
                </div>
              <?php endif; ?>
            </div>

            <footer class="mu-card__foot">
              <span class="mu-card__loaded" data-role="loaded"></span>
              <span class="mu-card__state" data-role="status"><?= $has_url ? 'Memuat' : 'Kosong' ?></span>
            </footer>
          </article>
        <?php endforeach; ?>
      </div>

      <aside class="mu-rail">
        <section class="mu-panel mu-rail__clock" aria-label="Waktu saat ini">
          <h1 class="mu-panel__title">Menu utama</h1>
          <p class="mu-clock"><time id="mu-clock" datetime="">--:--:--</time></p>
          <p class="mu-date"><time id="mu-date">—</time></p>
        </section>

        <section class="mu-panel mu-panel--grow" aria-labelledby="mu-summary-title">
          <div class="mu-summary__head">
            <h2 class="mu-section-title" id="mu-summary-title">Ringkasan monitor</h2>
          </div>

          <div class="mu-chips" aria-live="off">
            <span class="mu-chip mu-chip--ok" data-chip="online">
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-online">— aktif</span>
            </span>
            <span class="mu-chip mu-chip--warn" data-chip="loading" hidden>
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-loading">— memuat</span>
            </span>
            <span class="mu-chip mu-chip--danger" data-chip="error" hidden>
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-error">— gagal</span>
            </span>
            <span class="mu-chip mu-chip--muted" data-chip="empty" hidden>
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-empty">— kosong</span>
            </span>
          </div>

          <ul class="mu-list">
            <?php foreach ($slots_menu as $slot): ?>
              <li class="mu-list__row"
                  data-slot="<?= mu_e($slot['id']) ?>"
                  data-state="<?= $slot['url'] ? 'loading' : 'empty' ?>">
                <span class="mu-dot" aria-hidden="true"></span>
                <span class="mu-list__label">
                  <span class="mu-list__title"><?= mu_e($slot['title']) ?></span>
                  <span class="mu-list__area"><?= mu_e($slot['area']) ?></span>
                </span>
                <span class="mu-list__state" data-role="summary-status"><?= $slot['url'] ? 'Memuat' : 'Kosong' ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </aside>

    </div>
  </div>
</main>
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
        //var host = "localhost";
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
          // Topik sensor PLC AOI 5
          var topics = [
            "data/temperature/area501/+",
            "data/temperature/area502/+"
          ];

          topics.forEach(function(tp) {
            mqtt.subscribe(tp);
          });
          return false;
        }

        var ONE_HOUR_MS = 60 * 60 * 1000;

        function onMessageArrived(r_message) {
          try {
            var topic = r_message.destinationName || "";
            var payloadStr = r_message.payloadString || "{}";

            var data = JSON.parse(payloadStr);

            if (data && data.payload) {
              if (typeof data.payload === 'string') {
                try { data = JSON.parse(data.payload); } catch(ex) {}
              } else if (typeof data.payload === 'object') {
                data = data.payload;
              }
            }

            var raw_temp = undefined;
            if (data.temper !== undefined && data.temper !== null) raw_temp = data.temper;
            else if (data.temp !== undefined && data.temp !== null) raw_temp = data.temp;
            else if (data.temperature !== undefined && data.temperature !== null) raw_temp = data.temperature;

            var raw_hum = undefined;
            if (data.humidi !== undefined && data.humidi !== null) raw_hum = data.humidi;
            else if (data.humidity !== undefined && data.humidity !== null) raw_hum = data.humidity;
            else if (data.hum !== undefined && data.hum !== null) raw_hum = data.hum;
            else if (data.rh !== undefined && data.rh !== null) raw_hum = data.rh;
            else if (data.kelembapan !== undefined && data.kelembapan !== null) raw_hum = data.kelembapan;

            var temp_val = (raw_temp !== undefined && !isNaN(parseFloat(raw_temp))) ? Math.round(parseFloat(raw_temp) * 10) / 10 : null;
            var hum_val  = (raw_hum !== undefined && !isNaN(parseFloat(raw_hum))) ? Math.round(parseFloat(raw_hum) * 10) / 10 : null;
            var time_val = data._terminalTime || data.record_time || new Date().toISOString().slice(0, 19).replace('T', ' ');

            // Identifikasi sensor: 501 (Area_1) atau 502 (Area_2)
            var locId = null;
            var topic_lower = topic.toLowerCase();
            if (topic_lower.indexOf('area502') !== -1 || topic_lower.indexOf('502') !== -1) {
              locId = '502';
            } else if (topic_lower.indexOf('area501') !== -1 || topic_lower.indexOf('501') !== -1) {
              locId = '501';
            } else if (data._groupName && (data._groupName.indexOf('Area_2') !== -1 || data._groupName.indexOf('area_2') !== -1 || data._groupName.indexOf('area2') !== -1)) {
              locId = '502';
            } else if (data._groupName && (data._groupName.indexOf('Area_1') !== -1 || data._groupName.indexOf('area_1') !== -1 || data._groupName.indexOf('area1') !== -1)) {
              locId = '501';
            } else if (data.id_location) {
              locId = topic_map[data.id_location] || null;
            }

            // Jika bukan topik / sensor milik AOI 5, abaikan
            if (!locId) {
              return;
            }

            // Simpan otomatis hanya jika interval sudah 1 jam
            checkAndSaveHourly(locId, temp_val, hum_val, time_val);
          } catch (e) {
            console.error("[Background MQTT] Error parsing payload:", e);
          }
        }

        function checkAndSaveHourly(locId, temp, hum, recTime) {
          var now = Date.now();
          var storageKey = 'enviro_aoi5_last_save_' + locId;
          var lastSaved = parseInt(localStorage.getItem(storageKey) || '0', 10);

          if (now - lastSaved >= ONE_HOUR_MS) {
            localStorage.setItem(storageKey, now.toString());
            saveToDatabase(locId, temp, hum, recTime);
          }
        }

        function saveToDatabase(locId, temp, hum, recTime) {
          var payload = {
            id_location: locId,
            temp: temp !== null ? temp : 0,
            humidity: hum !== null ? hum : 0,
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
              console.log("[Background Auto-Save Hourly] ✓ Saved to temp_humid:", result.data);
            } else if (result.status === 'skipped') {
              console.log("[Background Auto-Save Hourly] - Throttled:", result.message);
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
    <script src="js/menu-utama.js?v=<?= filemtime(__DIR__ . '/js/menu-utama.js') ?>" defer></script>
  </body>
</html>
