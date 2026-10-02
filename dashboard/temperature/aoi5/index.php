<?php
include '../../../config_temp_humid.php';

// Definisi 2 Sensor Awal untuk AOI 5
$sensor_configs = array(
    array(
        'id_location' => '501',
        'topic_key'   => 'area501',
        'location'    => 'Sensor 1 - Temp & Humid AOI 5',
        'default_temp' => 25.0,
        'default_hum'  => 60.0
    ),
    array(
        'id_location' => '502',
        'topic_key'   => 'area502',
        'location'    => 'Sensor 2 - Temp & Humid AOI 5',
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
        'temp'             => $current_temp,
        'humidity'         => $current_hum,
        'last_record_time' => $last_record_time,
        'standard'         => $row_std ? $row_std : array(
            'min_value_temp'   => 10,
            'max_red_temp'     => 18,
            'max_yellow1_temp' => 21,
            'max_green_temp'   => 27,
            'max_yellow2_temp' => 30,
            'max_value_temp'   => 45,
            'min_value_hum'    => 30,
            'max_green_hum'    => 65,
            'max_yellow2_hum'  => 75,
            'max_value_hum'    => 100
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
                    <img src="../../../logo_aoi.png" width="110" alt="Logo AOI">
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
                    <img src="../../../logo_bbi.png" width="130" alt="Logo BBI">
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
                            <span class="last-update-tag" id="last_time_<?php echo $id_location; ?>">
                                Last: <?php echo htmlspecialchars($last_time); ?>
                            </span>
                        </div>
                        <div class="row">
                            <!-- Gauge Temperature -->
                            <div class="col-6 text-center border-right pr-2 pl-2">
                                <div class="gauge-label-container mb-1">
                                    <span class="font-weight-bold text-muted">Temperature</span>
                                    <span class="led-indicator" id="led_temp_<?php echo $id_location; ?>"></span>
                                </div>
                                <div id="gauge_temp_<?php echo $id_location; ?>" style="height: 230px;"></div>
                            </div>

                            <!-- Gauge Humidity -->
                            <div class="col-6 text-center pr-2 pl-2">
                                <div class="gauge-label-container mb-1">
                                    <span class="font-weight-bold text-muted">Humidity</span>
                                    <span class="led-indicator" id="led_hum_<?php echo $id_location; ?>"></span>
                                </div>
                                <div id="gauge_hum_<?php echo $id_location; ?>" style="height: 230px;"></div>
                            </div>
                        </div>
                    </div> <!-- .card-body -->
                </div> <!-- .card -->
            </div> <!-- .col-md-6 -->

            <script>
            // Register Standard Values for ID <?php echo $id_location; ?>
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
            let init_temp_<?php echo $id_location; ?> = <?php echo $temp; ?>;
            updateLED('led_temp_<?php echo $id_location; ?>', isTempInRedZone(init_temp_<?php echo $id_location; ?>, '<?php echo $id_location; ?>'));

            $('#gauge_temp_<?php echo $id_location; ?>').highcharts({
                chart: {
                    type: 'gauge',
                    plotBackgroundColor: null,
                    plotBackgroundImage: null,
                    plotBorderWidth: 0,
                    plotShadow: false,
                    backgroundColor: 'transparent'
                },
                title: { text: '' },
                credits: { enabled: false },
                pane: {
                    startAngle: -120,
                    endAngle: 120,
                    background: null,
                    center: ['50%', '60%'],
                    size: '100%'
                },
                yAxis: {
                    min: <?php echo floatval($std['min_value_temp']); ?>,
                    max: <?php echo floatval($std['max_value_temp']); ?>,
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

            // Set update function for temperature
            gauge_temp['<?php echo $id_location; ?>'] = function (newvalue) {
                var chart = $('#gauge_temp_<?php echo $id_location; ?>').highcharts();
                if (chart && chart.series && chart.series[0].points[0]) {
                    chart.series[0].points[0].update(newvalue);
                }
                updateLED('led_temp_<?php echo $id_location; ?>', isTempInRedZone(newvalue, '<?php echo $id_location; ?>'));
            };

            // Register Highcharts Gauge Humidity
            let init_hum_<?php echo $id_location; ?> = <?php echo $humidity; ?>;
            updateLED('led_hum_<?php echo $id_location; ?>', isHumInRedZone(init_hum_<?php echo $id_location; ?>, '<?php echo $id_location; ?>'));

            $('#gauge_hum_<?php echo $id_location; ?>').highcharts({
                chart: {
                    type: 'gauge',
                    plotBackgroundColor: null,
                    plotBackgroundImage: null,
                    plotBorderWidth: 0,
                    plotShadow: false,
                    backgroundColor: 'transparent'
                },
                title: { text: '' },
                credits: { enabled: false },
                pane: {
                    startAngle: -120,
                    endAngle: 120,
                    background: null,
                    center: ['50%', '60%'],
                    size: '100%'
                },
                yAxis: {
                    min: <?php echo floatval($std['min_value_hum']); ?>,
                    max: <?php echo floatval($std['max_value_hum']); ?>,
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

            // Set update function for humidity
            gauge_hum['<?php echo $id_location; ?>'] = function (newvalue) {
                var chart = $('#gauge_hum_<?php echo $id_location; ?>').highcharts();
                if (chart && chart.series && chart.series[0].points[0]) {
                    chart.series[0].points[0].update(newvalue);
                }
                updateLED('led_hum_<?php echo $id_location; ?>', isHumInRedZone(newvalue, '<?php echo $id_location; ?>'));
            };
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

        // Subscribing to AOI 5 temperature & humidity topics
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
            console.log("Subscribing to: " + tp);
            mqtt.subscribe(tp);
        });

        return false;
    }

    function onMessageArrived(r_message) {
        try {
            var topic = r_message.destinationName || "";
            var payloadStr = r_message.payloadString || "{}";
            console.log("MQTT Msg Arrived:", topic, payloadStr);

            // Parsing JSON Output
            // Format: {"_terminalTime":"2026-10-01 07:51:00.392","_groupName":"Area_1","temper":"31.5","humidi":"61.2"}
            var data = JSON.parse(payloadStr);

            var temp_val = Math.round(parseFloat(data.temper !== undefined ? data.temper : data.temp) * 10) / 10;
            var hum_val = Math.round(parseFloat(data.humidi !== undefined ? data.humidi : data.humidity) * 10) / 10;
            var time_val = data._terminalTime || data.record_time || new Date().toLocaleTimeString();

            // Extract sensor key / location ID from topic or payload
            var locId = '501';
            var sensorKey = 'area501';

            if (topic.indexOf('area502') !== -1 || topic.indexOf('502') !== -1 || topic.indexOf('temp2') !== -1) {
                locId = '502';
                sensorKey = 'area502';
            } else if (topic.indexOf('area501') !== -1 || topic.indexOf('501') !== -1 || topic.indexOf('temp1') !== -1) {
                locId = '501';
                sensorKey = 'area501';
            } else if (data._groupName && (data._groupName.toLowerCase().indexOf('area_2') !== -1 || data._groupName.toLowerCase().indexOf('area2') !== -1)) {
                locId = '502';
                sensorKey = 'area502';
            } else if (data._groupName && (data._groupName.toLowerCase().indexOf('area_1') !== -1 || data._groupName.toLowerCase().indexOf('area1') !== -1)) {
                locId = '501';
                sensorKey = 'area501';
            } else if (data.id_location) {
                locId = topic_to_location[data.id_location] || data.id_location;
                sensorKey = locId;
            }

            // Update Temperature Gauge
            if (gauge_temp[locId]) {
                gauge_temp[locId](temp_val);
            }

            // Update Humidity Gauge
            if (gauge_hum[locId]) {
                gauge_hum[locId](hum_val);
            }

            // Update timestamp
            var timeElem = document.getElementById("last_time_" + locId);
            if (timeElem) {
                timeElem.innerHTML = "Last: " + time_val;
            }

            document.getElementById("messages").innerHTML = "Received [" + sensorKey + "] -> Temp: " + temp_val + "°C | Hum: " + hum_val + "% (" + time_val + ")";

            // Auto-Save ke database MySQL via API save_temp_humid.php
            var save_time = data._terminalTime || data.record_time || new Date().toISOString().slice(0, 19).replace('T', ' ');
            saveToDatabase(locId, temp_val, hum_val, save_time);
        } catch (e) {
            console.error("Error parsing MQTT payload:", e, r_message.payloadString);
        }
    }

    function saveToDatabase(locId, temp, hum, recTime) {
        var payload = {
            id_location: locId,
            temp: temp,
            humidity: hum,
            record_time: recTime
        };

        fetch('../../../api/save_temp_humid.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.json(); })
        .then(function(result) {
            if (result.status === 'success') {
                console.log("[Dashboard Auto-Save] ✓ Saved to temp_humid:", result.data);
            } else {
                console.error("[Dashboard Auto-Save] ✗ DB Error:", result.message);
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
