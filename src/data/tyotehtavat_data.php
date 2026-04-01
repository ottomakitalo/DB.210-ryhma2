<?php
if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require 'db.php';

// Hae kaikki tehtävät
$kaikki_tehtavat = [];
$q = pg_query($yhteys,
    "SELECT tt.id, tt.nimi, tt.tuntihinta
     FROM tyotehtava tt
     ORDER BY tt.id");

while ($row = pg_fetch_assoc($q)) {
    $kaikki_tehtavat[(int)$row['id']] = [
        'tehtava' => $row['nimi'],
        'tuntihinta'   => (float)$row['tuntihinta'],
    ];
}
?>