<?php
if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

// Hae historiaan tallennetut tarvikkeet
$historia = [];
$q = pg_query($yhteys,
    "SELECT h.id, h.nimi, h.yksikko, h.sis_hinta, ty.alv_prosentti, h.merkki, 
    h.toimittaja, h.poistettu_pvm
     FROM tarvike_historia h
     JOIN tyyppi ty ON ty.nimi = h.tyyppi_nimi
     ORDER BY h.id");

while ($row = pg_fetch_assoc($q)) {
    $historia[(int)$row['id']] = [
        'tarvike'    => $row['nimi'],
        'yksikkö'    => $row['yksikko'],
        'hinta'      => (float)$row['sis_hinta'],
        'alv'        => (float)$row['alv_prosentti'] * 100,
        'merkki'     => $row['merkki'],
        'toimittaja' => $row['toimittaja'],
        'poistettu_pvm' => $row['poistettu_pvm']
    ];
}
?>