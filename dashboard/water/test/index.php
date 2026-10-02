<?php
// Koneksi database
$host = "192.168.74.187";
$user = "root";
$pass = "imperia";
$db   = "bullmer_iot_report";

$conn = new mysqli($host, $user, $pass, $db);

// Jika koneksi gagal
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Query data DATEOFSTART = hari ini
$sql = "
    SELECT *
    FROM dlist
    WHERE DATE(DATEOFSTART) = CURDATE()
    ORDER BY DATEOFSTART DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data DLIST Hari Ini</title>
    <style>
        table {
            width: 2000px;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }
        table, th, td {
            border: 1px solid #555;
        }
        th {
            background: #f0f0f0;
            padding: 6px;
            white-space: nowrap;
        }
        td {
            padding: 4px;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<h2>Data DLIST Hari Ini (<?php echo date("Y-m-d"); ?>)</h2>

<table>
    <tr>
        <th>MACHINEID</th>
        <th>DATEOFSTART</th>
        <th>TIMEOFSTART</th>
        <th>DATEOFEND</th>
        <th>TlMEOFEND</th>
        <th>MARKER</th>
        <th>PARAMFILE</th>
        <th>ORDER</th>
        <th>OPERATOR</th>
        <th>CUTDISTANCE</th>
        <th>POSDISTANCE</th>
        <th>PENDISTANCE</th>
        <th>CONTOURDISTANCE</th>
        <th>MARKERLENGTH</th>
        <th>MARKERHEIGHT</th>
        <th>JOBTIME</th>
        <th>CUTTIME</th>
        <th>DRYHAULCUTTIME</th>
        <th>CONTOURTIME</th>
        <th>PENTIME</th>
        <th>DRYHAULPENTIME</th>
        <th>BITETIME</th>
        <th>SECONDARYTIME</th>
        <th>BREAKTIME</th>
        <th>ABORTTIME</th>
        <th>TIME</th>
        <th>DATE</th>
        <th>V</th>
        <th>VMAX</th>
        <th>VMIN</th>
        <th>IGNOREDPOINTS</th>
        <th>POINTS</th>
        <th>SEGS</th>
        <th>IGNOREDSEGS</th>
        <th>XZOOM</th>
        <th>YZOOM</th>
        <th>SHARPEN</th>
        <th>SLIT</th>
        <th>STITCH</th>
        <th>VNOTCH</th>
        <th>DRILL</th>
        <th>HELPRDRILL</th>
        <th>MATCHINGPOINT</th>
        <th>TOOLDOWN</th>
        <th>LABELS</th>
        <th>SECTIONS</th>
        <th>LAYER</th>
        <th>Custom39</th>
        <th>Custom40</th>
        <th>Custom282</th>
        <th>Custom707</th>
        <th>TimeMaxTA</th>
        <th>TimeMaxR</th>
        <th>Ax3MaxV</th>
        <th>TimeMaxTAForPositioning</th>
        <th>HubVmin</th>
        <th>HubVmax</th>
        <th>TOTALAVERAGESPEED</th>
    </tr>

<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        echo "<tr>";

        foreach ($row as $kolom => $nilai) {
            echo "<td>".$nilai."</td>";
        }

        echo "</tr>";
    }

} else {
    echo "<tr><td colspan='60' style='text-align:center;'>Tidak ada data untuk hari ini</td></tr>";
}
?>

</table>

</body>
</html>

<?php
$conn->close();
?>
