<?php
require 'db.php';
require_once('navigation.php');
require_once('laskuluettelo.php');
require_once('lasku.php');

// Hae kaikki tarvikkeet
$kaikki_tarvikkeet = [];
$q = pg_query($yhteys,
    "SELECT tv.id, tv.nimi, tv.yksikko, tv.sis_hinta, ty.alv_prosentti
     FROM tarvike tv
     JOIN tyyppi ty ON ty.nimi = tv.tyyppi_nimi
     ORDER BY tv.id");

while ($row = pg_fetch_assoc($q)) {
    $kaikki_tarvikkeet[(int)$row['id']] = [
        'tarvike' => $row['nimi'],
        'yksikkö' => $row['yksikko'],
        'hinta'   => (float)$row['sis_hinta'],
        'alv'     => (float)$row['alv_prosentti'] * 100
    ];
}

// Functio tehty Copilotin avustuksella
if (isset($_POST['muokkaa_tarvikkeita'])) {
    $maarat = $_POST['maara'] ?? [];
    $alennukset = $_POST['alennus'] ?? [];

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
    exit;
}

?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Muokkaa tarvikkeita laskulle <?= htmlspecialchars($id) ?></title>
</head>
<body>

<h2>Muokkaa tarvikkeita laskulle <?= htmlspecialchars($id) ?></h2>
<form method="post" class="muokkaa-tarvikkeita">
    <h4>Tarvikkeet</h4>
    <table border="1" cellpadding="8" class="tarvikkeet">
        <tr>
            <th>Tarvike</th>
            <th>Määrä</th>
            <th>Alennusprosentti</th>
        </tr>

        <?php foreach($kaikki_tarvikkeet as $tid => $tarvike): ?>
        <tr>
            <td><?= htmlspecialchars($tarvike['tarvike']) ?></td>
            <td>
                <div>
                    <input
                        class="tarvike-input"
                        type="number"
                        name="maara[<?= $tid ?>]"
                        placeholder="0"
                        min="0"
                        value="<?= isset($_POST['maara'][$tid]) ? (int)$_POST['maara'][$tid] : 0 ?>">
                    <span><?= htmlspecialchars($tarvike['yksikkö']) ?></span>
                </div>
            </td>
            <td>
                <div>
                    <input
                        class="alennus-input"
                        type="number"
                        name="alennus[<?= $tid ?>]"
                        placeholder="0"
                        min="0"
                        max="100"
                        value="<?= isset($_POST['alennus'][$tid]) ? (float)$_POST['alennus'][$tid] : 0 ?>">
                    <span>%</span>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p>
        <button type="submit" name="muokkaa_tarvikkeita">Tallenna</button>
    </p>
</form>
</body>
</html>