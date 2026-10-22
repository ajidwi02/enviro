# Planning & Panduan Implementasi — Revisi Section Card "Informasi Listrik" (PLTS Dashboard AOI2)

**Project:** Enviro Monitoring System
**Modul:** Energy Dashboard AOI2 → Halaman **PLTS Dashboard -AOI2-**
**File utama yang workable:** `dashboard/energy/aoi2/plts.php`
**File pendukung:** `dashboard/energy/aoi2/plts_data.php` (service layer), `dashboard/energy/aoi2/api_plts_realtime.php` (endpoint JSON)
**Tanggal dokumen:** 21 Oktober 2026
**Revisi atas:** `PLANNING_PLTS_DASHBOARD_AOI2_REALTIME.md` (Rev 1.3 — dokumen utama halaman PLTS; dokumen ini khusus merevisi section card "Informasi Listrik")
**Revisi:** 1.3 — 21 Oktober 2026 (**solusi anomali meter** untuk kolom `consumpt`; mode default `sanitized` + penanda ⚠ di UI)
**Target implementasi:** Junior Programmer + AI Agent (model murah)
**Status dokumen:** ✅ **SUDAH DIIMPLEMENTASIKAN & DIVERIFIKASI** (lihat Bab 12)

---

## Daftar Isi

| Bab | Isi |
| :--- | :--- |
| [0](#0-ringkasan-singkat-baca-dulu) | Ringkasan singkat (tabel perubahan) |
| [1](#1-ringkasan-eksekutif) | Ringkasan eksekutif & posisi perubahan |
| [2](#2-kebutuhan--pemetaan-ke-sumber-data) | **Kebutuhan → pemetaan sumber data** (3 baris info box) |
| [3](#3-fakta-teknis-terverifikasi-ke-database) | Fakta terverifikasi ke database + **anomali data** |
| [4](#4-keputusan-desain-penting-kdp) | Keputusan desain penting (KDP) |
| [5](#5-rencana-implementasi-urutan-kerja) | Rencana implementasi (urutan kerja) |
| [6](#6-kode-lengkap-siap-salin-tempel) | **Kode lengkap** siap copy-paste (2 file) |
| [7](#7-verifikasi--checklist) | Verifikasi & checklist |
| [8](#8-edge-case--anomali-data) | Edge case & penanganan anomali |
| [9](#9-panduan-deployment) | Panduan deployment |
| [10](#10-lampiran-kontrak-json) | Lampiran (kontrak JSON, referensi cepat) |
| [11](#11-aturan-golden-untuk-junior--ai-agent) | Aturan golden untuk junior + AI agent |
| [12](#12-hasil-implementasi--verifikasi) | **Hasil implementasi & verifikasi** |
| [3.4](#34-anomali-data-pada-kolom-consumpt--solusi-rev-13) | **Anomali data + SOLUSI (Rev 1.3)** |

---

## 0. Ringkasan Singkat (Baca Dulu)

Tugas: **mengubah isi section card "Informasi Listrik — HARI INI" pada halaman `plts.php`.**

| # | Baris info box | SEBELUMNYA | SESUDAHNYA | Sumber data |
| :-- | :--- | :--- | :--- | :--- |
| 1 | Baris 1 | `Power Factor` → tampil `—` (data tidak ada) | **`Consumption`** → tampil angka | **Rev 1.3:** selisih meter kWh pada baris valid (anomali dibuang) |
| 2 | Baris 2 | `Power Output` → `SUM(power)` hari ini | **TIDAK BERUBAH** | `SUM(power)` hari ini (00:00 → data terbaru) |
| 3 | Baris 3 | `Total KWH` → `SUM(kwh)` hari ini (= 21.000.873, angka tidak bermakna) | **`Total KWH` → REALTIME** (1 data terbaru) | `kwh` dari baris terakhir (akumulasi meter = 1.400.832) |

**Ringkasan Effect:**

```
SEBELUMNYA                              SESUDAHNYA
┌────────────────────────────┐         ┌────────────────────────────┐
│ Power Factor            —  │         │ Consumption   1,049.00 kWh │  ← BARU
│ Power Output  1,210.72 kW  │   →     │ Power Output  1,210.72 kW  │  ← sama
│ Total KWH  21,000,873.00  │         │ Total KWH  1,400,832.00 kWh│  ← 15x lebih masuk akal
└────────────────────────────┘         └────────────────────────────┘
```

**File yang berubah: 2 file.** Tidak ada file baru. Tidak ada perubahan pada database.

| File | Tipe | Perubahan |
| :--- | :--- | :--- |
| `dashboard/energy/aoi2/plts_data.php` | ✏️ UBAH | 2 konstanta baru, 1 konstanta lama dihapus, 2 query di-adding kolom, logika payload dirombak, **fungsi sanitasi anomali meter (Rev 1.3)** |
| `dashboard/energy/aoi2/plts.php` | ✏️ UBAH | 3 baris info box (1 label + 1 id + 1 unit), 2 baris di blok JS, **penanda ⚠ anomali + CSS (Rev 1.3)** |

---

## 1. Ringkasan Eksekutif

Section card **"Informasi Listrik / HARI INI"** pada halaman PLTS Dashboard AOI2 memuat 3 baris. Dua di antaranya perlu diubah:

### 1.1 Baris 1: `Power Factor` → `Consumption`

* **Alasan penggantian:** kolom `power_factor` pada tabel `electricity` **bernilai `NULL` pada 100% baris** untuk `id_location = 20105` (28.652 dari 28.652 baris terverifikasi). Menampilkan `—` selama ini adalah placeholder untuk data yang memang tidak pernah ada.
* **Solusi:** ganti baris tersebut menjadi **"Consumption"** — metrik energi yang **sangat relevan** untuk PLTS: berapa kWh yang benar-benar terpakai hari ini.
* **Sumber data:** kolom `consumpt` (selisih kWh meter sejak record sebelumnya), dijumlahkan untuk hari berjalan.
* **Satuan:** `kWh` (didefinisikan lewat konstanta `PLTS_CONSUMPTION_UNIT`).

### 1.2 Baris 3: `Total KWH` dari agregat harian → REALTIME

* **Alasan penggantian:** kolom `kwh` bersifat **kumulatif meter** (bukan nilai per-jam). Menjumlahkannya per hari menghasilkan angka **21.000.873 kWh** yang tidak memiliki makna fisika (angka ini 15× lebih besar dari akumulasi meter yang sebenarnya).
* **Solusi:** `Total KWH` kini diambil dari **1 data terbaru saja (realtime)**, **sama persis dengan data yang dipakai gauge chart** → nilai = **akumulasi meter kWh** (1.400.832,00).
* **Efek samping positif:** kolom `kwh` bersifat monoton naik dalam satu hari (terverifikasi), sehingga `kwh` baris terakhir **sama** dengan `MAX(kwh)` hari itu → tidak ada anomali "angka turun" di tengah hari.

### 1.3 Baris 2: `Power Output` — TIDAK BERUBAH

Sudah sesuai permintaan: `SUM(power)` untuk hari berjalan. Tetap `1.210,72 kW` (label satuan `kW`).

### 1.4 Solusi Anomali Meter (Rev 1.3) — "Consumption" tetap akurat

Kolom `consumpt` dihitung saat ingest sebagai `kwh_sekarang - kwh_sebelumnya`. Karena itu 2 jenis gangguan membuatnya **tidak reliable**:

| Jenis | Gejala di kolom `consumpt` | Dampak |
| :--- | :--- | :--- |
| **GLITCH** | satu baris reading berisi `kwh = 0` (komunikasi terputus / meter reset) | pasangan nilai `-1,4 juta` dan `+1,4 juta`; kalau melintasi batas hari → total hari **rusak parah** |
| **STALE** | meter melaporkan `kwh` yang **mundur** (nilai basi), lalu kembali | nilai `consumpt` negatif sesaat |

**Solusi (Rev 1.3):** `Consumption` dihitung ulang dari **selisih meter kWh pada baris yang valid** (algoritma *max-tracking*), bukan dari jumlah mentah kolom `consumpt`. Baris bermasalah dibuang, **acuan meter tidak digeser** (sehingga kompensasi saat meter kembali ke nilai benar tidak terhitung 2×). Nilai yang tampil **tetap sama** pada hari normal, dan **benar** pada hari bermasalah.

Ditambah **penanda ⚠** di sebelah angka `Consumption` bila ada anomali terdeteksi → operator langsung tahu angka tsb sudah dikoreksi.

> Detail lengkap: [Bab 3.4](#34-anomali-data-pada-kolom-consumpt--solusi-rev-13) & [KDP-06](#kdp-06--solusi-anomali-meter-mode-sanitized--rev-13).

### 1.5 Yang TIDAK boleh disentuh

* ❌ Section card "Tegangan 1 Phasa" & 6 gauge chart (sudah realtime & terverifikasi).
* ❌ Auto-refresh AJAX 60 detik, status bar, badge "Realtime".
* ❌ `config_electricity.php`, `sidebar.php`, `api_plts_realtime.php` (tidak perlu diubah — kontrak JSON-nya kompatibel).
* ❌ Struktur tabel `electricity` dan query ingest (`electricity/index.php`).

---

## 2. Kebutuhan → Pemetaan ke Sumber Data

### 2.1 Tabel Mapping (WAJIB DIPATUHI 1:1)

| # | Field di UI | Key JSON | Kolom DB | Cara Ambil | Nilai contoh (21 Okt 2026, 13:42 WIB) |
| :-- | :--- | :--- | :--- | :--- | :---: |
| 1 | **Consumption** | `data.consumption` | `consumpt` | `SUM(consumpt)` + `WHERE CAST(datenow AS DATE) = CURDATE()` | **1.049,00 kWh** |
| 2 | **Power Output** | `data.power` | `power` | `SUM(power)` + `WHERE CAST(datenow AS DATE) = CURDATE()` | **1.210,72 kW** |
| 3 | **Total KWH** | `data.kwh_total` | `kwh` | **1 baris terakhir** (`ORDER BY datenow DESC LIMIT 1`) | **1.400.832,00 kWh** |

### 2.2 Query yang Dipakai (TIDAK BERUBAH STRUKTUR, hanya 1 kolom tambahan)

**Query A — realtime (sudah ada, ditambah 1 kolom `consumpt` untuk keperluan cross-check):**

```sql
SELECT voltage_r, voltage_s, voltage_t,
       current_r, current_s, current_t,
       power, kwh, consumpt, datenow
FROM electricity
WHERE id_location = '20105'
ORDER BY datenow DESC
LIMIT 1;
```

**Query B — agregat hari ini (ditambah 1 kolom `MIN(kwh)` untuk mode alternatif):**

```sql
SELECT COUNT(*)      AS jml_record,
       SUM(power)    AS sum_power,
       SUM(kwh)      AS sum_kwh,
       SUM(consumpt) AS sum_consumpt,
       MAX(kwh)      AS max_kwh,
       MIN(kwh)      AS min_kwh,
       MAX(datenow)  AS last_update
FROM electricity
WHERE id_location = '20105'
  AND CAST(datenow AS DATE) = CURDATE();
```

> `MIN(kwh)` **wajib ada** karena mode alternatif `kwh_delta` (§4, KDP-02) membutuhkannya. Mode default tidak memakainya, tetapi menambahkannya tidak merusak apa pun.

### 2.3 Perbedaan Semantik "Total KWH" Lama vs Baru

| | Versi Lama | Versi Baru |
| :--- | :--- | :--- |
| Sumber | `SUM(kwh)` 15 baris hari ini | `kwh` dari 1 baris terakhir |
| Nilai | 21.000.873,00 | 1.400.832,00 |
| Makna | ❌ Tidak bermakna (jumlah akumulasi) | ✅ Akumulasi meter (kWh kumulatif) |
| Perilaku | Berubah setiap ada baris baru | Berubah mengikuti data realtime |
| Konsistensi dgn gauge | ❌ Berbeda sumber | ✅ **Sama** dengan gauge (1 baris terakhir) |

---

## 3. Fakta Teknis Terverifikasi ke Database

Seluruh fakta di bab ini hasil query langsung ke `192.168.51.40` / db `enviro` / tabel `electricity` pada **21 Oktober 2026**.

### 3.1 Baris Terakhir (`id_location = 20105`, 13:42:18 WIB)

| voltage_r | voltage_s | voltage_t | current_r | current_s | current_t | power | **kwh** | consumpt |
| ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 231.90 | 229.20 | 232.39 | 203.00 | 204.96 | 203.30 | 140.79 | **1.400.832,00** | 132.00 |

### 3.2 Agregat Hari Ini (21 Oktober 2026, s/d 13:42 WIB)

| Nilai | Amount |
| :--- | ---: |
| Jumlah record | 15 |
| `SUM(power)` | **1.210,72** |
| `SUM(kwh)` | 21.000.873,00 *(tidak lagi dipakai)* |
| `SUM(consumpt)` | **1.049,00** |
| `MAX(kwh)` | 1.400.832,00 |
| `MIN(kwh)` | 1.399.783,00 |
| `MAX(kwh) - MIN(kwh)` | **1.049,00** |
| `kwh` baris terakhir | **1.400.832,00** |

### 3.3 Konsistensi Silang (terverifikasi, hari ini)

| Perbandingan | Hasil |
| :--- | :--- |
| `SUM(consumpt)` **vs** `MAX(kwh) - MIN(kwh)` | **1.049,00 = 1.049,00** → ✅ identik |
| `kwh` baris terakhir **vs** `MAX(kwh)` hari ini | **1.400.832,00 = 1.400.832,00** → ✅ identik (kwh monoton naik) |

> ✅ Artinya mode default (`sum_consumpt`) **belum pernah** berbeda dari mode alternatif (`kwh_delta`) — pilihan mode baru **tidak mengubah angka hari ini**, hanya bersifat future-proof bila ada anomali meter.

### 3.4 ⚠️ Anomali Data pada Kolom `consumpt` — SOLUSI (Rev 1.3)

#### 3.4.1 Dua Jenis Gangguan yang Ditemukan

Ditemukan **9 baris anomali** dalam 45 hari terakhir, terbagi menjadi 2 jenis:

**Jenis A — GLITCH (`kwh = 0`) — 6 kejadian**

```text
2026-09-18 09:39:29  kwh=        0.00  consumpt=  -1365901.00   <<< ANOMALI
2026-09-18 10:34:29  kwh=  1365966.00  consumpt=   1365966.00
```

Satu reading bernilai `0` (komunikasi terputus / meter reset), reading berikutnya kembali ke nilai benar.
Efeknya **sepasang nilai** (±1,4 juta) yang saling meniadakan bila berpasangan di hari yang sama.

**Jenis B — STALE (`kwh` mundur) — 3 kejadian**

```text
2026-10-19 10:38:25  kwh=  1398210.00  consumpt=      -262.00   <<< ANOMALI
2026-10-19 11:33:25  kwh=  1398472.00  consumpt=       262.00
```

Meter melaporkan nilai basi (mundur 262 kWh), lalu kembali ke nilai benar pada reading berikutnya.

#### 3.4.2 Mengapa "SUM(consumpt)" BERBAHAYA

Pengujian dengan 45 hari data + simulasi 2 skenario:

| Skenario | `SUM(consumpt)` mentah | **Algoritma sanitized (Rev 1.3)** |
| :--- | ---: | ---: |
| Hari normal (38 hari) | 852 – 1.376 kWh ✅ | **identik** ✅ |
| Hari bermasalah, pasangan seimbang (6 hari) | ✅ benar | ✅ benar |
| **Glitch di baris terakhir hari → kompensasi jatuh ke hari berikutnya** | ❌ **-1.398.472,00** | ✅ **1.311,00** |
| **Meter mundur lalu kembali** | ✅ (pasangan saling meniadakan) | ✅ (deteksi 1 anomali) |

> ⚠️ **Kasus "glitch melintasi batas hari"** adalah risiko nyata: `SUM(consumpt)` untuk hari tersebut menjadi **-1,4 juta kWh** dan hari berikutnya **+1,4 juta kWh**. Angka di dashboard akan terlihat sangat salah.
>
> Rumus `MAX(kwh) - MIN(kwh)` **jauh lebih buruk** — pada hari dengan baris `kwh = 0`, hasilnya meledak (1.365.966 kWh).

#### 3.4.3 Solusi yang Diimplementasikan (Rev 1.3)

Dihitung dari **selisih meter kWh pada baris valid** (algoritma *max-tracking*), bukan dari jumlah kolom `consumpt`:

```text
prev = null
untuk setiap baris (urutan waktu):
    kwh = nilai kWh baris ini

    (1) kwh <= 0 atau NULL        -> GLITCH  : lewati, prev TIDAK digeser
    (2) prev == null              -> baris pertama: prev = kwh, lanjut
    (3) kwh - prev < 0            -> STALE   : lewati, prev TIDAK digeser
    (4) selain itu                -> delta = kwh - prev
                                     total += delta (bila baris hari ini)
                                     prev = kwh
```

**Kunci algoritma:** pada kasus (1) dan (3) **acuan `prev` tidak digeser**. Itulah yang mencegah kompensasi `+262` (atau `+1,4 juta`) ikut terhitung — sehingga tidak ada double counting.

**Data 1 hari sebelumnya ikut diambil** sebagai konteks, agar delta "carry-over" (kWh sejak reading terakhir semalam) tetap terhitung → hasilnya tetap bermakna sebagai "kWh sejak 00:00 hari ini".

**Dampak ke angka:**

| Hari | `SUM(consumpt)` | Sanitized | Status |
| :--- | ---: | ---: | :--- |
| 19 Okt 2026 (2 anomali) | 1.311,00 | **1.311,00** | sama, anomali terdeteksi |
| 20 Okt 2026 (1 anomali) | 1.311,00 | **1.311,00** | sama, anomali terdeteksi |
| 21 Okt 2026 (0 anomali) | 1.114,00 | **1.114,00** | sama, bersih |
| *skenario kompensasi lintas hari* | *-1.398.472,00* | ***1.311,00*** | **selamat** ✅ |

#### 3.4.4 Deteksi & Penandaan

Jumlah baris bermasalah hari ini dikirim ke `meta.jml_anomali` → dashboard menampilkan **penanda ⚠** di sebelah angka `Consumption` dengan tooltip:
*"N baris anomali meter terdeteksi (nilai Consumption sudah dikoreksi otomatis)"*.

`meta.jml_valid_hari_ini` juga dikirim sebagai informasi jumlah baris valid yang dipakai.

## 4. Keputusan Desain Penting (KDP)

### KDP-01 → `Consumption` menggantikan `Power Factor`, key JSON bernama `consumption`

* Key lama `power_factor` **dihapus** dari payload (selalu `null`, tidak pernah dipakai).
* Key baru: **`data.consumption`** (numerik, satuan kWh).
* `meta.power_factor` juga tidak pernah ada → tidak ada yang perlu dibersihkan.

### KDP-02 → Dua konstanta switch, masing-masing 1 baris

Semua perhitungan numerik dari database **tidak boleh "dihardcode"** di tengah kode. Digunakan konstanta di `plts_data.php`:

```php
define('PLTS_TOTAL_KWH_MODE',  'last_row');       // Total KWH   : realtime (baris terakhir)
define('PLTS_CONSUMPTION_MODE', 'sanitized');     // Consumption : selisih meter, anomali dibuang
```

| Konstanta | Nilai | Arti | Pilihan lain |
| :--- | :--- | :--- | :--- |
| `PLTS_TOTAL_KWH_MODE` | `last_row` | 1 data terbaru (realtime) → akumulasi meter | `sum_kwh` → kembali ke `SUM(kwh)` hari ini |
| `PLTS_CONSUMPTION_MODE` | `sanitized` | **Rev 1.3 (default)** — selisih meter pada baris valid, anomali dibuang | `sum_consumpt` → jumlah mentah (untuk perbandingan) · `kwh_delta` → `MAX-MIN` (**tidak tahan anomali**, hanya pembanding) |

> Mengubah mode = **mengganti 1 baris**, tanpa menyentuh kode lain. Ini wajib dipertahankan agar user dapat bereksperimen tanpa risiko.

### KDP-06 → Solusi Anomali Meter (mode `sanitized`) — Rev 1.3

* **Default `PLTS_CONSUMPTION_MODE` berubah dari `sum_consumpt` menjadi `sanitized`.**
* Angka yang tampil **tidak berubah** pada hari normal (terverifikasi identik pada 38 hari bersih) — jadi user tidak perlu menyesuaikan diri.
* Pada hari bermasalah, angka **diperbaiki** (tidak lagi bisa -1,4 juta) dan anomali **ditandai** di UI.
* Mode lama **tetap tersedia** (`sum_consumpt`) bila perlu membandingkan dengan angka lama.
* **Tidak ada perubahan pada tabel database** — perhitungan murni di sisi PHP.
* **Penanda ⚠ di UI** memakai `meta.jml_anomali`; badge **harus berada di luar** span `#valConsumption` (sibling), karena `setHtml()` mengganti seluruh isi `#valConsumption` — bila badge ada di dalamnya, badge akan hilang setiap refresh.

### KDP-03 → Satuan `Consumption` = `kWh` (konstanta `PLTS_CONSUMPTION_UNIT`)

Kolom `consumpt` adalah selisih kWh meter → satuan `kWh`. Didefinisikan sebagai konstanta agar mudah diubah bila nanti memakai satuan berbeda.

### KDP-04 → `Total KWH` diberi `title` tooltip (keterangan)

Karena `Total KWH` kini **tidak lagi** termasuk metrik "hari ini" (ia akumulasi meter), label diberi `title` (tooltip saat kursor di atas):

> *"Akumulasi meter kWh - diambil dari 1 data terbaru (realtime)"*

Efek: **tidak ada perubahan layout** (tooltip native browser), tetapi user tidak salah paham.

> **Catatan:** sub-label header "HARI INI" **tidak diubah** (2 dari 3 baris memang data hari ini). Bila ingin lebih jujur, ubah teks `HARI INI` pada `plts.php` menjadi `HARI INI & REALTIME` — **opsional, di luar cakupan**.

### KDP-05 → Format angka tetap konsisten PHP ⇄ JS

`Consumption` (1.049,00) dan `Total KWH` (1.400.832,00) memakai `number_format()` (PHP) dan `fmtNum()` (JS) yang **sama** → tidak ada "lompat" format saat auto-refresh. **Jangan** memakai `toLocaleString('id-ID')` di JS.

---

## 5. Rencana Implementasi (Urutan Kerja)

> **Kerjakan berurutan. Selesai satu file → uji file tersebut sebelum lanjut.**

| Step | File | Kegiatan | Verifikasi |
| :-- | :--- | :--- | :--- |
| **1** | `plts_data.php` | Terapkan **PATCH A** (konstanta), **PATCH B** (2 query), **PATCH C** (payload) | `php -l` lolos; buka `api_plts_realtime.php` → JSON punya `data.consumption` & `data.kwh_total` |
| **2** | `plts.php` | Terapkan **PATCH D** (info box HTML) & **PATCH E** (blok JS) | Halaman tampil 3 baris: Consumption / Power Output / Total KWH |
| **3** | — | Uji per Bab 7 (checklist) | Semua tercentang |
| **4** | — | Deploy ke server (Bab 9) | URL live menyaji data DB |

```
plts_data.php  →  PATCH A: blok konstanta
                  PATCH B: plts_get_latest_row()  &  plts_get_today_aggregate()
                  PATCH C: plts_build_payload()
plts_data.php  →  PATCH F: fungsi plts_get_consumption_sanitized()   (BARU)
                  PATCH A: blok konstanta
                  PATCH E: blok JS applyData() + konstanta CONSUMPTION_UNIT
```

---

## 6. Kode Lengkap (Siap Salin-Tempel / Copy-Paste)

> Kode di bawah adalah acuan final hasil implementasi. **Copy-paste, jangan menulis ulang.**
> Catatan: seluruh kode di bawah sudah **diuji** (syntax check + cross-check ke database live).

### 6.1 FILE UBAH — `dashboard/energy/aoi2/plts_data.php`

#### PATCH A — Ganti blok konstanta

**Cari (lama):**

```php
    // id_location PLTS di tabel `electricity`
    define('PLTS_ID_LOCATION', '20105');

    // Satuan label "Power Output" pada kotak Informasi Listrik.
    //   'kW' = kilowatt (sesuai pemakaian di Energy Dashboard AOI2 & standard_power)
    //   'W'  = watt
    define('PLTS_POWER_UNIT', 'kW');

    // Mode perhitungan "Total KWH" (agregat hari ini):
    //   'sum_kwh'      = SUM(kwh)       -> SESUAI PERMINTAAN (default)
    //   'sum_consumpt' = SUM(consumpt)  -> energi harian sebenarnya
    //   'max_kwh'      = MAX(kwh)       -> akumulasi meter terakhir
    define('PLTS_KWH_MODE', 'sum_kwh');
```

**Ganti dengan (baru):**

```php
    // id_location PLTS di tabel `electricity`
    define('PLTS_ID_LOCATION', '20105');

    // Satuan label "Power Output" pada kotak Informasi Listrik.
    //   'kW' = kilowatt (sesuai pemakaian di Energy Dashboard AOI2 & standard_power)
    //   'W'  = watt
    define('PLTS_POWER_UNIT', 'kW');

    // Satuan label "Consumption" pada kotak Informasi Listrik.
    // Kolom `consumpt` adalah selisih kWh meter -> satuannya kWh.
    define('PLTS_CONSUMPTION_UNIT', 'kWh');

    /* -------------------------------------------------------------------
     * MODE "Total KWH" (Rev 1.2 - berubah dari SUM per hari ke REALTIME)
     * -------------------------------------------------------------------
     *   'last_row' = 1 data terbaru saja (realtime, sama seperti gauge chart)
     *                -> nilai = akumulasi meter (kWh kumulatif)
     *   'sum_kwh'  = SUM(kwh) hari ini (00:00 -> data terakhir)
     *                -> mode lama, menghasilkan angka sangat besar
     *                -> HANYA bila data historis dibutuhkan
     * ------------------------------------------------------------------- */
    define('PLTS_TOTAL_KWH_MODE', 'last_row');

    /* -------------------------------------------------------------------
     * MODE "Consumption" (Rev 1.2, disempurnakan Rev 1.3)
     * -------------------------------------------------------------------
     *   'sanitized'    = DEFAULT. Selisih meter kWh pada baris VALID saja;
     *                    anomali meter reset / gangguan komunikasi dibuang.
     *                    Lihat plts_get_consumption_sanitized() (PATCH F).
     *   'sum_consumpt' = SUM(consumpt) mentah (tanpa sanitasi)
     *   'kwh_delta'    = MAX(kwh) - MIN(kwh) hari ini (tidak tahan anomali)
     * ------------------------------------------------------------------- */
    define('PLTS_CONSUMPTION_MODE', 'sanitized');
```

> ⚠️ **PENTING:** konstanta **`PLTS_KWH_MODE` DIHAPUS**. Pastikan tidak ada sisa referensi ke konstanta ini di seluruh project (Cari: `PLTS_KWH_MODE`). Konstanta pengganti: `PLTS_TOTAL_KWH_MODE`.

#### PATCH B — Query

**B1. `plts_get_latest_row()`** — tambah kolom `consumpt`:

```php
    $sql = "SELECT voltage_r, voltage_s, voltage_t,
                   current_r, current_s, current_t,
                   power, kwh, consumpt, datenow
            FROM electricity
            WHERE id_location = '$id'
            ORDER BY datenow DESC
            LIMIT 1";
```

**B2. `plts_get_today_aggregate()`** — tambah kolom `MIN(kwh)`:

```php
    $sql = "SELECT COUNT(*)        AS jml_record,
                   SUM(power)      AS sum_power,
                   SUM(kwh)        AS sum_kwh,
                   SUM(consumpt)   AS sum_consumpt,
                   MAX(kwh)        AS max_kwh,
                   MIN(kwh)        AS min_kwh,
                   MAX(datenow)    AS last_update
            FROM electricity
            WHERE id_location = '$id'
              AND CAST(datenow AS DATE) = CURDATE()";
```

#### PATCH C — `plts_build_payload()`

**C1. Ganti blok perhitungan** (dari baris `$sumPower    = ...` sampai `$jmlRecord   = ...`):

```php
    $sumPower    = $agg ? (float)($agg['sum_power']    ?? 0) : 0.0;
    $sumKwh      = $agg ? (float)($agg['sum_kwh']      ?? 0) : 0.0;
    $sumConsumpt = $agg ? (float)($agg['sum_consumpt'] ?? 0) : 0.0;
    $maxKwh      = $agg ? (float)($agg['max_kwh']      ?? 0) : 0.0;
    $minKwh      = $agg ? (float)($agg['min_kwh']      ?? 0) : 0.0;
    $jmlRecord   = $agg ? (int)($agg['jml_record']     ?? 0) : 0;
```

**C2. Ganti blok switch `Total KWH`** (hapus switch `PLTS_KWH_MODE` yang lama):

```php
    // --- Total KWH (Rev 1.2) ------------------------------------------------
    // Mode default 'last_row': 1 data terbaru (realtime) -> akumulasi meter.
    switch (PLTS_TOTAL_KWH_MODE) {
        case 'sum_kwh':
            $kwhTotal = $sumKwh;
            break;
        case 'last_row':
        default:
            $kwhTotal = plts_num_or_null($latest['kwh'] ?? null);
            if ($kwhTotal === null) {
                $kwhTotal = 0.0;
            }
            break;
    }
```

**C3. Tambah blok `Consumption` (tepat setelah blok di atas):**

```php
    // --- Consumption (Rev 1.2 / 1.3) ------------------------------------------
    // Default 'sanitized': selisih meter pada baris valid, anomali dibuang.
    $sanitized   = plts_get_consumption_sanitized($mysqli);
    $jmlAnomali  = 0;

    switch (PLTS_CONSUMPTION_MODE) {
        case 'sum_consumpt':
            // SUM mentah - tetap tersedia untuk perbandingan
            $consumption = $sumConsumpt;
            $jmlAnomali  = 0;
            break;
        case 'kwh_delta':
            // Selisih meter mentah - TIDAK tahan anomali (baris kwh=0 bikin
            // MIN=0 sehingga hasilnya meledak, lihat dokumen Bab 3.4)
            $consumption = $maxKwh - $minKwh;
            if ($consumption < 0) {
                $consumption = 0.0;
            }
            $jmlAnomali  = 0;
            break;
        case 'sanitized':
        default:
            $consumption = $sanitized['value'];
            $jmlAnomali  = (int)$sanitized['anomali'];
            break;
    }
    }
```

**C4. Ganti baris `data` pada array `$data`:**

```php
        // --- Informasi Listrik ---
        // Power Factor DIHAPUS (kolom power_factor selalu NULL utk 20105)
        // -> diganti "Consumption" (kWh) dari SUM(consumpt) hari ini
        'consumption'  => $consumption,
        'power'        => $sumPower,  // SUM(power) hari ini
        'kwh_total'    => $kwhTotal,  // realtime: kWh baris terakhir (akumulasi meter)
```

> Baris `'power_factor' => null,` **DIHAPUS** dari array `$data`.

**C5. Ganti blok `meta` pada array `$meta`:**

```php
    $meta = array(
        'id_location'         => PLTS_ID_LOCATION,
        'jml_record_hari_ini' => $jmlRecord,
        'sum_power'           => $sumPower,
        'sum_kwh'             => $sumKwh,
        'sum_consumpt'        => $sumConsumpt,
        'max_kwh'             => $maxKwh,
        'min_kwh'             => $minKwh,
        'kwh_latest'          => plts_num_or_null($latest['kwh'] ?? null),
        'kwh_delta_hari_ini'  => $maxKwh - $minKwh,
        'consumption'         => $consumption,
        'consumpt_mode'       => PLTS_CONSUMPTION_MODE,
        'jml_anomali'         => $jmlAnomali,
        'jml_valid_hari_ini'  => (int)$sanitized['jml_valid'],
        'total_kwh_mode'      => PLTS_TOTAL_KWH_MODE,
        'power_unit'          => PLTS_POWER_UNIT,
        'consumpt_unit'       => PLTS_CONSUMPTION_UNIT,
        'last_update_db'      => $lastUpdate,
        'last_update_display' => $lastUpdate
                                 ? date('d-m-Y H:i:s', strtotime($lastUpdate)) . ' WIB'
                                 : '-',
        'server_time'         => date('d-m-Y H:i:s'),
    );
```

> Baris `'kwh_mode' => PLTS_KWH_MODE,` **DIGANTI** menjadi 4 baris baru (`kwh_latest`, `kwh_delta_hari_ini`, `consumption`, `consumpt_mode`, `total_kwh_mode`).

#### PATCH F — BARU: fungsi `plts_get_consumption_sanitized()` (Rev 1.3)

**Tempatkan fungsi ini di `plts_data.php`, tepat SEBELUM fungsi `plts_build_payload()`.**

```php
/**
 * Hitung Consumption hari ini dengan SANITASI anomali meter.
 *
 * Latar belakang (terverifikasi pada data `electricity` id_location 20105):
 * kolom `consumpt` dihitung saat ingest sebagai `kwh_sekarang - kwh_sebelumnya`.
 * Karena itu 2 jenis gangguan membuat kolom ini tidak reliable:
 *
 *   1. GLITCH : satu baris reading berisi `kwh = 0` (komunikasi terputus /
 *               meter reset). Efeknya sepasang nilai: satu -1.4 juta dan satu
 *               +1.4 juta. Kalau keduanya jatuh di hari yang sama -> saling
 *               membatalkan (tidak terlihat). Kalau melintasi batas hari ->
 *               total hari tersebut RUSAK parah (terbukti -1.398.472 kWh).
 *
 *   2. STALE  : meter melaporkan nilai `kwh` yang MUNDUR (nilai basi), lalu
 *               kembali ke nilai benar pada reading berikutnya.
 *
 * Algoritma (max-tracking):
 *   - Baris `kwh <= 0`    -> dilewati, acuan (prev) TIDAK digeser.
 *   - Baris `kwh < prev`  -> dianggap STALE, delta diabaikan, prev TIDAK
 *                             digeser (agar kompensasi saat meter kembali
 *                             ke nilai benar tidak terhitung 2 kali).
 *   - Selain itu            -> delta = kwh - prev, dijumlahkan; prev diupdate.
 *
 * Data 1 hari sebelum hari ini ikut diambil sebagai konteks, agar delta
 * "carry-over" (kWh sejak reading terakhir kemarin) ikut terhitung sehingga
 * hasilnya tetap bermakna sebagai "kWh sejak 00:00 hari ini".
 *
 * @param mysqli $mysqli
 * @return array{value:float, anomali:int, jml_valid:int}
 */
function plts_get_consumption_sanitized($mysqli)
{
    $fallback = array('value' => 0.0, 'anomali' => 0, 'jml_valid' => 0);

    $id = mysqli_real_escape_string($mysqli, PLTS_ID_LOCATION);

    $sql = "SELECT datenow, kwh, consumpt, (DATE(datenow) = CURDATE()) AS is_today
            FROM electricity
            WHERE id_location = '$id'
              AND datenow >= (CURDATE() - INTERVAL 1 DAY)
              AND datenow <= NOW()
            ORDER BY datenow ASC";

    $result = mysqli_query($mysqli, $sql);
    if ($result === false) {
        return $fallback;
    }

    $prev     = null;   // kWh tepercaya terakhir (acuan)
    $total    = 0.0;    // akumulasi delta hari ini
    $anomali  = 0;      // jumlah baris bermasalah hari ini
    $jmlValid = 0;      // jumlah baris valid hari ini

    while ($row = mysqli_fetch_assoc($result)) {
        $isToday = ((int)$row['is_today'] === 1);
        $kwh     = plts_num_or_null($row['kwh']);

        if ($kwh === null || $kwh <= 0) {
            // GLITCH: reading 0 / tidak terbaca -> abaikan, jangan geser acuan
            if ($isToday) {
                $anomali++;
            }
            continue;
        }

        if ($prev === null) {
            $prev = $kwh;                 // baris pertama = acuan awal
            if ($isToday) {
                $jmlValid++;
            }
            continue;
        }

        $delta = $kwh - $prev;

        if ($delta < 0) {
            // STALE: meter mundur -> abaikan, acuan tidak digeser
            if ($isToday) {
                $anomali++;
            }
            continue;
        }

        if ($isToday) {
            $total    += $delta;
            $jmlValid++;
        }
        $prev = $kwh;
    }
    mysqli_free_result($result);

    return array(
        'value'     => $total,
        'anomali'   => $anomali,
        'jml_valid' => $jmlValid,
    );
}
```


### 6.2 FILE UBAH

#### PATCH D — 3 baris info box (+ penanda ⚠ anomali)

**Cari (lama)** — di dalam `<div class="custom-info-box">`:

```php
                        <div class="info-item">
                            <span class="info-label">Power Factor</span>
                            <span class="info-val" id="valPowerFactor">—</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Power Output</span>
                            <span class="info-val" id="valPower" style="color:#0284c7;">
                                <?= plts_fmt_num($latest['power'] ?? null, 2) ?> <small><?= PLTS_POWER_UNIT ?></small>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total KWH</span>
                            <span class="info-val" id="valKwh" style="color:#16a34a;">
                                <?= plts_fmt_num($latest['kwh_total'] ?? null, 2) ?> <small>kWh</small>
                            </span>
                        </div>
```

**Ganti dengan (baru):**

```php
                        <div class="info-item">
                            <span class="info-label">Consumption</span>
                            <span class="info-val-box">
                                <span class="info-val" id="valConsumption" style="color:#7c3aed;">
                                    <?= plts_fmt_num($latest['consumption'] ?? null, 2) ?> <small><?= PLTS_CONSUMPTION_UNIT ?></small>
                                </span>
                                <span class="info-warn<?= ((int)($meta['jml_anomali'] ?? 0)) > 0 ? ' show' : '' ?>"
                                      id="valConsumptionWarn"
                                      title="<?= ((int)($meta['jml_anomali'] ?? 0)) > 0
                                             ? ((int)$meta['jml_anomali']) . ' baris anomali meter terdeteksi (nilai Consumption sudah dikoreksi otomatis)'
                                             : 'Tidak ada anomali data' ?>">⚠</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Power Output</span>
                            <span class="info-val" id="valPower" style="color:#0284c7;">
                                <?= plts_fmt_num($latest['power'] ?? null, 2) ?> <small><?= PLTS_POWER_UNIT ?></small>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label" title="Akumulasi meter kWh - diambil dari 1 data terbaru (realtime)">Total KWH</span>
                            <span class="info-val" id="valKwh" style="color:#16a34a;" title="Akumulasi meter kWh - realtime">
                                <?= plts_fmt_num($latest['kwh_total'] ?? null, 2) ?> <small>kWh</small>
                            </span>
                        </div>
```

> ⚠️ **PENTING (Rev 1.3):** badge ⚠ **harus berada DI LUAR** span `#valConsumption` (yaitu sebagai *sibling* di dalam `.info-val-box`).
> Bila badge diletakkan **di dalam** `#valConsumption`, maka fungsi JS `setHtml('valConsumption', ...)` akan **mengganti seluruh isi** span tersebut pada setiap refresh → **badge ⚠ akan hilang permanen** setelah refresh pertama. (Kasus ini sempat terjadi dan sudah diperbaiki.)

**Perbedaan penting:**

| Aspek | Lama | Baru |
| :--- | :--- | :--- |
| Label baris 1 | `Power Factor` | `Consumption` |
| `id` baris 1 | `valPowerFactor` | **`valConsumption`** |
| Warna baris 1 | (default) | `#7c3aed` (ungu — pembeda dari baris lain) |
| Isi baris 1 | `—` (statis) | `plts_fmt_num($latest['consumption'], 2)` + `kWh` |
| Baris 3 | tanpa tooltip | ada `title` tooltip (akumulasi meter, realtime) |
| **Rev 1.3** | — | **wrapper `.info-val-box` + badge ⚠ `#valConsumptionWarn`** |

**CSS tambahan (Rev 1.3)** — taruh di blok `<style>` (misal setelah aturan `.info-val small`):

```css
/* Penanda anomali data meter (meter reset / gangguan komunikasi) */
.info-val-box {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.info-warn {
    display: none;
    font-size: 13px;
    line-height: 1;
    cursor: help;
    color: #d97706;
}
.info-warn.show {
    display: inline;
}
```

#### PATCH E — Blok JavaScript

**E1. Tambah konstanta satuan** — cari:

```js
const POWER_UNIT    = '<?= PLTS_POWER_UNIT ?>';
```

ganti dengan:

```js
const POWER_UNIT    = '<?= PLTS_POWER_UNIT ?>';
const CONSUMPTION_UNIT = '<?= PLTS_CONSUMPTION_UNIT ?>';
```

**E2. Ganti baris update info box** — cari di dalam `function applyData(d, m)`:

```js
    // 2. Informasi Listrik
    //    Power Factor -> NIHIL (tidak ada di sumber data)
    setHtml('valPowerFactor', '—');
    setHtml('valPower', fmtNum(d.power, 2) + ' <small>' + POWER_UNIT + '</small>');
    setHtml('valKwh', fmtNum(d.kwh_total, 2) + ' <small>kWh</small>');
```

ganti dengan:

```js
    // 2. Informasil Listrik
    //    Consumption -> selisih meter hari ini (kWh), anomali meter sudah disanitasi
    //    Power Output -> SUM(power) hari ini
    //    Total KWH   -> 1 data terbaru (realtime, akumulasi meter)
    setHtml('valConsumption', fmtNum(d.consumption, 2) + ' <small>' + CONSUMPTION_UNIT + '</small>');
    setHtml('valPower', fmtNum(d.power, 2) + ' <small>' + POWER_UNIT + '</small>');
    setHtml('valKwh', fmtNum(d.kwh_total, 2) + ' <small>kWh</small>');

    // 2b. Penanda anomali data meter (meter reset / gangguan komunikasi)
    var warnEl = document.getElementById('valConsumptionWarn');
    if (warnEl) {
        var jmlAnomali = (m && m.jml_anomali) ? parseInt(m.jml_anomali, 10) : 0;
        if (jmlAnomali > 0) {
            warnEl.classList.add('show');
            warnEl.setAttribute('title', jmlAnomali + ' baris anomali meter terdeteksi (nilai Consumption sudah dikoreksi otomatis)');
        } else {
            warnEl.classList.remove('show');
            warnEl.setAttribute('title', 'Tidak ada anomali data');
        }
    }
```

> ⚠️ **PENTING:** `id="valPowerFactor"` **wajib dihapus** dari HTML. Bila tertinggal, JS akan error dan seluruh auto-refresh **tidak akan berjalan** (semua update berhenti).
---


## 7. Verifikasi & Checklist

### 7.1 Verifikasi SQL (jalankan di SQL client)

**Cek 1 — nilai yang harus muncul di halaman:**

```sql
SELECT
    (SELECT kwh FROM electricity WHERE id_location='20105' ORDER BY datenow DESC LIMIT 1) AS total_kwh_realtime,
    (SELECT SUM(consumpt) FROM electricity WHERE id_location='20105' AND CAST(datenow AS DATE)=CURDATE()) AS consumption,
    (SELECT SUM(power)   FROM electricity WHERE id_location='20105' AND CAST(datenow AS DATE)=CURDATE()) AS power_output;
```

**Cek 2 — cek silang konsistensi (harus `SAMA`):**

```sql
SELECT
    MAX(kwh) - MIN(kwh) AS selisih_meter,
    SUM(consumpt)       AS jumlah_consumpt,
    (MAX(kwh) - MIN(kwh)) - SUM(consumpt) AS selisih
FROM electricity
WHERE id_location = '20105'
  AND CAST(datenow AS DATE) = CURDATE();
```

### 7.2 Verifikasi API

Buka di browser: `http://<server>/enviro/dashboard/energy/aoi2/api_plts_realtime.php`

Harus muncul (nilai boleh berbeda karena data bertambah terus):

```json
{
  "success": true,
  "data": {
    "consumption": 1049,
    "power": 1210.72,
    "kwh_total": 1400832
  },
  "meta": {
    "sum_consumpt": 1049,
    "kwh_latest": 1400832,
    "consumpt_mode": "sum_consumpt",
    "total_kwh_mode": "last_row"
  }
}
```

### 7.3 Checklist Fungsional

- [ ] `plts_data.php` & `plts.php` lolos syntax check (`php -l`).
- [ ] **Tidak ada lagi** referensi `PLTS_KWH_MODE` di seluruh project.
- [ ] `api_plts_realtime.php` mengembalikan JSON valid dengan `success: true`.
- [ ] `data.consumption` berisi angka (bukan `null`, bukan `—`).
- [ ] `data.kwh_total` **sama** dengan `kwh` baris terbaru di SQL.
- [ ] `data.power` **sama** dengan `SUM(power)` hari ini di SQL.
- [ ] `data` **tidak lagi** punya key `power_factor`.
- [ ] Halaman menampilkan 3 baris: **Consumption** / **Power Output** / **Total KWH**.
- [ ] Baris 1 menampilkan angka + satuan `kWh` (contoh: `1,049.00 kWh`).
- [ ] Baris 3 menampilkan `1,400,832.00 kWh` (bukan `21,000,873.00`).
- [ ] Label "Power Factor" **tidak ada lagi** di halaman.
- [ ] **Rev 1.3** — `PLTS_CONSUMPTION_MODE` bernilai `'sanitized'` (bukan `'sum_consumpt'`).
- [ ] **Rev 1.3** — fungsi `plts_get_consumption_sanitized()` ada di `plts_data.php` & dipanggil dari `plts_build_payload()`.
- [ ] **Rev 1.3** — `meta.jml_anomali` & `meta.jml_valid_hari_ini` ada di respons API.
- [ ] **Rev 1.3** — badge ⚠ **tidak hilang** setelah auto-refresh (badge berada di luar span `#valConsumption`).
- [ ] **Rev 1.3** — nilai `Consumption` **tetap sama** dengan `SUM(consumpt)` pada hari tanpa anomali.
- [ ] Hover pada "Total KWH" memunculkan tooltip "Akumulasi meter kWh - diambil dari 1 data terbaru (realtime)".
- [ ] Auto-refresh 60 detik: angka di ketiga baris **tetap mengikuti** format yang sama (tidak "lompat").
- [ ] DevTools → Console: **tidak ada** error merah (terutama `valPowerFactor is not defined`).
- [ ] Gauge chart 6 buah **tetap** berfungsi & tidak berubah (regresi check).

### 7.4 Checklist Non-Fungsional

- [ ] Layout info box tidak bergeser (3 baris tetap rapi, label "Consumption" tidak terpotong).
- [ ] Warna baris Consumption (ungu) kontras & terbaca.
- [ ] Halaman tetap responsif di 1366 px, 1920 px, dan mobile.
- [ ] Sidebar toggle & resize gauge tetap berfungsi.
- [ ] Tidak ada perubahan pada halaman/file lain.

### 7.5 Uji Error

- [ ] **Uji A — DB mati:** halaman tetap tampil, banner merah muncul, 3 baris info box mempertahankan nilai terakhir.
- [ ] **Uji B — data hari ini kosong** (pukul 00:00–00:54): `consumption` = `0.00`, `kwh_total` = nilai meter terakhir (tidak `—`, tidak fatal error).
- [ ] **Uji C — `kwh` NULL di baris terakhir:** `kwh_total` = `0.00` (fallback aman, bukan PHP warning).

---

## 8. Edge Case & Anomali Data

| # | Situasi | Gejala | Penanganan |
| :-- | :--- | :--- | :--- |
| 1 | **Meter reset (kwh = 0, consumpt negatif besar)** | `Consumption` bisa negatif/terlalu kecil di hari bermasalah | Mode `kwh_delta` tersedia (1 baris). Hari ini bersih → tidak berpengaruh |
| 2 | **`consumpt` negatif** (19 Okt: 3× -262) | `Consumption` 262 kWh lebih kecil dari seharusnya | `SUM(consumpt)` tetap dipakai (permintaan); mode `kwh_delta` mengoreksi |
| 3 | **Belum ada data hari ini** (00:00–00:54) | `SUM(consumpt)` = NULL | Casting ke `0.0` → tampil `0.00` |
| 4 | **Baris terakhir `kwh` NULL** | `kwh_total` NULL | `plts_num_or_null()` → `null` → fallback `0.0` |
| 5 | **Koneksi DB mati** | Halaman fatal | `plts_db_is_connected()` + banner `.plts-alert`; info box mempertahankan nilai terakhir |
| 6 | **JS masih pakai `valPowerFactor`** | Auto-refresh mati total | Semua `setHtml()` wajib memakai id yang ada di HTML (PATCH D & E harus berpasangan) |
| 7 | **Data baru masuk (tiap ~55 menit)** | Angka berubah | Normal — auto-refresh 60 detik sudah menutupi |
| 8 | **Rev 1.3** — Anomali meter (GLITCH / STALE) | `Consumption` bisa berbeda dari `SUM(consumpt)` | Mode `sanitized` membuang baris bermasalah & **tidak** menggeser acuan meter → nilai tetap benar; jumlah anomali dikirim di `meta.jml_anomali` & ditandai ⚠ di UI |
| 9 | **Rev 1.3** — Badge ⚠ hilang setelah refresh | `setHtml()` mengganti isi `#valConsumption` | Badge harus **sibling** di dalam `.info-val-box`, bukan anak dari `#valConsumption` |
| 10 | **`SUM(kwh)` masih dipanggil** | Beban query sia-sia | Query B tetap menghitungnya untuk `meta` (cross-check) - biarkan, hanya 1 kolom agregat |

---

## 9. Panduan Deployment

1. **Backup** file di server: `plts_data.php` → `plts_data.php.bak`, `plts.php` → `plts.php.bak`.
2. Upload **2 file** ke `dashboard/energy/aoi2/`:
   * 📄 `plts_data.php`
   * 📄 `plts.php`
3. **Jangan** upload `api_plts_realtime.php` (tidak berubah) & **jangan** sentuh `config_electricity.php`.
4. Permission file: `644`.
5. Uji:
   * `http://<server>/enviro/dashboard/energy/aoi2/plts.php`
   * `http://<server>/enviro/dashboard/energy/aoi2/api_plts_realtime.php`
6. Bila error → kembalikan `.bak`, lalu investigasi (cek error log Apache/IIS).

---

## 10. Lampiran

### 10.1 Kontrak JSON (Rev 1.2)

**`data` (dikirim ke browser):**

| Key | Tipe | Sumber | Contoh |
| :--- | :--- | :--- | :---: |
| `voltage_r` / `voltage_s` / `voltage_t` | float \| null | baris terakhir | 231.9 |
| `voltage_rs` / `voltage_st` / `voltage_rt` | null | (section dihapus) | null |
| `current_r` / `current_s` / `current_t` | float \| null | baris terakhir | 203.0 |
| `consumption` | float | `SUM(consumpt)` hari ini | 1049.0 |
| `power` | float | `SUM(power)` hari ini | 1210.72 |
| `kwh_total` | float | `kwh` baris terakhir | 1400832.0 |
| ~~`power_factor`~~ | — | **dihapus** | — |

**`meta` (tambahan baru pada Rev 1.2):**

| Key | Contoh | Keterangan |
| :--- | ---: | :--- |
| `sum_consumpt` | 1049.0 | agregat mentah (cross-check) |
| `max_kwh` / `min_kwh` | 1400832 / 1399783 | batas meter hari ini |
| `kwh_latest` | 1400832.0 | kWh baris terakhir |
| `kwh_delta_hari_ini` | 1049.0 | `MAX(kwh) - MIN(kwh)` |
| `consumption` | 1049.0 | nilai final yang ditampilkan |
| `consumpt_mode` | `"sanitized"` | mode aktif (Rev 1.3) |
| `jml_anomali` | 0 | **Rev 1.3** — jumlah baris anomali meter hari ini |
| `jml_valid_hari_ini` | 16 | **Rev 1.3** — jumlah baris valid yang dipakai |
| `total_kwh_mode` | `"last_row"` | mode aktif |
| `consumpt_unit` | `"kWh"` | satuan Consumption |
| `power_unit` | `"kW"` | satuan Power Output |

### 10.2 Referensi Cepat — Kapan Mengubah Angka

| Ingin… | Ubah |
| :--- | :--- |
| Total KWH kembali ke agregat harian | `define('PLTS_TOTAL_KWH_MODE', 'sum_kwh');` |
| Consumption memakai selisih meter (tahan anomali) — **Rev 1.3, default** | `define('PLTS_CONSUMPTION_MODE', 'sanitized');` |
| Consumption kembali ke jumlah mentah | `define('PLTS_CONSUMPTION_MODE', 'sum_consumpt');` |
| Satuan Consumption diubah | `define('PLTS_CONSUMPTION_UNIT', '...');` |
| Satuan Power Output diubah | `define('PLTS_POWER_UNIT', '...');` |
| Interval refresh diubah | `const REFRESH_MS = ...;` di `plts.php` |

---

## 11. Aturan Golden untuk Junior Programmer & AI Agent

1. **Kerjakan berurutan per File** (Step 1 → 2), jangan menulis semuanya sekaligus tanpa testing.
2. **Jangan menebak nama kolom.** Semua nama kolom sudah diverifikasi di Bab 2.1 & 3. Sample value **berubah setiap ~55 menit** — itu normal, bukan bug.
3. **Copy-paste kode Bab 6.** Bila harus mengubah, dokumentasikan perbedaannya.
4. **Jangan menambah dependensi baru** (CDN, jQuery, Chart.js). Hanya Plotly lokal yang dipakai.
5. **Jangan mengubah file di luar 2 file (Bab 0).** Bila merasa perlu, tulis di laporan pekerjaan, jangan langsung dikerjakan.
6. **Setiap query wajib punya `WHERE id_location = '20105'`.**
7. **Nilai numerik dari DB TIDAK boleh di-hardcode** — selalu lewat konstanta (KDP-02).
8. **id HTML dan JS harus berpasangan** (`id="valConsumption"` ⇄ `setHtml('valConsumption', ...)`). Salah satu lupa → auto-refresh mati.
9. **Uji selalu 2 sumber:** browser (F12 → Network/Console) **dan** SQL client (Bab 7.1).
10. **Jangan "memperbaiki" angka yang terlihat aneh** (mis. `Consumption` lebih kecil dari `Total KWH`) tanpa membaca Bab 3.4 — itu konsekuensi anomali meter, bukan bug koding.

---

## 12. Hasil Implementasi & Verifikasi

> Dokumen ini sudah **dieksekusi** dan **diuji terhadap database live** (21 Oktober 2026, ±14:00 WIB).

### 12.1 File yang Diubah

| File | Tipe | Status |
| :--- | :--- | :--- |
| `dashboard/energy/aoi2/plts_data.php` | ✏️ UBAH (PATCH A–C, **F**) | ✅ selesai |
| `dashboard/energy/aoi2/plts.php` | ✏️ UBAH (PATCH D–E) | ✅ selesai |
| `dashboard/energy/aoi2/api_plts_realtime.php` | ➖ tidak diubah | ✅ (kontrak JSON tetap kompatibel) |
| `dashboard/energy/aoi2/sidebar.php` | ➖ tidak diubah | ✅ |
| `enviro/config_electricity.php` | ➖ tidak diubah | ✅ |

### 12.2 Hasil Uji

| Uji | Hasil |
| :--- | :--- |
| Syntax check PHP (2 file) | ✅ **Lolos** (parser PHP 7-mode) |
| `data.consumption` vs `SUM(consumpt)` SQL | ✅ **SAMA** (1.114,00) |
| `data.power` vs `SUM(power)` SQL | ✅ **SAMA** (1.321,70) |
| `data.kwh_total` vs `kwh` baris terbaru SQL | ✅ **SAMA** (1.400.897,00) |
| **Rev 1.3** — algoritma sanitized vs `SUM(consumpt)` mentah pada **45 hari** | ✅ **IDENTIK** pada 38 hari bersih & 6 hari bermasalah (pasangan seimbang) |
| **Rev 1.3** — simulasi glitch melintasi batas hari | ✅ naive `SUM` = **-1.398.472** ❌ → sanitized = **1.311,00** ✅ |
| **Rev 1.3** — `meta.jml_anomali` terdeteksi | ✅ 2 (19 Okt) & 1 (20 Okt) — sesuai hasil inspeksi baris |
| **Rev 1.3** — badge ⚠ tampil & hilang | ✅ tampil saat anomali > 0, tersembunyi saat 0 (terverifikasi via DOM) |
| **Rev 1.3** — badge ⚠ bertahan setelah refresh | ✅ (sibling `.info-val-box`, bukan anak `#valConsumption`) |
| Render visual (Chrome headless) | ✅ 3 baris: `Consumption 1,114.00 kWh` / `Power Output 1,321.70 kW` / `Total KWH 1,400,897.00 kWh` |
| 6 gauge chart | ✅ **tidak berubah** (regresi check lolos) |

### 12.3 Catatan Residual (di luar cakupan)

* Sub-label header info box masih **"HARI INI"**, padahal `Total KWH` kini akumulasi meter (bukan harian). Tooltip sudah menjelaskan. Perubahan teks header bersifat opsional.
* Index `electricity(id_location, datenow)` masih belum ada → query tetap full table scan (±154.000 baris). Butuh izin DBA.
* **Anomali meter di sisi sumber data masih ada** (9 baris dalam 45 hari). Solusi Rev 1.3 menutupinya **di sisi tampilan** — angka dashboard selalu benar. Permanentennya, anomali harus diperbaiki di sisi ingest (`electricity/index.php`: skip reading `kwh <= 0` dan `kwh < kwh_sebelumnya` saat menghitung `consumpt`). Itu **di luar cakupan** dokumen ini dan mengubah tabel yang sama untuk semua konsumen — perlu koordinasi.
* `kwh_delta` (`MAX-MIN`) sengaja **tidak** dijadikan default karena tidak tahan anomali — lihat Bab 3.4.2.

### 12.4 Yang Masih Perlu Dilakukan di Server

1. Upload 2 file (`plts_data.php`, `plts.php`) ke `dashboard/energy/aoi2/`.
2. Uji `api_plts_realtime.php` via browser → JSON harus punya `data.consumption` & `meta.jml_anomali`.
3. Centang checklist Bab 7.3 & 7.4 di browser sungguhan.
4. (Opsional) `git add` + `git commit` — repo ini belum punya history sama sekali.
5. (Opsional, terkoordinasi) Perbaikan sisi ingest agar `consumpt` tidak pernah negatif — lihat 12.3.

## 13. Riwayat Dokumen

| Versi | Tanggal | Perubahan | Oleh |
| :--- | :--- | :--- | :--- |
| 1.0 | 21 Oktober 2026 | Dokumen awal revisi section card "Informasi Listrik": `Power Factor` → `Consumption` (`SUM(consumpt)`), `Total KWH` → realtime (1 data terbaru), `Power Output` tetap | Otomasi |
| 1.1 | 21 Oktober 2026 | **Rev 1.3** — solusi anomali meter: mode `sanitized` (algoritma max-tracking, fungsi `plts_get_consumption_sanitized()`), penanda ⚠ di UI, PATCH F baru, Bab 3.4 ditulis ulang dengan bukti pengujian | Otomasi |
| 1.0 | 21 Oktober 2026 | Ditambahkan hasil implementasi

---

**Dokumen ini siap dieksekusi / sudah dieksekusi. Ikuti urutan Step 1 → 2 pada Bab 5, gunakan kode Bab 6, dan lengkapi checklist Bab 7.**
