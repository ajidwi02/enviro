<?php
include '../../../config_temp_humid.php';

// Definisi 2 Sensor Awal untuk AOI 5
$sensor_configs = array(
    array(
        'id_location' => '501',
        'topic_key'   => 'area501',
        'location'    => 'Folding Area',
        'default_temp' => 25.0,
        'default_hum'  => 60.0
    ),
    array(
        'id_location' => '502',
        'topic_key'   => 'area502',
        'location'    => 'Dry Room',
        'default_temp' => 25.0,
        'default_hum'  => 60.0
    )
);

$sensors = array();

foreach ($sensor_configs as $cfg) {
    $id_loc = $cfg['id_location'];
    $topic_key = $cfg['topic_key'];
    
    $current_temp = $cfg['default_temp'];
    $current_hum = $cfg['default_hum'];
    $last_record_time = 'Waiting data...';
    
    // 1. Ambil Data Terakhir dari Tabel temp_humid
    if (isset($conn) && $conn) {
        $query_latest = "SELECT temp, humidity, record_time 
                         FROM temp_humid 
                         WHERE id_location = '$id_loc' OR id_location = '$topic_key' OR id_location = 'temp" . ($id_loc == '501' ? '1' : '2') . "'
                         ORDER BY record_time DESC 
                         LIMIT 1";
        $res_latest = mysqli_query($conn, $query_latest);
        if ($res_latest && mysqli_num_rows($res_latest) > 0) {
            $row_latest = mysqli_fetch_assoc($res_latest);
            $current_temp = floatval($row_latest['temp']);
            $current_hum = floatval($row_latest['humidity']);
            $last_record_time = $row_latest['record_time'];
        }
        
        // 2. Ambil Standar Ambang Batas dari master_standard jika ada
        $select_std = "SELECT * FROM master_standard WHERE id_location = '$id_loc' OR id_location = '$topic_key' OR id_location = 'temp" . ($id_loc == '501' ? '1' : '2') . "'";
        $res_std = mysqli_query($conn, $select_std);
        $row_std = ($res_std && mysqli_num_rows($res_std) > 0) ? mysqli_fetch_assoc($res_std) : null;
    } else {
        $row_std = null;
    }

    $sensors[] = array(
        'id_location'      => $id_loc,
        'topic_key'        => $topic_key,
        'location'         => $cfg['location'],
        'temp'             => (is_numeric($current_temp)) ? floatval($current_temp) : 25.0,
        'humidity'         => (is_numeric($current_hum)) ? floatval($current_hum) : 60.0,
        'last_record_time' => $last_record_time,
        'standard'         => array(
            'min_value_temp'   => (!empty($row_std['min_value_temp'])) ? floatval($row_std['min_value_temp']) : 10,
            'max_red_temp'     => (!empty($row_std['max_red_temp'])) ? floatval($row_std['max_red_temp']) : 18,
            'max_yellow1_temp' => (!empty($row_std['max_yellow1_temp'])) ? floatval($row_std['max_yellow1_temp']) : 21,
            'max_green_temp'   => (!empty($row_std['max_green_temp'])) ? floatval($row_std['max_green_temp']) : 27,
            'max_yellow2_temp' => (!empty($row_std['max_yellow2_temp'])) ? floatval($row_std['max_yellow2_temp']) : 30,
            'max_value_temp'   => (!empty($row_std['max_value_temp'])) ? floatval($row_std['max_value_temp']) : 40,
            'min_value_hum'    => (!empty($row_std['min_value_hum'])) ? floatval($row_std['min_value_hum']) : 30,
            'max_green_hum'    => (!empty($row_std['max_green_hum'])) ? floatval($row_std['max_green_hum']) : 55,
            'max_yellow2_hum'  => (!empty($row_std['max_yellow2_hum'])) ? floatval($row_std['max_yellow2_hum']) : 60,
            'max_value_hum'    => (!empty($row_std['max_value_hum'])) ? floatval($row_std['max_value_hum']) : 100
        )
    );
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Dashboard Temperature and Humidity AOI 5">
    <title>Dashboard Temperature & Humidity AOI 5</title>
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="../../../css/simplebar.css">
    <link rel="stylesheet" href="../../../css/feather.css">
    <link rel="stylesheet" href="../../../css/app-light.css" id="lightTheme">
    
    <!-- JS Dependencies -->
    <script src="../../../js/jquery.min.js" type="text/javascript"></script>
    <script src="../../../js/highcharts.js" type="text/javascript"></script>
    <script src="../../../js/highcharts-more.js" type="text/javascript"></script>
    <script src="../../../mqtt/mqttws31.js" type="text/javascript"></script>
    
    <style>
        /* LED Indicator Styles */
        .led-indicator {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-block;
            margin: 0 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
            background-color: #999999;
            transition: all 0.3s ease;
        }

        .led-indicator.active {
            background-color: #ff0000;
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.9), inset 0 0 5px rgba(255, 100, 100, 0.8);
            animation: pulse-red 1s infinite alternate;
        }

        @keyframes pulse-red {
            from { transform: scale(1); opacity: 0.85; }
            to { transform: scale(1.15); opacity: 1; }
        }

        .gauge-label-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .sensor-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .last-update-tag {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
    <script>
    // Realtime Clock
    function showTime() {
        var d = new Date();
        var clockElem = document.getElementById("clock");
        if (clockElem) {
            clockElem.innerHTML = d.toLocaleTimeString();
        }
    }
    setInterval(showTime, 1000);

    // Auto reload page after 30 Minutes
    var timeout = 30 * 60 * 1000;
    setTimeout(() => {
        document.location.reload();
    }, timeout);

    // Global Registry for Gauges & Standards
    var gauge_temp = {};
    var gauge_hum = {};
    var standard_values = {};
    
    // Topic mapping: area501 -> 501, area502 -> 502, temp1 -> 501, temp2 -> 502
    var topic_to_location = {
        'area501': '501',
        'area502': '502',
        '501': '501',
        '502': '502',
        'temp1': '501',
        'temp2': '502',
        'Area_1': '501',
        'Area_2': '502'
    };

    // Threshold Check Functions
    function isTempInRedZone(temp_val, id_loc) {
        var std = standard_values[id_loc];
        if (!std) return false;
        return (temp_val < std.max_red_temp || temp_val > std.max_yellow2_temp);
    }

    function isHumInRedZone(hum_val, id_loc) {
        var std = standard_values[id_loc];
        if (!std) return false;
        return (hum_val > std.max_yellow2_hum);
    }

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
        <table style="width:100%" class="mb-3">
            <tr>
                <td style="width: 10%;text-align: center;vertical-align: middle;">
                    <img src="../../../logo_aoi.png" width="80" alt="Logo AOI">
                </td>
                <td style="width: 80%;vertical-align: middle;">
                    <div class="w-75 mx-auto text-center">
                        <h2 class="page-title mb-0 font-weight-bold">Temperature and Humidity AOI 5</h2>
                        <p class="mb-1 small text-muted">
                            <span id="datenow"></span> / <span id="clock"></span>
                            <span class="badge badge-info ml-2" id="mqtt_badge">MQTT Connecting...</span>
                            <a href="../../../report/temperature/aoi5/" class="badge badge-secondary ml-1"><i class="fe fe-file-text"></i> View Report</a>
                        </p>
                        <script>
                        const months = ["Jan", "Feb", "Mar","Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                        let current_datetime = new Date();
                        let formatted_date = current_datetime.getDate() + "-" + months[current_datetime.getMonth()] + "-" + current_datetime.getFullYear();
                        document.getElementById("datenow").innerHTML = formatted_date;
                        </script>
                    </div>
                </td>
                <td style="width: 10%;text-align:center;vertical-align: middle;">
                    <img src="../../../nagaileben.png" width="180" alt="Logo BBI">
                </td>
            </tr>
        </table>

        <!-- Grid Cards Sensor -->
        <div class="row">
            <?php foreach ($sensors as $sensor): 
                $id_location = $sensor['id_location'];
                $location = $sensor['location'];
                $temp = $sensor['temp'];
                $humidity = $sensor['humidity'];
                $last_time = $sensor['last_record_time'];
                $std = $sensor['standard'];
            ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow eq-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="sensor-card-title"><?php echo htmlspecialchars($location); ?></div>
                            <div class="text-right">
                                <span class="last-update-tag d-block" id="last_time_<?php echo $id_location; ?>">
                                    Last: <?php echo htmlspecialchars($last_time); ?>
                                </span>
                                <span class="small text-muted" id="db_status_<?php echo $id_location; ?>" style="font-size: 0.72rem;">
                                    Auto-Save DB: Setiap 1 Jam
                                </span>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Gauge Temperature -->
                            <div class="col-6 text-center border-right pr-2 pl-2">
                                <div class="gauge-label-container mb-1">
                                    <span class="font-weight-bold text-muted">Temperature</span>
                                    <span class="led-indicator" id="led_temp_<?php echo $id_location; ?>"></span>
                                </div>
                                <div id="gauge_temp_<?php echo $id_location; ?>" style="min-height: 220px; width: 100%;"></div>
                            </div>

                            <!-- Gauge Humidity -->
                            <div class="col-6 text-center pr-2 pl-2">
                                <div class="gauge-label-container mb-1">
                                    <span class="font-weight-bold text-muted">Humidity</span>
                                    <span class="led-indicator" id="led_hum_<?php echo $id_location; ?>"></span>
                                </div>
                                <div id="gauge_hum_<?php echo $id_location; ?>" style="min-height: 220px; width: 100%;"></div>
                            </div>
                        </div>
                    </div> <!-- .card-body -->
                </div> <!-- .card -->
            </div> <!-- .col-md-6 -->

            <script>
            $(document).ready(function() {
                /*  Register Standard Values for ID <?php echo $id_location; ?> */
                standard_values['<?php echo $id_location; ?>'] = {
                    'min_value_temp': <?php echo floatval($std['min_value_temp']); ?>,
                    'max_red_temp': <?php echo floatval($std['max_red_temp']); ?>,
                    'max_yellow1_temp': <?php echo floatval($std['max_yellow1_temp']); ?>,
                    'max_green_temp': <?php echo floatval($std['max_green_temp']); ?>,
                    'max_yellow2_temp': <?php echo floatval($std['max_yellow2_temp']); ?>,
                    'max_value_temp': <?php echo floatval($std['max_value_temp']); ?>,
                    'min_value_hum': <?php echo floatval($std['min_value_hum']); ?>,
                    'max_green_hum': <?php echo floatval($std['max_green_hum']); ?>,
                    'max_yellow2_hum': <?php echo floatval($std['max_yellow2_hum']); ?>,
                    'max_value_hum': <?php echo floatval($std['max_value_hum']); ?>
                };

                // Register Highcharts Gauge Temperature
                var init_temp_<?php echo $id_location; ?> = <?php echo floatval($temp); ?>;
                updateLED('led_temp_<?php echo $id_location; ?>', isTempInRedZone(init_temp_<?php echo $id_location; ?>, '<?php echo $id_location; ?>'));

                Highcharts.chart('gauge_temp_<?php echo $id_location; ?>', {
                    chart: {
                        type: 'gauge',
                        plotBackgroundColor: null,
                        plotBackgroundImage: null,
                        plotBorderWidth: 0,
                        plotShadow: false,
                        height: '70%',
                        backgroundColor: 'transparent'
                    },
                    title: { text: '' },
                    credits: { enabled: false },
                    pane: {
                        startAngle: -120,
                        endAngle: 120,
                        background: null,
                        center: ['50%', '60%'],
                        size: '110%'
                    },
                    yAxis: {
                        min: 10,
                        max: 40,
                        tickPixelInterval: 5,
                        tickPosition: 'inside',
                        tickColor: '#FFFFFF',
                        tickLength: 8,
                        tickWidth: 1,
                        minorTickInterval: 5,
                        labels: {
                            distance: 10,
                            style: { fontSize: '10px' }
                        },
                        plotBands: [
                            { from: <?php echo floatval($std['min_value_temp']); ?>, to: <?php echo floatval($std['max_red_temp']); ?>, color: '#e74c3c', thickness: 22 },
                            { from: <?php echo floatval($std['max_red_temp']); ?>, to: <?php echo floatval($std['max_yellow1_temp']); ?>, color: '#f1c40f', thickness: 22 },
                            { from: <?php echo floatval($std['max_yellow1_temp']); ?>, to: <?php echo floatval($std['max_green_temp']); ?>, color: '#2ecc71', thickness: 22 },
                            { from: <?php echo floatval($std['max_green_temp']); ?>, to: <?php echo floatval($std['max_yellow2_temp']); ?>, color: '#f1c40f', thickness: 22 },
                            { from: <?php echo floatval($std['max_yellow2_temp']); ?>, to: <?php echo floatval($std['max_value_temp']); ?>, color: '#e74c3c', thickness: 22 }
                        ]
                    },
                    series: [{
                        name: 'Temperature',
                        data: [init_temp_<?php echo $id_location; ?>],
                        animation: false,
                        tooltip: { valueSuffix: ' °C' },
                        dataLabels: {
                            format: '{y} °C',
                            borderWidth: 0,
                            color: '#333333',
                            style: { fontSize: '17px', fontWeight: 'bold' },
                            zIndex: 3
                        },
                        dial: {
                            radius: '100%',
                            backgroundColor: '#2c3e50',
                            borderColor: '#ffffff',
                            borderWidth: 1,
                            baseWidth: 6,
                            baseLength: '5%',
                            rearLength: '-10%'
                        },
                        pivot: {
                            backgroundColor: '#2c3e50',
                            radius: 5
                        }
                    }]
                });

                // Set update function for temperature (Instant Real-time)
                gauge_temp['<?php echo $id_location; ?>'] = function (newvalue) {
                    var chart = Highcharts.charts.find(function(c) { return c && c.renderTo && c.renderTo.id === 'gauge_temp_<?php echo $id_location; ?>'; }) || $('#gauge_temp_<?php echo $id_location; ?>').highcharts();
                    if (chart && chart.series && chart.series[0] && chart.series[0].points && chart.series[0].points[0]) {
                        chart.series[0].points[0].update(newvalue, true, { duration: 400 });
                    }
                    updateLED('led_temp_<?php echo $id_location; ?>', isTempInRedZone(newvalue, '<?php echo $id_location; ?>'));
                };

                // Register Highcharts Gauge Humidity
                var init_hum_<?php echo $id_location; ?> = <?php echo floatval($humidity); ?>;
                updateLED('led_hum_<?php echo $id_location; ?>', isHumInRedZone(init_hum_<?php echo $id_location; ?>, '<?php echo $id_location; ?>'));

                Highcharts.chart('gauge_hum_<?php echo $id_location; ?>', {
                    chart: {
                        type: 'gauge',
                        plotBackgroundColor: null,
                        plotBackgroundImage: null,
                        plotBorderWidth: 0,
                        plotShadow: false,
                        height: '70%',
                        backgroundColor: 'transparent'
                    },
                    title: { text: '' },
                    credits: { enabled: false },
                    pane: {
                        startAngle: -120,
                        endAngle: 120,
                        background: null,
                        center: ['50%', '60%'],
                        size: '110%'
                    },
                    yAxis: {
                        min: 30,
                        max: 100,
                        tickPixelInterval: 5,
                        tickPosition: 'inside',
                        tickColor: '#FFFFFF',
                        tickLength: 8,
                        tickWidth: 1,
                        minorTickInterval: 5,
                        labels: {
                            distance: 10,
                            style: { fontSize: '10px' }
                        },
                        plotBands: [
                            { from: <?php echo floatval($std['min_value_hum']); ?>, to: <?php echo floatval($std['max_green_hum']); ?>, color: '#2ecc71', thickness: 22 },
                            { from: <?php echo floatval($std['max_green_hum']); ?>, to: <?php echo floatval($std['max_yellow2_hum']); ?>, color: '#f1c40f', thickness: 22 },
                            { from: <?php echo floatval($std['max_yellow2_hum']); ?>, to: <?php echo floatval($std['max_value_hum']); ?>, color: '#e74c3c', thickness: 22 }
                        ]
                    },
                    series: [{
                        name: 'Humidity',
                        data: [init_hum_<?php echo $id_location; ?>],
                        animation: false,
                        tooltip: { valueSuffix: ' %' },
                        dataLabels: {
                            format: '{y} %',
                            borderWidth: 0,
                            color: '#333333',
                            style: { fontSize: '17px', fontWeight: 'bold' },
                            zIndex: 3
                        },
                        dial: {
                            radius: '100%',
                            backgroundColor: '#2c3e50',
                            borderColor: '#ffffff',
                            borderWidth: 1,
                            baseWidth: 6,
                            baseLength: '5%',
                            rearLength: '-10%'
                        },
                        pivot: {
                            backgroundColor: '#2c3e50',
                            radius: 5
                        }
                    }]
                });

                // Set update function for humidity (Instant Real-time)
                gauge_hum['<?php echo $id_location; ?>'] = function (newvalue) {
                    var chart = Highcharts.charts.find(function(c) { return c && c.renderTo && c.renderTo.id === 'gauge_hum_<?php echo $id_location; ?>'; }) || $('#gauge_hum_<?php echo $id_location; ?>').highcharts();
                    if (chart && chart.series && chart.series[0] && chart.series[0].points && chart.series[0].points[0]) {
                        chart.series[0].points[0].update(newvalue, true, { duration: 400 });
                    }
                    updateLED('led_hum_<?php echo $id_location; ?>', isHumInRedZone(newvalue, '<?php echo $id_location; ?>'));
                };
            });
            </script>
            <?php endforeach; ?>
        </div>

        <!-- Footer / Debug Status -->
        <div class="row mt-2">
            <div class="col-12 text-center text-muted small">
                <span id="status">MQTT Status: Initializing...</span> | 
                <span id="messages">Waiting for sensor data...</span>
            </div>
        </div>
      </main>
    </div>

    <!-- Scripts -->
    <script src="../../../js/popper.min.js"></script>
    <script src="../../../js/bootstrap.min.js"></script>

    <!-- MQTT Connection Logic -->
    <script type="text/javascript">
    var mqtt;
    var reconnectTimeout = 5000;
    var host = "192.168.51.40";
    var port = 8083;
    var connected_flag = 0;

    function onConnectionLost(responseObject) {
        console.log("MQTT Connection lost: " + (responseObject ? responseObject.errorMessage : ''));
        connected_flag = 0;
        document.getElementById("status").innerHTML = "MQTT Connection Lost";
        document.getElementById("mqtt_badge").className = "badge badge-danger ml-2";
        document.getElementById("mqtt_badge").innerHTML = "MQTT Disconnected";
        setTimeout(MQTTconnect, reconnectTimeout);
    }

    function onFailure(message) {
        console.log("MQTT Connect Failed");
        document.getElementById("status").innerHTML = "MQTT Connection Failed - Retrying in 5s";
        document.getElementById("mqtt_badge").className = "badge badge-warning ml-2";
        document.getElementById("mqtt_badge").innerHTML = "MQTT Retry";
        setTimeout(MQTTconnect, reconnectTimeout);
    }

    function onConnect() {
        connected_flag = 1;
        console.log("Connected to MQTT Broker " + host + ":" + port);
        document.getElementById("status").innerHTML = "Connected to MQTT Broker (" + host + ":" + port + ")";
        document.getElementById("mqtt_badge").className = "badge badge-success ml-2";
        document.getElementById("mqtt_badge").innerHTML = "MQTT Connected";
        return sub_topics();
    }

    function sub_topics() {
        if (connected_flag == 0) return false;

        // Subscribing to AOI 5 temperature & humidity PLC topics
        var topics = [
            "data/temperature/area501/+",
            "data/temperature/area502/+"
        ];

        topics.forEach(function(tp) {
            console.log("Subscribing to: " + tp);
            mqtt.subscribe(tp);
        });

        return false;
    }

    // Interval simpan database: 1 Jam (60 menit = 3.600.000 ms)
    var ONE_HOUR_MS = 60 * 60 * 1000;

    function onMessageArrived(r_message) {
        try {
            var topic = r_message.destinationName || "";
            var payloadStr = r_message.payloadString || "{}";
            console.log("MQTT Msg Arrived:", topic, payloadStr);

            // Parsing JSON Output
            // Format PLC: {"_terminalTime":"2026-10-20 15:18:43.154","_groupName":"Area_1","temper":"29.2","humidi":"49.7"}
            var data = JSON.parse(payloadStr);

            // Dukungan jika payload bersarang (nested payload)
            if (data && data.payload) {
                if (typeof data.payload === 'string') {
                    try { data = JSON.parse(data.payload); } catch(ex) {}
                } else if (typeof data.payload === 'object') {
                    data = data.payload;
                }
            }

            // Ekstrak temperature (temper, temp, temperature)
            var raw_temp = undefined;
            if (data.temper !== undefined && data.temper !== null) raw_temp = data.temper;
            else if (data.temp !== undefined && data.temp !== null) raw_temp = data.temp;
            else if (data.temperature !== undefined && data.temperature !== null) raw_temp = data.temperature;

            // Ekstrak humidity (humidi, humidity, hum, rh, kelembapan)
            var raw_hum = undefined;
            if (data.humidi !== undefined && data.humidi !== null) raw_hum = data.humidi;
            else if (data.humidity !== undefined && data.humidity !== null) raw_hum = data.humidity;
            else if (data.hum !== undefined && data.hum !== null) raw_hum = data.hum;
            else if (data.rh !== undefined && data.rh !== null) raw_hum = data.rh;
            else if (data.kelembapan !== undefined && data.kelembapan !== null) raw_hum = data.kelembapan;

            var temp_val = (raw_temp !== undefined && !isNaN(parseFloat(raw_temp))) ? Math.round(parseFloat(raw_temp) * 10) / 10 : null;
            var hum_val  = (raw_hum !== undefined && !isNaN(parseFloat(raw_hum))) ? Math.round(parseFloat(raw_hum) * 10) / 10 : null;
            var time_val = data._terminalTime || data.record_time || new Date().toLocaleTimeString();

            // Ekstrak sensor: 501 (Area_1) atau 502 (Area_2)
            var locId = null;
            var sensorKey = null;
            var topic_lower = topic.toLowerCase();

            if (topic_lower.indexOf('area502') !== -1 || topic_lower.indexOf('502') !== -1) {
                locId = '502';
                sensorKey = 'area502';
            } else if (topic_lower.indexOf('area501') !== -1 || topic_lower.indexOf('501') !== -1) {
                locId = '501';
                sensorKey = 'area501';
            } else if (data._groupName && (data._groupName.indexOf('Area_2') !== -1 || data._groupName.indexOf('area_2') !== -1 || data._groupName.indexOf('area2') !== -1)) {
                locId = '502';
                sensorKey = 'area502';
            } else if (data._groupName && (data._groupName.indexOf('Area_1') !== -1 || data._groupName.indexOf('area_1') !== -1 || data._groupName.indexOf('area1') !== -1)) {
                locId = '501';
                sensorKey = 'area501';
            } else if (data.id_location) {
                locId = topic_to_location[data.id_location] || null;
                sensorKey = locId;
            }

            // Jika bukan sensor milik AOI 5, abaikan
            if (!locId) {
                return;
            }

            // 1. UPDATE GAUGE SUHU SECARA INSTAN / REAL-TIME
            if (temp_val !== null && gauge_temp[locId]) {
                gauge_temp[locId](temp_val);
            }

            // 2. UPDATE GAUGE HUMIDITY SECARA INSTAN / REAL-TIME
            if (hum_val !== null && gauge_hum[locId]) {
                gauge_hum[locId](hum_val);
            }

            // 3. UPDATE WAKTU PENERIMAAN SENSOR DI HEADER CARD
            var timeElem = document.getElementById("last_time_" + locId);
            if (timeElem) {
                timeElem.innerHTML = "Last: " + time_val;
            }

            // 4. LOG STATUS PESAN
            var msg = "Received [" + sensorKey + "]";
            if (temp_val !== null) msg += " -> Temp: " + temp_val + "°C";
            if (hum_val !== null) msg += " | Hum: " + hum_val + "%";
            msg += " (" + time_val + ")";
            document.getElementById("messages").innerHTML = msg;

            // 5. THROTLED AUTO-SAVE KE DATABASE (HANYA SEKALI SETIAP 1 JAM PER SENSOR)
            var save_time = data._terminalTime || data.record_time || new Date().toISOString().slice(0, 19).replace('T', ' ');
            checkAndSaveHourly(locId, temp_val, hum_val, save_time);
        } catch (e) {
            console.error("Error parsing MQTT payload:", e, r_message.payloadString);
        }
    }

    function checkAndSaveHourly(locId, temp, hum, recTime) {
        var now = Date.now();
        var storageKey = 'enviro_aoi5_last_save_' + locId;
        var lastSaved = parseInt(localStorage.getItem(storageKey) || '0', 10);

        // Hanya kirim ke server jika sudah berlalu minimal 1 jam (3.600.000 ms)
        if (now - lastSaved >= ONE_HOUR_MS) {
            localStorage.setItem(storageKey, now.toString());
            saveToDatabase(locId, temp, hum, recTime);
        } else {
            var minsRemaining = Math.max(1, Math.round((ONE_HOUR_MS - (now - lastSaved)) / 60000));
            var dbStatusElem = document.getElementById("db_status_" + locId);
            if (dbStatusElem) {
                dbStatusElem.innerHTML = "<span class='text-muted' title='Interval simpan DB: 1 jam'>DB: Aktif (Simpan brikutnya ~" + minsRemaining + " mnt)</span>";
            }
        }
    }

    function saveToDatabase(locId, temp, hum, recTime) {
        var payload = {
            id_location: locId,
            temp: temp !== null ? temp : 0,
            humidity: hum !== null ? hum : 0,
            record_time: recTime
        };

        fetch('../../../api/save_temp_humid.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.json(); })
        .then(function(result) {
            var dbStatusElem = document.getElementById("db_status_" + locId);
            if (result.status === 'success') {
                console.log("[Dashboard Auto-Save Hourly] ✓ Saved to temp_humid:", result.data);
                if (dbStatusElem) {
                    var displayTime = (recTime && recTime.indexOf(' ') !== -1) ? recTime.split(' ')[1] : recTime;
                    dbStatusElem.innerHTML = "<span class='text-success font-weight-bold'>✓ DB Tersimpan (" + displayTime + ")</span>";
                }
            } else if (result.status === 'skipped') {
                console.log("[Dashboard Auto-Save Hourly] - Throttled by Backend API:", result.message);
                if (dbStatusElem) {
                    dbStatusElem.innerHTML = "<span class='text-info'>✓ DB Snapshot Aktif (1 Jam)</span>";
                }
            } else {
                console.error("[Dashboard Auto-Save Hourly] ✗ DB Error:", result.message);
            }
        })
        .catch(function(err) {
            console.error("[Dashboard Auto-Save] Error calling API:", err);
        });
    }

    function MQTTconnect() {
        var x = Math.floor(Math.random() * 10000);
        var cname = "enviro_Dashboard_AOI5_" + x;
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
            console.error("MQTT connection error:", err);
            setTimeout(MQTTconnect, reconnectTimeout);
        }
        return false;
    }
    </script>
  </body>
</html>
