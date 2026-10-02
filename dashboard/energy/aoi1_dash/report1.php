<?php 
// index.php - Monitoring Harian (tanpa LWBP1 & LWBP2)

include 'db.php';

// ==============================
// AMBIL DATA UNTUK DROPDOWN LOKASI
// ==============================
$locations = [];
$resLoc = $conn->query("SELECT DISTINCT location FROM electricity_aoi1_harian ORDER BY location ASC");
while ($row = $resLoc->fetch_assoc()) {
    $locations[] = $row['location'];
}

// ==============================
// HANDLE FILTER
// ==============================
$where = "1";
if (!empty($_GET['location'])) {
    $location = $conn->real_escape_string($_GET['location']);
    $where .= " AND location='$location'";
}
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $from = $conn->real_escape_string($_GET['from']);
    $to = $conn->real_escape_string($_GET['to']);
    $where .= " AND tanggal BETWEEN '$from' AND '$to'";
}

// ==============================
// QUERY DATA UTAMA
// ==============================
$sql = "SELECT tanggal, location, 
               lwbp_consumpt, wbp_consumpt,
               lwbp_tarif, wbp_tarif,
               total_tarif
        FROM electricity_aoi1_harian
        WHERE $where
        ORDER BY tanggal ASC";
$result = $conn->query($sql);

$data = [];
$total_lwbp_tarif = $total_wbp_tarif = $total_semua = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_lwbp_tarif += $row['lwbp_tarif'];
        $total_wbp_tarif  += $row['wbp_tarif'];
        $total_semua      += $row['total_tarif'];
    }
}

// ==============================
// EXPORT KE EXCEL
// ==============================
if (isset($_GET['export']) && $_GET['export'] == 'xls') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=data_harian.xls");
    echo "<table border='1'>
            <tr style='background:#dce6f1'>
                <th>Tanggal</th>
                <th>Lokasi</th>
                <th>LWBP (KWh)</th>
                <th>WBP (KWh)</th>
                <th>Tarif LWBP (Rp)</th>
                <th>Tarif WBP (Rp)</th>
                <th>Total Tarif (Rp)</th>
            </tr>";
    foreach ($data as $r) {
        echo "<tr>
                <td>{$r['tanggal']}</td>
                <td>{$r['location']}</td>
                <td>".number_format($r['lwbp_consumpt'], 1)."</td>
                <td>".number_format($r['wbp_consumpt'], 1)."</td>
                <td>".number_format($r['lwbp_tarif'], 0, ',', '.')."</td>
                <td>".number_format($r['wbp_tarif'], 0, ',', '.')."</td>
                <td>".number_format($r['total_tarif'], 0, ',', '.')."</td>
              </tr>";
    }
    echo "<tr style='font-weight:bold;background:#fce4d6'>
            <td colspan='4'>Total Hasil Filter</td>
            <td>".number_format($total_lwbp_tarif, 0, ',', '.')."</td>
            <td>".number_format($total_wbp_tarif, 0, ',', '.')."</td>
            <td>".number_format($total_semua, 0, ',', '.')."</td>
          </tr>";
    echo "</table>";
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monitoring Harian LVMDP</title>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}
.main-container { display: flex; }
#sidebar {
    width: 250px;
    background: #1f2a40;
    color: white;
    height: 100vh;
    padding-top: 20px;
    position: fixed;
    transition: all 0.3s ease;
    overflow-y: auto;
}
#sidebar.hidden { transform: translateX(-250px); }
.toggle-btn {
    position: fixed;
    left: 260px;
    top: 15px;
    background: #1f2a40;
    color: #fff;
    padding: 6px 10px;
    cursor: pointer;
    border-radius: 4px;
    z-index: 200;
    transition: all 0.3s;
}
.toggle-btn.closed { left: 10px; }
.toggle-btn:hover { background: #00acc1; }
.content {
    flex: 1;
    padding: 20px;
    margin-left: 250px;
    transition: margin-left 0.3s;
}
.content.full { margin-left: 0; }

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    margin-top: 15px;
}
th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
}
th {
    background: #1f2a40;
    color: white;
}

.filter-box {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
.filter-box input, .filter-box select, .filter-box button {
    padding: 6px 10px;
    margin-right: 8px;
}
button {
    cursor: pointer;
    border: none;
    background: #1f2a40;
    color: white;
    border-radius: 4px;
}
button:hover { background: #00acc1; }

.total-box {
    background: #fff3e0;
    border: 1px solid #ccc;
    padding: 10px;
    margin-top: 15px;
    border-radius: 8px;
    font-weight: bold;
}
.total-box span {
    display: inline-block;
    min-width: 200px;
}
</style>
</head>
<body>
<div class="main-container">
    <?php include 'sidebar.php'; ?>
    <div id="toggleSidebar" class="toggle-btn">☰</div>

    <div id="content" class="content">
        <h2>Monitoring Konsumsi Harian (KWh & Tarif)</h2>

        <form class="filter-box" method="GET">
            <label>Lokasi:</label>
            <select name="location">
                <option value="">-- Semua Lokasi --</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc ?>" <?= (isset($_GET['location']) && $_GET['location']==$loc)?'selected':'' ?>>
                        <?= $loc ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Dari:</label>
            <input type="date" name="from" value="<?= $_GET['from'] ?? '' ?>">

            <label>Sampai:</label>
            <input type="date" name="to" value="<?= $_GET['to'] ?? '' ?>">

            <button type="submit">Filter</button>
            <?php if (!empty($data)): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'xls'])) ?>">
                    <button type="button">Export Excel</button>
                </a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Lokasi</th>
                    <th>LWBP (KWh)</th>
                    <th>WBP (KWh)</th>
                    <th>Tarif LWBP (Rp)</th>
                    <th>Tarif WBP (Rp)</th>
                    <th>Total Tarif (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="7">Tidak ada data ditemukan.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['tanggal']) ?></td>
                            <td><?= htmlspecialchars($r['location']) ?></td>
                            <td><?= number_format($r['lwbp_consumpt'], 1) ?></td>
                            <td><?= number_format($r['wbp_consumpt'], 1) ?></td>
                            <td><?= number_format($r['lwbp_tarif'], 0, ',', '.') ?></td>
                            <td><?= number_format($r['wbp_tarif'], 0, ',', '.') ?></td>
                            <td><?= number_format($r['total_tarif'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($data)): ?>
        <div class="total-box">
            <span>Total Tarif LWBP: Rp <?= number_format($total_lwbp_tarif, 0, ',', '.') ?></span>
            <span>Total Tarif WBP: Rp <?= number_format($total_wbp_tarif, 0, ',', '.') ?></span><br>
            <span style="color:#d84315;font-size:18px;">TOTAL SEMUA: Rp <?= number_format($total_semua, 0, ',', '.') ?></span>
        </div>
        <?php endif; ?>
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

let sidebarStatus=localStorage.getItem("sidebarStatus")||"closed";
updateSidebar(sidebarStatus);

toggleBtn.addEventListener("click",()=>{
    sidebarStatus=sidebar.classList.contains("hidden")?"open":"closed";
    localStorage.setItem("sidebarStatus",sidebarStatus);
    updateSidebar(sidebarStatus);
});
</script>
</body>
</html>
