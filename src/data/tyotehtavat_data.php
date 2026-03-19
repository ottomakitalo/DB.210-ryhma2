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

// Functio tehty Copilotin avustuksella
if (isset($_POST['muokkaa_tehtavia'])) {
    $post_tunnit = $_POST['tunnit'] ?? [];
    $post_alennukset = $_POST['alennus'] ?? [];

    // Poista vanhat tehtävät laskulta
    $del = pg_query_params($yhteys, "DELETE FROM tehtavat WHERE tyosuoritus_id = (SELECT tyosuoritus_id FROM lasku WHERE id = $1)", [$id]);
    if ($del === false) {
        die('Poisto epäonnistui: ' . pg_last_error($yhteys));
    }

    // Lisää uudet tehtävät (vain kun tunnit > 0)
    foreach ($kaikki_tehtavat as $tehtava_id => $tehtava) {
        $tunnit_arvo = isset($post_tunnit[$tehtava_id]) ? (int)$post_tunnit[$tehtava_id] : 0;
        if ($tunnit_arvo <= 0) continue;
        $alennus = isset($post_alennukset[$tehtava_id]) ? (float)$post_alennukset[$tehtava_id] : 0.0;

        $insert = pg_query_params(
            $yhteys,
            "INSERT INTO tehtavat (tyosuoritus_id, tyotehtava_id, tunnit, alennus) VALUES ((SELECT tyosuoritus_id FROM lasku WHERE id = $1), $2, $3, $4)",
            [$id, $tehtava_id, $tunnit_arvo, $alennus]
        );

        if ($insert === false) {
            die("Tehtävän lisäys epäonnistui: " . pg_last_error($yhteys));
        }
    }

    header("Location: lasku.php?id=" . $id);
    exit;
}
?>