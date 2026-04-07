<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once('data/lasku_data.php');
require_once('data/tarvikkeet_data.php');
require_once('paivita_summa.php');

if ($lasku === null || $lasku['erapvm'] !== '') {
    exit();
}

// Functio tehty Copilotin avustuksella
if (isset($_POST['lisaa_tarvikkeita'])) {
    $maarat = $_POST['maara'] ?? [];
    $alennukset = $_POST['alennus'] ?? [];
    $tyyppi = $_POST['tyyppi'] ?? [];

    // Lisää uudet tarvikkeet (vain kun määrä > 0)
    foreach ($kaikki_tarvikkeet as $tarvike_id => $tarvike) {
        $maara = isset($maarat[$tarvike_id]) ? (int)$maarat[$tarvike_id] : 0;
        if ($maara <= 0) continue;
        $alennus = isset($alennukset[$tarvike_id]) ? (float)$alennukset[$tarvike_id] : 0.0;

        $insert = pg_query_params(
            $yhteys,
            "UPDATE tarvikkeet SET maara = maara + $3, alennus = $4 WHERE tyosuoritus_id = (SELECT tyosuoritus_id FROM lasku WHERE id = $1) AND tarvike_id = $2",
            [$id, $tarvike_id, $maara, $alennus]
        );

        if ($insert === false) {
            die("Tuotteen lisäys epäonnistui: " . pg_last_error($yhteys));
        }

        $updateVarasto = pg_query_params(
            $yhteys,
            "UPDATE tarvike SET varasto = varasto - $1 WHERE id = $2",
            [$maara, $tarvike_id]
        );

        if(!$updateVarasto) {
            die("Varaston päivitys epäonnistui: " . pg_last_error($yhteys));
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
    <title>Lisää tarvikkeita laskulle <?= htmlspecialchars($id) ?></title>
</head>
<body>
<?php require 'lasku.php'; ?>
<div class="content-container" style="margin-top: 20px">
<h2>Lisää tarvikkeita laskulle <?= htmlspecialchars($id) ?></h2>
<h4>Alennus päivitetään myös aikasemmille tarvikkeille</h4>
<form method="post" class="lisaa-tarvikkeita">
    <input type="hidden" name="tyyppi" value="<?= htmlspecialchars($lasku['tyyppi'] ?? '') ?>">
    <table border="1" cellpadding="8" class="tarvikkeet">
        <tr>
            <th>Tarvike</th>
            <th>Määrä</th>
            <?php if ($lasku['tyyppi'] !== 'Urakka'): ?>
            <th>Alennusprosentti</th>
            <?php endif; ?>
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
            <?php if ($lasku['tyyppi'] !== 'Urakka'): ?>
            <td>
                <div>
                    <input
                        class="alennus-input"
                        type="number"
                        name="alennus[<?= $tid ?>]"
                        placeholder="<?= isset($tarvikkeet[$tid]['alennus']) ? htmlspecialchars($tarvikkeet[$tid]['alennus']) : 0 ?>"
                        min="0"
                        max="100"
                        value="<?= isset($_POST['alennus'][$tid]) ? (float)$_POST['alennus'][$tid] : '' ?>"
                    <span>%</span>
                </div>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>

    <p>
        <button type="submit" name="lisaa_tarvikkeita">Tallenna</button>
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