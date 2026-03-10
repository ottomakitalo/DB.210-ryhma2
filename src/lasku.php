<?php
require 'db.php';
require_once('navigation.php');
require_once('laskuluettelo.php');

// id saadaan parametrina URL:stä, esim. lasku.php?id=1
$id = $_GET['id'] ?? null;

if ($id === null || !array_key_exists($id, $laskut)) {
    echo "Laskua ei löytynyt.";
    exit;
}

$lasku = $laskut[$id];

// Hae tarvikkeet laskulle
$tarvikkeet = [];
$q = pg_query($yhteys,
    "SELECT tv.id, tv.nimi, tvt.maara, tv.yksikko, tvt.alennus
    FROM tarvike tv
    JOIN tarvikkeet tvt ON tvt.tarvike_id = tv.id
    JOIN tyosuoritus ts ON ts.id = tvt.tyosuoritus_id
    WHERE ts.id = (SELECT tyosuoritus_id FROM lasku WHERE id = $id)");

while ($row = pg_fetch_assoc($q)) {
    $tarvikkeet[$row['id']] = [
        'tarvike' => $row['nimi'],
        'maara'   => (float)$row['maara'],
        'yksikko' => $row['yksikko'],
        'alennus' => (float)$row['alennus']
    ];
}

// Hae työtehtävät laskulle
$tyotehtavat = [];
$q = pg_query($yhteys,
    "SELECT te.id, te.nimi, tet.tunnit, tet.alennus
    FROM tyotehtava te
    JOIN tehtavat tet ON tet.tyotehtava_id = te.id
    JOIN tyosuoritus ts ON ts.id = tet.tyosuoritus_id
    WHERE ts.id = (SELECT tyosuoritus_id FROM lasku WHERE id = $id)");

while ($row = pg_fetch_assoc($q)) {
    $tyotehtavat[$row['id']] = [
        'tehtava' => $row['nimi'],
        'tunnit'   => (float)$row['tunnit'],
        'alennus' => (float)$row['alennus']
    ];
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Lasku <?= $id ?></title>
</head>
<body>

<h2>Lasku <?= $id ?></h2>

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

</body>
</html>