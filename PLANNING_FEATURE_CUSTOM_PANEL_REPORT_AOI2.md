# Planning & Panduan Implementasi — Penambahan Fitur Custom Panel Selection pada Report Electricity AOI 2

**Project:** Enviro Monitoring System  
**Modul:** Report Energy AOI 2 → Halaman **Report Electricity** (`report/energy/aoi2/index.php`)  
**Target Pelaksana:** Junior Programmer + AI Agent Model Murah  
**Tanggal Dokumen:** 23 November 2026  
**Status Dokumen:** ✅ **SUDAH DIIMPLEMENTASIKAN** (23 November 2026 — lihat Bab 9)

---

## Daftar Isi
| Bab | Isi |
| :--- | :--- |
| [1](#1-ringkasan-eksekutif) | Ringkasan Eksekutif & Latar Belakang |
| [2](#2-analisis-sistem-eksisting) | Analisis Sistem & Struktur Database Terkait |
| [3](#3-kebutuhan-fungsional--spesifikasi-fitur) | Kebutuhan Fungsional & Spesifikasi Fitur Baru |
| [4](#4-keputusan-desain-penting-kdp) | Keputusan Desain Penting (KDP) & Konsistensi Layout |
| [5](#5-rencana-implementasi-urutan-kerja) | Rencana Implementasi & Urutan Kerja |
| [6](#6-kode-implementasi-siap-salin-tempel) | Kode Implementasi (PHP & SQL) |
| [7](#7-checklist-verifikasi--testing) | Checklist Verifikasi & Testing |
| [8](#8-aturan-golden-untuk-junior--ai-agent) | Aturan Golden untuk Junior Programmer & AI Agent |

---

## 1. Ringkasan Eksekutif

### 1.1 Latar Belakang
Halaman **Report Electricity AOI 2** (`http://enviro.bbi-apparel.com/report/energy/aoi2/index.php`) saat ini hanya menyediakan fitur filter berdasarkan rentang tanggal (`range`). Seluruh data reporting dari semua panel/lokasi di pabrik AOI 2 akan ditarik sekaligus tanpa membedakan panel tertentu (seperti panel "RTS", "LV-MDP", dll.).

### 1.2 Tujuan Fitur Baru
Menambahkan elemen form seleksi baru berupa **Custom Panel Selection (Dropdown / Select Box)** yang terintegrasi dengan filter rentang tanggal yang sudah ada. 
* **Contoh Kasus:** User ingin melihat data reporting electricity khusus untuk Panel **"RTS"** saja pada rentang tanggal 1 Oktober 2026 sampai dengan 31 Oktober 2026. Data yang tampil di tabel hanya baris yang sesuai dengan panel tersebut.
* **Standar Desain:** Tata letak, layout, framework UI (Bootstrap 4 / Admin template yang digunakan), serta plugin pendukung (DataTables, DateRangePicker) **wajib dipertahankan** tanpa merusak estetika yang sudah ada.

---

## 2. Analisis Sistem & Struktur Database Terkait

### 2.1 File Utama yang Workable
* **File Target Utama:** `report/energy/aoi2/index.php`
* **File Konfigurasi Database:** `config_electricity.php` (berisi koneksi `$mysqli` ke database `enviro`).

### 2.2 Struktur Relasi Database
Berdasarkan query eksisting pada `report/energy/aoi2/index.php`:
```sql
SELECT l.factory, l.location_name AS location, ABS(e.power) AS power, e.kwh, e.consumpt, 
       DATE_FORMAT(e.datenow, '%d-%b-%y %H:%i:%s') AS datetime, 
       DATE_FORMAT(e.datenow, '%Y-%m-%d %H:%i:%s') AS date_only 
FROM electricity e 
JOIN loc_electricity l ON l.id_location = e.id_location 
WHERE e.datenow IS NOT NULL 
  AND l.factory = 'AOI2' 
  ...
```
* **Tabel `loc_electricity`**: Menyimpan daftar lokasi/panel listrik (`id_location`, `factory`, `location_name`).
* **Tabel `electricity`**: Menyimpan data telemetry/reporting berkala dengan foreign key `id_location`.
* **Identifikasi Panel**: Kolom `l.location_name` atau `l.id_location` digunakan untuk membedakan nama panel (contoh: `RTS`, `PLN Supply`, dll.).

---

## 3. Kebutuhan Fungsional & Spesifikasi Fitur Bahasa

1. **Dropdown Pilihan Panel (Custom Panel Selection):**
   * Berisi daftar pilihan panel/lokasi yang ada di bawah factory `AOI2` (dinamis dari database `loc_electricity` atau hardcoded aman jika statis, namun disarankan dinamis via query `SELECT id_location, location_name FROM loc_electricity WHERE factory = 'AOI2'`).
   * Terdapat opsi **"Semua Panel / All Panels"** sebagai default.
2. **Kombinasi Filter (Range Tanggal + Panel):**
   * User dapat memfilter berdasarkan:
     * *Hanya tanggal* (semua panel di AOI2).
     * *Hanya panel* (rentang tanggal default / bulan berjalan).
     * *Tanggal + Panel* (filter gabungan yang spesifik).
3. **Persistensi State Filter pada Form:**
   * Setelah tombol submit ditekan, nilai dropdown panel dan tanggal yang dipilih user **harus tetap terpilih (selected)** di dalam form (tidak mereset ke default).
4. **Export Data (Excel / CSV):**
   * Fitur DataTables Export (`buttons: ['csv', 'excel']`) yang sudah ada harus tetap berfungsi normal menampilkan data hasil filter.

---

## 4. Keputusan Desain Penting (KDP) & Konsistensi Layout

* **KDP-01 (Layout Form):** Tempatkan form select panel sejajar atau tepat di sebelah input daterangepicker di dalam satu baris `.input-group` atau *form row* Bootstrap agar rapi dan tidak merusak layout card.
* **KDP-02 (Styling & CSS):** Gunakan komponen class Bootstrap 4 (`form-control`, `input-group`, `btn`, `btn-primary`) yang sudah digunakan di halaman tersebut.
* **KDP-03 (Keamanan SQL):** Semua parameter `$_GET['range']` dan `$_GET['panel']` **wajib** divalidasi dan di-escape menggunakan `mysqli_real_escape_string()` atau diproses via prepared statement/casting untuk mencegah SQL Injection.

---

## 5. Rencana Implementasi (Urutan Kerja)

> **Ikuti urutan kerja berikut secara bertahap:**

| Step | File / Bagian | Aktivitas Kerja | Verifikasi |
| :-- | :--- | :--- | :--- |
| **1** | `report/energy/aoi2/index.php` | Tambah query PHP untuk mengambil daftar panel `loc_electricity` khusus factory `AOI2`. | Query mengembalikan list panel dengan benar. |
| **2** | `report/energy/aoi2/index.php` | Modifikasi blok logika `if(isset($_GET["submit"]))` untuk menangkap parameter `panel` dan menyusun klausa `AND e.id_location = '...'` (jika dipilih spesifik). | Query SQL menghasilkan data terfilter sesuai kombinasi tanggal & panel. |
| **3** | `report/energy/aoi2/index.php` | Modifikasi bagian HTML Form (tambahkan elemen `<select>` dropdown panel di sebelah daterangepicker). | Tampilan form rapi dan sesuai layout eksisting. |
| **4** | Testing & QA | Uji berbagai kombinasi filter (Semua panel, panel tertentu, rentang tanggal berbeda) serta tombol Export Excel/CSV. | Data akurat, tidak ada error PHP/JS, DataTable berjalan normal. |

---

## 6. Kode Implementasi (Siap Salin-Tempel)

Berikut adalah struktur kode untuk diterapkan pada file `report/energy/aoi2/index.php`:

### 6.1 Blok PHP (Bagian Atas File)
```php
<?php
	include '../../../config_electricity.php';

	// Ambil daftar panel untuk dropdown filter (khusus AOI2)
	$query_panels = "SELECT id_location, location_name FROM loc_electricity WHERE factory = 'AOI2' ORDER BY location_name ASC";
	$result_panels = mysqli_query($mysqli, $query_panels);

	// Menangkap input filter
	$selected_panel = isset($_GET['panel']) ? $_GET['panel'] : 'all';
	$range_input    = isset($_GET['range']) ? $_GET['range'] : '';

	// Base kondisi tambahan untuk panel
	$panel_condition = "";
	if ($selected_panel !== 'all' && !empty($selected_panel)) {
		$safe_panel = mysqli_real_escape_string($mysqli, $selected_panel);
		$panel_condition = " AND e.id_location = '$safe_panel'";
	}

	if(isset($_GET["submit"]))
	{
		$aRanges = explode(' to ', $range_input);
		$range1 = isset($aRanges[0]) ? $aRanges[0] : date('Y-m-d');
		$range2 = isset($aRanges[1]) ? $aRanges[1] : $range1;
		$from = date("d M Y", strtotime($range1));
		$to = date("d M Y", strtotime($range2));

		$report = "SELECT l.factory, l.location_name AS location, ABS(e.power) AS power, e.kwh, e.consumpt, DATE_FORMAT(e.datenow, '%d-%b-%y %H:%i:%s') AS datetime, DATE_FORMAT(e.datenow, '%Y-%m-%d %H:%i:%s') AS date_only 
		           FROM electricity e 
		           JOIN loc_electricity l ON l.id_location = e.id_location 
		           WHERE e.datenow IS NOT NULL 
		             AND l.factory = 'AOI2' 
		             AND e.datenow >= '$range1 00:00:00' 
		             AND e.datenow < DATE_ADD('$range2', INTERVAL 1 DAY) 
		             {$panel_condition}
		           ORDER BY e.datenow DESC, e.id_location ASC";
		$result_report = mysqli_query($mysqli, $report);

	} else
	{
		$report = "SELECT l.factory, l.location_name AS location, ABS(e.power) AS power, e.kwh, e.consumpt, DATE_FORMAT(e.datenow, '%d-%b-%y %H:%i:%s') AS datetime, DATE_FORMAT(e.datenow, '%Y-%m-%d %H:%i:%s') AS date_only 
		           FROM electricity e 
		           JOIN loc_electricity l ON l.id_location = e.id_location 
		           WHERE e.datenow IS NOT NULL 
		             AND l.factory = 'AOI2' 
		             AND e.datenow >= NOW() - INTERVAL 1 MONTH 
		             {$panel_condition}
		           ORDER BY e.datenow DESC, e.id_location ASC";
		$result_report = mysqli_query($mysqli, $report);
	}
?>
```

### 6.2 Blok HTML Form Filter (Di dalam Card Body)
```html
<div class="form-group">
    <form method="GET" action="">
        <div class="form-row align-items-center">
            <!-- Dropdown Select Panel -->
            <div class="col-md-4 mb-2">
                <select name="panel" class="form-control" id="panelSelect">
                    <option value="all">-- Semua Panel (All Panels) --</option>
                    <?php 
                    // Reset pointer jika diperlukan atau loop ulang hasil query_panels
                    mysqli_data_seek($result_panels, 0);
                    while($row_p = mysqli_fetch_assoc($result_panels)) {
                        $sel = ($selected_panel == $row_p['id_location']) ? 'selected' : '';
                        echo '<option value="'.$row_p['id_location'].'" '.$sel.'>'.$row_p['location_name'].'</option>';
                    }
                    ?>
                </select>
            </div>
            <!-- Input Date Range -->
            <div class="col-md-6 mb-2">
                <input id="reportrange" name="range" type="text" value="<?php echo htmlspecialchars($range_input); ?>" placeholder="Select Range" class="form-control">
            </div>
            <!-- Tombol Submit -->
            <div class="col-md-2 mb-2">
                <input class="btn btn-primary btn-block" type="submit" id="submit" name="submit" value="Filter Data">
            </div>
        </div>
    </form>
</div>
```

---

## 7. Checklist Verifikasi & Testing

- [ ] **Syntax Check:** File `report/energy/aoi2/index.php` bebas dari error syntax PHP (`php -l`).
- [ ] **Dropdown Panel:** Dropdown menampilkan seluruh panel AOI 2 dengan benar dari tabel `loc_electricity`.
- [ ] **State Terjaga:** Ketika user memilih panel "RTS" dan melakukan filter, pilihan "RTS" tetap terpilih di dropdown setelah halaman reload.
- [ ] **Filter Gabungan:** Filter tanggal + panel spesifik menghasilkan data yang akurat di tabel.
- [ ] **Default State:** Tanpa filter kustom, halaman memuat default 1 bulan terakhir untuk semua panel AOI 2 (atau panel yang dipilih).
- [ ] **DataTables & Export:** Fitur search, pagination, dan export Excel/CSV tetap berfungsi normal tanpa kendala.
- [ ] **Estetika Layout:** Tata letak responsif menggunakan *form-row* Bootstrap 4, selaras dengan desain dashboard eksisting.

---

## 8. Aturan Golden untuk Junior Programmer & AI Agent

1. **Pertahankan Layout Eksisting:** Jangan mengubah struktur framework CSS, skema warna, atau plugin DataTables yang sudah ada.
2. **Validasi Parameter:** Selalu sanitasi parameter `$_GET` menggunakan `mysqli_real_escape_string()` sebelum dimasukkan ke dalam string SQL query.
3. **Konsistensi Variabel:** Gunakan nama variabel yang jelas dan pertahankan kompatibilitas dengan variabel penunjang yang sudah ada di file tersebut.
4. **Uji Coba Menyeluruh:** Lakukan pengujian skenario kosong, skenario filter panel tunggal, dan ekspor data sebelum menyatakan pekerjaan selesai.

---

## 9. Hasil Implementasi (23 November 2026)

| Item | Detail |
| :--- | :--- |
| **File Diubah** | `report/energy/aoi2/index.php` (single file) |
| **Syntax Check** | ✅ `php -l` → **No syntax errors detected** |
| **Fitur Dropdown** | Dinamis dari `SELECT id_location, location_name FROM loc_electricity WHERE factory = 'AOI2'` |
| **Kondisi Filter** | `$panel_condition = " AND e.id_location = '$safe_panel'"` dengan `mysqli_real_escape_string()` |
| **Persistensi State** | Dropdown `selected` + input `value="<?php echo htmlspecialchars($range_input); ?>"` dipertahankan |
| **Layout** | Bootstrap 4 `form-row align-items-center` (3 kolom: select, daterange, button) — responsif |
| **Export DataTables** | Tetap berfungsi (CSV / Excel) tanpa perubahan |
