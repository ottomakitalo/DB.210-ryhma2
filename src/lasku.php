<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require_once('laskuluettelo.php');
require_once('data/lasku_data.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['laskuta'])) {
    if ($id === null) {
        echo "Laskua ei löytynyt.";
        exit;
    }

    $annettu = date('Y-m-d');
    // Eräpäivä 14 päivää annettu-pvm:stä
    $erapvm = date('Y-m-d', strtotime('+14 days'));

    $res = pg_query_params(
        $yhteys,
        'UPDATE lasku SET annettu_pvm = $1, era_pvm = $2 WHERE id = $3',
        [$annettu, $erapvm, $id]
    );

    if ($res) {
        header("Location: lasku.php?id=" . urlencode($id));
        exit();
    } else {
        echo "Päivitys epäonnistui.";
        exit();
    }
}

?>


<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Lasku <?= $id ?></title>
</head>
<body>

<h2>Lasku <?= htmlspecialchars($id) ?></h2>

<p><strong>Asiakas:</strong> <?= $lasku['asiakas'] ?></p>
<p><strong>Työkohde:</strong> <?= $lasku['kohde'] ?></p>
<p><strong>Tyyppi:</strong> <?= $lasku['tyyppi'] ?></p>
<p><strong>Päiväys:</strong> <?= $lasku['pvm'] ?: $lasku['luotu'] ?></p>
<p><strong>Eräpäivä:</strong> <?= $lasku['erapvm'] ?: '' ?></p>
<p><strong>Summa:</strong> <?= $lasku['yhteensä'] ?></p>

<?php if($tarvikkeet !== []): ?>
    <h3>Tarvikkeet</h3>
    <table border="1" cellpadding="8" class="tarvikkeet">
        <tr>
            <th>Tarvike</th>
            <th>Määrä</th>
            <th>Yksikkö</th>
            <?php if ($lasku['tyyppi'] !== 'Urakka'): ?>
                <th>Alennus</th>
            <?php endif; ?>
        </tr>

        <?php foreach($tarvikkeet as $tarvike): ?>
        <tr>
            <td><?= $tarvike['tarvike'] ?></td>
            <td><?= $tarvike['maara'] ?></td>
            <td><?= $tarvike['yksikko'] ?></td>
            <?php if ($lasku['tyyppi'] !== 'Urakka'): ?>
                <td><?= $tarvike['alennus'] ?>%</td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Ei tarvikkeita.</p>
<?php endif; ?>

<?php if($tyotehtavat !== []): ?>
    <h3>Työtehtävät</h3>
    <table border="1" cellpadding="8" class="tarvikkeet">
        <tr>
            <th>Työtehtävä</th>
            <th>Tunnit</th>
            <th>Alennus</th>
        </tr>

        <?php foreach($tyotehtavat as $tyotehtava): ?>
        <tr>
            <td><?= $tyotehtava['tehtava'] ?></td>
            <td><?= $tyotehtava['tunnit'] ?></td>
            <td><?= $tyotehtava['alennus'] ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if($lasku['erapvm'] === ''): ?>
    <form method="post" action="lasku.php?id=<?= $id ?>">
        <div class="submit-button-container">
            <button type="submit" name="laskuta">Laskuta lasku</button>
            <div>
                <input type="checkbox" name="valmis" value="valmis" id="valmis" required>
                <label for="valmis">Valmis laskutettavaksi</label>
            </div>
        </div>
    </form>
<?php if($lasku['tyyppi'] !== 'Urakka'): ?>
    <p><a href="muokkaa_tarvikkeita.php?id=<?= $id ?>">Muokkaa tarvikkeita</a></p>
    <p><a href="muokkaa_tyotehtavia.php?id=<?= $id ?>">Muokkaa työtehtäviä</a></p>
<?php endif; ?>
<?php endif; ?>
</body>
</html>