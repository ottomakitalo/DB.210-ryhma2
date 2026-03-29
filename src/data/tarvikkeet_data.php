<?php
if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require 'db.php';
require_once('paivita_summa.php');

// Hae kaikki tarvikkeet
$kaikki_tarvikkeet = [];
$q = pg_query($yhteys,
    "SELECT tv.id, tv.nimi, tv.yksikko, tv.sis_hinta, ty.alv_prosentti, tv.merkki, tv.toimittaja
     FROM tarvike tv
     JOIN tyyppi ty ON ty.nimi = tv.tyyppi_nimi
     ORDER BY tv.id");

while ($row = pg_fetch_assoc($q)) {
    $kaikki_tarvikkeet[(int)$row['id']] = [
        'tarvike'    => $row['nimi'],
        'yksikkö'    => $row['yksikko'],
        'hinta'      => (float)$row['sis_hinta'],
        'alv'        => (float)$row['alv_prosentti'] * 100,
        'merkki'     => $row['merkki'],
        'toimittaja' => $row['toimittaja']
    ];
}

// Functio tehty Copilotin avustuksella
if (isset($_POST['muokkaa_tarvikkeita'])) {
    $maarat = $_POST['maara'] ?? [];
    $alennukset = $_POST['alennus'] ?? [];
    $tyyppi = $_POST['tyyppi'] ?? [];

    // Poista vanhat tarvikkeet laskulta
    $del = pg_query_params($yhteys, "DELETE FROM tarvikkeet WHERE tyosuoritus_id = (SELECT tyosuoritus_id FROM lasku WHERE id = $1)", [$id]);
    if ($del === false) {
        die('Poisto epäonnistui: ' . pg_last_error($yhteys));
    }

    // Lisää uudet tarvikkeet (vain kun määrä > 0)
    foreach ($kaikki_tarvikkeet as $tarvike_id => $tarvike) {
        $maara = isset($maarat[$tarvike_id]) ? (int)$maarat[$tarvike_id] : 0;
        if ($maara <= 0) continue;
        $alennus = isset($alennukset[$tarvike_id]) ? (float)$alennukset[$tarvike_id] : 0.0;

        $insert = pg_query_params(
            $yhteys,
            "INSERT INTO tarvikkeet (tyosuoritus_id, tarvike_id, maara, alennus) VALUES ((SELECT tyosuoritus_id FROM lasku WHERE id = $1), $2, $3, $4)",
            [$id, $tarvike_id, $maara, $alennus]
        );

        if ($insert === false) {
            die("Tuotteen lisäys epäonnistui: " . pg_last_error($yhteys));
        }
    }

    // Urakkatyössä summa ei muutu, muuten päivitetään summa uusilla tiedoilla
    if ($tyyppi !== 'Urakka') {
        paivitaSumma($yhteys, $id);
    }


    header("Location: lasku.php?id=" . $id);
    exit;
}
?>