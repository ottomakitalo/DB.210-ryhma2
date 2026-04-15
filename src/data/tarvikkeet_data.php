<?php
require 'db.php';

// Hae kaikki tarvikkeet
$kaikki_tarvikkeet = [];
$q = pg_query($yhteys,
    "SELECT tv.id, tv.nimi, tv.yksikko, tv.sis_hinta, ty.alv_prosentti, tv.merkki, 
    tv.toimittaja, tv.varasto
     FROM tarvike tv
     JOIN tyyppi ty ON ty.nimi = tv.tyyppi_nimi
     ORDER BY tv.id");

while ($row = pg_fetch_assoc($q)) {
    $kaikki_tarvikkeet[(int)$row['id']] = [
        'id'         => (int)$row['id'],
        'tarvike'    => $row['nimi'],
        'yksikkö'    => $row['yksikko'],
        'hinta'      => (float)$row['sis_hinta'],
        'alv'        => (float)$row['alv_prosentti'] * 100,
        'merkki'     => $row['merkki'],
        'varasto'    => $row['varasto'],
        'toimittaja' => $row['toimittaja']
    ];
}
?>