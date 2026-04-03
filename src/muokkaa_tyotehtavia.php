<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require_once('data/lasku_data.php');
require_once('data/tyotehtavat_data.php');
require_once('paivita_summa.php');

// Vain keskeneräisiä laskuja voi muokata
if ($lasku === null || $lasku['erapvm'] !== '') {
    exit();
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

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Muokkaa tehtäviä laskulle <?= htmlspecialchars($id) ?></title>
</head>
<body>
<?php require 'lasku.php'; ?>
<div class="content-container" style="margin-top: 20px">
<h2>Muokkaa tehtäviä laskulle <?= htmlspecialchars($id) ?></h2>
<form method="post" class="muokkaa-tehtavia">
    <input type="hidden" name="tyyppi" value="<?= htmlspecialchars($lasku['tyyppi'] ?? '') ?>">
    <h4>Tehtävät</h4>
    <table border="1" cellpadding="8" class="tehtavat">
        <tr>
            <th>Tehtävä</th>
            <th>Tunnit</th>
            <?php if ($lasku['tyyppi'] !== 'Urakka'): ?>
            <th>Tuntihinta (sis. alv)</th>
            <th>Alennusprosentti</th>
            <?php endif; ?>
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
                        value="<?= isset($_POST['tunnit'][$tid]) ? (int)$_POST['tunnit'][$tid] : 0 ?>"
                    >
                </div>
            </td>
            <?php if ($lasku['tyyppi'] !== 'Urakka'): ?>
            <td>
                <?= number_format($tehtava['tuntihinta'] * 1.24, 2) ?> €
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
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>

    <p>
        <button type="submit" name="muokkaa_tehtavia">Tallenna</button>
    </p>
</form>
</div>
<script>
    window.scrollTo({
        top: document.body.scrollHeight,
        behavior: 'smooth'
    });
</script>
</body>
</html>