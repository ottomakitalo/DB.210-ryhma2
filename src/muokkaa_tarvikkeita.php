<?php
require 'db.php';
require_once('navigation.php');
require_once('laskuluettelo.php');
require_once('data/lasku_data.php');
require_once('data/tarvikkeet_data.php');

if ($lasku === null || $lasku['erapvm'] !== '') {
    exit();
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Muokkaa tarvikkeita laskulle <?= htmlspecialchars($id) ?></title>
</head>
<body>
<?php require 'lasku.php'; ?>
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