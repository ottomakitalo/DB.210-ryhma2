<?php
require 'db.php';
require_once('navigation.php');
require_once('laskuluettelo.php');
require_once('data/lasku_data.php');
require_once('data/tyotehtavat_data.php');
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