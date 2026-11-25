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
