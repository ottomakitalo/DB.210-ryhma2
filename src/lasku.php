<?php
require 'db.php';
require_once('navigation.php');
require_once('laskuluettelo.php');
require_once('data/lasku_data.php');
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
<p><strong>Päiväys:</strong> <?= $lasku['pvm'] ?></p>
<p><strong>Eräpäivä:</strong> <?= $lasku['erapvm'] ?></p>
<p><strong>Summa:</strong> <?= $lasku['yhteensä'] ?></p>

<?php if($tarvikkeet !== []): ?>
    <h3>Tarvikkeet</h3>
    <table border="1" cellpadding="8" class="tarvikkeet">
        <tr>
            <th>Tarvike</th>
            <th>Määrä</th>
            <th>Yksikkö</th>
            <th>Alennus</th>
        </tr>

        <?php foreach($tarvikkeet as $tarvike): ?>
        <tr>
            <td><?= $tarvike['tarvike'] ?></td>
            <td><?= $tarvike['maara'] ?></td>
            <td><?= $tarvike['yksikko'] ?></td>
            <td><?= $tarvike['alennus'] ?>%</td>
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

<p><a href="muokkaa_tarvikkeita.php?id=<?= $id ?>">Muokkaa tarvikkeita</a></p>

<?php if($lasku['tyyppi'] !== 'Urakka'): ?>
<p><a href="muokkaa_tyotehtavia.php?id=<?= $id ?>">Muokkaa työtehtäviä</a></p>
<?php endif; ?>

</body>
</html>