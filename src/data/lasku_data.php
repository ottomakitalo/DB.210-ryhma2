<?php
require 'db.php';
require_once('laskut_data.php');

// id saadaan parametrina URL:stä, esim. lasku.php?id=1
$id = $_GET['id'] ?? null;

if ($id === null || !array_key_exists($id, $laskut)) {
    echo "Laskua ei löytynyt.";
    exit;
}

$lasku = $laskut[$id];

// Hae tarvikkeet laskulle
$tarvikkeet = [];
$q = pg_query_params(
    $yhteys,
    "SELECT tv.id, tv.nimi, tvt.maara, tv.yksikko, tvt.alennus
     FROM tarvike tv
     JOIN tarvikkeet tvt ON tvt.tarvike_id = tv.id
     JOIN tyosuoritus ts ON ts.id = tvt.tyosuoritus_id
     WHERE ts.id = (
        SELECT tyosuoritus_id FROM lasku WHERE id = $1
     )",
    [$id]
);

while ($row = pg_fetch_assoc($q)) {
    $tarvikkeet[$row['id']] = [
        'id' => $row['id'],
        'tarvike' => $row['nimi'],
        'maara'   => (float)$row['maara'],
        'yksikko' => $row['yksikko'],
        'alennus' => (float)$row['alennus']
    ];
}

// Hae työtehtävät laskulle
$tyotehtavat = [];
$q = pg_query_params(
    $yhteys,
    "SELECT te.id, te.nimi, tet.tunnit, tet.alennus
    FROM tyotehtava te
    JOIN tehtavat tet ON tet.tyotehtava_id = te.id
    JOIN tyosuoritus ts ON ts.id = tet.tyosuoritus_id
    WHERE ts.id = (
        SELECT tyosuoritus_id FROM lasku WHERE id = $1
    )",
    [$id]
);

while ($row = pg_fetch_assoc($q)) {
    $tyotehtavat[$row['id']] = [
        'tehtava' => $row['nimi'],
        'tunnit'   => (float)$row['tunnit'],
        'alennus' => (float)$row['alennus']
    ];
}
?>