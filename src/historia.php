<?php
require 'db.php';
require_once('navigation.php');
if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require_once('data/historia_data.php');
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Historia</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/taulu.css">
</head>

<body>
    <div class="content-container historia">
        <h2>Tarvikehistoria</h2>
        <table>
            <tr>
                <th>Tarvike</th>
                <th>Merkki</th>
                <th>Toimittaja</th>
                <th>Sisäänottohinta</th>
                <th>Yksikkö</th>
                <th>Alv</th>
                <th>Poistettu/muokattu pvm</th>
            </tr>
            <?php foreach ($historia as $tarvike): ?>
            <tr>
                <td><?= htmlspecialchars($tarvike['tarvike']) ?></td>
                <td><?= htmlspecialchars($tarvike['merkki']) ?></td>
                <td><?= htmlspecialchars($tarvike['toimittaja']) ?></td>
                <td><?= htmlspecialchars($tarvike['hinta']) ?> €</td>
                <td><?= htmlspecialchars($tarvike['yksikkö']) ?></td>
                <td><?= htmlspecialchars($tarvike['alv']) ?> %</td>
                <td><?= htmlspecialchars(date('d.m.Y', strtotime($tarvike['poistettu_pvm']))) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
