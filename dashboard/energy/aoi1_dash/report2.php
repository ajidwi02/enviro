<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

// ==============================
// Ambil daftar lokasi untuk dropdown
// ==============================
$lokasi_result = $conn->query("SELECT id_location, location FROM loc_electricity_aoi1 ORDER BY location ASC");
$lokasi_options = [];
while ($row = $lokasi_result->fetch_assoc()) {
    $lokasi_options[] = $row;
}

// ==============================
// Ambil filter lokasi & tanggal
// ==============================
$id_lokasi = isset($_GET['id_lokasi']) ? $_GET['id_lokasi'] : '101';
$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
$to   = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// ==============================
// Query utama
// ==============================
$sql = "SELECT 
            e.id_lokasi,
            l.location,
            e.voltage_r, e.voltage_s, e.voltage_t,
            e.current_r, e.current_s, e.current_t,
            e.power_factor, e.power, e.kwh_total, e.datenow
        FROM electricity_aoi1_reporting e
        LEFT JOIN loc_electricity_aoi1 l 
        ON e.id_lokasi = l.id_location
        WHERE e.id_lokasi = '$id_lokasi'
          AND DATE(e.datenow) BETWEEN '$from' AND '$to'
        ORDER BY e.datenow DESC";

$result = $conn->query($sql);
if (!$result) {
    die("SQL Error: " . $conn->error . "<br>Query: " . $sql);
}

// ==============================
// Export ke Excel
// ==============================
if (isset($_GET['export']) && $_GET['export'] == 'xls') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=report_electricity_$from-$to.xls");

    echo "<table border='1'>";
    echo "<tr style='background:#dce6f1'>
            <th>Lokasi</th>
            <th>Voltage R</th>
            <th>Voltage S</th>
            <th>Voltage T</th>
            <th>Current R</th>
            <th>Current S</th>
            <th>Current T</th>
            <th>Power Factor</th>
            <th>Power (W)</th>
            <th>kWh Total</th>
            <th>Date</th>
          </tr>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['location']}</td>
                    <td>{$row['voltage_r']}</td>
                    <td>{$row['voltage_s']}</td>
                    <td>{$row['voltage_t']}</td>
                    <td>{$row['current_r']}</td>
                    <td>{$row['current_s']}</td>
                    <td>{$row['current_t']}</td>
                    <td>{$row['power_factor']}</td>
                    <td>{$row['power']}</td>
                    <td>{$row['kwh_total']}</td>
                    <td>{$row['datenow']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='11'>Tidak ada data.</td></tr>";
    }

    echo "</table>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report AOI1 - Electricity Monitoring</title>

<!-- STYLE -->
<link rel="stylesheet" href="css/flatpickr.min.css">
<style>
body { margin:0; font-family:Arial,sans-serif; background:#f4f6f9; }
.main-container { display:flex; }
#sidebar {
    width:250px; background:#1f2a40; color:white; height:100vh;
    padding-top:20px; position:fixed; left:0; top:0;
    transition:all 0.3s ease; overflow-y:auto; z-index:100;
}
#sidebar.hidden { transform:translateX(-250px); }

.toggle-btn {
    position:fixed; top:15px; left:260px; background:#1f2a40; color:#fff;
    padding:6px 10px; cursor:pointer; border-radius:4px; z-index:9999; transition:all 0.3s ease;
}
.toggle-btn.closed { left:15px; }
.toggle-btn:hover { background:#00acc1; }

.content {
    flex:1; padding:20px; margin-left:250px; transition:margin-left 0.3s ease;
}
.content.full { margin-left:0; }

h2 { color:#333; }
.filter-box {
    background:white; padding:10px 15px; border-radius:6px;
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
    display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:15px;
}
.filter-box select, .filter-box input {
    padding:5px 8px; border:1px solid #ccc; border-radius:4px;
}
.filter-box button {
    padding:6px 12px; background:#1f2a40; color:white; border:none; border-radius:4px; cursor:pointer;
}
.filter-box button:hover { background:#00acc1; }
table {
    width:100%; border-collapse:collapse; background:white; box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#1f2a40; color:white; }
tr:nth-child(even){background:#f9f9f9;}
tr:hover{background:#eaf4ff;}
.export-btn { background:#388e3c; }
.export-btn:hover { background:#2e7d32; }
</style>
</head>
<body>
<div class="main-container">
    <?php include 'sidebar.php'; ?>

    <div id="toggleSidebar" class="toggle-btn">✖</div>

    <div id="content" class="content">
        <h2>Data Monitoring Energy Listrik</h2>

        <form method="GET" class="filter-box">
            <label><b>Lokasi:</b></label>
            <select name="id_lokasi">
                <?php foreach ($lokasi_options as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['id_location']) ?>" <?= $opt['id_location']==$id_lokasi?'selected':'' ?>>
                        <?= htmlspecialchars($opt['location']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label><b>Dari:</b></label>
            <input type="text" id="from" name="from" value="<?= htmlspecialchars($from) ?>" placeholder="yyyy-mm-dd">

            <label><b>Sampai:</b></label>
            <input type="text" id="to" name="to" value="<?= htmlspecialchars($to) ?>" placeholder="yyyy-mm-dd">

            <button type="submit">Filter</button>

            <?php if ($result->num_rows > 0): ?>
                <a href="?<?= http_build_query(array_merge($_GET,['export'=>'xls'])) ?>">
                    <button type="button" class="export-btn">Export Excel</button>
                </a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Lokasi</th>
                    <th>Voltage R</th>
                    <th>Voltage S</th>
                    <th>Voltage T</th>
                    <th>Current R</th>
                    <th>Current S</th>
                    <th>Current T</th>
                    <th>Power Factor</th>
                    <th>Power (W)</th>
                    <th>kWh Total</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td><?= htmlspecialchars($row['voltage_r']) ?></td>
                            <td><?= htmlspecialchars($row['voltage_s']) ?></td>
                            <td><?= htmlspecialchars($row['voltage_t']) ?></td>
                            <td><?= htmlspecialchars($row['current_r']) ?></td>
                            <td><?= htmlspecialchars($row['current_s']) ?></td>
                            <td><?= htmlspecialchars($row['current_t']) ?></td>
                            <td><?= htmlspecialchars($row['power_factor']) ?></td>
                            <td><?= htmlspecialchars($row['power']) ?></td>
                            <td><?= htmlspecialchars($row['kwh_total']) ?></td>
                            <td><?= htmlspecialchars($row['datenow']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11">Tidak ada data untuk filter ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="js/flatpickr.min.js"></script>
<script>
flatpickr("#from", { dateFormat: "Y-m-d" });
flatpickr("#to", { dateFormat: "Y-m-d" });

// === Sidebar toggle ===
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleSidebar");
const content = document.getElementById("content");

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

let sidebarStatus = localStorage.getItem("sidebarStatus") || "open";
updateSidebar(sidebarStatus);

toggleBtn.addEventListener("click", ()=>{
    sidebarStatus = sidebar.classList.contains("hidden") ? "open" : "closed";
    localStorage.setItem("sidebarStatus", sidebarStatus);
    updateSidebar(sidebarStatus);
});
</script>
</body>
</html>
<?php $conn->close(); ?>
