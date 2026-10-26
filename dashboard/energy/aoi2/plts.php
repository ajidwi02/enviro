<?php
// plts.php - PLTS Dashboard AOI2 (Gauge Chart View)
// Data source: MySQL `enviro` -> tabel `electricity` -> id_location = 20105 (PLTS)
// Layer akses data: ./plts_data.php   |   Endpoint refresh: ./api_plts_realtime.php

require_once __DIR__ . '/plts_data.php';

// Status koneksi database
$plts_db_ok = plts_db_is_connected($mysqli);
$plts_error = $plts_db_ok
    ? ''
    : (isset($GLOBALS['PLTS_DB_ERROR']) ? $GLOBALS['PLTS_DB_ERROR'] : 'Koneksi database gagal.');

// Susun data untuk render pertama (tanpa perlu reload halaman)
$latest   = array();
$meta     = array();
$lastUpd  = '-';

if ($plts_db_ok) {
    try {
        $payload = plts_build_payload($mysqli);
        $latest  = $payload['data'];
        $meta    = $payload['meta'];
        $lastUpd = $meta['last_update_display'];
    } catch (Throwable $e) {
        $plts_db_ok = false;
        $plts_error = 'Gagal membaca data: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>PLTS Dashboard -AOI2-</title>
<!-- Plotly JS -->
<script src="js/plotly-2.30.0.min.js"></script>
<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: Arial, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}
.main-container {
    display: flex;
}
.content {
    flex: 1;
    padding: 24px;
    margin-left: 250px;
    transition: margin-left .3s ease;
    min-height: 100vh;
}
.content.full {
    margin-left: 0;
}
.toggle-btn {
    position: fixed;
    left: 260px;
    top: 15px;
    background: #1f2a40;
    color: #fff;
    padding: 6px 12px;
    cursor: pointer;
    border-radius: 4px;
    z-index: 1050;
    transition: all .3s ease;
    font-size: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    user-select: none;
}
.toggle-btn.closed {
    left: 15px;
}
.toggle-btn:hover {
    background: #00acc1;
    color: #fff;
}

/* Header Table */
.header-table {
    width: 100%;
    margin-bottom: 24px;
    background: #fff;
    border-radius: 12px;
    padding: 12px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
}
.header-title-container {
    text-align: center;
}
.header-title-container h2 {
    margin: 0;
    color: #0f172a;
    font-size: 24px;
    font-weight: 700;
}
.header-title-container p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #64748b;
}

/* Badge Dummy -> dihapus (badge "Data Dummy Sementara" sudah diganti badge-live) */

/* Section Card */
.section-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}
.section-title {
    margin: 0 0 16px 0;
    font-size: 17px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
}

/* Section 1 Layout: Tegangan 1 Phasa */
.tegangan-container {
    display: grid;
    grid-template-columns: 1fr 2px 1fr;
    gap: 20px;
    align-items: stretch;
}
.tegangan-col {
    display: flex;
    flex-direction: column;
}
.group-title {
    text-align: center;
    font-weight: 600;
    font-size: 14px;
    color: #475569;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 2px solid #e2e8f0;
}
.v-divider {
    width: 2px;
    background: #e2e8f0;
    border-radius: 2px;
}

/* 3-Column Gauge Grid */
.gauge-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    width: 100%;
}

/* Gauge Item Card */
.gauge-card-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    height: 190px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.gauge-card-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 8px rgba(0,0,0,0.08);
}

/* Plotly Container Fixes - PENTING agar SVG tidak tumpuk ke bawah */
.gauge-plot {
    width: 100% !important;
    height: 100% !important;
    position: relative !important;
}
.gauge-plot .plot-container.plotly {
    width: 100% !important;
    height: 100% !important;
    position: relative !important;
}
.gauge-plot .svg-container {
    position: relative !important;
    width: 100% !important;
    height: 100% !important;
}
.gauge-plot svg.main-svg {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
}

/* Section 2 Layout: Arus & Info Listrik */
.arus-container {
    display: grid;
    grid-template-columns: 3fr 1fr;
    gap: 20px;
    align-items: stretch;
}
.arus-col {
    display: flex;
    flex-direction: column;
}

/* Custom Info Box */
.info-box-wrapper {
    display: flex;
    flex-direction: column;
}
.custom-info-box {
    background: linear-gradient(145deg, #f0fdfa, #e0f2fe);
    border: 1px solid #7dd3fc;
    border-radius: 10px;
    padding: 16px 20px;
    height: 100%;
    min-height: 190px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.info-box-header {
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(14, 165, 233, 0.25);
    padding-bottom: 8px;
    margin-bottom: 8px;
}
.info-icon {
    font-size: 22px;
}
.info-box-header h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0369a1;
}
.info-box-header .info-sub {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0284c7;
    font-weight: 600;
}
.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px dashed rgba(14, 165, 233, 0.18);
}
.info-item:last-child {
    border-bottom: none;
}
.info-label {
    font-size: 13px;
    color: #334155;
    font-weight: 500;
}
.info-val {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}
.info-val small {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
}

/* Responsif */
@media(max-width: 1200px) {
    .tegangan-container {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .v-divider {
        display: none;
    }
    .arus-container {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
@media(max-width: 680px) {
    .gauge-grid-3 {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .gauge-card-item {
        height: 180px;
    }
    .content {
        margin-left: 0 !important;
        padding: 16px;
    }
}

/* ==================== TAMBAHAN: Status Bar, Badge & Layout 1 Phasa ==================== */

/* Section "Tegangan 1 Phasa" sekarang 1 kolom penuh
   (container "3 Phasa (Antar Fasa)" beserta divider-nya sudah dihapus) */
.tegangan-container {
    grid-template-columns: 1fr;
    gap: 20px;
}

/* Badge Realtime */
.badge-live {
    display: inline-block;
    background: #dcfce7;
    color: #15803d;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 12px;
    border: 1px solid #bbf7d0;
    margin-left: 10px;
    vertical-align: middle;
}

/* Banner error */
.plts-alert {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 13px;
    font-weight: 500;
    padding: 10px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
}

/* Status bar */
.plts-status-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 16px;
    margin-bottom: 16px;
    font-size: 12.5px;
    color: #475569;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    background: #94a3b8;
    flex: 0 0 auto;
}
.status-dot.ok {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34,197,94,0.18);
}
.status-dot.err {
    background: #ef4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.18);
}
.status-sep {
    color: #cbd5e1;
}
.plts-status-bar b {
    color: #0f172a;
}
.btn-refresh {
    margin-left: auto;
    background: #1f2a40;
    color: #fff;
    border: none;
    font-size: 12px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background .2s ease;
}
.btn-refresh:hover {
    background: #00acc1;
}

/* Penanda anomali data meter (meter reset / gangguan komunikasi) */
.info-val-box {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.info-warn {
    display: none;
    font-size: 13px;
    line-height: 1;
    cursor: help;
    color: #d97706;
}
.info-warn.show {
    display: inline;
}
/* ==================== AKHIR TAMBAHAN ==================== */
</style>
</head>
<body>

<div class="main-container">
    <!-- Sidebar Menu AOI2 -->
    <?php include 'sidebar.php'; ?>
    <div id="toggleSidebar" class="toggle-btn" title="Toggle Sidebar">☰</div>

    <div id="content" class="content">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 10%; text-align: center; vertical-align: middle;">
                    <img src="../../../logo_aoi.png" width="105" alt="Logo AOI">
                </td>
                <td style="width: 80%; vertical-align: middle;">
                    <div class="header-title-container">	
                        <h2>PLTS Dashboard -AOI2- <span class="badge-live">Realtime</span></h2>
                        <p><span id="datenow"></span> / <span id="clock"></span></p>
                        <script>
                        const months = ["Jan", "Feb", "Mar","Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                        let current_datetime = new Date();
                        let formatted_date = current_datetime.getDate() + "-" + months[current_datetime.getMonth()] + "-" + current_datetime.getFullYear();
                        document.getElementById("datenow").innerHTML = formatted_date;

                        function showTime() {
                            var d = new Date();
                            var clockEl = document.getElementById("clock");
                            if (clockEl) clockEl.innerHTML = d.toLocaleTimeString();
                        }
                        setInterval(showTime, 1000);
                        showTime();
                        </script>
                    </div>
                </td>
                <td style="width: 10%; text-align: center; vertical-align: middle;">
                    <img src="../../../logo_bbi.png" width="125" alt="Logo BBI">
                </td>
            </tr>
        </table>

        <?php if (!$plts_db_ok): ?>
        <div class="plts-alert">
            ⚠️ <?= htmlspecialchars($plts_error) ?> — Dashboard menampilkan data terakhir yang berhasil dimuat (bila ada).
        </div>
        <?php endif; ?>

        <div class="plts-status-bar" id="pltsStatusBar">
            <span class="status-dot <?= $plts_db_ok ? 'ok' : 'err' ?>" id="statusDot"></span>
            <span id="statusText"><?= $plts_db_ok ? 'Terhubung ke database' : 'Database tidak terhubung' ?></span>
            <span class="status-sep">|</span>
            <span>Terakhir update: <b id="lastUpdate"><?= htmlspecialchars($lastUpd) ?></b></span>
            <button type="button" class="btn-refresh" id="btnRefresh" title="Refresh sekarang">⟳ Refresh</button>
        </div>

        <!-- 1. Card Section: Tegangan 1 Phasa -->
        <div class="section-card">
            <h4 class="section-title">⚡ Tegangan 1 Phasa</h4>
            <div class="tegangan-container">
                <!-- 1 Phasa (REALTIME dari DB) -->
                <div class="tegangan-col">
                    <div class="group-title">1 Phasa</div>
                    <div class="gauge-grid-3">
                        <div class="gauge-card-item">
                            <div id="gaugeR" class="gauge-plot"></div>
                        </div>
                        <div class="gauge-card-item">
                            <div id="gaugeS" class="gauge-plot"></div>
                        </div>
                        <div class="gauge-card-item">
                            <div id="gaugeT" class="gauge-plot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Card Section: Arus R, S, T (Ampere) & Informasi Listrik -->
        <div class="section-card">
            <h4 class="section-title">⚙️ Arus R, S, T (Ampere) & Informasi Listrik</h4>
            <div class="arus-container">
                <!-- 3 Current Gauges (REALTIME dari DB) -->
                <div class="arus-col">
                    <div class="gauge-grid-3">
                        <div class="gauge-card-item">
                            <div id="gaugeIR" class="gauge-plot"></div>
                        </div>
                        <div class="gauge-card-item">
                            <div id="gaugeIS" class="gauge-plot"></div>
                        </div>
                        <div class="gauge-card-item">
                            <div id="gaugeIT" class="gauge-plot"></div>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="info-box-wrapper">
                    <div class="custom-info-box">
                        <div class="info-box-header">
                            <span class="info-icon">⚡</span>
                            <div>
                                <h4>Informasi Listrik</h4>
                                <span class="info-sub">Hari Ini</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Consumption</span>
                            <span class="info-val-box">
                                <span class="info-val" id="valConsumption" style="color:#7c3aed;">
                                    <?= plts_fmt_num($latest['consumption'] ?? null, 2) ?> <small><?= PLTS_CONSUMPTION_UNIT ?></small>
                                </span>
                                <span class="info-warn<?= ((int)($meta['jml_anomali'] ?? 0)) > 0 ? ' show' : '' ?>"
                                      id="valConsumptionWarn"
                                      title="<?= ((int)($meta['jml_anomali'] ?? 0)) > 0
                                             ? ((int)$meta['jml_anomali']) . ' baris anomali meter terdeteksi (nilai Consumption sudah dikoreksi otomatis)'
                                             : 'Tidak ada anomali data' ?>">⚠</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Power Output</span>
                            <span class="info-val" id="valPower" style="color:#0284c7;">
                                <?= plts_fmt_num($latest['power'] ?? null, 2) ?> <small><?= PLTS_POWER_UNIT ?></small>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label" title="Akumulasi meter kWh - diambil dari 1 data terbaru (realtime)">Total KWH</span>
                            <span class="info-val" id="valKwh" style="color:#16a34a;" title="Akumulasi meter kWh - realtime">
                                <?= plts_fmt_num($latest['kwh_total'] ?? null, 2) ?> <small>kWh</small>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// ===================================================================
// KONFIGURASI
// ===================================================================
const API_URL       = 'api_plts_realtime.php';
const REFRESH_MS    = 60000;   // interval auto refresh (ms) -> 60 detik
const POWER_UNIT    = '<?= PLTS_POWER_UNIT ?>';
const CONSUMPTION_UNIT = '<?= PLTS_CONSUMPTION_UNIT ?>';

// Konfigurasi gauge. max = nilai maksimum sumbu.
// Current max 300 (data aktual s/d 282 A) - JANGAN turunkan ke 200.
const GAUGE_CONFIG = {
    gaugeR:  { key: 'voltage_r', label: 'Voltage R (V)', color: '#ef4444', max: 250 },
    gaugeS:  { key: 'voltage_s', label: 'Voltage S (V)', color: '#3b82f6', max: 250 },
    gaugeT:  { key: 'voltage_t', label: 'Voltage T (V)', color: '#f59e0b', max: 250 },
    gaugeIR: { key: 'current_r', label: 'Current R (A)', color: '#ef4444', max: 300 },
    gaugeIS: { key: 'current_s', label: 'Current S (A)', color: '#3b82f6', max: 300 },
    gaugeIT: { key: 'current_t', label: 'Current T (A)', color: '#f59e0b', max: 300 }
};

// Data awal (dari render PHP)
let latest = <?= json_encode((object) $latest) ?>;
let meta   = <?= json_encode((object) $meta) ?>;

// ===================================================================
// SIDEBAR TOGGLE (TIDAK BERUBAH)
// ===================================================================
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleSidebar");
const content = document.getElementById("content");
const closeBtn = document.getElementById("closeSidebar");

function resizeAllPlotly() {
    ['gaugeR','gaugeS','gaugeT','gaugeIR','gaugeIS','gaugeIT'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && typeof Plotly !== 'undefined' && el.data) {
            Plotly.Plots.resize(el);
        }
    });
}

function updateSidebar(status) {
    if (status === "closed") {
        sidebar.classList.add("hidden");
        content.classList.add("full");
        toggleBtn.classList.add("closed");
        toggleBtn.textContent = "☰";
    } else {
        sidebar.classList.remove("hidden");
        content.classList.remove("full");
        toggleBtn.classList.remove("closed");
        toggleBtn.textContent = "✖";
    }
    setTimeout(resizeAllPlotly, 320);
}

let sidebarStatus = localStorage.getItem("sidebarStatus_aoi2") || "closed";
updateSidebar(sidebarStatus);

toggleBtn.addEventListener("click", () => {
    sidebarStatus = sidebar.classList.contains("hidden") ? "open" : "closed";
    localStorage.setItem("sidebarStatus_aoi2", sidebarStatus);
    updateSidebar(sidebarStatus);
});

if (closeBtn) {
    closeBtn.addEventListener("click", () => {
        sidebarStatus = "closed";
        localStorage.setItem("sidebarStatus_aoi2", sidebarStatus);
        updateSidebar(sidebarStatus);
    });
}

window.addEventListener('resize', resizeAllPlotly);

// ===================================================================
// HELPER
// ===================================================================
function isNum(v) {
    return v !== null && v !== undefined && v !== '' && !isNaN(parseFloat(v));
}

// Format angka HARUS sama dengan PHP number_format(): ribuan ',', desimal '.'
function fmtNum(v, dec) {
    if (!isNum(v)) return '—';
    var parts = Number(v).toFixed(dec).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return parts.join('.');
}

function setText(id, text) {
    var el = document.getElementById(id);
    if (el) el.textContent = text;
}

function setHtml(id, html) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = html;
}

function setStatus(ok, text) {
    var dot = document.getElementById('statusDot');
    var txt = document.getElementById('statusText');
    if (dot) dot.className = 'status-dot ' + (ok ? 'ok' : 'err');
    if (txt) txt.textContent = text;
}

// ===================================================================
// GAUGE PLOTLY
// ===================================================================
// Function Pembuat Gauge Plotly dengan Layout Presisi
function makeGauge(id, value, label, color, maxVal) {
    var el = document.getElementById(id);
    if (!el) return;

    var data = [{
        type: "indicator",
        mode: "gauge+number",
        value: value,
        title: {
            text: "<b>" + label + "</b>",
            font: { size: 13, color: "#334155" }
        },
        number: {
            font: { size: 26, color: "#0f172a", family: "Arial, sans-serif" },
            valueformat: ".1f"
        },
        gauge: {
            axis: {
                range: [0, maxVal],
                tickwidth: 1,
                tickcolor: "#94a3b8",
                tickfont: { size: 10, color: "#64748b" }
            },
            bar: { color: color, thickness: 0.22 },
            bgcolor: "#f8fafc",
            borderwidth: 1,
            bordercolor: "#e2e8f0",
            steps: [
                { range: [0, maxVal * 0.7], color: "#f8fafc" },
                { range: [maxVal * 0.7, maxVal * 0.88], color: "#e0f2fe" },
                { range: [maxVal * 0.88, maxVal], color: "#bae6fd" }
            ],
            threshold: {
                line: { color: "#dc2626", width: 3 },
                thickness: 0.8,
                value: maxVal * 0.92
            }
        }
    }];

    var layout = {
        margin: { t: 35, b: 20, l: 20, r: 20 },
        paper_bgcolor: "transparent",
        plot_bgcolor: "transparent",
        autosize: true
    };

    var config = {
        responsive: true,
        displayModeBar: false // Hilangkan toolbar plotly agar tampilan bersih
    };

    // react() untuk update, newPlot() untuk render pertama
    if (el.data) {
        Plotly.react(id, data, layout, config);
    } else {
        Plotly.newPlot(id, data, layout, config);
    }
}

// ===================================================================
// Terapkan data ke tampilan
// ===================================================================
function applyData(d, m) {
    d = d || {};
    m = m || {};

    // 1. Gauge Voltage 1 Phasa & Current (realtime)
    Object.keys(GAUGE_CONFIG).forEach(function (id) {
        var cfg = GAUGE_CONFIG[id];
        var val = isNum(d[cfg.key]) ? parseFloat(d[cfg.key]) : 0;
        makeGauge(id, val, cfg.label, cfg.color, cfg.max);
    });

    // 2. Informasi Listrik
    //    Consumption -> selisih meter hari ini (kWh), anomali meter sudah disanitasi
    //    Power Output -> SUM(power) hari ini
    //    Total KWH   -> 1 data terbaru (realtime, akumulasi meter)
    setHtml('valConsumption', fmtNum(d.consumption, 2) + ' <small>' + CONSUMPTION_UNIT + '</small>');
    setHtml('valPower', fmtNum(d.power, 2) + ' <small>' + POWER_UNIT + '</small>');
    setHtml('valKwh', fmtNum(d.kwh_total, 2) + ' <small>kWh</small>');

    // 2b. Penanda anomali data meter (meter reset / gangguan komunikasi)
    var warnEl = document.getElementById('valConsumptionWarn');
    if (warnEl) {
        var jmlAnomali = (m && m.jml_anomali) ? parseInt(m.jml_anomali, 10) : 0;
        if (jmlAnomali > 0) {
            warnEl.classList.add('show');
            warnEl.setAttribute('title', jmlAnomali + ' baris anomali meter terdeteksi (nilai Consumption sudah dikoreksi otomatis)');
        } else {
            warnEl.classList.remove('show');
            warnEl.setAttribute('title', 'Tidak ada anomali data');
        }
    }

    // 3. Waktu update dari database (bukan jam browser)
    if (m.last_update_display) {
        setText('lastUpdate', m.last_update_display);
    }
}

// ===================================================================
// AUTO REFRESH (AJAX)
// ===================================================================
let refreshTimer = null;

function fetchLatest() {
    fetch(API_URL + '?t=' + Date.now(), { cache: 'no-store' })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function (json) {
            if (!json || json.success !== true) {
                throw new Error((json && json.message) ? json.message : 'Format respons tidak valid');
            }
            setStatus(true, 'Data realtime (' + ((json.meta && json.meta.jml_record_hari_ini) || 0) + ' record hari ini)');
            applyData(json.data, json.meta);
        })
        .catch(function (err) {
            setStatus(false, 'Gagal memuat data: ' + err.message);
        });
}

function startAutoRefresh() {
    if (refreshTimer !== null) return;
    refreshTimer = setInterval(fetchLatest, REFRESH_MS);
}

// Render pertama memakai data dari PHP
applyData(latest, meta);

// Auto refresh
startAutoRefresh();
fetchLatest(); // panggil sekali lagi untuk memastikan data paling baru

// Tombol refresh manual
var btnRefresh = document.getElementById('btnRefresh');
if (btnRefresh) {
    btnRefresh.addEventListener('click', function () {
        setStatus(true, 'Memuat data...');
        fetchLatest();
    });
}

// Refresh otomatis lagi saat tab diklik kembali dari background
document.addEventListener('visibilitychange', function () {
    if (!document.hidden) fetchLatest();
});
</script>
</body>
</html>
