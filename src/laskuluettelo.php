<?php
require 'db.php';
require_once('navigation.php');

$q = pg_query($yhteys,
"SELECT l.id, l.annettu_pvm, l.era_pvm, l.maksettu_status,
        a.nimi AS asiakas, k.osoite AS kohde,
        ts.tyotyyppi
 FROM lasku l
 JOIN asiakas a ON a.id = l.asiakas_id
 JOIN tyosuoritus ts ON ts.id = l.tyosuoritus_id
 JOIN tyokohde k ON k.id = ts.tyokohde_id
 ORDER BY l.annettu_pvm DESC"
);

$laskut = [];

while ($row = pg_fetch_assoc($q)) {
    $laskut[$row['id']] = [
        'id' => $row['id'],
        'asiakas' => $row['asiakas'],
        'kohde'   => $row['kohde'],
        'tyyppi'  => ($row['tyotyyppi'] === 'tunti' ? 'Tuntityö' : 'Urakka'),
        'pvm'     => date('d.m.Y', strtotime($row['annettu_pvm'])),
        'erapvm'  => date('d.m.Y', strtotime($row['era_pvm'])),
        'yhteensä' => '---'
    ];
}


?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Laskut</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/laskut.css">
</head>
<body>
    <h2>Laskut</h2>
    <table border="1" cellpadding="8" class="laskut">
        <tr>
            <th>Lasku</th>
            <th>Asiakas</th>
            <th>Työkohde</th>
            <th>Tyyppi</th>
            <th>Päiväys</th>
            <th>Eräpäivä</th>
            <th>Summa</th>
        </tr>

        <?php foreach($laskut as $id => $lasku): ?>
        <tr>
            <td><a href="lasku.php?id=<?= $id ?>"><?= $id ?></a></td>
            <td><?= $lasku['asiakas'] ?></td>
            <td><?= $lasku['kohde'] ?></td>
            <td><?= $lasku['tyyppi'] ?></td>
            <td><?= $lasku['pvm'] ?></td>
            <td><?= $lasku['erapvm'] ?></td>
            <td><?= $lasku['yhteensä'] ?></td>
        </tr>
        <?php endforeach; ?>    
    </table>

</body>
</html>