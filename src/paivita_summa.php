<?php
if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

function paivitaSumma($yhteys, $id) {
    // Laske tehtävien summa (24% alv)
    $tehtavat_query = pg_query_params($yhteys,
        "SELECT SUM(t.tunnit * tt.tuntihinta * (1 - t.alennus/100) * 1.24) AS summa
            FROM tehtavat t
            JOIN tyotehtava tt ON tt.id = t.tyotehtava_id
            WHERE t.tyosuoritus_id = (SELECT tyosuoritus_id FROM lasku WHERE id = $1)",
        [$id]
    );

    if ($tehtavat_query === false) {
        die('Tehtävien summahaku epäonnistui: ' . pg_last_error($yhteys));
    }

    $tehtavat_row = pg_fetch_assoc($tehtavat_query);
    $tehtavat_summa = (float)($tehtavat_row['summa'] ?? 0);

    // Laske tarvikkeiden summa (25% voittoprosentti + alv)
    $tarvikkeet_query = pg_query_params($yhteys,
        "SELECT SUM(t.maara * (tt.sis_hinta * 1.25) * (1 - (t.alennus/100)) * (1 + ty.alv_prosentti/100)) AS summa
            FROM tarvikkeet t
            JOIN tarvike tt ON tt.id = t.tarvike_id
            JOIN tyyppi ty ON ty.nimi = tt.tyyppi_nimi
            WHERE t.tyosuoritus_id = (SELECT tyosuoritus_id FROM lasku WHERE id = $1)",
        [$id]
    );

    if ($tarvikkeet_query === false) {
        die('Tarvikkeiden summahaku epäonnistui: ' . pg_last_error($yhteys));
    }

    $tarvikkeet_row = pg_fetch_assoc($tarvikkeet_query);
    $tarvikkeet_summa = (float)($tarvikkeet_row['summa'] ?? 0);

    // Laske kokonaisumma ja päivitä lasku
    $kokonais_summa = $tehtavat_summa + $tarvikkeet_summa;

    $update = pg_query_params($yhteys,
        "UPDATE lasku SET yhteensa = $1 WHERE id = $2",
        [$kokonais_summa, $id]
    );

    if ($update === false) {
        die('Laskun päivitys epäonnistui: ' . pg_last_error($yhteys));
    }
}
?>