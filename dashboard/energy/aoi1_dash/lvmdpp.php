<?php
include 'db.php';

// ===========================
// Ambil data terakhir (untuk gauge & info tambahan)
// ===========================
$sql_latest = "SELECT voltage_r, voltage_s, voltage_t, voltage_rs, voltage_st, voltage_rt,
                      current_r, current_s, current_t,
                      power_factor, power, kwh_total
               FROM electricity_aoi1_reporting 
               WHERE id_lokasi='101'
               ORDER BY datenow DESC LIMIT 1";
$result_latest = $conn->query($sql_latest);
$latest = [
    'voltage_r' => 0, 'voltage_s' => 0, 'voltage_t' => 0,
    'voltage_rs' => 0, 'voltage_st' => 0, 'voltage_rt' => 0,
    'current_r' => 0, 'current_s' => 0, 'current_t' => 0,
    'power_factor' => 0, 'power' => 0, 'kwh_total' => 0
];
if ($result_latest && $result_latest->num_rows > 0) {
    $latest = $result_latest->fetch_assoc();
}

// ===========================
// Ambil data hari ini (untuk grafik line)
// ===========================
$sql = "SELECT datenow, power, 
               voltage_r, voltage_s, voltage_t,
               voltage_rs, voltage_st, voltage_rt,
               current_r, current_s, current_t
        FROM electricity_aoi1_reporting 
        WHERE id_lokasi='101' 
          AND DATE(datenow) = CURDATE()
        ORDER BY datenow ASC";
$result = $conn->query($sql);

$labels = [];
$valuesPower = [];
$valuesVr = [];
$valuesVs = [];
$valuesVt = [];
$valuesVrs = [];
$valuesVst = [];
$valuesVrt = [];
$valuesIr = [];
$valuesIs = [];
$valuesIt = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['datenow'];
        $valuesPower[] = $row['power'];
        $valuesVr[] = $row['voltage_r'];
        $valuesVs[] = $row['voltage_s'];
        $valuesVt[] = $row['voltage_t'];
        $valuesVrs[] = $row['voltage_rs'];
        $valuesVst[] = $row['voltage_st'];
        $valuesVrt[] = $row['voltage_rt'];
        $valuesIr[] = $row['current_r'];
        $valuesIs[] = $row['current_s'];
        $valuesIt[] = $row['current_t'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>LVMDP Monitoring</title>
<script src="js/plotly-2.30.0.min.js"></script>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}
.main-container {
    display: flex;
}
.content {
    flex: 1;
    padding: 20px;
    margin-left: 250px;
    transition: margin-left 0.3s;
}
.content.full {
    margin-left: 0;
}
.toggle-btn {
    position: fixed;
    left: 260px;
    top: 15px;
    background: #1f2a40;
    color: white;
    padding: 6px 10px;
    cursor: pointer;
    border-radius: 4px;
    z-index: 200;
    transition: all 0.3s ease;
}
.toggle-btn.closed {
    left: 10px;
}

/* ===== Gauge Container ===== */
.gauge-container {
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-bottom: 30px;
}

.gauge-group {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    border: 1px solid #ddd;
}

.gauge-row {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 10px;
}

.gauge-box {
    background: #fff;
    border-radius: 12px;
    width: 18%;
    min-width: 160px;
    height: 200px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    border: 1px solid #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===== Kombinasi Gauge ===== */
.gauge-row.combined {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: nowrap;
}

.sub-group {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sub-title {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.gauge-subrow {
    display: flex;
    gap: 40px;
}

.divider {
    width: 2px;
    background: #ccc;
    margin: 0 15px;
    border-radius: 2px;
    height: 180px;
    align-self: center;
}

/* ===== Info Box (Power, PF, KWH) ===== */
.info-box {
    background: linear-gradient(135deg, #e0f7fa, #b2ebf2);
    border: 2px solid #00acc1;
    border-radius: 12px;
    padding: 20px;
    width: 260px;
    height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
}
.info-box h3 {
    color: #006064;
    margin: 5px 0;
    font-size: 20px;
}
.info-box span {
    font-size: 24px;
    font-weight: bold;
    color: #004d40;
}

/* ===== Chart Layout ===== */
.chart-row {
    display: flex;
    flex-direction: column;
    gap: 25px;
}
.chart-box {
    width: 100%;
    height: 350px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    padding: 10px;
}
h2, h3 {
    color: #333;
}
h4 {
    color: #444;
    margin-left: 10px;
    margin-top: 5px;
}

/* ===== Responsif ===== */
@media (max-width: 1200px) {
    .gauge-box {
        width: 20%;
        height: 160px;
        min-width: 120px;
    }
    .info-box {
        width: 220px;
        height: 160px;
    }
}
@media (max-width: 768px) {
    .content { margin-left: 0; }
    .gauge-box { width: 30%; height: 130px; }
    .divider { display: none; }
    .info-box { width: 100%; height: auto; margin-top: 15px; }
}
</style>
</head>

<body>
<div class="main-container">
    <?php include 'sidebar.php'; ?>

    <div id="toggleSidebar" class="toggle-btn">☰</div>

    <div id="content" class="content">
        <h2>📊 Data LVMDP Hari Ini</h2>

        <!-- === GAGUE SECTION === -->
        <div class="gauge-container">

            <!-- ===== Gabungan Tegangan 1 & 3 Phasa ===== -->
            <div class="gauge-group">
                <h4>⚡ Tegangan 1 Phasa & 3 Phasa</h4>
                <div class="gauge-row combined">
                    <div class="sub-group">
                        <div class="sub-title">1 Phasa</div>
                        <div class="gauge-subrow">
                            <div id="gaugeR" class="gauge-box"></div>
                            <div id="gaugeS" class="gauge-box"></div>
                            <div id="gaugeT" class="gauge-box"></div>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <div class="sub-group">
                        <div class="sub-title">3 Phasa (Antar Fasa)</div>
                        <div class="gauge-subrow">
                            <div id="gaugeRS" class="gauge-box"></div>
                            <div id="gaugeST" class="gauge-box"></div>
                            <div id="gaugeRT" class="gauge-box"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Arus & Info Box ===== -->
            <div class="gauge-group">
                <h4>⚙️ Arus R, S, T (Ampere)</h4>
                <div class="gauge-row">
                    <div id="gaugeIR" class="gauge-box"></div>
                    <div id="gaugeIS" class="gauge-box"></div>
                    <div id="gaugeIT" class="gauge-box"></div>

                    <div class="info-box">
                        <h3>Power Factor: <span><?= number_format($latest['power_factor'], 3) ?></span></h3>
                        <h3>Power: <span><?= number_format($latest['power'], 2) ?> W</span></h3>
                        <h3>KWH Total: <span><?= number_format($latest['kwh_total'], 2) ?> kWh</span></h3>
                    </div>
                </div>
            </div>

        </div>

        <div class="chart-row">
            <div><h3>Tegangan R, S, T (Volt)</h3><div id="chartVoltage" class="chart-box"></div></div>
            <div><h3>Tegangan RS, ST, RT (Volt)</h3><div id="chartVoltageLine" class="chart-box"></div></div>
            <div><h3>Arus R, S, T (Ampere)</h3><div id="chartCurrent" class="chart-box"></div></div>
            <div><h3>Monitoring Power Hari Ini</h3><div id="chartPower" class="chart-box"></div></div>
        </div>
    </div>
</div>

<script>
// Sidebar toggle
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleSidebar");
const content = document.getElementById("content");
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("hidden");
    content.classList.toggle("full");
    toggleBtn.classList.toggle("closed");
    toggleBtn.textContent = sidebar.classList.contains("hidden") ? "☰" : "✖";
});
setTimeout(() => { location.reload(); }, 60000);

// Data dari PHP
var labels = <?php echo json_encode($labels); ?>;
var valuesPower = <?php echo json_encode($valuesPower); ?>;
var valuesVr = <?php echo json_encode($valuesVr); ?>;
var valuesVs = <?php echo json_encode($valuesVs); ?>;
var valuesVt = <?php echo json_encode($valuesVt); ?>;
var valuesVrs = <?php echo json_encode($valuesVrs); ?>;
var valuesVst = <?php echo json_encode($valuesVst); ?>;
var valuesVrt = <?php echo json_encode($valuesVrt); ?>;
var valuesIr = <?php echo json_encode($valuesIr); ?>;
var valuesIs = <?php echo json_encode($valuesIs); ?>;
var valuesIt = <?php echo json_encode($valuesIt); ?>;
var latest = <?php echo json_encode($latest); ?>;

// ===== Gauge =====
function makeGauge(id, value, label, color, maxVal = 250) {
    Plotly.newPlot(id, [{
        type: "indicator",
        mode: "gauge+number",
        value: value,
        title: { text: label, font: { size: 14 } },
        gauge: {
            axis: { range: [0, maxVal], tickwidth: 1, tickcolor: "#333" },
            bar: { color: color },
            bgcolor: "white",
            borderwidth: 1,
            bordercolor: "#ccc",
            steps: [
                { range: [0, maxVal * 0.7], color: "#f9f9ff" },
                { range: [maxVal * 0.7, maxVal * 0.88], color: "#dbe8ff" },
                { range: [maxVal * 0.88, maxVal], color: "#a6c8ff" }
            ],
            threshold: {
                line: { color: "red", width: 3 },
                thickness: 0.75,
                value: maxVal * 0.92
            }
        }
    }], {
        margin: { t: 10, b: 10, l: 10, r: 10 },
        paper_bgcolor: "#fff"
    });
}

// Gauge Voltage
makeGauge('gaugeR', latest.voltage_r, "Voltage R (V)", "red");
makeGauge('gaugeS', latest.voltage_s, "Voltage S (V)", "blue");
makeGauge('gaugeT', latest.voltage_t, "Voltage T (V)", "orange");
makeGauge('gaugeRS', latest.voltage_rs, "Voltage RS (V)", "purple", 450);
makeGauge('gaugeST', latest.voltage_st, "Voltage ST (V)", "green", 450);
makeGauge('gaugeRT', latest.voltage_rt, "Voltage RT (V)", "brown", 450);
makeGauge('gaugeIR', latest.current_r, "Current R (A)", "red", 200);
makeGauge('gaugeIS', latest.current_s, "Current S (A)", "blue", 200);
makeGauge('gaugeIT', latest.current_t, "Current T (A)", "orange", 200);

// ===== Chart =====
const layoutSame = {
    margin: { l: 60, r: 40, t: 40, b: 40 },
    xaxis: { title: 'Waktu' },
    hovermode: 'x unified',
    showlegend: true,
    autosize: true
};
Plotly.newPlot('chartVoltage', [
    { x: labels, y: valuesVr, mode: 'lines', line: {color: 'red'}, name: 'R' },
    { x: labels, y: valuesVs, mode: 'lines', line: {color: 'blue'}, name: 'S' },
    { x: labels, y: valuesVt, mode: 'lines', line: {color: 'orange'}, name: 'T' }
], {...layoutSame});
Plotly.newPlot('chartVoltageLine', [
    { x: labels, y: valuesVrs, mode: 'lines', line: {color: 'purple'}, name: 'RS' },
    { x: labels, y: valuesVst, mode: 'lines', line: {color: 'green'}, name: 'ST' },
    { x: labels, y: valuesVrt, mode: 'lines', line: {color: 'brown'}, name: 'RT' }
], {...layoutSame});
Plotly.newPlot('chartCurrent', [
    { x: labels, y: valuesIr, mode: 'lines', line: {color: 'red'}, name: 'R' },
    { x: labels, y: valuesIs, mode: 'lines', line: {color: 'blue'}, name: 'S' },
    { x: labels, y: valuesIt, mode: 'lines', line: {color: 'orange'}, name: 'T' }
], {...layoutSame});
Plotly.newPlot('chartPower', [
    { x: labels, y: valuesPower, mode: 'lines+markers', line: {color: 'darkgreen'}, name: 'Power' }
], {...layoutSame});
</script>
</body>
</html>
