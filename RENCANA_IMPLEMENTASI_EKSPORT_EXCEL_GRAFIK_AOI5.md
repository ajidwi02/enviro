# Rencana Implementasi: Fitur Ekspor Excel dengan Grafik Visual Multi-Granularitas (Temperature & Humidity AOI 5)

**Project:** Enviro Monitoring System  
**Unit Pabrik:** PT Apparel One Indonesia (AOI 5 - Semarang)  
**Modul:** Report Temperature & Humidity  
**Dokumen:** Rencana Implementasi Teknis (*Technical Implementation Plan - Final Approved*)  
**Tanggal Pembaruan:** 10 November 2026  
**Status:** **Disetujui Pengguna & Siap Implementasi (*Approved for Implementation*)**  

---

## 1. Ringkasan Keputusan Desain (*User Decisions*)

Berdasarkan masukan dan arahan spesifik dari pengguna (*user requirements*), implementasi ekspor Excel mengadopsi 4 pilar utama:

1. **Struktur Lembar Kerja (Pilihan: Opsi B - Multi-Sheet)**:
   * **Sheet 1 (`Dashboard & Grafik`)**: Lembar kerja eksekutif yang memuat Header resmi perusahaan, Kartu KPI Statistik Ringkas (termasuk kepatuhan batas aman), serta **4 Panel Grafik Visual Tren**.
   * **Sheet 2 (`Data Detail Historis`)**: Lembar kerja data mentah (*raw data*) yang memuat seluruh baris tabel rekaman per jam secara lengkap dengan pemformatan sel profesional dan penandaan nilai yang melampaui batas aman.
2. **Penempatan Tombol (Pilihan: Opsi 2 - Menggantikan Tombol Excel Lama)**:
   * Tombol bawaan Excel DataTables yang lama **digantikan secara langsung** oleh tombol ekspor baru berikon Excel.
   * Saat tombol diklik, sistem secara otomatis mengeksekusi *pipeline* pembuatan workbook ExcelJS multi-sheet lengkap dengan 4 grafik dan tabel data.
3. **Granularitas Grafik (Pilihan: 4 Dimensi Waktu Sekaligus)**:
   * Grafik tren disajikan dalam **4 resolusi waktu**:
     1. **Tren Per Jam (*Hourly*)**: Detail fluktuasi suhu dan kelembapan jam-demi-jam.
     2. **Tren Per Hari (*Daily*)**: Rata-rata (*Average*), Titik Tertinggi (*Max*), dan Titik Terendah (*Min*) harian.
     3. **Tren Per Minggu (*Weekly*)**: Rata-rata mingguan untuk evaluasi pola kerja dan shift operasional pabrik.
     4. **Tren Per Bulan (*Monthly*)**: Analisis makro iklim mikro pabrik per bulan.
4. **Batas Acuan / Ambang Batas Aman Standar Pabrik (Threshold Limits)**:
   * **Batas Maksimal Suhu (*Max Temperature Threshold*)**: **`30.0 °C`**  
     * Kondisi $\le 30.0^\circ\text{C}$ = **AMAN (NORMAL)**  
     * Kondisi $> 30.0^\circ\text{C}$ = **BAHAYA / MELEBIHI BATAS (*OVERHEAT ALERT*)**
   * **Batas Maksimal Kelembapan (*Max Humidity Threshold*)**: **`60.0 %`**  
     * Kondisi $\le 60.0\%$ = **AMAN (NORMAL)**  
     * Kondisi $> 60.0\%$ = **BAHAYA / MELEBIHI BATAS (*HIGH HUMIDITY ALERT*)**
   * Kedua batas aman ini disematkan sebagai **Garis Referensi Horizontal Merah (*Red Reference Threshold Line*)** di setiap grafik, dicantumkan pada kartu KPI Kepatuhan, dan diberi sorotan (*conditional formatting/highlight*) pada baris data tabel.

---

## 2. Arsitektur & Alur Kerja Ekspor (*End-to-End Workflow*)

Sistem menggunakan **Client-Side Engine (ExcelJS + HTML5 Canvas Chart)** untuk memastikan **100% bebas dependensi server (FileZilla-ready)** dan kompatibel dengan seluruh aplikasi spreadsheet:

```mermaid
flowchart TD
    subgraph 1. Interaksi Pengguna
        USER["Pengguna Klik Tombol 'Excel' di DataTables"]
        FILTER["Rentang Tanggal Terpilih (DateRangePicker)"]
    end

    subgraph 2. Agregasi Data & Evaluasi Batas Aman
        EXTRACT["Ekstraksi Data dari Tabel Report"]
        EVAL["Cek Kepatuhan Ambang Batas:<br/>Suhu <= 30°C | Humidity <= 60%"]
        AGGR_H["1. Agregasi Per Jam (Hourly Series)"]
        AGGR_D["2. Agregasi Per Hari (Daily Avg, Min, Max)"]
        AGGR_W["3. Agregasi Per Minggu (Weekly Avg)"]
        AGGR_M["4. Agregasi Per Bulan (Monthly Avg)"]
    end

    subgraph 3. Rendering Grafik Visual dengan Garis Batas Aman
        RENDER_H["Render Canvas Grafik 1: Per Jam + Garis 30°C & 60%"]
        RENDER_D["Render Canvas Grafik 2: Per Hari + Garis 30°C & 60%"]
        RENDER_W["Render Canvas Grafik 3: Per Minggu + Garis 30°C & 60%"]
        RENDER_M["Render Canvas Grafik 4: Per Bulan + Garis 30°C & 60%"]
        CONVERT["Konversi Canvas ke High-Res Base64 PNG (2x Retina)"]
    end

    subgraph 4. Komposisi Workbook ExcelJS (Multi-Sheet)
        WB["Buat Workbook Excel (.xlsx)"]
        S1["Sheet 1: 'Dashboard & Grafik'<br/>- Logo & Header Resmi<br/>- KPI Cards (Avg, Min, Max, % Kepatuhan)<br/>- 4 Gambar Grafik Beresolusi Tinggi<br/>- Tabel Agregasi Ringkas"]
        S2["Sheet 2: 'Data Detail Historis'<br/>- Tabel Lengkap Data Jam-demi-Jam<br/>- Highlight Merah jika Suhu > 30°C atau Hum > 60%<br/>- Kolom Cuaca Luar & Day Name"]
    end

    subgraph 5. Output
        DOWNLOAD["Download Otomatis:<br/>Laporan_Temp_Humid_AOI5_YYYYMMDD.xlsx"]
    end

    USER --> EXTRACT
    FILTER --> EXTRACT
    EXTRACT --> EVAL
    EVAL --> AGGR_H & AGGR_D & AGGR_W & AGGR_M
    AGGR_H --> RENDER_H
    AGGR_D --> RENDER_D
    AGGR_W --> RENDER_W
    AGGR_M --> RENDER_M
    RENDER_H & RENDER_D & RENDER_W & RENDER_M --> CONVERT
    CONVERT --> WB
    EXTRACT --> WB
    WB --> S1 & S2
    S1 & S2 --> DOWNLOAD
```

---

## 3. Desain Tata Letak Multi-Sheet (*Workbook Layout Specification*)

### 3.1. Sheet 1: `Dashboard & Grafik`
Lembar kerja ini diformat sebagai laporan eksekutif (*Executive Dashboard*) siap cetak (*print-friendly*):

```text
+----------------------------------------------------------------------------------------------------+
| [LOGO AOI]  PT APPAREL ONE INDONESIA - FACTORY 5                                                   |
|             EXECUTIVE SUMMARY & GRAFIK TREN TEMPERATURE & HUMIDITY                                 |
|             Periode Data: 01 Nov 2026 s/d 10 Nov 2026 | Dibuat: 10 Nov 2026 08:00 WIB              |
+----------------------------------------------------------------------------------------------------+
| [ KPI 1: FOLDING AREA (501) ]   [ KPI 2: DRY ROOM (502) ]       [ KPI 3: KEPATUHAN BATAS AMAN ]    |
| Avg Suhu : 28.4 °C (Min/Max)    Avg Suhu : 27.2 °C (Min/Max)    Batas Acuan : Suhu <=30°C, Hum<=60%|
| Avg Humid: 52.1 %  (Min/Max)    Avg Humid: 48.6 %  (Min/Max)    Kepatuhan   : 98.5% (Aman)         |
| Status   : OPTIMAL              Status   : OPTIMAL              Pelanggaran : 7 jam dari 480 jam   |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|  [ PANEL 1: GRAFIK TREN FLUKTUASI PER JAM (HOURLY) ]                                               |
|  - Dilengkapi Garis Merah Horizontal: Batas Suhu 30°C & Batas Humidity 60%                         |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|  [ PANEL 2: GRAFIK TREN RATA-RATA PER HARI (DAILY) ]                                               |
|  - Menampilkan rata-rata harian dengan rentang Min-Max serta Garis Batas 30°C / 60%                |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|  [ PANEL 3: GRAFIK TREN RATA-RATA PER MINGGU (WEEKLY) ]                                            |
|  - Evaluasi stabilitas mingguan terhadap ambang batas acuan 30°C & 60%                             |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|  [ PANEL 4: GRAFIK TREN RATA-RATA PER BULAN (MONTHLY) ]                                            |
|  - Analisis tren jangka panjang per bulan terhadap ambang batas acuan 30°C & 60%                   |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
| TABEL RINGKASAN AGREGASI HARIAN / MINGGUAN                                                         |
| Tanggal | Avg Suhu 501 | Min/Max | Avg Hum 501 | Avg Suhu 502 | Min/Max | Avg Hum 502 | Status Acuan|
+----------------------------------------------------------------------------------------------------+
```

### 3.2. Sheet 2: `Data Detail Historis`
Lembar kerja ini memuat seluruh data mentah dari database untuk kebutuhan audit kepatuhan (*compliance audit*):
* **Kolom 1**: No Urut
* **Kolom 2**: Factory (`AOI 5`)
* **Kolom 3**: ID Location (`501` / `502`)
* **Kolom 4**: Nama Lokasi (*Folding Area* / *Dry Room*)
* **Kolom 5**: Temperature (°C) — *dengan format 1 desimal. Jika nilai > 30°C, sel diberi warna latar merah muda dan teks merah tebal*
* **Kolom 6**: Humidity (%) — *dengan format 1 desimal. Jika nilai > 60%, sel diberi warna latar merah muda dan teks merah tebal*
* **Kolom 7**: Status Kepatuhan (*AMAN* jika Suhu $\le 30^\circ\text{C}$ & Hum $\le 60\%$, *ALERT* jika melampaui)
* **Kolom 8**: Status Cuaca Luar (*CERAH* / *MENDUNG* / *HUJAN*)
* **Kolom 9**: Tanggal & Waktu Pencatatan (`YYYY-MM-DD HH:mm:ss`)
* **Kolom 10**: Nama Hari (*Day Name*)
* **Gaya Sel**: Header berwarna biru korporat (#2B4C7E), tulisan putih tebal, garis batas (*thin borders*), dan warna baris selang-seling (*zebra striping* #F8F9FA).

---

## 4. Logika Agregasi Data 4 Granularitas Waktu & Batas Acuan

Untuk menghasilkan 4 grafik yang akurat dari dataset yang ditarik, modul JavaScript akan memproses data melalui 4 algoritma agregasi:

### A. Granularitas 1: Per Jam (*Hourly*)
* **Sumber Data**: Setiap baris data record dari sensor `501` dan `502`.
* **Sumbu X**: Label waktu berformat `DD MMM HH:00` (contoh: `01 Nov 08:00`).
* **Sumbu Y (Dual Axis)**:
  * Kiri: Suhu dalam °C (skala 10–40°C).
  * Kanan: Kelembapan dalam % (skala 30–100%).
* **Garis Referensi**:
  * Garis Merah Solid pada **Y1 = 30°C** (*Batas Aman Suhu Maks*).
  * Garis Merah Putus-putus pada **Y2 = 60%** (*Batas Aman Humidity Maks*).

### B. Granularitas 2: Per Hari (*Daily*)
* **Pengelompokan**: Kunci tanggal `YYYY-MM-DD`.
* **Perhitungan**:
  $$\text{Avg Temp} = \frac{\sum \text{Temp}}{n}, \quad \text{Min Temp} = \min(\text{Temp}), \quad \text{Max Temp} = \max(\text{Temp})$$
  $$\text{Avg Humid} = \frac{\sum \text{Humid}}{n}, \quad \text{Min Humid} = \min(\text{Humid}), \quad \text{Max Humid} = \max(\text{Humid})$$
* **Sumbu X**: Label tanggal `DD MMM YYYY` (contoh: `01 Nov 2026`).
* **Visualisasi**: Garis rata-rata harian dengan area pita batas (*min-max band*) serta garis acuan $30^\circ\text{C}$ dan $60\%$.

### C. Granularitas 3: Per Minggu (*Weekly*)
* **Pengelompokan**: Berdasarkan nomor minggu ISO-8601 atau rentang 7 hari berturut-turut (`Minggu ke-W (Tgl - Tgl)`).
* **Perhitungan**: Rata-rata suhu dan kelembapan untuk setiap minggu operasional dibandingkan dengan batas acuan $30^\circ\text{C}$ dan $60\%$.
* **Sumbu X**: Label minggu (contoh: `W44 (27 Okt - 02 Nov)`, `W45 (03 Nov - 09 Nov)`).

### D. Granularitas 4: Per Bulan (*Monthly*)
* **Pengelompokan**: Kunci bulan `YYYY-MM`.
* **Perhitungan**: Rata-rata bulanan, suhu tertinggi bulanan, dan kelembapan terendah bulanan dibandingkan dengan batas acuan $30^\circ\text{C}$ dan $60\%$.
* **Sumbu X**: Label bulan (contoh: `Okt 2026`, `Nov 2026`).

---

## 5. Spesifikasi Teknis Grafik (Chart Configuration)

Keempat grafik akan dikonfigurasi dengan standar visual SCADA / Industrial Monitoring:
1. **Pewarnaan Seri Standar Pabrik**:
   * Suhu Folding Area (`501`): **Oranye Koral** (`#E67E22`)
   * Kelembapan Folding Area (`501`): **Biru Safir** (`#2980B9`)
   * Suhu Dry Room (`502`): **Kuning Mustard** (`#F39C12`)
   * Kelembapan Dry Room (`502`): **Biru Teal** (`#16A085`)
   * **Batas Acuan Aman Suhu (30°C)**: **Garis Horizontal Merah Tegas (`#E74C3C`, width: 2px)** dengan label *"Batas Acuan Suhu (30°C)"*
   * **Batas Acuan Aman Humidity (60%)**: **Garis Horizontal Merah Putus-putus (`#C0392B`, dash: [6, 4], width: 2px)** dengan label *"Batas Acuan Humidity (60%)"*
2. **Kualitas Gambar Ekspor**:
   * Resolusi kanvas diatur ke skala **2x Retina Display** (`width: 1200px, height: 450px`).
   * Menggunakan format gambar PNG tanpa latar belakang hitam (*transparent/clean white background*).
3. **Penyematan di Excel**:
   * Gambar grafik disisipkan menggunakan metode `workbook.addImage()` dan diikat ke sel yang presisi (`tl: { col: 1, row: ... }, ext: { width: 900, height: 350 }`) sehingga grafik tidak bergeser saat kolom di-resize.

---

## 6. Rincian Modifikasi File Proyek

| File | Status | Peran & Rincian Perubahan |
| :--- | :---: | :--- |
| `js/exceljs.min.js` | **File Baru** | Pustaka pembuatan workbook `.xlsx` mandiri berbasis JavaScript di browser. |
| `report/temperature/aoi5/index.php` | **Modifikasi** | 1. Memanggil pustaka `js/exceljs.min.js`.<br/>2. Mengganti tombol bawaan `excel` DataTables dengan action handler custom.<br/>3. Mengonfigurasi batas acuan: `TEMP_LIMIT = 30` dan `HUM_LIMIT = 60`.<br/>4. Menambahkan fungsi agregasi waktu (`aggregateHourly`, `aggregateDaily`, `aggregateWeekly`, `aggregateMonthly`).<br/>5. Menambahkan modul *off-screen Canvas render* untuk 4 grafik lengkap dengan garis batas acuan 30°C dan 60%.<br/>6. Mengonfigurasi pembuatan Sheet 1 (Dashboard, KPI kepatuhan, 4 Grafik) & Sheet 2 (Data Detail dengan highlight merah jika melampaui batas).<br/>7. Menambahkan indikator visual loading (*spinner*) saat file sedang digenerate. |

---

## 7. Rencana Jadwal Eksekusi (*Execution Roadmap*)

Implementasi akan segera dimulai dengan tahapan berikut:

### Langkah 1: Persiapan Asset Pustaka
- Mengunduh dan menempatkan `js/exceljs.min.js` ke folder proyek `js/`.
- Memverifikasi ketersediaan logo perusahaan `logo_aoi.png` untuk header Excel.

### Langkah 2: Pembangunan Mesin Agregasi Data & Canvas Render
- Membuat fungsi JavaScript pengelompok data per jam, per hari, per minggu, dan per bulan.
- Mengonfigurasi 4 template grafik menggunakan Canvas 2D API / Chart engine dengan penyematan garis batas acuan:
  - **Suhu: 30 °C** (Merah tegas)
  - **Kelembapan: 60 %** (Merah putus-putus)
- Menguji konversi kanvas ke data URL base64 PNG dengan ketajaman tinggi.

### Langkah 3: Integrasi Penggantian Tombol di DataTables
- Memodifikasi inisialisasi tombol DataTables di [`report/temperature/aoi5/index.php`](file:///e:/Apparel/Enviro/enviro/report/temperature/aoi5/index.php).
- Menggantikan tombol `extend: 'excel'` lama dengan custom button `action: function(e, dt, button, config) { ... }`.
- Menambahkan *loading state* pada tombol ("Sedang Menyusun 4 Grafik & File Excel...").

### Langkah 4: Penyusunan Template ExcelJS (Multi-Sheet)
- Menyusun Sheet 1: Header logo, kartu KPI (termasuk % kepatuhan $\le 30^\circ\text{C}$ & $\le 60\%$), 4 gambar grafik, dan tabel ringkasan agregasi.
- Menyusun Sheet 2: Tabel seluruh data detail dengan penandaan baris/sel merah untuk data $> 30^\circ\text{C}$ atau $> 60\%$.
- Pengujian unduh otomatis (`saveAs` ke file `.xlsx`).

### Langkah 5: Uji Coba & Validasi Kualitas (*QA*)
- Menguji ekspor pada berbagai filter tanggal (Hari ini, 7 hari, 30 hari).
- Membuka file di Microsoft Excel, Google Sheets, dan WPS Office untuk memastikan:
  - Sheet 1 dan Sheet 2 terbentuk rapi.
  - Garis batas acuan 30°C dan 60% terlihat jelas pada seluruh grafik.
  - Nilai yang melampaui batas tersorot dengan jelas.

---

*Rencana ini telah diperbarui dengan batas acuan Temperature = 30°C dan Humidity = 60%, dan siap untuk dieksekusi.*
