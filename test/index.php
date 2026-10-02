<?php
include 'config.php';

// Cek jika ada data masuk via URL: index.php?humidity=60&measurement=100
if (isset($_GET['humidity'])) {
    $humidity = intval($_GET['humidity']);
    $measurement = isset($_GET['measurement']) ? intval($_GET['measurement']) : 0;
    
    $sql = "INSERT INTO sensors (humidity, measurement) VALUES ($humidity, $measurement)";
    
    if ($conn->query($sql) === TRUE) {
        // Refresh halaman agar data baru langsung muncul
        header("Location: index.php");
        exit();
    }
}

// Ambil 10 data terbaru
$result = $conn->query("SELECT * FROM sensors ORDER BY timestamp DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Sensor IoT</title>
    <style>
        body { font-family: sans-serif; margin: 40px; background: #f4f4f4; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #273a3fff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-box { padding: 10px; background: #e2f3ff; border-left: 5px solid #007bff; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Testing Dashboard Get Data Sensor from database</h2>
    
    <div class="status-box">
        <strong>Cara Input Data:</strong><br>
        <code>localhost/folder_anda/index.php?humidity=75&measurement=120</code>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Kelembapan (Humidity)</th>
                <th>Pengukuran (Measurement)</th>
                <th>Waktu (Timestamp)</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= $row['humidity']; ?>%</td>
                    <td><?= $row['measurement']; ?></td>
                    <td><?= $row['timestamp']; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">Belum ada data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>