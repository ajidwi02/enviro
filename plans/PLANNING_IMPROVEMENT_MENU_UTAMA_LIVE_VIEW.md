# PLANNING IMPROVEMENT DESIGN — Halaman Menu Utama (Live Monitor Wall)

| Field | Isi |
|---|---|
| Status | Draft siap implementasi |
| Versi | 1.0 — 25 November 2026 |
| Target utama | `index.php` (halaman Menu Utama) |
| File baru | `config/menu_utama.php`, `css/menu-utama.css`, `js/menu-utama.js` |
| Stack | PHP native (kompatibel PHP 7.4), HTML, CSS Grid/Flexbox, JavaScript vanilla |
| Layar acuan | NOC/TV 1920 × 1080, fallback desktop 1366 × 768 dan mobile |
| Dokumen terkait | `plans/planning-layout-menu-utama.md` (wireframe asli), `plans/RENCANA_PERBAIKAN_KOMPATIBILITAS_PHP_7_4.md` |

Dokumen ini adalah instruksi kerja. Junior programmer atau AI agent dapat mengerjakan seluruh isi dokumen ini tanpa mengambil keputusan desain baru. Jika ada hal yang belum pasti, lihat bagian 17 (Risiko & Pertanyaan Terbuka); jangan mengarang URL atau asumsi.

---

## 0. Cara Memakai Dokumen Ini

1. Baca bagian 1–4 untuk memahami tujuan dan arah desain.
2. Implementasikan sesuai urutan di bagian 14. Setiap langkah punya checkpoint.
3. Gunakan bagian 10 sebagai referensi kode (config, markup, CSS, JS).
4. Uji memakai bagian 15 (kriteria penerimaan) dan bagian 16 (QA matrix).
5. Jangan mengubah sidebar, topnav, dan script MQTT background di `index.php`. Perubahan hanya pada area `<main>` dan file baru.

---

## 1. Ringkasan

Halaman Menu Utama saat ini sudah memiliki kerangka: satu kartu putih besar berisi grid 3 × 3 dengan placeholder abu-abu "NaN" dan panel kanan. Kerangka itu masih berwujud wireframe: kotak abu-abu, teks uppercase, blok status tanpa makna, dan belum ada konten live.

Pekerjaan ini mengubahnya menjadi **dinding monitor live (live monitor wall)**:

- **Enam kartu dashboard** (bukan sembilan), masing-masing menampilkan **iframe live view** dari dashboard berbeda.
- Setiap kartu punya **status yang terbaca** (aktif / memuat / gagal / kosong), judul, area, waktu dimuat, dan tombol buka dashboard penuh.
- **Rail kanan** berisi judul halaman, jam besar, tanggal, dan ringkasan status seluruh monitor.
- Tampilan mengikuti identitas aplikasi yang sudah ada (Overpass, putih, abu-abu `#f8f9fa`, aksen biru `#1b68ff`) sehingga terasa sebagai bagian dari produk, bukan template tempelan.
- Di layar NOC 1920 × 1080 seluruh isi terlihat tanpa scroll halaman.

Hasil akhir: halaman terlihat modern, minimalis, dan elegan, tetapi tetap terang dan konsisten dengan halaman lain di aplikasi ini.

---

## 2. Konteks Produk, Audiens, dan Pekerjaan Utama Halaman

**Produk:** portal pemantauan lingkungan dan energi pabrik (BBI Apparel). Sumber data: sensor suhu/kelembapan (temp humid) dan listrik (energy) di beberapa area (AOI 1, AOI 2, AOI 5). Dashboard aslinya berupa halaman terpisah berbasis PHP + Highcharts/MQTT dengan tema terang yang sama (`app-light.css`, font Overpass).

**Audiens:** operator fasilitas dan supervisor yang melihat layar NOC di ruang kontrol atau desktop. Mereka tidak mengoperasikan halaman ini terus-menerus; mereka memindai status dalam hitungan detik, lalu membuka satu dashboard jika perlu detail.

**Pekerjaan utama halaman:** memperlihatkan enam dashboard sekaligus, memberi tahu mana yang tampil dan mana yang gagal, dan menyediakan jalan satu klik menuju dashboard penuh.

**Implikasi desain:**

- Informasi status harus terbaca dari jarak beberapa meter: kontras cukup, ukuran teks minimum 11 px pada layar 1920.
- Tidak boleh ada animasi yang mengalihkan perhatian. Hanya indikator memuat yang bergerak, dan itu pun bisa dimatikan.
- Tidak boleh ada scroll ganda di layar NOC. Iframe tidak boleh punya scrollbar sendiri.
- Karena dashboard sumber berbentuk landscape, bentuk kartu mengikuti: **2 kolom × 3 baris** dengan rasio mendekati 16:9, bukan 3 × 3 portrait.

---

## 3. Rencana Desain (Token)

### 3.1 Prinsip

1. **Terang, tenang, presisi.** Halaman ini adalah alat ukur, bukan poster. Permukaan putih, garis tipis, dan ruang kosong yang cukup.
2. **Identitas mengikuti aplikasi.** Overpass, biru `#1b68ff`, abu `#f8f9fa`, border `#e9ecef` diambil langsung dari `app-light.css`. Tidak ada palet baru yang bertabrakan dengan sidebar dan dashboard.
3. **Satu momen berani: jam.** Jam besar bertabular di rail kanan adalah satu-satunya elemen yang menonjol. Sisanya kecil, rapi, dan disiplin.
4. **Chrome kartu adalah instrumen, bukan dekorasi.** Header kartu menyandikan status, nama, area, dan aksi buka. Footer menyandikan waktu dimuat dan status teks. Tidak ada label hiasan.
5. **Warna bukan satu-satunya pembeda.** Setiap titik status selalu disertai teks status.
6. **Iframe mengikuti bentuk kartu, bukan sebaliknya.** Iframe diskalakan dengan lebar desain tetap dan tinggi dihitung dari rasio kartu, sehingga tidak ada ruang kosong atau konten terpotong.

### 3.2 Warna (token inti)

| Token | Nilai | Pemakaian |
|---|---|---|
| `--mu-board-bg` | `#ffffff` | Permukaan board dan kartu |
| `--mu-view-bg` | `#f8f9fa` | Latar viewport iframe (sama dengan `body` dashboard, agar letterbox tak terlihat) |
| `--mu-raised` | `#f4f7fb` | Panel rail kanan |
| `--mu-raised-2` | `#eef2f7` | Hover kontrol |
| `--mu-line` | `#e9ecef` | Border kartu dan board (token border aplikasi) |
| `--mu-line-strong` | `#dde3ea` | Garis pemisah baris ringkasan |
| `--mu-ink` | `#212b36` | Judul dan jam |
| `--mu-ink-2` | `#4b5b6b` | Teks label utama |
| `--mu-muted` | `#6c757d` | Teks sekunder (warna body aplikasi) |
| `--mu-faint` | `#98a2b3` | Titik status kosong |
| `--mu-primary` | `#1b68ff` | Fokus keyboard, progress memuat, tautan (primary aplikasi) |
| `--mu-ok` / `--mu-ok-ink` / `--mu-ok-soft` | `#3ad29f` / `#157f5f` / `#e7faf3` | Status aktif |
| `--mu-warn` / `--mu-warn-ink` / `--mu-warn-soft` | `#eea303` / `#8a5c00` / `#fdf5e3` | Status memuat |
| `--mu-danger` / `--mu-danger-ink` / `--mu-danger-soft` | `#dc3545` / `#b02a37` / `#fbeaec` | Status gagal |

Catatan: titik memakai warna vivid (`--mu-ok`, dst.), teks status memakai varian `-ink` yang lebih gelap agar kontras teks ≥ 4.5:1 di atas tint.

### 3.3 Tipografi

Satu keluarga font: **Overpass** (sudah dimuat di `index.php`). Tidak ada font baru.

Skala (root 16 px; body aplikasi 14 px):

| Peran | Ukuran / line-height | Berat | Catatan |
|---|---|---|---|
| Jam `.mu-clock` | 34 px / 1.1 | 600 | `font-variant-numeric: tabular-nums`, letter-spacing -0.015em |
| Judul halaman | 18 px / 1.33 | 600 | Sentence case |
| Judul panel `.mu-section-title` | 13 px / 1.4 | 600 | Sentence case |
| Judul kartu | 13 px / 1.2 | 600 | Sentence case |
| Area kartu | 11 px / 1.2 | 500 | Abu |
| Teks state | 13 px / 1.4 | 600 | Judul state |
| Body state | 12 px / 1.4 | 400 | Maksimal 24 karakter per baris |
| Meta footer | 11 px / 1.2 | 400 | "Dimuat 14:31" |
| Status footer | 11 px / 1.2 | 600 | "Aktif", "Gagal", dst. |
| Chip | 11 px / 1.2 | 600 | Angka ringkasan |
| Tombol | 12 px / 1 | 600 | "Muat ulang" |

Aturan:

- Gunakan **sentence case**, bukan ALL CAPS. Ini juga alasan teks "TEMP HUMID" di wireframe diganti "Temp humid".
- Angka waktu memakai tabular numerals agar tidak bergeser tiap detik.
- Tidak ada label eyebrow di atas judul, tidak ada meta string dengan pemisah titik tengah, tidak ada tanda "→" di teks tombol.

### 3.4 Bentuk, bayangan, dan spasi

| Token | Nilai | Alasan |
|---|---|---|
| Radius board | 16 px | Permukaan besar, lembut tidak kekanak-kanakan |
| Radius panel rail | 12 px | Satu tingkat di bawah board |
| Radius kartu | 10 px | Satu tingkat di bawah panel |
| Radius kontrol | 8 px | Tombol dan tombol ikon |
| Bayangan board | `0 1px 2px rgba(18,38,63,.04), 0 12px 32px -16px rgba(18,38,63,.18)` | Hampir tak terlihat; kedalaman tanpa efek "kartu SaaS" |
| Bayangan kartu | tidak ada | Kartu dibedakan oleh border 1 px, bukan shadow |
| Jarak grid `--mu-gap` | 16 px | Konsisten |
| Padding board `--mu-pad` | 16 px | Konsisten |
| Padding panel | 16 px | Konsisten |

Rasio radius berlapis (16 → 12 → 10 → 8) memberi hierarki bentuk. Hindari satu radius untuk semua elemen.

### 3.5 Konsep Layout

**Board** mengisi area `main-content` yang tersisa. Di dalamnya: grid dua kolom — **kiri 6 kartu (2 kolom × 3 baris)**, **kanan rail 320 px**.

```
main-content (background #f8f9fa, padding dari app 1.2rem)
└── .mu-board (putih, radius 16, border #e9ecef, padding 16)
    └── .mu-board__grid (grid: 1fr | 320px, gap 16)
        ├── .mu-tiles (grid 2 kolom × 3 baris, gap 16)
        │   ├── .mu-card  Temp humid AOI 1   iframe live
        │   ├── .mu-card  Energy     AOI 1   kosong (belum ada URL)
        │   ├── .mu-card  Temp humid AOI 2   iframe live
        │   ├── .mu-card  Energy     AOI 2   iframe live
        │   ├── .mu-card  Temp humid AOI 5   iframe live
        │   └── .mu-card  Energy     AOI 5   iframe live
        └── .mu-rail
            ├── .mu-panel (judul, jam besar, tanggal)
            └── .mu-panel--grow (ringkasan: chips + daftar 6 monitor)
```

Wireframe detail (layar 1920 × 1080):

```
┌────────────────────────────────────────────────────────────────────────────────┐
│ topnav aplikasi (tetap, tidak diubah)                                          │
├────────────────────────────────────────────────────────────────────────────────┤
│ ┌ .mu-board ───────────────────────────────────────────────────────────────┐  │
│ │ ┌ Temp humid · AOI 1──────────┐ ┌ Energy · AOI 1 ─────────────┐          │  │
│ │ │ ● Temp humid       [↗]      │ │ ● Energy            [↗]     │          │  │
│ │ │   AOI 1                     │ │   AOI 1                     │          │  │
│ │ │                             │ │                             │  ┌─────┐ │  │
│ │ │        iframe live          │ │      "Belum terpasang"      │  │Menu │ │  │
│ │ │                             │ │                             │  │utama│ │  │
│ │ │ Dimuat 14:31        Aktif   │ │                     Kosong  │  │14:32│ │  │
│ │ └─────────────────────────────┘ └─────────────────────────────┘  │05   │ │  │
│ │ ┌ Temp humid · AOI 2──────────┐ ┌ Energy · AOI 2 ─────────────┐  │     │ │  │
│ │ │ ● Temp humid       [↗]      │ │ ● Energy            [↗]     │  │Rabu,│ │  │
│ │ │   AOI 2                     │ │   AOI 2                     │  │25   │ │  │
│ │ │        iframe live          │ │        iframe live          │  │Nov  │ │  │
│ │ │ Dimuat 14:31        Aktif   │ │ Dimuat 14:32        Aktif   │  │2026 │ │  │
│ │ └─────────────────────────────┘ └─────────────────────────────┘  └─────┘ │  │
│ │ ┌ Temp humid · AOI 5──────────┐ ┌ Energy · AOI 5 ─────────────┐  ┌─────┐ │  │
│ │ │ ● Temp humid       [↗]      │ │ ● Energy            [↗]     │  │Ring-│ │  │
│ │ │   AOI 5                     │ │   AOI 5                     │  │kasan│ │  │
│ │ │        iframe live          │ │        iframe live          │  │moni-│ │  │
│ │ │ Dimuat 14:32        Aktif   │ │ Dimuat 14:33        Aktif   │  │tor  │ │  │
│ │ └─────────────────────────────┘ └─────────────────────────────┘  │ ... │ │  │
│ └──────────────────────────────────────────────────────────────────┴─────┘─┘  │
└────────────────────────────────────────────────────────────────────────────────┘
```

Aturan perataan:

- Semua teks di header kartu, footer, rail, dan daftar ringkasan **rata kiri**.
- Hanya teks state (memuat/gagal/kosong) yang **rata tengah** karena ia mengisi seluruh viewport.
- Angka jam rata kiri agar sejajar dengan judul dan tanggal di atasnya.
- Tidak ada konten yang dijustify.

### 3.6 Review Rencana terhadap Default Generik

| Default generik | Keputusan | Alasan |
|---|---|---|
| Grid 3 × 3 abu-abu seperti wireframe | **2 × 3 kartu landscape** | Enam dashboard adalah enam layar landscape. Grid 3 × 3 memaksa bentuk portrait dan menyisakan tiga slot tak terpakai. |
| Semua kartu radius besar + shadow `rgba(0,0,0,.1)` (SaaS-card kit) | Radius berlapis 16/12/10, shadow hanya di board | Hierarki bentuk, bukan seragam. Kartu dibaca sebagai panel instrumen. |
| Label ALL CAPS ala NOC gelap | Sentence case Overpass | Mengikuti bahasa UI aplikasi; ALL CAPS adalah salah satu tanda halaman hasil generator. |
| Tema gelap dengan aksen acid green | Terang, biru `#1b68ff` milik aplikasi | Konsistensi dengan sidebar dan dashboard; brief meminta "sesuai dengan web app". |
| Blok abu-abu placeholder | Header status + viewport + footer fungsional | Struktur mengodekan informasi (status, waktu, aksi), bukan hiasan. |
| Eyebrow/meta string/monospace | Dihapus | Tidak ada kebutuhan informasi; jadi hiasan. |
| Big number + gradient sebagai hero | Jam tabular di rail | Jam memang informasi utama ruang kontrol; satu-satunya elemen besar. |

Yang **dipertahankan** dari wireframe: board putih besar, pembagian kiri (kartu) dan kanan (informasi), serta enam dashboard pada urutan area yang sama.

---

## 4. Perubahan dari Implementasi Saat Ini

1. **Hapus** blok `<style>` inline menu utama dan blok `<script>` iframe-scaling inline yang sekarang ada di dalam `<main>`.
2. **Ganti** markup grid 9 slot "NaN" menjadi 6 kartu dari konfigurasi PHP.
3. **Hapus** class generik lama (`.noc-card`, `.tile`, `.badge`, `.tag`, `.side`) karena berisiko bertabrakan dengan Bootstrap (`.badge`) dan kebiasaan selektor global. Semua komponen baru memakai prefix `.mu-`.
4. **Tambahkan** `mu-page` pada `<body>`.
5. **Tambahkan** `<link>` CSS baru di `<head>` setelah `app-light.css` dan `<script defer>` baru sebelum `</body>`.
6. **Pertahankan** sidebar, topnav, logo, dan seluruh script MQTT background. Jangan dipindahkan atau dihapus.
7. **Ubah** `<html lang="en">` menjadi `<html lang="id">` (konten dan sidebar berbahasa Indonesia).
8. **Hapus** pemuatan `js/config.js` yang dobel (opsional, housekeeping kecil; tidak wajib untuk fitur ini).

---

## 5. Arsitektur File

```
enviro/
├── index.php                    ← ubah: body class, link CSS/JS, markup <main>
├── config/
│   └── menu_utama.php           ← baru: satu sumber kebenaran data 6 slot
├── css/
│   └── menu-utama.css           ← baru: seluruh style .mu-*
└── js/
    └── menu-utama.js            ← baru: state, iframe fit, jam, ringkasan
```

Alasan pemisahan: `index.php` sudah memuat script MQTT yang panjang; menambah style/script inline membuat file sulit dirawat dan sulit di-cache browser. File terpisah juga memudahkan AI agent berikutnya membaca konteks dengan cepat.

Pemuatan aset memakai cache-busting yang sudah dipakai proyek:

```php
<link rel="stylesheet" href="css/menu-utama.css?v=<?= filemtime(__DIR__ . '/css/menu-utama.css') ?>">
<script src="js/menu-utama.js?v=<?= filemtime(__DIR__ . '/js/menu-utama.js') ?>" defer></script>
```

`defer` aman karena script tidak butuh jQuery dan dijalankan setelah DOM siap.

---

## 6. Model Data Slot

### 6.1 Prinsip

- Satu array PHP menjadi satu-satunya sumber kebenaran untuk judul, area, URL, dan lebar desain iframe.
- Kartu dan daftar ringkasan dirender server-side dari array yang sama.
- Slot tanpa URL tetap dirender sebagai kartu "kosong" agar grid seimbang.

### 6.2 Isi Konfigurasi

`config/menu_utama.php`:

```php
<?php
// Enam slot dashboard Menu Utama.
// url = null berarti kartu tampil kosong (tidak memuat iframe).
// design_width = lebar logis iframe dalam px; lihat bagian 8.3.

return array(
    array(
        'id'           => 'aoi1-temp',
        'title'        => 'Temp humid',
        'area'         => 'AOI 1',
        'url'          => 'dashboard/temperature/aoi1/',
        'design_width' => 1600,
    ),
    array(
        'id'           => 'aoi1-energy',
        'title'        => 'Energy',
        'area'         => 'AOI 1',
        'url'          => null, // belum ada dashboard energy AOI 1
        'design_width' => 1600,
    ),
    array(
        'id'           => 'aoi2-temp',
        'title'        => 'Temp humid',
        'area'         => 'AOI 2',
        'url'          => 'dashboard/temperature/aoi2/',
        'design_width' => 1600,
    ),
    array(
        'id'           => 'aoi2-energy',
        'title'        => 'Energy',
        'area'         => 'AOI 2',
        'url'          => 'dashboard/energy/aoi2/',
        'design_width' => 1600,
    ),
    array(
        'id'           => 'aoi5-temp',
        'title'        => 'Temp humid',
        'area'         => 'AOI 5',
        'url'          => 'dashboard/temperature/aoi5/',
        'design_width' => 1600,
    ),
    array(
        'id'           => 'aoi5-energy',
        'title'        => 'Energy',
        'area'         => 'AOI 5',
        'url'          => 'http://iotelectricity.bbi-apparel.com',
        'design_width' => 1600,
    ),
);
```

### 6.3 Tabel Slot

| # | id | Judul | Area | URL | Status awal | Catatan |
|---|---|---|---|---|---|---|
| 1 | `aoi1-temp` | Temp humid | AOI 1 | `dashboard/temperature/aoi1/` | Memuat | same-origin |
| 2 | `aoi1-energy` | Energy | AOI 1 | — | Kosong | belum ada link di sidebar (href "#") |
| 3 | `aoi2-temp` | Temp humid | AOI 2 | `dashboard/temperature/aoi2/` | Memuat | same-origin |
| 4 | `aoi2-energy` | Energy | AOI 2 | `dashboard/energy/aoi2/` | Memuat | same-origin |
| 5 | `aoi5-temp` | Temp humid | AOI 5 | `dashboard/temperature/aoi5/` | Memuat | same-origin |
| 6 | `aoi5-energy` | Energy | AOI 5 | `http://iotelectricity.bbi-apparel.com` | Memuat | **cross-origin — wajib uji iframe policy**, lihat bagian 17 |

Catatan URL:

- Gunakan path relatif tanpa leading slash (contoh `dashboard/temperature/aoi1/`) agar tetap benar saat portal berjalan di subfolder (Laragon: `/enviro/`) maupun di root domain. Jangan memakai `/dashboard/...` karena akan menunjuk ke root domain, bukan root aplikasi.
- Jika portal dipasang di domain berbeda dari dashboard, ganti dengan URL absolut **https** yang benar.
- Jangan menambahkan `?embed=1` pada URL v1; dashboard belum mendukung parameter itu. Parameter tersebut disiapkan sebagai follow-up opsional (bagian 19).
- Sidebar tetap memakai tautan yang sudah ada. Jangan mengubahnya pada pekerjaan ini.

### 6.4 Helper Escaping

Definisikan sekali di `index.php` sebelum markup:

```php
function mu_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
```

Semua nilai dari config yang dicetak ke HTML wajib melewati `mu_e()`.

---

## 7. Spesifikasi Layout

### 7.1 Shell Halaman

`main-content` dari aplikasi punya `padding: 1.2rem` dan `min-height: 100vh`. Agar board pas satu layar tanpa scroll (layar NOC), tambahkan override yang dibatasi `body.mu-page` dan hanya untuk layar ≥ 1200 px:

```css
@media (min-width: 1200px) {
  body.mu-page { overflow: hidden; }
  body.mu-page .main-content {
    height: calc(100vh - var(--mu-topnav-h));
    min-height: 0;
    display: flex;
  }
}
```

- `--mu-topnav-h` diukur di aplikasi ini = `1.5rem` (24 px): navbar hanya berisi avatar kosong dengan padding vertikal 8 px. Token awal `3.5rem` ternyata menyisakan pita kosong 32 px di bawah board. Jika markup topnav berubah, ukur ulang dan sesuaikan token; jangan mengubah markup topnav.
- Di bawah 1200 px, halaman boleh scroll; board mengikuti tinggi konten.

### 7.2 Board

- Latar putih, border 1 px `--mu-line`, radius 16 px, shadow board, padding 16 px.
- Mengisi seluruh area `main-content` yang tersisa (`flex: 1 1 auto`).
- Di dalamnya `.mu-board__grid`: `grid-template-columns: minmax(0, 1fr) var(--mu-rail-w)`, gap 16 px, `min-height: 0`.

### 7.3 Grid Kartu

- `display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); grid-template-rows: repeat(3, minmax(0, 1fr)); gap: 16px; min-height: 0;`
- Kartu mengisi sel penuh (`min-height: 0`, `overflow: hidden`).
- Enam slot tetap dua kolom pada ≥ 1200 px. Jika suatu saat slot bertambah menjadi 7–9, ganti makro grid menjadi 3 × 3 dan tambahkan breakpoint baru; jangan memaksa 4 baris di layar 1080.

### 7.4 Rail Kanan

- Lebar `--mu-rail-w`: 320 px (≥ 1440 px) dan 288 px (1200–1439 px).
- Dua panel bertumpuk, gap 16 px, `min-height: 0`.
- Panel 1: judul, jam, tanggal. Tinggi mengikuti isi (bukan persentase).
- Panel 2: ringkasan; mengisi sisa tinggi (`flex: 1 1 auto`), daftar bisa scroll internal jika perlu.

### 7.5 Proporsi Acuan 1920 × 1080

| Elemen | Nilai perkiraan | Catatan |
|---|---|---|
| Area konten setelah sidebar 256 px dan padding | 1626 × 986 px | padding app 1.2rem |
| Board setelah padding 16 px | 1594 × 954 px | border 1 px |
| Rail | 320 px | gap 16 |
| Grid kartu | 1258 × 954 px | 2 kolom, 3 baris |
| Ukuran kartu | ± 621 × 307 px | rasio ± 2.0 |
| Header kartu | 44 px | tetap |
| Footer kartu | 28 px | tetap |
| Viewport iframe | ± 621 × 235 px | sisa tinggi |

Angka ini bukan target kaku; yang penting proporsi dan tidak ada scroll.

---

## 8. Spesifikasi Komponen

### 8.1 Kartu (`.mu-card`)

Anatomi dari atas ke bawah:

```
┌──────────────────────────────────────────┐
│ ● Temp humid                    [ ↗ ]    │  header 44px, border-bottom
│   AOI 1                                  │
├──────────────────────────────────────────┤
│                                          │
│   iframe live / state layer              │  viewport flex:1, bg #f8f9fa
│                                          │
├──────────────────────────────────────────┤
│ Dimuat 14:31                    Aktif    │  footer 28px, border-top
└──────────────────────────────────────────┘
```

- Latar putih, border 1 px `--mu-line`, radius 10 px, `overflow: hidden`, tanpa shadow.
- `display: flex; flex-direction: column; min-width: 0; min-height: 0;`
- `aria-label`: `"Pratinjau <judul> <area>"`, atau untuk slot kosong `"<judul> <area> belum terpasang"`.

### 8.2 Header Kartu

- Tinggi tetap 44 px; `display: flex; align-items: center; gap: 8px; padding: 0 8px 0 12px; border-bottom: 1px solid var(--mu-line);`
- **Titik status** 8 px, `border-radius: 50%`, `aria-hidden="true"`. Warna per state: aktif `--mu-ok` + ring lembut, memuat `--mu-warn` + animasi pulse (lihat 8.5), gagal `--mu-danger`, kosong `--mu-faint`.
- **Blok teks**: dua baris — judul dashboard 13 px/600 (`--mu-ink`) dan area 11 px/500 (`--mu-muted`). Jika judul panjang: `overflow: hidden; text-overflow: ellipsis; white-space: nowrap;`.
- **Tombol buka** di ujung kanan: `<a>` 32 × 32 px, ikon Feather `fe-external-link` 14 px, warna `--mu-muted`, radius 8 px. Hover: latar `--mu-raised-2`, warna `--mu-ink`. `aria-label="Buka Temp humid AOI 1 di tab baru"`, `target="_blank"`, `rel="noopener"`, `title` sama.
- Slot kosong **tidak menampilkan** tombol buka.

### 8.3 Viewport Iframe dan Algoritma Fit

Struktur:

```html
<div class="mu-card__view">
  <iframe class="mu-frame" title="Pratinjau live Temp humid AOI 1"
          tabindex="-1" aria-hidden="true" scrolling="no"
          sandbox="allow-same-origin allow-scripts allow-forms"></iframe>
  <div class="mu-state mu-state--loading">…</div>
  <div class="mu-state mu-state--error">…</div>
</div>
```

- Viewport: `position: relative; flex: 1 1 auto; min-height: 0; overflow: hidden; background: var(--mu-view-bg);`
- Iframe: `position: absolute; top: 0; left: 0; border: 0; transform-origin: 0 0; pointer-events: none;`
- Iframe tidak boleh difokus keyboard (`tabindex="-1"`) dan tidak dibacakan screen reader (`aria-hidden="true"`); rute aksesibel adalah tombol buka.
- `sandbox` wajib agar dashboard tetap terisolasi. Kombinasi `allow-same-origin allow-scripts` diperlukan karena dashboard same-origin memakai JS, cookie, dan MQTT WebSocket.

**Algoritma fit (penting, jangan disederhanakan):**

Lebar logis iframe tetap (`data-design-width`, default 1600 px). Tinggi logis **dihitung dari rasio kartu**, sehingga viewport dashboard selalu sebanding dengan kotak yang tersedia. Tidak ada letterbox, tidak ada crop.

```js
function fit(card) {
  var frame = card.querySelector('.mu-frame');
  var view = card.querySelector('.mu-card__view');
  if (!frame || !view) return;

  var w = view.clientWidth;
  var h = view.clientHeight;
  if (!w || !h) return;

  var designW = parseInt(card.getAttribute('data-design-width'), 10) || 1600;
  var scale = w / designW;
  var designH = Math.round(h / scale);

  frame.style.width = designW + 'px';
  frame.style.height = designH + 'px';
  frame.style.transform = 'scale(' + scale + ')';
}
```

- Panggil `fit()` sebelum `src` diisi, saat `load`, dan saat ukuran kartu berubah.
- Jangan memakai `Math.max` (cover) karena memotong sisi dashboard; jangan memakai tinggi desain statis karena menimbulkan pita kosong.
- Contoh hasil: kartu 621 × 235 px, designW 1600 → scale 0.388, designH 606 px. Dashboard dirender pada viewport logis 1600 × 606.
- Jika CPU NOC tinggi karena enam iframe, turunkan `design_width` menjadi 1440 pada config (kualitas sedikit turun, beban render turun). Ukur dulu.

### 8.4 Footer Kartu

- Tinggi 28 px; `display: flex; justify-content: space-between; align-items: center; padding: 0 12px; border-top: 1px solid var(--mu-line); font-size: 11px;`
- Kiri: waktu dimuat `Dimuat 14:31` (muncul setelah state aktif).
- Kanan: teks status — `Aktif` (hijau ink), `Memuat` (amber ink), `Gagal` (merah ink), `Kosong` (abu). Teks ini wajib ada agar status tidak bergantung pada warna saja.
- Sebelum iframe berhasil dimuat, sisi kiri kosong.

### 8.5 State Layer

Tiga blok state dirender di HTML dan ditampilkan lewat CSS sesuai `data-state` kartu:

```css
.mu-state { display: none; }
.mu-card[data-state="loading"] .mu-state--loading,
.mu-card[data-state="error"]   .mu-state--error,
.mu-card[data-state="empty"]   .mu-state--empty { display: flex; }
```

Konten per state:

| State | Judul | Body | Aksi |
|---|---|---|---|
| `loading` | `Memuat dashboard…` | — | progress bar tipis di atas viewport |
| `error` | `Dashboard tidak merespons` | `Periksa koneksi jaringan, lalu muat ulang.` | tombol `Muat ulang` |
| `empty` | `Belum terpasang` | `Dashboard ini belum dihubungkan ke menu utama.` | — |

Aturan visual:

- Layer menutup seluruh viewport (`inset: 0`), latar `--mu-view-bg`, konten rata tengah, padding 16 px, gap 4 px (8 px sebelum tombol).
- Loading: gelombang 2 px di tepi atas viewport, warna transisi ke `--mu-primary`, animasi 1.2 s linear infinite. Titik header berdenyut lembut (opacity 1 → .45 → 1, 1.6 s).
- Error: tombol `Muat ulang` adalah satu-satunya aksi (class `.mu-btn`), memicu reload manual dan mereset hitungan retry.
- Empty: tenang, tanpa tombol.
- Semua animasi dimatikan di `prefers-reduced-motion: reduce`.

### 8.6 Rail: Judul, Jam, Tanggal

- Panel latar `--mu-raised`, radius 12 px, padding 16 px.
- Judul `Menu utama` 18 px/600, margin bawah 12 px.
- Jam `<time id="mu-clock">` 34 px/600 tabular, format `HH:MM:SS` 24 jam, locale `id-ID`, warna `--mu-ink`.
- Tanggal `<time id="mu-date">` 12 px, warna `--mu-muted`, format `Rabu, 25 November 2026` (locale `id-ID`).
- Jam di-update tiap detik oleh JS. Jangan pakai `aria-live` pada jam (akan mengumumkan tiap detik).

### 8.7 Rail: Ringkasan Monitor

- Panel kedua mengisi sisa tinggi. Isi:
  1. Judul `Ringkasan monitor` 13 px/600.
  2. Baris chip ringkasan: `X aktif`, `Y memuat`, `Z gagal`, `W kosong`. Semua chip hanya tampil jika angkanya > 0. Warna chip mengikuti status; teks memakai varian `-ink`.
  3. Daftar enam monitor (dirender server-side dari config): titik status + judul + area di kiri, teks status di kanan. Jarak baris 8 px vertikal, pemisah 1 px `--mu-line-strong` antar baris (tanpa garis di baris pertama).
- Daftar bukan tautan pada v1 (ringkasan status saja). Aksi membuka dashboard ada di header kartu.
- Jika seluruh slot kosong: tampilkan `Belum ada monitor terpasang.` dan sembunyikan chips.
- Daftar bisa `overflow-y: auto` bila tinggi panel kurang; scrollbar tipis dan hanya muncul saat perlu.

### 8.8 Tombol dan Tautan

- `.mu-btn`: tinggi 28 px, padding 0 10 px, radius 8 px, border 1 px `--mu-line-strong`, latar putih, teks 12 px/600 `--mu-ink-2`. Hover: latar `--mu-raised`.
- Fokus keyboard: `outline: 2px solid var(--mu-primary); outline-offset: 2px;`. **Penting:** tema aplikasi punya `button:focus { outline: none; }`; aturan `.mu-btn:focus-visible` dan `.mu-card__open:focus-visible` harus ditulis setelah stylesheet aplikasi (file kita dimuat setelah `app-light.css`).
- Tidak ada teks "→" di tombol. Ikon Feather boleh dipakai, selalu dengan `aria-hidden="true"` bila teks sudah menjelaskan aksi.

---

## 9. State Machine dan Perilaku

### 9.1 State

| State | Arti | Tampilan |
|---|---|---|
| `empty` | Slot tidak punya URL | State layer "Belum terpasang"; tanpa iframe |
| `loading` | Iframe sedang dimuat atau retry | State layer "Memuat dashboard…" + progress bar |
| `online` | Event `load` iframe terakhir berhasil | Iframe tampil; footer `Dimuat HH:MM` + `Aktif` |
| `error` | Timeout 25 s atau event `error` | State layer error + tombol `Muat ulang` |

Catatan semantik: `online` berarti **dokumen dashboard berhasil dimuat browser**, bukan jaminan data sensor segar. Karena itu teks status memakai "Aktif/Gagal", bukan klaim kualitas data. Dashboard sumber sudah punya auto-reload internal 30 menit.

### 9.2 Aturan Transisi

- `empty` → tidak pernah berubah (tidak ada URL).
- `loading` → `online` saat event `load`.
- `loading` → `error` saat timeout 25 detik atau event `error`.
- `error` → `loading` saat retry otomatis atau tombol `Muat ulang`.
- `online` → `loading` hanya jika pengguna menekan muat ulang (tidak ada di v1 untuk state online) atau halaman di-reload penuh.
- Jika `load` terlambat datang setelah timeout (mis. jaringan lambat), terima sebagai `online` dan batalkan retry.

### 9.3 Timing

| Parameter | Nilai | Alasan |
|---|---|---|
| Jeda antar iframe saat init | 250 ms | Menghindari enam request berat serentak |
| Timeout per iframe | 25 000 ms | Cukup untuk dashboard Highcharts di jaringan pabrik |
| Retry otomatis | 30 s, 60 s, 120 s (maksimal 3×) | Backoff sederhana; setelah habis berhenti di state error |
| Reset retry | Saat load sukses atau tombol `Muat ulang` | Mencegah loop tak terbatas |
| Debounce resize | 150 ms | Menghindari perhitungan fit berulang saat drag window |
| Update jam | 1 000 ms | Presisi detik |
| Reload penuh harian | Opsional pukul 02:00 | Menyegarkan sesi/koneksi; tidak wajib v1 |

### 9.4 Perilaku Tambahan

- Semua iframe dimuat bergiliran saat DOM siap (bukan menunggu terlihat), karena layar NOC menampilkan semua kartu.
- Ringkasan (chips + status per baris) dihitung ulang setiap state berubah.
- `ResizeObserver` pada grid kartu memanggil `fit()` untuk semua kartu; fallback `window.resize` bila tidak didukung.
- Saat sidebar di-collapse (lebar berubah), `ResizeObserver` menyesuaikan iframe.

---

## 10. Referensi Implementasi

> Kode di bawah adalah referensi kerja. Boleh dirapikan, tetapi jangan mengubah keputusan struktur, nama class, teks, atau timing tanpa memperbarui dokumen ini.

### 10.1 Markup `index.php`

Ganti seluruh isi `<main role="main" class="main-content">…</main>` yang sekarang (termasuk `<style>` dan `<script>` inline menu) dengan:

```php
<?php
$slots_menu = require __DIR__ . '/config/menu_utama.php';

function mu_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<main role="main" class="main-content">
  <div class="mu-board">
    <div class="mu-board__grid">

      <div class="mu-tiles">
        <?php foreach ($slots_menu as $slot): ?>
          <?php $has_url = !empty($slot['url']); ?>
          <article class="mu-card"
                   data-id="<?= mu_e($slot['id']) ?>"
                   data-state="<?= $has_url ? 'loading' : 'empty' ?>"
                   data-design-width="<?= (int) $slot['design_width'] ?>"
                   <?= $has_url ? 'data-src="' . mu_e($slot['url']) . '"' : '' ?>
                   aria-label="<?= mu_e(($has_url ? 'Pratinjau ' : '') . $slot['title'] . ' ' . $slot['area'] . ($has_url ? '' : ' belum terpasang')) ?>">

            <header class="mu-card__head">
              <span class="mu-dot" aria-hidden="true"></span>
              <span class="mu-card__id">
                <span class="mu-card__title"><?= mu_e($slot['title']) ?></span>
                <span class="mu-card__area"><?= mu_e($slot['area']) ?></span>
              </span>
              <?php if ($has_url): ?>
                <a class="mu-card__open"
                   href="<?= mu_e($slot['url']) ?>"
                   target="_blank" rel="noopener"
                   aria-label="Buka <?= mu_e($slot['title'] . ' ' . $slot['area']) ?> di tab baru"
                   title="Buka <?= mu_e($slot['title'] . ' ' . $slot['area']) ?> di tab baru">
                  <i class="fe fe-external-link" aria-hidden="true"></i>
                </a>
              <?php endif; ?>
            </header>

            <div class="mu-card__view">
              <?php if ($has_url): ?>
                <iframe class="mu-frame"
                        title="Pratinjau live <?= mu_e($slot['title'] . ' ' . $slot['area']) ?>"
                        tabindex="-1" aria-hidden="true" scrolling="no"
                        sandbox="allow-same-origin allow-scripts allow-forms"></iframe>
                <div class="mu-state mu-state--loading">
                  <p class="mu-state__title">Memuat dashboard…</p>
                </div>
                <div class="mu-state mu-state--error">
                  <p class="mu-state__title">Dashboard tidak merespons</p>
                  <p class="mu-state__body">Periksa koneksi jaringan, lalu muat ulang.</p>
                  <button type="button" class="mu-btn mu-state__action" data-action="reload">Muat ulang</button>
                </div>
              <?php else: ?>
                <div class="mu-state mu-state--empty">
                  <p class="mu-state__title">Belum terpasang</p>
                  <p class="mu-state__body">Dashboard ini belum dihubungkan ke menu utama.</p>
                </div>
              <?php endif; ?>
            </div>

            <footer class="mu-card__foot">
              <span class="mu-card__loaded" data-role="loaded"></span>
              <span class="mu-card__state" data-role="status"><?= $has_url ? 'Memuat' : 'Kosong' ?></span>
            </footer>
          </article>
        <?php endforeach; ?>
      </div>

      <aside class="mu-rail">
        <section class="mu-panel mu-rail__clock" aria-label="Waktu saat ini">
          <h1 class="mu-panel__title">Menu utama</h1>
          <p class="mu-clock"><time id="mu-clock" datetime="">--:--:--</time></p>
          <p class="mu-date"><time id="mu-date">—</time></p>
        </section>

        <section class="mu-panel mu-panel--grow" aria-labelledby="mu-summary-title">
          <div class="mu-summary__head">
            <h2 class="mu-section-title" id="mu-summary-title">Ringkasan monitor</h2>
          </div>

          <div class="mu-chips" aria-live="off">
            <span class="mu-chip mu-chip--ok" data-chip="online">
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-online">— aktif</span>
            </span>
            <span class="mu-chip mu-chip--warn" data-chip="loading" hidden>
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-loading">— memuat</span>
            </span>
            <span class="mu-chip mu-chip--danger" data-chip="error" hidden>
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-error">— gagal</span>
            </span>
            <span class="mu-chip mu-chip--muted" data-chip="empty" hidden>
              <span class="mu-dot" aria-hidden="true"></span>
              <span data-role="count-empty">— kosong</span>
            </span>
          </div>

          <ul class="mu-list">
            <?php foreach ($slots_menu as $slot): ?>
              <li class="mu-list__row"
                  data-slot="<?= mu_e($slot['id']) ?>"
                  data-state="<?= $slot['url'] ? 'loading' : 'empty' ?>">
                <span class="mu-dot" aria-hidden="true"></span>
                <span class="mu-list__label">
                  <span class="mu-list__title"><?= mu_e($slot['title']) ?></span>
                  <span class="mu-list__area"><?= mu_e($slot['area']) ?></span>
                </span>
                <span class="mu-list__state" data-role="summary-status"><?= $slot['url'] ? 'Memuat' : 'Kosong' ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </aside>

    </div>
  </div>
</main>
```

Di `<body>`: `class="vertical light mu-page"`. Di `<head>`, setelah `app-light.css`, tambahkan link CSS baru. Sebelum `</body>`, setelah script aplikasi, tambahkan script baru dengan `defer`.

### 10.2 CSS `css/menu-utama.css`

```css
/* Semua token dan aturan hanya berlaku pada halaman Menu Utama. */
body.mu-page {
  --mu-board-bg: #ffffff;
  --mu-view-bg: #f8f9fa;
  --mu-raised: #f4f7fb;
  --mu-raised-2: #eef2f7;
  --mu-line: #e9ecef;
  --mu-line-strong: #dde3ea;
  --mu-ink: #212b36;
  --mu-ink-2: #4b5b6b;
  --mu-muted: #6c757d;
  --mu-faint: #98a2b3;
  --mu-primary: #1b68ff;
  --mu-ok: #3ad29f;
  --mu-ok-ink: #157f5f;
  --mu-ok-soft: #e7faf3;
  --mu-warn: #eea303;
  --mu-warn-ink: #8a5c00;
  --mu-warn-soft: #fdf5e3;
  --mu-danger: #dc3545;
  --mu-danger-ink: #b02a37;
  --mu-danger-soft: #fbeaec;
  --mu-topnav-h: 1.5rem;
  --mu-gap: 1rem;
  --mu-pad: 1rem;
  --mu-rail-w: 20rem;
  --mu-r-board: 1rem;
  --mu-r-panel: 0.75rem;
  --mu-r-tile: 0.625rem;
  --mu-r-control: 0.5rem;
  --mu-shadow-board: 0 1px 2px rgba(18, 38, 63, .04), 0 12px 32px -16px rgba(18, 38, 63, .18);
}

@media (max-width: 1439.98px) {
  body.mu-page { --mu-rail-w: 18rem; }
}

@media (min-width: 1200px) {
  body.mu-page { overflow: hidden; }
  body.mu-page .main-content {
    height: calc(100vh - var(--mu-topnav-h));
    min-height: 0;
    display: flex;
  }
}

/* Board */
.mu-board {
  display: flex;
  flex: 1 1 auto;
  min-height: 0;
  padding: var(--mu-pad);
  background: var(--mu-board-bg);
  border: 1px solid var(--mu-line);
  border-radius: var(--mu-r-board);
  box-shadow: var(--mu-shadow-board);
}

.mu-board__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) var(--mu-rail-w);
  gap: var(--mu-gap);
  flex: 1 1 auto;
  min-height: 0;
}

/* Grid kartu */
.mu-tiles {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  grid-template-rows: repeat(3, minmax(0, 1fr));
  gap: var(--mu-gap);
  min-height: 0;
}

/* Kartu */
.mu-card {
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
  background: var(--mu-board-bg);
  border: 1px solid var(--mu-line);
  border-radius: var(--mu-r-tile);
}

.mu-card__head {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 0 0 2.75rem;
  padding: 0 0.5rem 0 0.75rem;
  border-bottom: 1px solid var(--mu-line);
}

.mu-card__id {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.mu-card__title {
  font-size: 0.8125rem;
  line-height: 1.2;
  font-weight: 600;
  color: var(--mu-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mu-card__area {
  font-size: 0.6875rem;
  line-height: 1.2;
  font-weight: 500;
  color: var(--mu-muted);
}

.mu-card__open {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 2rem;
  height: 2rem;
  margin-left: auto;
  border-radius: var(--mu-r-control);
  color: var(--mu-muted);
}

.mu-card__open:hover {
  background: var(--mu-raised-2);
  color: var(--mu-ink);
  text-decoration: none;
}

.mu-card__open:focus-visible {
  outline: 2px solid var(--mu-primary);
  outline-offset: 2px;
}

.mu-card__open .fe { font-size: 0.875rem; }

/* Titik status */
.mu-dot {
  flex: 0 0 auto;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: var(--mu-faint);
}

.mu-card[data-state="online"] .mu-dot { background: var(--mu-ok); box-shadow: 0 0 0 3px var(--mu-ok-soft); }
.mu-card[data-state="loading"] .mu-dot { background: var(--mu-warn); box-shadow: 0 0 0 3px var(--mu-warn-soft); animation: mu-pulse 1.6s ease-in-out infinite; }
.mu-card[data-state="error"] .mu-dot { background: var(--mu-danger); box-shadow: 0 0 0 3px var(--mu-danger-soft); }

/* Viewport iframe */
.mu-card__view {
  position: relative;
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
  background: var(--mu-view-bg);
}

.mu-frame {
  position: absolute;
  top: 0;
  left: 0;
  border: 0;
  transform-origin: 0 0;
  pointer-events: none;
}

/* State layer */
.mu-state {
  position: absolute;
  inset: 0;
  z-index: 1;
  display: none;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  padding: 1rem;
  text-align: center;
  background: var(--mu-view-bg);
}

.mu-card[data-state="loading"] .mu-state--loading,
.mu-card[data-state="error"] .mu-state--error,
.mu-card[data-state="empty"] .mu-state--empty {
  display: flex;
}

.mu-state__title {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--mu-ink-2);
}

.mu-card[data-state="error"] .mu-state__title { color: var(--mu-ink); }

.mu-state__body {
  max-width: 24ch;
  margin: 0;
  font-size: 0.75rem;
  line-height: 1.4;
  color: var(--mu-muted);
}

.mu-state__action { margin-top: 0.5rem; }

.mu-card[data-state="loading"] .mu-state--loading::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 40%;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--mu-primary), transparent);
  animation: mu-sweep 1.2s ease-in-out infinite;
}

/* Tombol */
.mu-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 1.75rem;
  padding: 0 0.625rem;
  border: 1px solid var(--mu-line-strong);
  border-radius: var(--mu-r-control);
  background: #fff;
  color: var(--mu-ink-2);
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1;
  cursor: pointer;
}

.mu-btn:hover {
  background: var(--mu-raised);
  color: var(--mu-ink);
}

.mu-btn:focus-visible {
  outline: 2px solid var(--mu-primary);
  outline-offset: 2px;
}

/* Footer kartu */
.mu-card__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  flex: 0 0 1.75rem;
  padding: 0 0.75rem;
  border-top: 1px solid var(--mu-line);
  font-size: 0.6875rem;
  color: var(--mu-muted);
}

.mu-card__state { font-weight: 600; }
.mu-card[data-state="online"] .mu-card__state { color: var(--mu-ok-ink); }
.mu-card[data-state="loading"] .mu-card__state { color: var(--mu-warn-ink); }
.mu-card[data-state="error"] .mu-card__state { color: var(--mu-danger-ink); }

/* Rail */
.mu-rail {
  display: flex;
  flex-direction: column;
  gap: var(--mu-gap);
  min-height: 0;
}

.mu-panel {
  padding: 1rem;
  border-radius: var(--mu-r-panel);
  background: var(--mu-raised);
}

.mu-panel--grow {
  display: flex;
  flex: 1 1 auto;
  flex-direction: column;
  gap: 0.75rem;
  min-height: 0;
  overflow: hidden;
}

.mu-panel__title {
  margin: 0 0 0.75rem;
  font-size: 1.125rem;
  line-height: 1.33;
  font-weight: 600;
  color: var(--mu-ink);
}

.mu-clock {
  margin: 0;
  font-size: 2.125rem;
  line-height: 1.1;
  font-weight: 600;
  letter-spacing: -0.015em;
  color: var(--mu-ink);
  font-variant-numeric: tabular-nums;
}

.mu-date {
  margin: 0.125rem 0 0;
  font-size: 0.75rem;
  line-height: 1.4;
  color: var(--mu-muted);
}

.mu-summary__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.mu-section-title {
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.4;
  font-weight: 600;
  color: var(--mu-ink);
}

.mu-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.mu-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  height: 1.5rem;
  padding: 0 0.5rem;
  border-radius: 999px;
  font-size: 0.6875rem;
  font-weight: 600;
}

.mu-chip[hidden] { display: none; }
.mu-chip .mu-dot { width: 0.375rem; height: 0.375rem; box-shadow: none; }
.mu-chip--ok { background: var(--mu-ok-soft); color: var(--mu-ok-ink); }
.mu-chip--ok .mu-dot { background: var(--mu-ok); }
.mu-chip--warn { background: var(--mu-warn-soft); color: var(--mu-warn-ink); }
.mu-chip--warn .mu-dot { background: var(--mu-warn); }
.mu-chip--danger { background: var(--mu-danger-soft); color: var(--mu-danger-ink); }
.mu-chip--danger .mu-dot { background: var(--mu-danger); }
.mu-chip--muted { background: var(--mu-raised-2); color: var(--mu-muted); }
.mu-chip--muted .mu-dot { background: var(--mu-muted); }

.mu-list {
  min-height: 0;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  list-style: none;
  scrollbar-width: thin;
}

.mu-list__row {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
  border-top: 1px solid var(--mu-line-strong);
}

.mu-list__row:first-child { border-top: 0; }

.mu-list__label {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.mu-list__title {
  font-size: 0.75rem;
  line-height: 1.3;
  font-weight: 600;
  color: var(--mu-ink-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mu-list__area {
  font-size: 0.6875rem;
  line-height: 1.3;
  color: var(--mu-muted);
}

.mu-list__state {
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--mu-muted);
}

.mu-list__row[data-state="online"] .mu-dot { background: var(--mu-ok); }
.mu-list__row[data-state="loading"] .mu-dot { background: var(--mu-warn); }
.mu-list__row[data-state="error"] .mu-dot { background: var(--mu-danger); }
.mu-list__row[data-state="online"] .mu-list__state { color: var(--mu-ok-ink); }
.mu-list__row[data-state="loading"] .mu-list__state { color: var(--mu-warn-ink); }
.mu-list__row[data-state="error"] .mu-list__state { color: var(--mu-danger-ink); }

/* Animasi */
@keyframes mu-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.45; }
}

@keyframes mu-sweep {
  from { transform: translateX(-100%); }
  to { transform: translateX(350%); }
}

@media (prefers-reduced-motion: reduce) {
  .mu-card[data-state="loading"] .mu-dot,
  .mu-card[data-state="loading"] .mu-state--loading::before {
    animation: none;
  }
}

/* Responsif di bawah layar NOC */
@media (max-width: 1199.98px) {
  body.mu-page { overflow: auto; }
  body.mu-page .main-content {
    height: auto;
    min-height: 0;
    display: block;
  }

  .mu-board__grid { grid-template-columns: minmax(0, 1fr); }
  .mu-rail { flex-direction: row; order: -1; }
  .mu-rail .mu-panel { flex: 1 1 0; }
  .mu-list { display: none; }

  .mu-tiles { grid-template-rows: none; }

  .mu-card__view {
    flex: none;
    aspect-ratio: 16 / 9;
  }
}

@media (max-width: 767.98px) {
  .mu-tiles { grid-template-columns: minmax(0, 1fr); }
  .mu-rail { flex-direction: column; }
}
```

Catatan specificity:

- `body.mu-page .main-content` (0,2,1) mengalahkan `.vertical .main-content` (0,2,0) dari tema tanpa `!important`.
- Jangan menulis selektor global seperti `.card`, `.badge`, `.nav-link`, atau `section { … }` di file ini.
- Aturan fokus ditulis setelah stylesheet tema karena tema menghapus outline tombol secara global.
- Muat `menu-utama.css` **setelah** `app-light.css`.

### 10.3 JavaScript `js/menu-utama.js`

```js
(function () {
  'use strict';

  var STAGGER_MS = 250;
  var TIMEOUT_MS = 25000;
  var RETRY_DELAYS = [30000, 60000, 120000];
  var LABELS = { online: 'Aktif', loading: 'Memuat', error: 'Gagal', empty: 'Kosong' };

  var cards = Array.prototype.slice.call(document.querySelectorAll('.mu-card'));
  var loadQueue = cards.filter(function (card) {
    return card.getAttribute('data-src');
  });

  function $(selector, root) {
    return (root || document).querySelector(selector);
  }

  function fit(card) {
    var frame = $('.mu-frame', card);
    var view = $('.mu-card__view', card);
    if (!frame || !view) return;

    var w = view.clientWidth;
    var h = view.clientHeight;
    if (!w || !h) return;

    var designW = parseInt(card.getAttribute('data-design-width'), 10) || 1600;
    var scale = w / designW;
    var designH = Math.round(h / scale);

    frame.style.width = designW + 'px';
    frame.style.height = designH + 'px';
    frame.style.transform = 'scale(' + scale + ')';
  }

  function setState(card, state) {
    card.setAttribute('data-state', state);

    var footer = $('[data-role="status"]', card);
    if (footer) footer.textContent = LABELS[state] || '';

    if (state === 'loading') {
      var loaded = $('[data-role="loaded"]', card);
      if (loaded) loaded.textContent = '';
    }

    var row = $('.mu-list__row[data-slot="' + card.getAttribute('data-id') + '"]');
    if (row) {
      row.setAttribute('data-state', state);
      var rowState = $('[data-role="summary-status"]', row);
      if (rowState) rowState.textContent = LABELS[state] || '';
    }

    updateSummary();
  }

  function updateSummary() {
    var counts = { online: 0, loading: 0, error: 0, empty: 0 };
    cards.forEach(function (card) {
      var s = card.getAttribute('data-state');
      if (counts[s] !== undefined) counts[s]++;
    });

    var defs = [
      ['online', 'count-online', 'aktif'],
      ['loading', 'count-loading', 'memuat'],
      ['error', 'count-error', 'gagal'],
      ['empty', 'count-empty', 'kosong']
    ];

    defs.forEach(function (def) {
      var chip = document.querySelector('.mu-chip[data-chip="' + def[0] + '"]');
      var label = document.querySelector('[data-role="' + def[1] + '"]');
      if (label) label.textContent = counts[def[0]] + ' ' + def[2];
      if (chip) chip.hidden = counts[def[0]] === 0;
    });
  }

  function stampLoaded(card) {
    var el = $('[data-role="loaded"]', card);
    var ts = parseInt(card.getAttribute('data-loaded-at'), 10);
    if (!el || !ts) return;
    var date = new Date(ts);
    el.textContent = 'Dimuat ' + pad2(date.getHours()) + ':' + pad2(date.getMinutes());
  }

  function clearTimer(card) {
    if (card._muTimer) {
      clearTimeout(card._muTimer);
      card._muTimer = null;
    }
  }

  function clearRetry(card) {
    if (card._muRetry) {
      clearTimeout(card._muRetry);
      card._muRetry = null;
    }
  }

  function fail(card) {
    clearTimer(card);
    setState(card, 'error');

    var attempt = parseInt(card.getAttribute('data-attempt') || '0', 10);
    if (attempt < RETRY_DELAYS.length) {
      card.setAttribute('data-attempt', String(attempt + 1));
      clearRetry(card);
      card._muRetry = setTimeout(function () { load(card); }, RETRY_DELAYS[attempt]);
    }
  }

  function load(card) {
    var frame = $('.mu-frame', card);
    var url = card.getAttribute('data-src');
    if (!frame || !url) return;

    clearTimer(card);
    clearRetry(card);
    setState(card, 'loading');
    fit(card);

    if (!frame._muWired) {
      frame._muWired = true;

      frame.addEventListener('load', function () {
        clearTimer(card);
        clearRetry(card);
        card.setAttribute('data-attempt', '0');
        card.setAttribute('data-loaded-at', String(Date.now()));
        stampLoaded(card);
        setState(card, 'online');
        fit(card);
      });

      frame.addEventListener('error', function () {
        fail(card);
      });
    }

    card._muTimer = setTimeout(function () { fail(card); }, TIMEOUT_MS);
    frame.src = url;
  }

  function pad2(value) {
    return value < 10 ? '0' + value : String(value);
  }

  function tick() {
    var now = new Date();
    var clock = document.getElementById('mu-clock');
    var date = document.getElementById('mu-date');

    if (clock) {
      clock.textContent = pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());
      clock.setAttribute('datetime', now.toISOString());
    }

    if (date) {
      date.textContent = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      }).format(now);
      date.setAttribute('datetime', now.toISOString().slice(0, 10));
    }
  }

  function bindReload() {
    cards.forEach(function (card) {
      var button = $('[data-action="reload"]', card);
      if (!button) return;
      button.addEventListener('click', function () {
        card.setAttribute('data-attempt', '0');
        clearRetry(card);
        load(card);
      });
    });
  }

  function init() {
    bindReload();
    updateSummary();
    tick();
    setInterval(tick, 1000);

    loadQueue.forEach(function (card, index) {
      setTimeout(function () { load(card); }, index * STAGGER_MS);
    });

    var resizeTimer = null;
    function scheduleFit() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        cards.forEach(fit);
      }, 150);
    }

    window.addEventListener('resize', scheduleFit);

    if ('ResizeObserver' in window) {
      var observer = new ResizeObserver(scheduleFit);
      var tiles = document.querySelector('.mu-tiles');
      if (tiles) observer.observe(tiles);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
```

Catatan perilaku:

- Jam diformat manual (`pad2`) agar selalu 24 jam dengan titik dua; `Intl` `id-ID` memakai titik (contoh `14.32.05`), sedangkan desain meminta `14:32:05`. Tanggal tetap memakai `Intl` `id-ID`.
- `load` event cross-origin bisa muncul untuk halaman yang ditolak `X-Frame-Options` (browser menampilkan halaman error sendiri). Karena tidak bisa dideteksi dari JS, tombol buka di header adalah fallback resmi. Uji manual host energy (bagian 17).
- Jangan menambahkan auto-refresh iframe tiap menit; dashboard sumber sudah reload sendiri tiap 30 menit. Refresh dini bisa memutus WebSocket MQTT-nya.
- Reload harian opsional (pukul 02:00) boleh ditambahkan di `init()` dengan `setTimeout(function(){ location.reload(); }, msUntil0200)`.

---

## 11. Spesifikasi Responsif

| Lebar | Layout | Perilaku |
|---|---|---|
| ≥ 1440 px | Board fix satu layar, kartu 2 × 3, rail 320 px | Tanpa scroll halaman; semua kartu & ringkasan terlihat |
| 1200–1439 px | Sama, rail 288 px | Tanpa scroll; teks tetap |
| 992–1199 px | Rail menjadi strip horizontal di atas kartu (jam kiri, ringkasan kanan, daftar ringkasan disembunyikan); kartu 2 kolom rasio viewport 16:9 | Halaman scroll |
| 768–991 px | Sama seperti di atas; sidebar aplikasi dikelola tema | Halaman scroll |
| < 768 px | Kartu 1 kolom, rasio 16:9; rail strip vertikal (jam lalu ringkasan) | Halaman scroll |
| < 576 px | Sama; padding board 16 px | Halaman scroll |

Target utama tetap 1920 × 1080. Di bawah 1200 px kualitas "wall" tidak wajib, yang penting tidak rusak dan tetap terbaca.

---

## 12. Aksesibilitas

Checklist wajib:

- [ ] `<html lang="id">`; semua teks antarmuka berbahasa Indonesia.
- [ ] Kontras teks: `--mu-muted #6c757d` di atas `#f4f7fb`/`#ffffff` ≥ 4.5:1; teks status memakai varian `-ink`, bukan warna vivid.
- [ ] Status tidak hanya warna: titik + teks (`Aktif`, `Memuat`, `Gagal`, `Kosong`) di footer dan daftar.
- [ ] Fokus keyboard terlihat pada tombol buka dan tombol `Muat ulang` (override `button:focus { outline: none }` dari tema).
- [ ] Iframe `tabindex="-1"` dan `aria-hidden="true"`; jalur aksesibel adalah tautan buka.
- [ ] Setiap `<article>` kartu punya `aria-label` yang jelas.
- [ ] Rail memakai `<aside>`; panel ringkasan `<section aria-labelledby>`.
- [ ] Jam dan tanggal tidak memakai `aria-live`; status ringkasan `aria-live="off"`.
- [ ] `prefers-reduced-motion` mematikan pulse dan progress animation.
- [ ] Ukuran target sentuh tombol ikon 32 × 32 px.
- [ ] Judul panjang memakai ellipsis, tidak terpotong tanpa penanda.
- [ ] Uji zoom browser 150% dan 200%: layout masuk breakpoint berikutnya dan halaman bisa discroll.

---

## 13. Copy Deck

Gunakan teks ini persis. Jangan mengganti dengan sinonim atau bahasa Inggris.

| Lokasi | Teks |
|---|---|
| Judul rail | `Menu utama` |
| Format jam | `14:32:05` (24 jam) |
| Format tanggal | `Rabu, 25 November 2026` |
| Judul ringkasan | `Ringkasan monitor` |
| Chip | `5 aktif`, `1 memuat`, `1 gagal`, `1 kosong` |
| Footer kiri | `Dimuat 14:31` |
| Footer kanan | `Aktif` / `Memuat` / `Gagal` / `Kosong` |
| State memuat | `Memuat dashboard…` |
| State gagal | `Dashboard tidak merespons` + `Periksa koneksi jaringan, lalu muat ulang.` + tombol `Muat ulang` |
| State kosong | `Belum terpasang` + `Dashboard ini belum dihubungkan ke menu utama.` |
| Tombol buka (aria/title) | `Buka Temp humid AOI 1 di tab baru` |
| Iframe (title) | `Pratinjau live Temp humid AOI 1` |
| Daftar ringkasan kosong | `Belum ada monitor terpasang.` |

Aturan penulisan: sentence case, kata kerja aktif, tanpa tanda "→", tanpa em dash berspasi, tanpa ALL CAPS.

---

## 14. Langkah Implementasi (Urutan Kerja)

1. **Siapkan branch/backup.** `git checkout -b feature/menu-utama-live-view`. Pastikan versi `index.php` sekarang tersimpan.
2. **Buat `config/menu_utama.php`** sesuai bagian 6. Isi enam slot; slot `aoi1-energy` memakai `url => null`.
3. **Buat `css/menu-utama.css`** dari bagian 10.2. Jangan menambah selektor global.
4. **Buat `js/menu-utama.js`** dari bagian 10.3.
5. **Ubah `index.php`:**
   - `<html lang="id">`.
   - `<body class="vertical light mu-page">`.
   - Tambah link `menu-utama.css` setelah `app-light.css`.
   - Hapus `<style>` inline menu dan seluruh markup grid lama.
   - Ganti `<main>` dengan markup bagian 10.1.
   - Tambah `<script src="js/menu-utama.js?v=<?= filemtime(__DIR__ . '/js/menu-utama.js') ?>" defer></script>` sebelum `</body>`.
   - **Jangan sentuh** sidebar, topnav, logo, dan script MQTT.
6. **Ukur topnav.** Buka halaman, ukur tinggi topnav dengan DevTools. Sesuaikan `--mu-topnav-h`.
7. **Uji cepat:** buka `http://localhost/enviro/` (atau domain Laragon). Console bersih, iframe memuat (slot energy AOI 1 kosong), jam berjalan.
8. **Uji state gagal:** sementara ubah salah satu URL di config menjadi `dashboard/tidak-ada/`, muat ulang. Pastikan state error muncul, retry berjalan, tombol `Muat ulang` berfungsi. Kembalikan URL.
9. **Uji cross-origin:** pastikan host energy AOI 5 bisa tampil. Jika ditolak, lihat mitigasi bagian 17.
10. **Uji responsif & aksesibilitas** memakai bagian 11, 12, dan 16.
11. **Uji PHP:** `php -l index.php` dan `php -l config/menu_utama.php`.
12. **Rapikan:** hapus kode mati, pastikan tidak ada `!important`, tidak ada style inline baru.
13. **Screenshot** pada 1920 × 1080, 1366 × 768, dan 390 × 844. Tempel di PR/issue.

Checkpoint di akhir setiap langkah: halaman tidak boleh error PHP, tidak boleh ada console error baru, dan sidebar tetap bisa dipakai.

---

## 15. Kriteria Penerimaan (Definition of Done)

- [ ] Enam kartu tampil dengan judul dan area yang benar sesuai tabel 6.3.
- [ ] Lima kartu memuat iframe live; kartu `Energy AOI 1` menampilkan state "Belum terpasang".
- [ ] Setiap kartu punya titik status, judul, area, footer `Dimuat HH:MM`, teks status, dan tombol buka (kecuali slot kosong).
- [ ] Rail kanan menampilkan `Menu utama`, jam berjalan tiap detik (tabular), tanggal `id-ID`, chips ringkasan yang akurat, dan daftar enam monitor.
- [ ] Di 1920 × 1080 tidak ada scrollbar halaman; tidak ada scrollbar di dalam tile; tidak ada pita kosong besar di iframe.
- [ ] Iframe tetap proporsional saat window di-resize dan saat sidebar collapse (debounce 150 ms).
- [ ] Timeout 25 s menghasilkan state gagal; retry 30/60/120 s berjalan; tombol `Muat ulang` mereset retry.
- [ ] Tidak ada `!important`; tidak ada selektor global baru yang memengaruhi halaman lain.
- [ ] Semua class baru berprefix `mu-`; tidak ada class lama `.noc-card`, `.tile`, `.badge`, `.tag`, `.side` tersisa.
- [ ] Semua output PHP dari config di-escape dengan `htmlspecialchars`.
- [ ] Kode kompatibel PHP 7.4; `php -l` bersih.
- [ ] Fokus keyboard terlihat; reduced motion dihormati; kontras teks lolos.
- [ ] Sidebar, topnav, dan auto-save MQTT tetap berfungsi seperti sebelumnya.
- [ ] Tidak ada error console di Chrome dan Edge versi terbaru.
- [ ] Screenshot tiga ukuran terlampir.

---

## 16. QA & Test Matrix

### 16.1 Perangkat/Layar

| Ukuran | Fokus |
|---|---|
| 1920 × 1080 | Target utama; tanpa scroll; enam kartu + rail terlihat |
| 1600 × 900 | Proporsi rail dan kartu |
| 1366 × 768 | Layar operator umum; cek tinggi tile |
| 1280 × 800 | Kartu dan teks tidak terpotong |
| 1024 × 768 | Rail pindah ke atas; halaman scroll; daftar ringkasan tersembunyi |
| 390 × 844 | Satu kolom; tombol tetap bisa disentuh |

Browser: Chrome (terbaru), Edge (Chromium), Firefox. Jika NOC memakai Chrome kiosk, uji juga tanpa tombol back.

### 16.2 Skenario Fungsional

1. **Normal:** semua slot ber-URL memuat; state `online`; chips `5 aktif`, `1 kosong`; footer waktu terisi.
2. **URL salah:** ubah satu URL menjadi 404; state `Gagal`; retry 3×; tombol manual berfungsi.
3. **Timeout:** throttle jaringan ke "Slow 3G"; progress muncul; jika melewati 25 s, state gagal.
4. **Iframe ditolak:** uji host cross-origin; catat perilaku; pastikan tombol buka tetap bekerja.
5. **Resize:** ubah lebar window perlahan; iframe tidak blank, tidak melar, tidak ada scrollbar internal.
6. **Collapse sidebar:** klik toggle sidebar; iframe menyesuaikan lebar.
7. **Sesi habis:** jika dashboard redirect ke login, tile menampilkan halaman login. Catat sebagai risiko; buka tab baru.
8. **Reload halaman:** semua kartu kembali ke loading lalu aktif; tidak ada state nyangkut.
9. **Tanpa JavaScript:** kartu menampilkan "Memuat dashboard…" dan tombol buka tetap bisa diklik. (Opsional: tambahkan `<noscript>` singkat.)

### 16.3 Kolom Bug yang Sering Muncul

| Gejala | Penyebab umum | Perbaikan |
|---|---|---|
| Halaman masih scroll di 1080p | `--mu-topnav-h` salah atau override `.main-content` tidak aktif | Ukur topnav; pastikan file CSS dimuat setelah tema |
| Iframe berhenti 1–2 px dari tepi | Pembulatan scale | `Math.round` untuk tinggi; boleh tambah 1 px pada lebar |
| Roller muncul di dalam tile | `scrolling="no"` hilang atau tinggi iframe statis | Pastikan algoritma fit dipakai |
| Kartu tidak menyusut | `min-height: 0` hilang di grid/flex | Tambahkan di kartu dan viewport |
| Outline fokus tak terlihat | Tema menghapus outline tombol | Pastikan rule `:focus-visible` dimuat setelah tema |
| Ringkasan salah hitung | `data-state` tidak ter-update | Periksa `setState` dipanggil di semua transisi |

---

## 17. Risiko, Keterbatasan, dan Pertanyaan Terbuka

### 17.1 Risiko Teknis

| Risiko | Dampak | Mitigasi |
|---|---|---|
| `iotelectricity.bbi-apparel.com` mengirim `X-Frame-Options`/CSP `frame-ancestors` | Iframe menampilkan halaman penolakan browser | Minta admin mengizinkan domain portal; sementara itu tombol buka selalu tersedia. Bila tetap ditolak, set slot menjadi kosong dengan catatan. |
| Portal HTTPS, dashboard HTTP | Mixed content diblokir | Gunakan URL https atau same-origin relatif |
| Sesi/auth berbeda antar domain | Tile menampilkan halaman login | Samakan domain atau SSO; sediakan tombol buka |
| Enam iframe berat (Highcharts + MQTT) | CPU/GPU NOC tinggi | Stagger 250 ms; turunkan `design_width` ke 1440; uji di PC NOC sebenarnya |
| Tinggi topnav berbeda dari asumsi | Scroll muncul | Ukur dan sesuaikan `--mu-topnav-h` |
| Dashboard terlalu tinggi untuk rasio tile | Konten bawah terpotong | Rasio iframe dinamis; prioritaskan konten atas; pertimbangkan mode embed (follow-up) |
| `load` event palsu untuk iframe yang diblokir | Status salah "Aktif" | Batasan browser; uji manual; tombol buka sebagai jalur utama |

### 17.2 Pertanyaan Terbuka (konfirmasi sebelum/selama implementasi)

1. Apakah portal dan dashboard benar-benar satu domain? Jika tidak, URL absolut mana yang dipakai?
2. Apakah `iotelectricity.bbi-apparel.com` mengizinkan iframe? Perlu bukti uji.
3. URL dashboard Energy AOI 1 kapan tersedia?
4. Apakah perlu mode kiosk (menyembunyikan sidebar/topnav)?
5. Apakah tombol buka sebaiknya tab baru (default sekarang) atau tab yang sama?
6. Zona waktu: pakai waktu perangkat (default) atau dipaksa WIB?
7. Apakah dashboard sumber akan ditambah `?embed=1` untuk menyembunyikan header/logonya?

Jangan mengubah URL pada config tanpa keputusan pemilik produk.

---

## 18. Non-Goals (Tidak Dikerjakan di Iterasi Ini)

- Redesain sidebar, topnav, atau logo.
- Mengubah halaman dashboard sumber (aoi1/aoi2/aoi5/energy) — kecuali follow-up mode embed.
- Tema gelap atau sakelar tema.
- Data agregat baru dari database (suhu rata-rata, total daya, dsb.).
- Autentikasi/otorisasi baru.
- Animasi dekoratif, grafik latar, atau ilustrasi.
- Perubahan pada script auto-save MQTT background.

---

## 19. Follow-Up Opsional (Setelah v1 Stabil)

1. **Mode embed dashboard (`?embed=1`).** Di setiap halaman dashboard same-origin, baca parameter dan sembunyikan header logo/judul serta kurangi padding agar isi tile lebih lega. File kandidat: `dashboard/temperature/aoi1/index.php`, `dashboard/temperature/aoi2/index.php`, `dashboard/temperature/aoi5/index.php`, `dashboard/energy/aoi2/index.php`. Host `iotelectricity` tidak dapat diubah.
2. **Kiosk mode.** Parameter `?kiosk=1` untuk menyembunyikan sidebar dan topnav pada TV.
3. **Health check sebenarnya.** Endpoint ringan yang melaporkan kesegaran data (bukan hanya iframe load), lalu tampilkan di footer.
4. **Reload harian terjadwal** pukul 02:00 untuk menyegarkan sesi.
5. **Dukungan 7–9 slot.** Jika dashboard bertambah, ubah makro grid ke 3 × 3 dan sesuaikan breakpoint.
6. **Konfigurasi via database/admin** agar slot bisa diubah tanpa edit file.
7. **Pause iframe di luar jam operasional** untuk menghemat resource (butuh keputusan operasional).

---

## 20. Ringkasan File yang Berubah

| File | Aksi | Isi |
|---|---|---|
| `index.php` | Ubah | `lang="id"`, body `mu-page`, link CSS/JS baru, markup `<main>` baru; hapus style/script inline menu |
| `config/menu_utama.php` | Baru | Array 6 slot (id, judul, area, url, design_width) |
| `css/menu-utama.css` | Baru | Token + seluruh komponen `.mu-*` + responsif |
| `js/menu-utama.js` | Baru | Load stagger, fit iframe, state machine, retry, jam, ringkasan |
| `plans/PLANNING_IMPROVEMENT_MENU_UTAMA_LIVE_VIEW.md` | Baru | Dokumen ini |

Selesai. Jika ada keputusan yang menyimpang dari dokumen ini, perbarui dokumen terlebih dahulu agar implementasi berikutnya tetap konsisten.

---

## 21. Addendum: Sidebar Drawer (Post-v1 Perbaikan Responsif)

**Trigger**: Permintaan pengguna untuk menambahkan navigasi sidebar dinamis (show/hide) default tersembunyi, hamburger dengan animasi mulus, modern minimalis, tanpa bug.

**Perubahan Desain**:
- Sidebar dijadikan overlay drawer (bukan push), default hidden, dibuka via hamburger di topbar.
- Topbar diperluas menjadi 56px (`--mu-topnav-h: 3.5rem`), sticky, berisi hamburger, brand "Environment", label "Menu Utama".
- Drawer fixed, top 56px, lebar 16rem, transform offscreen → 0 (300ms open / 220ms close cubic-bezier), scrim blur 2px, z-index 1000/990/1010.
- Progressive enhancement: inline `<script>document.documentElement.classList.add('mu-js')</script>` di `<head>`; tanpa JS sidebar tetap terlihat seperti semula.
- Accessibility: `aria-expanded`/`aria-controls` pada toggle, `aria-label` pada drawer, focus trap (toggle + drawer), `inert` pada drawer/main, Esc/scrim/link tutup, kembalikan fokus ke toggle, `prefers-reduced-motion` nonaktifkan transisi.
- CSS specificity diperkuat agar override tema: `html.mu-js body.mu-page.vertical .main-content` margin-left 0, `min-height:0` di ≥1200px agar hover tema tidak menambah 56px tinggi.
- Scroll lock pada <1200px: kompensasi scrollbar 15px via `main.style.paddingRight` agar iframe tidak resize saat drawer buka/tutup.
- Hapus tombol toggle lama (terpotong di mobile) dan class `shadow` dari aside.

**File yang Berubah**:
- `index.php` (markup topbar, sidebar, scrim, inline script)
- `css/menu-utama.css` (token, topbar, hamburger, drawer, scrim, reduced motion, focus, min-height fix)
- `js/menu-utama.js` (drawer logic, scrollbar kompensasi, focus target, trap, link close, submenu stay open)

**Verifikasi (Headless Chrome CDP, 4 breakpoint)**:
| Ukuran | Tutup | Buka | Hover tema | Escape | Scrim | Link eksternal | Submenu | Scroll lock | No layout shift |
|---|---|---|---|---|---|---|---|---|---|
| 1920×1080 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 1366×768 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 1024×768 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| 390×844 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

**Catatan Risiko**:
- Dashboard lokal (dev) lambat ~21s karena `mysqli` timeout; produksi seharusnya cepat.
- Cross-origin AOI5 energy (iotelectricity) load tapi konten abu-abu di env test; `X-Frame-Options` risiko eksternal.
- `design_width: 1600` pada slot; saat sidebar hidden lebar kartu bertambah, rasio aspek view semakin lebar → iframe logical height mengecil; jika terlihat pemotongan, pertimbangkan menaikkan `design_width` ke 1920 setelah review visual.

---

**File Akhir yang Berubah (Rincian)**:
| File | Aksi |
|---|---|
| `index.php` | Ubah: markup topbar, sidebar, scrim, inline mu-js |
| `css/menu-utama.css` | Ubah: token, topbar, hamburger, drawer, scrim, reduced motion, focus, min-height |
| `js/menu-utama.js` | Ubah: drawer logic, scrollbar compensation, focus, trap |
| `plans/PLANNING_IMPROVEMENT_MENU_UTAMA_LIVE_VIEW.md` | Tambah: addendum ini |

Lakukan `php -l index.php`, `node --check js/menu-utama.js`, CSS brace balance → **lolos**.
