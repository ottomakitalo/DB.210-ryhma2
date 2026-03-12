<?php
require 'db.php';
require_once('navigation.php');
require_once('laskuluettelo.php');
require_once('lasku_data.php');

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

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Muokkaa tehtäviä laskulle <?= htmlspecialchars($id) ?></title>
</head>
<body>
<?php require 'lasku.php'; ?>
<h2>Muokkaa tehtäviä laskulle <?= htmlspecialchars($id) ?></h2>
<form method="post" class="muokkaa-tehtavia">
    <h4>Tehtävät</h4>
    <table border="1" cellpadding="8" class="tehtavat">
        <tr>
            <th>Tehtävä</th>
            <th>Tunnit</th>
            <th>Tuntihinta</th>
            <th>Alennusprosentti</th>
        </tr>

        <?php foreach($kaikki_tehtavat as $tid => $tehtava): ?>
        <tr>
            <td><?= htmlspecialchars($tehtava['tehtava']) ?></td>
            <td>
                <div>
                    <input
                        class="tarvike-input"
                        type="number"
                        name="tunnit[<?= $tid ?>]"
                        placeholder="0"
                        min="0"
                        value="<?= isset($_POST['tunnit'][$tid]) ? (int)$_POST['tunnit'][$tid] : 0 ?>">
                </div>
            </td>
            <td>
                <?= number_format($tehtava['tuntihinta'], 2) ?> €
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
        <button type="submit" name="muokkaa_tehtavia">Tallenna</button>
    </p>
</form>
</body>
</html>