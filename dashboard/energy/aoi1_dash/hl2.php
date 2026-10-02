<?php
// index.php - Dashboard lengkap terintegrasi (gauge, chart, tarif, filter, export)

// OPTIONAL: aktifkan ini jika butuh melihat error saat debugging
// ini_set('display_errors', 1); error_reporting(E_ALL);

include 'db.php';

// ---------------------------
// EXPORT HANDLER (Excel .xls sederhana / CSV)
// Jika tombol export diklik, langsung proses dan exit
// ---------------------------
if (isset($_GET['export_type']) && in_array($_GET['export_type'], ['excel','csv'])) {
    $export_type = $_GET['export_type'];
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
    $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    // ambil data untuk export
    $sql_export = "SELECT tanggal, lwbp_tarif, wbp_tarif, total_tarif
                   FROM electricity_aoi1_harian
                   WHERE id_location='103'
                   AND tanggal BETWEEN '$start_date' AND '$end_date'
                   ORDER BY tanggal ASC";
    $res_export = $conn->query($sql_export);

    if ($export_type === 'excel') {
        // Simple Excel-compatible TSV (xls)
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=tarif_harian_{$start_date}_to_{$end_date}.xls");
        echo "\xEF\xBB\xBF"; // BOM untuk Excel agar UTF-8 terbaca baik
        echo "Tanggal\tLWBP Tarif\tWBP Tarif\tTotal Tarif\n";
        if ($res_export && $res_export->num_rows > 0) {
            while($r = $res_export->fetch_assoc()) {
                // tulis angka tanpa format ribuan agar Excel bisa parse numeric
                echo $r['tanggal'] . "\t" . $r['lwbp_tarif'] . "\t" . $r['wbp_tarif'] . "\t" . $r['total_tarif'] . "\n";
            }
        } else {
            echo "Tidak ada data\t\t\t\n";
        }
        $conn->close();
        exit;
    }

    if ($export_type === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=tarif_harian_{$start_date}_to_{$end_date}.csv");
        echo "\xEF\xBB\xBF"; // BOM
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Tanggal','LWBP Tarif','WBP Tarif','Total Tarif']);
        if ($res_export && $res_export->num_rows > 0) {
            while($r = $res_export->fetch_assoc()) {
                fputcsv($out, [$r['tanggal'], $r['lwbp_tarif'], $r['wbp_tarif'], $r['total_tarif']]);
            }
        } else {
            fputcsv($out, ['Tidak ada data','','','']);
        }
        fclose($out);
        $conn->close();
        exit;
    }
}

// ---------------------------
// NORMAL PAGE: ambil data untuk dashboard
// ---------------------------

// ===========================
// Data terakhir (hari ini untuk gauge & info)
$sql_latest = "SELECT voltage_r, voltage_s, voltage_t, voltage_rs, voltage_st, voltage_rt,
                      current_r, current_s, current_t,
                      power_factor, power, kwh_total
               FROM electricity_aoi1_reporting 
               WHERE id_lokasi='103'
               ORDER BY datenow DESC LIMIT 1";
$result_latest = $conn->query($sql_latest);
$latest = [
    'voltage_r'=>0,'voltage_s'=>0,'voltage_t'=>0,
    'voltage_rs'=>0,'voltage_st'=>0,'voltage_rt'=>0,
    'current_r'=>0,'current_s'=>0,'current_t'=>0,
    'power_factor'=>0,'power'=>0,'kwh_total'=>0
];
if($result_latest && $result_latest->num_rows>0){
    $latest = $result_latest->fetch_assoc();
}

// ===========================
// Data hari ini (chart)
$sql = "SELECT datenow, power, voltage_r, voltage_s, voltage_t,
               voltage_rs, voltage_st, voltage_rt,
               current_r, current_s, current_t
        FROM electricity_aoi1_reporting
        WHERE id_lokasi='103' AND datenow >= CURDATE()
        ORDER BY datenow ASC";
$result = $conn->query($sql);

$labels=[]; $valuesPower=[]; $valuesVr=[]; $valuesVs=[]; $valuesVt=[]; 
$valuesVrs=[]; $valuesVst=[]; $valuesVrt=[]; $valuesIr=[]; $valuesIs=[]; $valuesIt=[];

if($result && $result->num_rows>0){
    while($row=$result->fetch_assoc()){
        $labels[] = $row['datenow'];
        $valuesPower[] = (float)$row['power'];
        $valuesVr[] = (float)$row['voltage_r']; $valuesVs[] = (float)$row['voltage_s']; $valuesVt[] = (float)$row['voltage_t'];
        $valuesVrs[] = (float)$row['voltage_rs']; $valuesVst[] = (float)$row['voltage_st']; $valuesVrt[] = (float)$row['voltage_rt'];
        $valuesIr[] = (float)$row['current_r']; $valuesIs[] = (float)$row['current_s']; $valuesIt[] = (float)$row['current_t'];
    }
}

// ===========================
// Data konsumsi hari kemarin (dengan fallback)
$sql_yesterday = "SELECT lwbp1_consumpt, lwbp2_consumpt, lwbp_consumpt, wbp_consumpt,
                         lwbp1_tarif, lwbp2_tarif, lwbp_tarif, wbp_tarif, total_tarif
                  FROM electricity_aoi1_harian
                  WHERE id_location='103' AND tanggal=CURDATE()-INTERVAL 1 DAY
                  LIMIT 1";

$result_yesterday = $conn->query($sql_yesterday);
$yesterday = [
    'lwbp1_consumpt'=>0,'lwbp2_consumpt'=>0,'lwbp_consumpt'=>0,'wbp_consumpt'=>0,
    'lwbp1_tarif'=>0,'lwbp2_tarif'=>0,'lwbp_tarif'=>0,'wbp_tarif'=>0,'total_tarif'=>0
];

if ($result_yesterday && $result_yesterday->num_rows > 0) {
    $yesterday = $result_yesterday->fetch_assoc();
} else {
    // fallback ke data terakhir yang tersedia
    $sql_latest_yest = "SELECT lwbp1_consumpt, lwbp2_consumpt, lwbp_consumpt, wbp_consumpt,
                               lwbp1_tarif, lwbp2_tarif, lwbp_tarif, wbp_tarif, total_tarif,
                               tanggal
                        FROM electricity_aoi1_harian
                        WHERE id_location='103'
                        ORDER BY tanggal DESC
                        LIMIT 1";
    $res_latest_yest = $conn->query($sql_latest_yest);
    if ($res_latest_yest && $res_latest_yest->num_rows > 0) {
        $yesterday = $res_latest_yest->fetch_assoc();
    }
}

// Hitung total KWH
$total_kwh = ($yesterday['lwbp_consumpt'] ?? 0) + ($yesterday['wbp_consumpt'] ?? 0);

// ===========================
// Data Tarif Harian (Chart Bar dengan filter tanggal)
// ===========================
// ambil dari GET, default 7 hari terakhir
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$sql_tarif = "SELECT tanggal, lwbp_tarif, wbp_tarif, total_tarif 
              FROM electricity_aoi1_harian
              WHERE id_location='103'
              AND tanggal BETWEEN '$start_date' AND '$end_date'
              ORDER BY tanggal ASC";
$result_tarif = $conn->query($sql_tarif);

$labelsTarif = [];
$lwbpTarif = [];
$wbpTarif = [];
$totalTarif = [];

if($result_tarif && $result_tarif->num_rows>0){
    while($row = $result_tarif->fetch_assoc()){
        $labelsTarif[] = $row['tanggal'];
        $lwbpTarif[] = (float)$row['lwbp_tarif'];
        $wbpTarif[] = (float)$row['wbp_tarif'];
        $totalTarif[] = (float)$row['total_tarif'];
    }
}

// Jangan close koneksi di sini, karena kita butuh db lagi di export handler jika dipanggil sebelum.
// Namun pada halaman normal, setelah semua query selesai, kita akan tutup koneksi:
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>SDP-HL.2/1F Monitoring</title>
<script src="js/plotly-2.30.0.min.js"></script>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f4f6f9;}
.main-container{display:flex;}
.content{flex:1;padding:20px;margin-left:250px;transition:margin-left .3s;}
.content.full{margin-left:0;}
.toggle-btn{position:fixed;left:260px;top:15px;background:#1f2a40;color:#fff;padding:6px 10px;cursor:pointer;border-radius:4px;z-index:200;transition:all .3s;}
.toggle-btn.closed{left:10px;}
.toggle-btn:hover{background:#00acc1;}

/* Gauge & info */
.gauge-container{display:flex;flex-direction:column;gap:25px;margin-bottom:30px;}
.gauge-group{background:#fff;padding:20px;border-radius:12px;box-shadow:0 3px 8px rgba(0,0,0,.15);border:1px solid #ddd;}
.gauge-row{display:flex;justify-content:center;flex-wrap:wrap;gap:15px;margin-top:10px;}
.gauge-box{background:#fff;border-radius:12px;width:18%;min-width:180px;height:200px;box-shadow:0 3px 6px rgba(0,0,0,.1);border:1px solid #ccc;display:flex;align-items:center;justify-content:center;}
.gauge-row.combined{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:nowrap;}
.sub-group{display:flex;flex-direction:column;align-items:center;}
.sub-title{font-weight:bold;color:#333;margin-bottom:5px;}
.gauge-subrow{display:flex;gap:40px;}
.divider{width:2px;background:#ccc;margin:0 15px;border-radius:2px;height:180px;align-self:center;}

/* Info box gabungan */
.info-box{background:#b2ebf2;border:2px solid #00acc1;border-radius:12px;padding:20px;width:260px;height:auto;display:flex;flex-direction:column;justify-content:flex-start;box-shadow:0 3px 6px rgba(0,0,0,.15);margin-left:15px;}
.info-box h3{font-size:18px;color:#006064;margin:5px 0;}
.info-box h4{font-size:16px;color:#004d40;margin:10px 0 5px 0;}
.info-box p{font-size:16px;margin:2px 0;}

/* Info Hari Kemarin */
.info-yesterday{margin-top:15px;background:#fff3e0;border:2px solid #ffb74d;border-radius:12px;padding:15px;}
.info-yesterday table{width:100%;border-collapse:collapse;}
.info-yesterday table td{padding:6px 10px;font-size:16px;}
.info-yesterday table td:nth-child(2){text-align:right;font-weight:bold;}
.info-yesterday table td:nth-child(3){text-align:right;font-weight:bold;color:#1976d2;}
.info-yesterday table tr.total td{font-size:20px;font-weight:bold;color:#e64a19;border-top:2px solid #ccc;padding-top:10px;}
.info-yesterday table tr.total-tarif td{font-size:20px;font-weight:bold;color:#6a1b9a;border-top:2px solid #ccc;padding-top:10px;}

/* Chart */
.chart-row{display:flex;flex-direction:column;gap:25px;}
.chart-box{width:85%;height:350px;background:#fff;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.1);padding:10px;}
h2,h3{color:#333;}
h4{color:#444;margin-left:10px;margin-top:5px;}

/* Filter & export */
.filter-form {text-align:center;margin:10px 0;}
.filter-form input[type="date"]{padding:6px;border:1px solid #ccc;border-radius:4px;margin:0 6px;}
.export-btn{background:#4caf50;color:#fff;padding:7px 12px;border-radius:6px;text-decoration:none;margin-left:8px;}

/* Responsif */
@media(max-width:1200px){.gauge-box{width:20%;height:160px;min-width:120px;}.info-box{width:220px;height:auto;} }
@media(max-width:768px){.content{margin-left:0;}.gauge-box{width:30%;height:130px;}.divider{display:none;}.info-box{width:100%;height:auto;margin-top:15px;} }
</style>
</head>
<body>
<div class="main-container">
<?php include 'sidebar.php'; ?>
<div id="toggleSidebar" class="toggle-btn">☰</div>

<div id="content" class="content">
<h2>📊 Data SDP-HL.2/1F Hari Ini</h2>

<!-- Gauge & Info -->
<div class="gauge-container">
    <!-- Tegangan -->
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

    <!-- Arus & Info Box -->
    <div class="gauge-group">
        <h4>⚙️ Arus R, S, T (Ampere) & Informasi Listrik</h4>
        <div class="gauge-row">
            <div id="gaugeIR" class="gauge-box"></div>
            <div id="gaugeIS" class="gauge-box"></div>
            <div id="gaugeIT" class="gauge-box"></div>

            <!-- Info Box -->
            <div class="info-box">
                <h3>⚡ Informasi Listrik</h3>
                <h4>Hari Ini</h4>
                <p>Power Factor: <span><?= number_format($latest['power_factor'],3) ?></span></p>
                <p>Power: <span><?= number_format($latest['power'],2) ?> W</span></p>
                <p>KWH Total: <span><?= number_format($latest['kwh_total'],2) ?> kWh</span></p>
            </div>
        </div>

        <!-- Info Hari Kemarin -->
        <div class="info-yesterday">
            <h4>📊 Konsumsi & Tarif Hari Kemarin</h4>
            <table>
                <tr><td>LWBP1</td><td><?= number_format($yesterday['lwbp1_consumpt'],2) ?> kWh</td><td>Rp <?= number_format($yesterday['lwbp1_tarif'],0) ?></td></tr>
                <tr><td>LWBP2</td><td><?= number_format($yesterday['lwbp2_consumpt'],2) ?> kWh</td><td>Rp <?= number_format($yesterday['lwbp2_tarif'],0) ?></td></tr>
                <tr><td>LWBP</td><td><?= number_format($yesterday['lwbp_consumpt'],2) ?> kWh</td><td>Rp <?= number_format($yesterday['lwbp_tarif'],0) ?></td></tr>
                <tr><td>WBP</td><td><?= number_format($yesterday['wbp_consumpt'],2) ?> kWh</td><td>Rp <?= number_format($yesterday['wbp_tarif'],0) ?></td></tr>
                <tr class="total"><td colspan="2" style="text-align:right;">Total KWH:</td><td><?= number_format($total_kwh,2) ?> kWh</td></tr>
                <tr class="total-tarif"><td colspan="2" style="text-align:right;">Total Tarif:</td><td>Rp <?= number_format($yesterday['total_tarif'],0) ?></td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="chart-row">
    <div><h3>Tegangan R, S, T (Volt)</h3><div id="chartVoltage" class="chart-box"></div></div>
    <div><h3>Tegangan RS, ST, RT (Volt)</h3><div id="chartVoltageLine" class="chart-box"></div></div>
    <div><h3>Arus R, S, T (Ampere)</h3><div id="chartCurrent" class="chart-box"></div></div>
    <div><h3>Monitoring Power Hari Ini</h3><div id="chartPower" class="chart-box"></div></div>

    <!-- Chart Tarif dengan filter & export -->
    <div>
        <h3>💰 Total Tarif Harian</h3>

        <!-- Filter tanggal & Export -->
        <div class="filter-form">
            <form method="GET" style="display:inline-block;">
                <label>Dari:</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                <label>Sampai:</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                <button type="submit">Tampilkan</button>
            </form>

            <!-- Export: panggil page ini lagi dengan param export_type -->
            <form method="GET" style="display:inline-block;margin-left:10px;">
               <!-- 
                <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                <button type="submit" name="export_type" value="excel" style="background:#4caf50;color:#fff;padding:7px 12px;border-radius:6px;border:none;cursor:pointer;">
                    ⬇️ Export Excel
                </button>
                -->
            </form>
            <form method="GET" style="display:inline-block;margin-left:8px;">
                <!-- 
                <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                <button type="submit" name="export_type" value="csv" style="background:#ffb300;color:#fff;padding:7px 12px;border-radius:6px;border:none;cursor:pointer;">
                    ⬇️ Export CSV
                </button>
                -->
            </form>
        </div>

        <div id="chartTarif" class="chart-box" style="height:420px;"></div>
    </div>
</div>
</div>
</div>

<script>
const sidebar=document.getElementById("sidebar");
const toggleBtn=document.getElementById("toggleSidebar");
const content=document.getElementById("content");

function updateSidebar(status){
    if(status==="closed"){
        sidebar.classList.add("hidden");
        content.classList.add("full");
        toggleBtn.classList.add("closed");
        toggleBtn.textContent="☰";
    } else {
        sidebar.classList.remove("hidden");
        content.classList.remove("full");
        toggleBtn.classList.remove("closed");
        toggleBtn.textContent="✖";
    }
}

let sidebarStatus = localStorage.getItem("sidebarStatus") || "closed";
updateSidebar(sidebarStatus);

toggleBtn.addEventListener("click",()=>{
    sidebarStatus = sidebar.classList.contains("hidden") ? "open" : "closed";
    localStorage.setItem("sidebarStatus", sidebarStatus);
    updateSidebar(sidebarStatus);
});

// Auto-refresh tiap 1 menit
setTimeout(()=>{location.reload();},60000);

// Data PHP ke JS
var labels=<?= json_encode($labels) ?>;
var valuesPower=<?= json_encode($valuesPower) ?>;
var valuesVr=<?= json_encode($valuesVr) ?>;
var valuesVs=<?= json_encode($valuesVs) ?>;
var valuesVt=<?= json_encode($valuesVt) ?>;
var valuesVrs=<?= json_encode($valuesVrs) ?>;
var valuesVst=<?= json_encode($valuesVst) ?>;
var valuesVrt=<?= json_encode($valuesVrt) ?>;
var valuesIr=<?= json_encode($valuesIr) ?>;
var valuesIs=<?= json_encode($valuesIs) ?>;
var valuesIt=<?= json_encode($valuesIt) ?>;
var latest=<?= json_encode($latest) ?>;

// Tarif chart data
var labelsTarif=<?= json_encode($labelsTarif) ?>;
var lwbpTarif=<?= json_encode($lwbpTarif) ?>;
var wbpTarif=<?= json_encode($wbpTarif) ?>;
var totalTarif=<?= json_encode($totalTarif) ?>;

// ===== Gauge =====
function makeGauge(id,value,label,color,maxVal=250){
    Plotly.newPlot(id,[{type:"indicator",mode:"gauge+number",value:value,
        title:{text:label,font:{size:14}},
        gauge:{axis:{range:[0,maxVal],tickwidth:1,tickcolor:"#333"},
               bar:{color:color},bgcolor:"white",borderwidth:1,bordercolor:"#ccc",
               steps:[
                 {range:[0,maxVal*0.7],color:"#f9f9ff"},
                 {range:[maxVal*0.7,maxVal*0.88],color:"#dbe8ff"},
                 {range:[maxVal*0.88,maxVal],color:"#a6c8ff"}
               ],
               threshold:{line:{color:"red",width:3},thickness:0.75,value:maxVal*0.92}
        }
    }],{margin:{t:10,b:10,l:10,r:10},paper_bgcolor:"#fff"});
}

// Gauge
makeGauge('gaugeR', latest.voltage_r,"Voltage R (V)","red");
makeGauge('gaugeS', latest.voltage_s,"Voltage S (V)","blue");
makeGauge('gaugeT', latest.voltage_t,"Voltage T (V)","orange");
makeGauge('gaugeRS', latest.voltage_rs,"Voltage RS (V)","purple",450);
makeGauge('gaugeST', latest.voltage_st,"Voltage ST (V)","green",450);
makeGauge('gaugeRT', latest.voltage_rt,"Voltage RT (V)","brown",450);
makeGauge('gaugeIR', latest.current_r,"Current R (A)","red",200);
makeGauge('gaugeIS', latest.current_s,"Current S (A)","blue",200);
makeGauge('gaugeIT', latest.current_t,"Current T (A)","orange",200);

// Chart
const layoutSame={margin:{l:60,r:40,t:40,b:40},xaxis:{title:'Waktu'},paper_bgcolor:'#fff',plot_bgcolor:'#fff'};

Plotly.newPlot('chartVoltage',[
  {x:labels,y:valuesVr,type:'scatter',name:'R',line:{color:'red'}},
  {x:labels,y:valuesVs,type:'scatter',name:'S',line:{color:'blue'}},
  {x:labels,y:valuesVt,type:'scatter',name:'T',line:{color:'orange'}}
],{...layoutSame,title:'Tegangan Phasa'});

Plotly.newPlot('chartVoltageLine',[
  {x:labels,y:valuesVrs,type:'scatter',name:'RS',line:{color:'purple'}},
  {x:labels,y:valuesVst,type:'scatter',name:'ST',line:{color:'green'}},
  {x:labels,y:valuesVrt,type:'scatter',name:'RT',line:{color:'brown'}}
],{...layoutSame,title:'Tegangan Antar Phasa'});

Plotly.newPlot('chartCurrent',[
  {x:labels,y:valuesIr,type:'scatter',name:'R',line:{color:'red'}},
  {x:labels,y:valuesIs,type:'scatter',name:'S',line:{color:'blue'}},
  {x:labels,y:valuesIt,type:'scatter',name:'T',line:{color:'orange'}}
],{...layoutSame,title:'Arus'});

Plotly.newPlot('chartPower',[
  {x:labels,y:valuesPower,type:'scatter',name:'Power',line:{color:'#43a047'},fill: 'tozeroy'}
],{...layoutSame,title:'Power Hari Ini'});

// ===== Chart Bar: LWBP, WBP, Total Tarif Harian =====
Plotly.newPlot('chartTarif', [
  {
    x: labelsTarif,
    y: lwbpTarif,
    type: 'bar',
    name: 'LWBP Tarif',
    marker: {color: '#f44336'}
  },
  {
    x: labelsTarif,
    y: wbpTarif,
    type: 'bar',
    name: 'WBP Tarif',
    marker: {color: '#2196f3'}
  },
  {
    x: labelsTarif,
    y: totalTarif,
    type: 'bar',
    name: 'Total Tarif',
    marker: {color: '#4caf50'}
  }
], {
  //title: 'LWBP, WBP, dan Total Tarif Harian',
  barmode: 'group',
  xaxis: {title: 'Tanggal'},
  yaxis: {title: 'Tarif (Rp)', tickprefix: 'Rp '},
  margin: {l:60, r:40, t:40, b:40},
  paper_bgcolor:'#fff',
  plot_bgcolor:'#fff',
  legend: {orientation:'h', x:0.3, y:1.15}
});
</script>
</body>
</html>
