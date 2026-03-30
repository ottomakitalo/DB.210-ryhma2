<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require_once('data/laskut_data.php');
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Laskut</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/laskuluettelo.css">
</head>
<body>
    <div class="content-container">
        <h2>Laskut</h2>
        <table>
            <tr>
                <th>Lasku</th>
                <th>Asiakas</th>
                <th>Työkohde</th>
                <th>Tyyppi</th>
                <th>Päiväys</th>
                <th>Eräpäivä</th>
                <th>Status</th>
                <th>Summa</th>
            </tr>

            <?php foreach($laskut as $id => $lasku): ?>
            <tr>
                <td><a href="lasku.php?id=<?= $id ?>"><?= $id ?></a></td>
                <td><?= $lasku['asiakas'] ?></td>
                <td><?= $lasku['kohde'] ?></td>
                <td><?= $lasku['tyyppi'] ?></td>
                <td><?= $lasku['pvm'] ?: $lasku['luotu'] ?></td>
                <td><?= $lasku['erapvm'] ?: '' ?></td>
                <td><?= $lasku['status'] ?></td>
                <td><?= $lasku['yhteensä'] ?></td>
            </tr>
            <?php endforeach; ?>    
        </table>
    </div>
</body>
</html>