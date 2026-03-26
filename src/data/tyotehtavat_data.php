<?php
if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require 'db.php';
require_once('paivita_summa.php');

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

// Functio tehty Copilotin avustuksella
if (isset($_POST['muokkaa_tehtavia'])) {
    $tunnit = $_POST['tunnit'] ?? [];
    $alennukset = $_POST['alennus'] ?? [];
    $tyyppi = $_POST['tyyppi'] ?? [];

    // Poista vanhat tehtävät laskulta
    $del = pg_query_params($yhteys, "DELETE FROM tehtavat WHERE tyosuoritus_id = (SELECT tyosuoritus_id FROM lasku WHERE id = $1)", [$id]);
    if ($del === false) {
        die('Poisto epäonnistui: ' . pg_last_error($yhteys));
    }

    // Lisää uudet tehtävät (vain kun tunnit > 0)
    foreach ($kaikki_tehtavat as $tehtava_id => $tehtava) {
        $tunnit_arvo = isset($tunnit[$tehtava_id]) ? (int)$tunnit[$tehtava_id] : 0;
        if ($tunnit_arvo <= 0) continue;
        $alennus = isset($alennukset[$tehtava_id]) ? (float)$alennukset[$tehtava_id] : 0.0;

        $insert = pg_query_params(
            $yhteys,
            "INSERT INTO tehtavat (tyosuoritus_id, tyotehtava_id, tunnit, alennus) VALUES ((SELECT tyosuoritus_id FROM lasku WHERE id = $1), $2, $3, $4)",
            [$id, $tehtava_id, $tunnit_arvo, $alennus]
        );

        if ($insert === false) {
            die("Tehtävän lisäys epäonnistui: " . pg_last_error($yhteys));
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