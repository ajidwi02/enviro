<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h3>⚡ MENU</h3>
        <button id="closeSidebar" class="close-btn" title="Tutup Menu">✖</button>
    </div>
    <ul>
        <li>
            <a href="index.php" class="<?= ($currentPage == 'index.php' || $currentPage == '') ? 'active' : '' ?>">
                <span>🏠</span> Dashboard
            </a>
        </li>
        <li>
            <a href="plts.php" class="<?= ($currentPage == 'plts.php') ? 'active' : '' ?>">
                <span>☀️</span> PLTS
            </a>
        </li>
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
    transition: transform 0.3s ease;
    overflow-y: auto;
    box-shadow: 3px 0 15px rgba(0,0,0,0.3);
    z-index: 1040;
    transform: translateX(0);
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
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: 1px;
    color: #ffffff;
}

.close-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    padding: 2px 6px;
    line-height: 1;
    transition: transform 0.2s ease, color 0.2s ease;
}
.close-btn:hover {
    transform: rotate(90deg);
    color: #00acc1;
}

/* Links */
.sidebar ul {
    list-style: none;
    margin: 0;
    padding: 10px 0;
}
.sidebar ul li a {
    display: flex;
    align-items: center;
    color: #d1d1d1;
    text-decoration: none;
    padding: 12px 20px;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.2s ease;
}
.sidebar ul li a span {
    margin-right: 12px;
    font-size: 18px;
    display: inline-block;
    width: 22px;
    text-align: center;
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
    font-weight: 600;
}

/* Hide Mode */
.sidebar.hidden {
    transform: translateX(-100%);
    box-shadow: none;
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
