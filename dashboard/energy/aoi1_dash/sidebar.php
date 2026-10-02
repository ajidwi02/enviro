<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h3>⚡ MENU</h3>
        <button id="closeSidebar" class="close-btn">✖</button>
    </div>
    <ul>
        <li><a href="index.php"><span>🏠</span> Dashboard</a></li>
        <li><a href="lvmdp.php"><span>⚡</span> LVMDP</a></li>
        <li><a href="hl1.php"><span>⚙️</span> SDP-HL.1/1F</a></li>
        <li><a href="hl2.php"><span>⚙️</span> SDP-HL.2/1F</a></li>
        <li><a href="cutting.php"><span>🪚</span> SDP-CA/1F</a></li>
        <li><a href="ppa1.php"><span>🏭</span> SDP-PPA.1/MZF</a></li>
        <li><a href="ppa2.php"><span>🏭</span> SDP-PPA.2/MZF</a></li>
        <li><a href="fg.php"><span>📦</span> SDP-FG/1F</a></li>
        <li><a href="wha.php"><span>🏢</span> SDP-WHA/1F</a></li>
        <li><a href="embro.php"><span>🧵</span> EMBROIDERY</a></li>
        <li><a href="off.php"><span>💡</span> LP-OFF/MZF</a></li>
        <li><a href="compresor1.php"><span>🔩</span> PP-COMPRSR.1/1F</a></li>
        <li><a href="compresor2.php"><span>🔩</span> PP-COMPRSR.2/1F</a></li>
        <li><a href="boiller1.php"><span>🔥</span> PP-BOILER.1.1/1F</a></li>
        <li><a href="boiller2.php"><span>🔥</span> PP-BOILER.1.2/1F</a></li>
        <li><a href="boiller3.php"><span>🔥</span> PP-BOILER.1.3/1F</a></li>
        <li><a href="boiller4.php"><span>🔥</span> PP-BOILER.1.4/1F</a></li>
        <li><a href="boiller5.php"><span>🔥</span> PP-BOILER.2.1/1F</a></li>
        <li><a href="pump.php"><span>💧</span> DP-PUMP</a></li>
        <li><a href="solar.php"><span>🏢</span> SOLAR PANEL</a></li>
        <li><a href="exhausefan.php"><span>⚙️</span> EXHAUSE-FAN</a></li>
        <li><a href="intakefan.php"><span>⚙️</span> INTAKE-FAN</a></li>
        <li><a href="report1.php"><span>📈</span> EXPORT TARIF</a></li>
        <li><a href="report2.php"><span>📊</span> EXPORT HARIAN</a></li>
    </ul>
</div>

<style>
.sidebar {
    width: 250px;
    background: linear-gradient(180deg, #1f2a40, #2c3b55);
    color: #fff;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    transition: all 0.3s ease;
    overflow-y: auto;
    box-shadow: 3px 0 15px rgba(0,0,0,0.3);
    z-index: 100;
}

/* Header */
.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 20px;
    background: #253554;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    margin: 0;
    font-size: 1.2rem;
    letter-spacing: 1px;
    color: #ffffff; /* 🔹 Tambahan agar warna tulisan putih */
}

.close-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    transition: transform 0.2s ease;
}
.close-btn:hover {
    transform: rotate(90deg);
}

/* Links */
.sidebar ul {
    list-style: none;
    margin: 0;
    padding: 0;
}
.sidebar ul li a {
    display: flex;
    align-items: center;
    color: #d1d1d1;
    text-decoration: none;
    padding: 12px 20px;
    font-size: 15px;
    transition: all 0.2s ease;
}
.sidebar ul li a span {
    margin-right: 10px;
    font-size: 18px;
}
.sidebar ul li a:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
    padding-left: 25px;
}

/* Highlight Active Menu */
.sidebar ul li a.active {
    background: #007bff;
    color: #fff;
}

/* Hide Mode */
.sidebar.hidden {
    width: 0;
    overflow: hidden;
}

/* Scrollbar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}
.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
}
</style>

<script>
document.getElementById("closeSidebar").addEventListener("click", function() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("hidden");
});
</script>
