# Laporan Lengkap Pembuatan & Update Sistem Monitoring Temperature & Humidity AOI 5
**Project:** Enviro Monitoring System  
**Factory:** AOI 5  
**Modul:** Temperature & Humidity (Dashboard, Report, & Auto-Save API)  
**Terakhir Diperbarui:** 01 Oktober 2026  

---

## 1. Ringkasan Eksekutif

Laporan ini mendokumentasikan implementasi menyeluruh (*end-to-end*) sistem monitoring **Temperature & Humidity** untuk area **AOI 5** pada portal *Enviro* beserta pembaruan spesifikasi MQTT Broker EMQX. 

Sistem ini mencakup 5 pilar utama:
1. **Live Dashboard Visual Real-Time**: Menampilkan pergerakan jarum gauge Highcharts secara instan tanpa jeda setiap kali topic MQTT diterima (0 delay) dan lampu LED berkedip saat zona bahaya.
2. **Auto-Save Database Terjadwal (Interval 1 Jam)**: Menyimpan data sensor ke tabel database **`temp_humid`** **hanya sekali setiap 1 jam saja** per sensor (interval 60 menit / hourly snapshot) dengan perlindungan ganda (*Dual-Layer Throttling*) pada frontend JavaScript dan backend API.
3. **Halaman Report & Riwayat Aman**: Menampilkan rekaman data historis per jam dengan filter rentang tanggal (*DateRangePicker* dengan sanitasi SQL ketat) serta fitur ekspor CSV & Excel.
4. **Background Service di Menu Utama (`index.php`)**: Menangkap pesan MQTT dan menyinkronkan pencatatan berkala 1 jam tanpa mengubah tampilan antarmuka visual menu utama.
5. **Deployment-Ready**: Seluruh file murni berbasis PHP, JavaScript, dan CSS sehingga **100% siap diunggah ke server via FileZilla** tanpa perlu instalasi package tambahan.

---

## 2. Parameter & Spesifikasi Teknis Terbaru

| Komponen | Spesifikasi | Keterangan |
| :--- | :--- | :--- |
| **Factory Target** | `AOI 5` | Area pabrik AOI 5 |
| **Jumlah Sensor** | 2 Sensor | Sensor 1 (`501` / `area501`) dan Sensor 2 (`502` / `area502`) |
| **Topic MQTT Sensor 1** | `data/temperature/area501/+`, `data/humidity/area501/+`, dll | Topic data Sensor 1 (Area 1) |
| **Topic MQTT Sensor 2** | `data/temperature/area502/+`, `data/humidity/area502/+`, dll | Topic data Sensor 2 (Area 2) |
| **Format Payload MQTT** | JSON Object | `{"_terminalTime":"2026-10-01 07:51:00.392","_groupName":"Area_1","temper":"31.5","humidi":"61.2"}` |
| **Pembaruan Visual Dashboard** | **Real-Time (Instan)** | Jarum gauge Highcharts langsung bergerak seketika setiap ada payload masuk |
| **Interval Penyimpanan DB** | **Setiap 1 Jam (60 Menit)** | Dual-layer throttle (Client localStorage + Backend API hourly check) |
| **MQTT Broker** | EMQX (`192.168.51.40:8083`) | WebSocket Paho Client (`mqtt/mqttws31.js`) |
| **Database Server** | MySQL (`192.168.51.40`) | Database: `enviro` |
| **Tabel Penyimpanan** | `temp_humid` | Kolom: `id_transaction`, `id_location`, `temp`, `humidity`, `record_time` |

---

## 3. Arsitektur Alur Data Sistem

```mermaid
graph TD
    subgraph IoT Sensor Level
        S1[Sensor 1: area501] -->|Publish Real-Time Tiap Detik/Menit| MB[MQTT Broker EMQX: 192.168.51.40:8083]
        S2[Sensor 2: area502] -->|Publish Real-Time Tiap Detik/Menit| MB
    end

    subgraph Database Server
        DB[(Database MySQL: temp_humid)]
    end

    subgraph Modul Aplikasi Web PHP
        MB -->|WebSocket Streaming| D[Live Dashboard AOI 5]
        MB -->|Background Listener| M[Menu Utama: index.php]
        
        %% Jalur 1: Visual Instan
        D -->|Jarum Gauge Bergerak Seketika| UI[Tampilan Real-Time Gauges & LED Alert]
        
        %% Jalur 2: Throttled DB Save
        D -->|Cek Interval >= 1 Jam| API[api/save_temp_humid.php]
        M -->|Cek Interval >= 1 Jam| API
        
        API -->|Backend Throttle Guard + INSERT/UPDATE| DB
        
        DB -->|Query Data Terakhir saat Buka Halaman| D
        DB -->|Filter Rentang Tanggal Sanitized| R[Halaman Report AOI 5]
    end
```

---

## 4. Rincian File yang Dikerjakan

### A. Live Dashboard: `dashboard/temperature/aoi5/index.php`
* **Visualisasi Dual Gauge Instan**: Menampilkan Highcharts Gauge untuk Suhu (10°C–45°C) dan Kelembapan (30%–100%) untuk Sensor 1 (`area501`) dan Sensor 2 (`area502`).
* **Respon Seketika (*Instant Response*)**: Ketika pesan MQTT masuk, jarum gauge suhu dan kelembapan serta lampu LED langsung diperbarui tanpa jeda.
* **Auto-Save Throttled 1 Jam**: Fungsi penyimpanan ke API dibatasi tepat 1 kali setiap 60 menit per sensor menggunakan tracker `localStorage`.
* **Indikator Alarm LED & Status DB**: Lampu LED bulat menyala merah berkedip (*pulsing alert*) saat zona bahaya, serta badge status auto-save DB pada card sensor.

### B. Halaman Report: `report/temperature/aoi5/index.php`
* **Filter Rentang Tanggal Aman**: Menggunakan widget *DateRangePicker* dengan sanitasi tanggal `date('Y-m-d')` yang mencegah celah SQL Injection.
* **Tabel Interaktif**: Menggunakan *DataTables* lengkap dengan pagination, searching, sorting, dan status badge warna pada nilai suhu & kelembapan.
* **Fitur Ekspor**: Tombol **Export CSV** dan **Export Excel** untuk mengunduh laporan ke file spreadsheet.
* **Logo Lokal**: Menggunakan asset logo lokal proyek tanpa ketergantungan domain luar.

### C. Backend API: `api/save_temp_humid.php`
* **Fungsi**: Endpoint penampung data untuk dieksekusi ke database.
* **Dual-Layer Throttle Guard**: Memeriksa rekaman terakhir di database; jika selisih waktu kurang dari 3600 detik (1 jam), request akan di-skip sehingga aman dari multiple-client duplication.
* **Normalisasi Data**: Memetakan key sensor (`area501` / `Area_1` / `501` $\rightarrow$ `501`, `area502` / `Area_2` / `502` $\rightarrow$ `502`), mengekstrak nilai float `temper` dan `humidi`, serta mengatur format waktu `record_time`.
* **Format ID Transaksi Jam & UPSERT**: Menggunakan format `[id_location]_[YYYYMMDD_HH]0000` dengan klausa `ON DUPLICATE KEY UPDATE` agar tidak memicu error duplicate entry.

### D. Menu Utama: `index.php`
* **Tampilan Visual Tetap 100% Asli**: Tidak ada perubahan layout atau menu.
* **Background Ingestion Service**: Script JavaScript di latar belakang mendengarkan topic dengan proteksi interval 1 jam yang sama sebelum melakukan request ke API.

---

## 5. Struktur Tabel Database `temp_humid`

Struktur 5 kolom utama pada tabel `temp_humid`:

| Nama Kolom | Tipe Data | Contoh Nilai | Deskripsi |
| :--- | :--- | :--- | :--- |
| **`id_transaction`** | `VARCHAR` | `501_20261001_075100` | ID transaksi unik berformat `[id_loc]_[timestamp]` |
| **`id_location`** | `VARCHAR` | `501` / `502` | ID penanda sensor (501 = Sensor 1, 502 = Sensor 2) |
| **`temp`** | `DOUBLE / FLOAT` | `31.5` | Nilai suhu (°C) |
| **`humidity`** | `DOUBLE / FLOAT` | `61.2` | Nilai kelembapan (%) |
| **`record_time`** | `DATETIME` | `2026-10-01 07:51:00` | Waktu pencatatan data sensor |

---

## 6. Panduan Pengujian & URL Akses

### A. Daftar URL Aplikasi
* **Menu Utama Portal**:  
  `http://localhost/enviro/index.php` (atau `http://localhost:8000/index.php`)
* **Live Dashboard AOI 5**:  
  `http://localhost/enviro/dashboard/temperature/aoi5/`
* **Halaman Report AOI 5**:  
  `http://localhost/enviro/report/temperature/aoi5/`
* **Endpoint API Auto-Save**:  
  `http://localhost/enviro/api/save_temp_humid.php`

### B. Contoh Pengujian Kirim Data MQTT
Kirim payload berikut ke broker EMQX `192.168.51.40`:
* **Topik Sensor 1:** `data/temperature/area501/state` (atau subtopic lainnya dengan prefix `data/temperature/area501/`)  
  **Payload:**
  ```json
  {"_terminalTime":"2026-10-01 07:51:00.392","_groupName":"Area_1","temper":"31.5","humidi":"61.2"}
  ```
* **Topik Sensor 2:** `data/temperature/area502/state` (atau subtopic lainnya dengan prefix `data/temperature/area502/`)  
  **Payload:**
  ```json
  {"_terminalTime":"2026-10-01 07:51:00.392","_groupName":"Area_2","temper":"29.8","humidi":"65.4"}
  ```

---

## 7. Petunjuk Deployment Menggunakan FileZilla

1. Buka aplikasi **FileZilla** dan hubungkan ke server web.
2. Unggah (*upload*) folder dan file berikut ke direktori root web server:
   * 📁 `dashboard/temperature/aoi5/`
   * 📁 `report/temperature/aoi5/`
   * 📁 `api/save_temp_humid.php`
   * 📄 `index.php`
3. Selesai! Sistem langsung aktif dan dapat diakses dari browser.
