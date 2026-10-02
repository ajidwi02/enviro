<?php
include 'db.php';

/* =========================================================
   MODE JSON API - GAUGE LIVE
   ========================================================= */
if (isset($_GET['chart']) && $_GET['chart'] === 'gauge') {

    $q = mysqli_query($conn,"
        SELECT voltage_rs,voltage_st,voltage_rt,
               current_r,current_s,current_t
        FROM electricity_aoi1_reporting
        WHERE id_lokasi='101'
        ORDER BY id_transaction DESC LIMIT 1
    ");

    $d = mysqli_fetch_assoc($q);

    header('Content-Type: application/json');
    echo json_encode([
        'vrs' => (float)($d['voltage_rs'] ?? 0),
        'vst' => (float)($d['voltage_st'] ?? 0),
        'vrt' => (float)($d['voltage_rt'] ?? 0),
        'cr'  => (float)($d['current_r'] ?? 0),
        'cs'  => (float)($d['current_s'] ?? 0),
        'ct'  => (float)($d['current_t'] ?? 0),
    ]);
    exit;
}

/* =========================================================
   MODE JSON API - BAR HARIAN (LVMDP - MOVE KE TARIF HARIAN)
   ========================================================= */
if (isset($_GET['chart']) && $_GET['chart'] === 'bar') {
    $labels = []; 
    $tarifs = [];

    $sql = "
        SELECT DATE(tanggal) AS tgl, SUM(total_tarif) AS total_tarif
        FROM electricity_aoi1_harian
        WHERE location='LVMDP'
          AND MONTH(tanggal)=MONTH(CURDATE())
          AND YEAR(tanggal)=YEAR(CURDATE())
        GROUP BY DATE(tanggal)
        ORDER BY DATE(tanggal)
    ";

    $res = mysqli_query($conn,$sql);
    while($r=mysqli_fetch_assoc($res)){
        $labels[] = date('d', strtotime($r['tgl']));
        $tarifs[] = (float)$r['total_tarif'];
    }

    header('Content-Type: application/json');
    echo json_encode(['labels'=>$labels,'tarifs'=>$tarifs]);
    exit;
}

/* =========================================================
   MODE JSON API - BAR BULANAN (ID LOCATION = 101)
   ========================================================= */
if (isset($_GET['chart']) && $_GET['chart'] === 'monthly') {

    $labels = [];
    $tarifs = [];

    $sql = "
        SELECT 
            DATE_FORMAT(tanggal,'%Y-%m') AS bulan,
            SUM(total_tarif) AS total
        FROM electricity_aoi1_harian
        WHERE id_location = '101'
        GROUP BY DATE_FORMAT(tanggal,'%Y-%m')
        ORDER BY DATE_FORMAT(tanggal,'%Y-%m')
    ";

    $res = mysqli_query($conn,$sql);

    while($r = mysqli_fetch_assoc($res)){
        $labels[] = $r['bulan'];
        $tarifs[] = (float)$r['total'];
    }

    header('Content-Type: application/json');
    echo json_encode(['labels'=>$labels,'tarifs'=>$tarifs]);
    exit;
}

/* =========================================================
   MODE JSON API - PIE CHART
   ========================================================= */
if (isset($_GET['chart']) && $_GET['chart'] === 'pie') {
    $labels=[]; 
    $values=[];

    $sql="
        SELECT 
            location,
            SUM(COALESCE(lwbp_consumpt,0)+COALESCE(wbp_consumpt,0)) total_kwh
        FROM electricity_aoi1_harian
        WHERE DATE(tanggal)=CURDATE()
        AND location<>'LVMDP'
        AND location <> 'SOLAR-PANEL'
        GROUP BY location ORDER BY location
    ";

    $res=mysqli_query($conn,$sql);

    if(mysqli_num_rows($res)==0){
        $sql="
            SELECT 
                location,
                SUM(COALESCE(lwbp_consumpt,0)+COALESCE(wbp_consumpt,0)) total_kwh
            FROM electricity_aoi1_harian
            WHERE DATE(tanggal)=CURDATE()-INTERVAL 1 DAY
            AND location<>'LVMDP'
            AND location <> 'SOLAR-PANEL'
            GROUP BY location ORDER BY location
        ";
        $res=mysqli_query($conn,$sql);
    }

    while($r=mysqli_fetch_assoc($res)){
        $labels[]=$r['location']; 
        $values[]=(float)$r['total_kwh'];
    }

    header('Content-Type: application/json');
    echo json_encode(['labels'=>$labels,'values'=>$values]);
    exit;
}

/* =========================================================
   MODE JSON API - POWER (HARI INI)
   ========================================================= */
if (isset($_GET['chart']) && $_GET['chart'] === 'power') {
    $labels=[]; 
    $values=[];

    $sql="
        SELECT DATE_FORMAT(datenow,'%H:%i') jam,power
        FROM electricity_aoi1_reporting
        WHERE id_lokasi='101' AND DATE(datenow)=CURDATE()
        ORDER BY datenow
    ";

    $res=mysqli_query($conn,$sql);

    while($r=mysqli_fetch_assoc($res)){
        $labels[]=$r['jam']; 
        $values[]=(float)$r['power'];
    }

    header('Content-Type: application/json');
    echo json_encode(['labels'=>$labels,'values'=>$values]);
    exit;
}

/* =========================================================
   DATA RINGKASAN (CARD)
   ========================================================= */
$q1 = mysqli_query($conn,"
    SELECT lwbp_consumpt,wbp_consumpt,total_tarif 
    FROM electricity_aoi1_harian 
    WHERE location='LVMDP' 
    ORDER BY id DESC LIMIT 1
");

$d1 = mysqli_fetch_assoc($q1);

$total_consumpt = $d1 ? ($d1['lwbp_consumpt'] + $d1['wbp_consumpt']) : 0;
$total_tarif = $d1 ? $d1['total_tarif'] : 0;

/* ===== laporan live ===== */
$q2 = mysqli_query($conn,"
    SELECT kwh_total,voltage_rs,voltage_st,voltage_rt,
           current_r,current_s,current_t 
    FROM electricity_aoi1_reporting 
    WHERE id_lokasi='101' 
    ORDER BY id_transaction DESC LIMIT 1
");

$d2 = mysqli_fetch_assoc($q2);

$kwh_total = $d2['kwh_total'] ?? 0;

$vrs = $d2['voltage_rs'] ?? 0; 
$vst = $d2['voltage_st'] ?? 0; 
$vrt = $d2['voltage_rt'] ?? 0;
$cr  = $d2['current_r'] ?? 0; 
$cs  = $d2['current_s'] ?? 0; 
$ct  = $d2['current_t'] ?? 0;

/* ===== total tarif bulan ini ===== */
$q3 = mysqli_query($conn,"
    SELECT SUM(total_tarif) total_bulan 
    FROM electricity_aoi1_harian 
    WHERE location='LVMDP'
    AND MONTH(tanggal)=MONTH(CURDATE()) 
    AND YEAR(tanggal)=YEAR(CURDATE())
");

$d3 = mysqli_fetch_assoc($q3);
$total_tarif_bulan_ini = $d3['total_bulan'] ?? 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>⚙️ Energy Power Monitor</title>
<link rel="stylesheet" href="style.css">
<script src="js/chart.js"></script>
<script src="js/chartjs-plugin-datalabels.min.js"></script>
<script src="js/highcharts.js"></script>
<script src="js/highcharts-more.js"></script>
<script src="js/solid-gauge.js"></script>

<style>
body{margin:0;background:#0e1621;color:#e0e6ed;font-family:'Orbitron','Roboto Mono',monospace;}
.main-container{display:flex;flex-wrap:wrap;}
.content{flex:1;padding:16px;margin-left:250px;transition:.3s;}
.content.full{margin-left:0;}
h2{color:#00ffcc;text-shadow:0 0 8px #00ffcc;margin:0 0 12px;}

.card-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
    margin-bottom:12px;
}
.card{
    background:#12202b;
    border:1px solid #00bfff44;
    border-radius:8px;
    text-align:center;
    padding:10px;
}
.card h3{color:#00ccff;font-size:13px;margin:0 0 4px;}
.card .value{color:#00ff99;font-weight:700;}

.gauge-row{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:8px;
    margin-bottom:14px;
}

.gauge-box{
    background:#1b2838;
    border-radius:8px;
    text-align:center;
    border:1px solid #00bfff44;
    padding:8px;
}
.gauge-title{color:#00e6e6;font-size:12px;margin-bottom:6px;}

.chart-row-3{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
    margin-bottom:12px;
}

.chart-box{
    background:#1b2838;
    border-radius:10px;
    padding:6px;
    border:1px solid #00bfff44;
}

.chart-title{
    text-align:center;
    color:#00e6e6;
    font-weight:700;
    margin-bottom:6px;
    font-size:13px;
}

.chart-box canvas{width:100% !important;height:auto !important;}

.toggle-btn{
    position:fixed;
    top:15px;
    left:260px;
    background:#00bfff;
    color:#fff;
    border:none;
    font-size:20px;
    border-radius:6px;
    padding:6px 10px;
    cursor:pointer;
    z-index:101;
}

@media(max-width:900px){
    .chart-row-3{grid-template-columns:1fr;}
    .gauge-row{grid-template-columns:repeat(3,1fr);}
    .card-row{grid-template-columns:repeat(2,1fr);}
}

.chart-row-1 {
    display: grid;
    grid-template-columns: 1fr; /* 1 Kolom Full Width */
    gap: 12px;
    margin-bottom: 12px;
}

/* Pastikan Canvas punya tinggi yang cukup agar tidak gepeng */
#horizontalPanelChart {
    min-height: 300px;
}
</style>
</head>
<body>

<div class="main-container">
<?php include 'sidebar.php'; ?>

<button id="toggleBtn" class="toggle-btn">✖</button>
<div id="content" class="content">

<h2>⚡ EMS (Energy Monitoring System)</h2>

<div class="card-row">
    <div class="card">
        <h3>Total Consumption Kemarin</h3>
        <p class="value"><?=number_format($total_consumpt,2)?> kWh</p>
    </div>

    <div class="card">
        <h3>Total Tarif Kemarin</h3>
        <p class="value">Rp <?=number_format($total_tarif,0,',','.')?></p>
    </div>

    <div class="card">
        <h3>Total kWh Keseluruhan</h3>
        <p class="value"><?=number_format($kwh_total,2)?> kWh</p>
    </div>

    <div class="card">
        <h3>Total Tarif Bulan Ini</h3>
        <p class="value">Rp <?=number_format($total_tarif_bulan_ini,0,',','.')?></p>
    </div>
</div>

<div class="gauge-row">
    <div class="gauge-box"><div class="gauge-title">Voltage RS</div><div id="g_rs" style="height:90px"></div></div>
    <div class="gauge-box"><div class="gauge-title">Voltage ST</div><div id="g_st" style="height:90px"></div></div>
    <div class="gauge-box"><div class="gauge-title">Voltage RT</div><div id="g_rt" style="height:90px"></div></div>
    <div class="gauge-box"><div class="gauge-title">Current R</div><div id="g_cr" style="height:90px"></div></div>
    <div class="gauge-box"><div class="gauge-title">Current S</div><div id="g_cs" style="height:90px"></div></div>
    <div class="gauge-box"><div class="gauge-title">Current T</div><div id="g_ct" style="height:90px"></div></div>
</div>

<div class="chart-row-3">
    <div class="chart-box">
        <div class="chart-title">⚙️ Power Hari Ini</div>
        <canvas id="powerChart"></canvas>
    </div>

    <div class="chart-box">
        <div class="chart-title">📅 Tarif Harian (LVMDP)</div>
        <canvas id="tarifChart"></canvas>
    </div>

    <div class="chart-box">
        <div class="chart-title">📅 Total Tarif Bulanan (LVMDP)</div>
        <canvas id="monthlyChart"></canvas>
    </div>

    <div class="chart-box">
        <div class="chart-title">🥧 Konsumsi Per Panel</div>
        <canvas id="pieChart"></canvas>
    </div>
</div>

<div class="chart-row-1">
    <div class="chart-box">
        <div class="chart-title">📊 Detail Konsumsi Per Panel (Horizontal)</div>
        <canvas id="horizontalPanelChart"></canvas>
    </div>
</div>

</div>
</div>

<script>
const sb = document.getElementById('sidebar'),
      ct = document.getElementById('content'),
      btn = document.getElementById('toggleBtn');

btn.onclick = ()=>{
    const hidden = sb.classList.toggle('hidden');
    ct.classList.toggle('full', hidden);

    if(hidden){
        btn.style.left = "15px";
        btn.textContent = '☰';
    } else {
        btn.style.left = "260px";
        btn.textContent = '✖';
    }
};

const gauges = {};

function createGauge(id,val,max,type){
    let color = (type==='voltage') ? '#00ffcc' : '#ff9933';

    gauges[id] = Highcharts.chart(id,{
        chart:{type:'solidgauge',backgroundColor:'transparent',height:100},
        title:null,
        pane:{
            startAngle:-90,endAngle:90,
            background:[{
                outerRadius:'100%',
                innerRadius:'60%',
                shape:'arc',
                backgroundColor:'#14202a'
            }]
        },
        yAxis:{min:0,max:max,lineWidth:0,tickWidth:0,labels:{enabled:false}},
        series:[{data:[{y:val,color:color}]}],
        credits:{enabled:false}
    });
}

function updateGauge(id,val){
    if(gauges[id]){
        gauges[id].series[0].points[0].update(val);
    }
}

createGauge('g_rs',<?=$vrs?>,450,'V','voltage');
createGauge('g_st',<?=$vst?>,450,'V','voltage');
createGauge('g_rt',<?=$vrt?>,450,'V','voltage');
createGauge('g_cr',<?=$cr?>,1500,'A','current');
createGauge('g_cs',<?=$cs?>,1500,'A','current');
createGauge('g_ct',<?=$ct?>,1500,'A','current');

function loadChart(id,url,type,color){
    fetch(url).then(r=>r.json()).then(d=>{
        const ctx=document.getElementById(id).getContext('2d');
        if(window[id] && typeof window[id].destroy==='function') window[id].destroy();
        window[id]=new Chart(ctx,{
            type:type,
            data:{
                labels:d.labels,
              datasets:[{
                     data: d.values || d.tarifs,
                     borderColor: color,
                     backgroundColor: color,
                     borderWidth: 2,
                     hoverBackgroundColor: color,
                    }]

            },
            options:{plugins:{legend:{display:false}}}
        });
    });
}

//loadChart('powerChart','index.php?chart=power','line','#00ff99');
//loadChart('tarifChart','index.php?chart=bar','bar','#00bfff');
//loadChart('monthlyChart','index.php?chart=monthly','bar','#ffcc00');

loadChart('powerChart','index.php?chart=power','line','#00ff99');

// Tarif Harian LVMDP → Biru Cerah Neon
loadChart('tarifChart','index.php?chart=bar','bar','#00f6ff');

// Total Tarif Bulanan → Kuning Terang Neon
loadChart('monthlyChart','index.php?chart=monthly','bar','#ffff00');


fetch('index.php?chart=pie').then(r=>r.json()).then(d=>{
    const ctx=document.getElementById('pieChart').getContext('2d');
    window.pieChart=new Chart(ctx,{
        type:'pie',
        data:{labels:d.labels,datasets:[{data:d.values}]},
        options:{plugins:{legend:{display:false},datalabels:{color:'#fff'}}},
        plugins:[ChartDataLabels]
    });
});

/* =========================================
   AUTO REFRESH CHART (SETIAP 60 DETIK)
   ========================================= */
setInterval(() => {

    // Power realtime
    loadChart('powerChart','index.php?chart=power','line','#00ff99');

    // Tarif harian LVMDP
    loadChart('tarifChart','index.php?chart=bar','bar','#00f6ff');

    // Tarif bulanan LVMDP
    loadChart('monthlyChart','index.php?chart=monthly','bar','#ffff00');

    // Refresh gauge
 fetch('index.php?chart=gauge')
  .then(r=>r.json())
  .then(d=>{
      updateGauge('g_rs',d.vrs);
      updateGauge('g_st',d.vst);
      updateGauge('g_rt',d.vrt);
      updateGauge('g_cr',d.cr);
      updateGauge('g_cs',d.cs);
      updateGauge('g_ct',d.ct);
  });

  fetch('index.php?chart=pie').then(r => r.json()).then(d => {
    const ctx = document.getElementById('horizontalPanelChart').getContext('2d');
    
    // Inisialisasi Chart
    window.horizontalPanelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: d.labels, // Nama Panel (Location)
            datasets: [{
                label: 'Total Konsumsi (kWh)',
                data: d.values, // Total kWh
                backgroundColor: 'rgba(255, 99, 132, 0.7)', // Warna Merah/Pink Neon
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1,
                barPercentage: 0.6
            }]
        },
        options: {
            indexAxis: 'y', // <--- KUNCI UTAMA: Mengubah Bar menjadi Horizontal
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, // Sembunyikan legenda jika tidak perlu
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    color: '#fff',
                    formatter: function(value) {
                        return value.toFixed(1) + ' kWh'; // Menampilkan angka di ujung bar
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#ffffff22' }, // Garis grid tipis transparan
                    ticks: { color: '#e0e6ed' }
                },
                y: {
                    grid: { display: false }, // Hilangkan grid vertikal agar bersih
                    ticks: { 
                        color: '#00ccff', // Warna text label panel
                        font: { size: 12 }
                    }
                }
            }
        },
        plugins: [ChartDataLabels] // Menggunakan plugin label (opsional, sudah ada di script anda)
    });
});

    // Pie chart konsumsi panel
    fetch('index.php?chart=pie')
        .then(r=>r.json())
        .then(d=>{
            if(window.pieChart){
                pieChart.data.labels = d.labels;
                pieChart.data.datasets[0].data = d.values;
                pieChart.update();
            }
        });

}, 60000); // 60000 ms = 60 detik

</script>

</body>
</html>
