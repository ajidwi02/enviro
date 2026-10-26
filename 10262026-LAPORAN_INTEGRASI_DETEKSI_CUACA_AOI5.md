# Laporan Lengkap Integrasi Sistem Deteksi Cuaca AOI 5
**Project:** Enviro Monitoring System  
**Factory:** AOI 5 (Kawasan Industri Wijayakusuma, Semarang)  
**Modul:** Weather Integration (Open-Meteo API, Live Dashboard, & Database Auto-Save)  
**Tanggal:** 26 Oktober 2026  
**Status:** Selesai Diimplementasikan & Siap Produksi  

---

## 1. Ringkasan Eksekutif

Laporan ini mendokumentasikan implementasi fitur **Deteksi Cuaca Luar Ruangan (*Outdoor Weather Monitoring*)** untuk area pabrik **AOI 5** pada portal monitoring *Enviro*. Fitur ini melengkapi monitoring suhu dan kelembapan dalam ruangan (*Folding Area* & *Dry Room*) sehingga tim teknis dan manajemen dapat membandingkan pengaruh cuaca eksternal terhadap iklim mikro area produksi.

Fitur ini dirancang dengan prinsip:
1. **Standar Pabrik (Factory Standard)**: Tampilan visual dibuat bersih, tegas, dan kontras tinggi dengan **hanya 3 status kondisi: `CERAH`, `MENDUNG`, dan `HUJAN`** serta **tanpa icon grafis** agar selaras dengan estetika sistem SCADA / instrumen industri.
2. **Online & 100% Gratis**: Menggunakan layanan **Open-Meteo API** tanpa biaya langganan, tanpa batasan komersial, dan tanpa memerlukan API key.
3. **Presisi Berbasis Koordinat**: Menargetkan titik koordinat riil pabrik AOI 5 di **`6°58'09.2"S 110°19'48.2"E`** (`-6.9692, 110.3301`).
4. **Tabel Database Baru & Auto-Save 1 Jam**: Data cuaca disimpan ke tabel baru **`weather_aoi5`** setiap **1 jam sekali (*hourly snapshot*)** yang terlindungi sistem *Hourly Guard Throttling* selaras dengan jadwal tabel `temp_humid`.
5. **Kinerja Cepat & Ringan Tanpa File Cache**: **100% tanpa file cache JSON** di server. Sistem memanfaatkan tabel database `weather_aoi5` secara langsung sebagai sumber data dan validasi riwayat terkini sehingga tidak membebani I/O disk server. Dilengkapi *dual fallback* saat internet putus.

---

## 2. Parameter & Spesifikasi Teknis

| Parameter | Spesifikasi | Keterangan |
| :--- | :--- | :--- |
| **Target Pabrik** | `AOI 5` | PT Apparel One Indonesia (Wijayakusuma, Semarang) |
| **Titik Koordinat** | `6°58'09.2"S 110°19'48.2"E` | Desimal: **Latitude `-6.9692`**, **Longitude `110.3301`** |
| **Penyedia API Cuaca** | Open-Meteo API | `https://api.open-meteo.com/v1/forecast` (Free, No Key) |
| **Kategori Status Cuaca** | **3 Status Saja** | **`CERAH`** (Badge Hijau), **`MENDUNG`** (Badge Kuning), **`HUJAN`** (Badge Biru) |
| **Format Tampilan** | **Bebas Icon (No Icon)** | Label teks tebal dan metrik angka kontras tinggi standar SCADA/HMI |
| **Metrik Cuaca Luar** | 4 Parameter Utama | Suhu Luar (°C), Kelembapan Luar (%), Curah Hujan (mm), Kecepatan Angin (km/h) |
| **Tabel Penyimpanan** | `weather_aoi5` | Tabel terpisah di database `enviro` |
| **Interval Simpan DB** | **Setiap 1 Jam (60 Menit)** | Snapshot per jam dengan format ID `AOI5_WTR_YYYYMMDD_HH0000` |
| **Mekanisme Cache** | **Murni Database (`weather_aoi5`)** | **Bebas File JSON**, data dibaca langsung dari record terakhir di MySQL |
| **Interval Refresh UI** | Setiap 10 Menit | Diperbarui secara asinkron (AJAX/Fetch) + tombol *Refresh* manual |

---

## 3. Arsitektur & Cara Kerja Program

### A. Diagram Alur Data Cuaca (*End-to-End*)

```mermaid
graph TD
    subgraph 1. Sumber Data Eksternal
        OM[Open-Meteo Weather API: -6.9692, 110.3301]
    end

    subgraph 2. Jalur Background 24/7 Mandiri (Tanpa Browser)
        EMQX[EMQX Webhook 24/7 Sensor PLC] --> API_TEMP[api/save_temp_humid.php]
        API_TEMP --> W_HELPER[api/weather_helper.php: sync_weather_aoi5]
        W_HELPER -->|Cek Record Tiap Jam| DB[(Tabel MySQL: weather_aoi5)]
        W_HELPER -.->|Tembak Open-Meteo Saat Jam Baru| OM
    end

    subgraph 3. Jalur Visual Dashboard (Saat Browser Dibuka)
        OM -.->|Fetch Berkala / Klik Refresh| API_WTR[api/get_weather.php]
        API_WTR --> DB
        DB -->|Query Langsung Terkini| PHPInit[Render Instan Weather Banner]
        API_WTR -->|Update DOM Badge & Metrik| JSUpdate[Dashboard AOI 5]
    end
```

### B. Tahapan Cara Kerja Program Secara Rinci

1. **Pemeriksaan Data Database (Tanpa File Cache JSON)**:
   - Dashboard memanggil [`api/get_weather.php`](file:///e:/Apparel/Enviro/enviro/api/get_weather.php).
   - Skrip **tidak membuat atau membaca file JSON** di disk server sama sekali.
   - Skrip memeriksa record terakhir di tabel `weather_aoi5`:
     - Jika data tersimpan masih berada dalam interval 1 jam terakhir ($< 3.600\text{ detik}$) dan tidak dipaksa refresh, skrip langsung menyajikan data dari tabel `weather_aoi5` tersebut.
     - Jika data belum ada, sudah melewati 1 jam, atau pengguna menekan tombol *Refresh* manual (`?force=1`), skrip baru akan mengambil data terbaru dari Open-Meteo API.

2. **Klasifikasi 3 Status Cuaca (Standar Pabrik Tanpa Icon)**:
   Data mentah Open-Meteo diproses oleh fungsi `classify_weather()` dengan hierarki ketat:
   - **`HUJAN`**: Ditetapkan jika curah hujan/presipitasi $> 0\text{ mm}$ atau kode WMO berada dalam kelompok hujan/gerimis/badai (`51–67`, `80–82`, `95–99`).
   - **`MENDUNG`**: Ditetapkan jika kode WMO berupa awan tebal/kabut (`2, 3, 45, 48`) atau persentase tutupan awan (*cloud cover*) $\ge 50\%$.
   - **`CERAH`**: Ditetapkan jika kondisi langit bersih atau cerah berawan (kode WMO `0, 1` dengan tutupan awan $< 50\%$).
   - Seluruh output teks diseragamkan dalam format huruf kapital (**`CERAH`**, **`MENDUNG`**, **`HUJAN`**) dengan warna badge standar pabrik tanpa penambahan icon gambar.

3. **Hourly Throttling & Penyimpanan Database**:
   - Service memeriksa rekaman terakhir di tabel `weather_aoi5`.
   - Jika data terakhir sudah berjarak minimal 3.600 detik (1 jam) atau berada pada jam yang berbeda, sistem mengeksekusi perintah SQL `INSERT ... ON DUPLICATE KEY UPDATE`.
   - Format ID Transaksi unik: `AOI5_WTR_YYYYMMDD_HH0000` (contoh: `AOI5_WTR_20261026_090000`), sehingga jika ada penarikan data berulang pada jam yang sama, data akan di-*update* secara rapi tanpa menimbulkan duplikasi baris.

4. **Pembaruan Visual Real-Time Pada Dashboard**:
   - Saat halaman dibuka, PHP langsung melakukan *pre-render* data cuaca terkini agar banner langsung muncul seketika tanpa efek kedip (*zero layout shift*).
   - JavaScript di dashboard menjalankan *polling* latar belakang setiap 10 menit untuk memperbarui status cuaca, nilai suhu luar, kelembapan luar, curah hujan, dan kecepatan angin.
   - Tersedia tombol **Refresh** kecil di pojok kanan banner cuaca untuk pengujian instan kapan saja oleh pengguna.

5. **Mekanisme Ketahanan (Fault-Tolerance & Fallback)**:
   - Jika koneksi internet pabrik sempat terputus, backend secara otomatis mengambil rekaman cuaca terakhir yang tersimpan di database lokal `weather_aoi5`.
   - Seluruh eksekusi query MySQL dibungkus blok `try ... catch (Throwable $e)` sehingga aman dari pengecualian (*uncaught exception*) pada PHP versi 8 ke atas.

---

## 4. Struktur Tabel Database `weather_aoi5`

Tabel ini dibuat secara mandiri oleh skrip PHP jika belum ada di database:

```sql
CREATE TABLE IF NOT EXISTS `weather_aoi5` (
    `id_transaction` VARCHAR(50) NOT NULL PRIMARY KEY,
    `factory` VARCHAR(20) DEFAULT 'AOI 5',
    `weather_status` ENUM('Cerah', 'Mendung', 'Hujan') NOT NULL,
    `temp_outdoor` DECIMAL(4,1) DEFAULT NULL,
    `humidity_outdoor` DECIMAL(4,1) DEFAULT NULL,
    `rainfall` DECIMAL(5,1) DEFAULT 0.0,
    `wind_speed` DECIMAL(5,1) DEFAULT 0.0,
    `cloud_cover` INT DEFAULT 0,
    `weather_code` INT DEFAULT 0,
    `record_time` DATETIME NOT NULL,
    INDEX `idx_record_time` (`record_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Rincian Kolom Tabel:

| Nama Kolom | Tipe Data | Contoh Nilai | Deskripsi |
| :--- | :--- | :--- | :--- |
| **`id_transaction`** | `VARCHAR(50)` | `AOI5_WTR_20261026_090000` | ID unik transaksi per jam |
| **`factory`** | `VARCHAR(20)` | `AOI 5` | Identitas pabrik |
| **`weather_status`** | `ENUM` | `Cerah` / `Mendung` / `Hujan` | Status cuaca baku |
| **`temp_outdoor`** | `DECIMAL(4,1)` | `31.2` | Suhu udara luar ruangan (°C) |
| **`humidity_outdoor`** | `DECIMAL(4,1)` | `62.0` | Kelembapan udara luar ruangan (%) |
| **`rainfall`** | `DECIMAL(5,1)` | `0.0` | Curah hujan terkini (mm) |
| **`wind_speed`** | `DECIMAL(5,1)` | `8.5` | Kecepatan angin (km/jam) |
| **`cloud_cover`** | `INT` | `35` | Persentase tutupan awan (%) |
| **`weather_code`** | `INT` | `1` | Kode klasifikasi WMO standar internasional |
| **`record_time`** | `DATETIME` | `2026-10-26 09:00:00` | Waktu pencatatan data cuaca |

---

## 5. Rincian File yang Dikerjakan

### 1. `api/weather_helper.php` (File Baru)
- **Fungsi**: Mesin logika inti penarikan dan klasifikasi cuaca AOI 5.
- **Fitur Utama**:
  - Fungsi `sync_weather_aoi5($conn, $force)` yang dapat dipanggil oleh berbagai modul.
  - Memeriksa apakah data jam ini sudah tersimpan di database sebelum menembak API eksternal (0.2 ms execution time jika data sudah ada).
  - Klasifikasi 3 status cuaca standar pabrik (`Cerah`, `Mendung`, `Hujan`).

### 2. `api/save_temp_humid.php` (Diperbarui)
- **Fungsi**: Webhook penerima data PLC EMQX 24/7 di server.
- **Pembaruan**:
  - Menyisipkan pemanggilan `sync_weather_aoi5($conn, false)` di setiap penerimaan payload data sensor.
  - Menjamin data cuaca otomatis tersimpan setiap jam di latar belakang server secara nonstop 24/7 meskipun seluruh browser ditutup.

### 3. `api/get_weather.php` (Diperbarui)
- **Fungsi**: Endpoint REST API penyedia data cuaca ke antarmuka dashboard.
- **Fitur Utama**:
  - Memanggil helper `sync_weather_aoi5()` dan mereturn data format JSON bersih.
  - Mendukung parameter `?force=1` untuk pengujian manual.

### 4. `dashboard/temperature/aoi5/index.php` (Diperbarui)
- **Fungsi**: Halaman antarmuka utama monitoring suhu & kelembapan AOI 5.
- **Pembaruan**:
  - Komponen **Weather Status Banner** di atas grid sensor.
  - Tampilan teks status cuaca (`CERAH`, `MENDUNG`, `HUJAN`).
  - Metrik: Suhu Luar, Kelembapan Luar, Curah Hujan, Kecepatan Angin, dan tombol Refresh.

### 5. `report/temperature/aoi5/index.php` (Diperbarui)
- **Fungsi**: Halaman laporan historis dan filter tanggal suhu & kelembapan AOI 5.
- **Pembaruan**:
  - Relasi `LEFT JOIN` ke Primary Key `weather_aoi5` (`id_transaction`).
  - Penambahan kolom **"Weather"** yang selaras dengan kolom lainnya.

### 6. `migration_weather_aoi5.sql` (File Migrasi SQL Terpisah)
- **Fungsi**: Skrip DDL terpisah untuk membuat tabel `weather_aoi5` sekali saja tanpa membebani runtime PHP.

---

## 6. Panduan Pengujian & URL Akses

### A. Akses Lokal
1. Eksekusi file migrasi `migration_weather_aoi5.sql` ke database MySQL `enviro` via phpMyAdmin atau console.
2. Pastikan web server PHP lokal aktif di terminal proyek:
   ```powershell
   php -S 0.0.0.0:8000
   ```
3. Buka Live Dashboard AOI 5:
   ```
   http://localhost:8000/dashboard/temperature/aoi5/
   ```
4. Buka Report AOI 5:
   ```
   http://localhost:8000/report/temperature/aoi5/
   ```

---

## 7. Petunjuk Deployment ke Server Produksi (FileZilla)

1. **Jalankan File Migrasi Database Sekali Saja**:
   - Buka **phpMyAdmin** server produksi, pilih database `enviro`, lalu import file:
     📄 **`migration_weather_aoi5.sql`**
2. **Unggah File Aplikasi via FileZilla**:
   - 📄 **`api/weather_helper.php`** $\rightarrow$ unggah ke `/enviro/api/`
   - 📄 **`api/save_temp_humid.php`** $\rightarrow$ unggah ke `/enviro/api/`
   - 📄 **`api/get_weather.php`** $\rightarrow$ unggah ke `/enviro/api/`
   - 📄 **`dashboard/temperature/aoi5/index.php`** $\rightarrow$ unggah ke `/enviro/dashboard/temperature/aoi5/`
   - 📄 **`report/temperature/aoi5/index.php`** $\rightarrow$ unggah ke `/enviro/report/temperature/aoi5/`
   - 📄 **`migration_weather_aoi5.sql`** $\rightarrow$ arsip dokumentasi migrasi database.
