<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once('turvallisuusraportti_luonti.php');
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Turvallisuusraportti</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/turvallisuusraportti.css">
    <link rel="stylesheet" href="styles/taulu.css">
</head>
<body>
    <div class="content-container">
        <a href="turvallisuusraportti.php" class="link-button">Takaisin turvallisuusraportin luontiin</a>
        <?php if(empty($_SESSION['turvallisuusraportti'])): ?>
        <p><strong>Turvallisuusraporttia ei olemassa. Luo ensin turvallisuusraportti.</strong></p>
        <?php else: ?>
        <h2>Turvallisuusraportti</h2>
        <table class="turvallisuusraportti">
            <tr>
                <th>Asiakas</th>
                <th>Työkohteen osoite</th>
                <th>Tarvike</th>
                <th>Tarvikkeen merkki</th>
                <th>Tarvikkeiden määrä</th>
                <th>Tarvikkeen toimittaja</th>
            </tr>

            <?php foreach($_SESSION['turvallisuusraportti'] as $id => $rivi): ?>
            <tr>
                <td>
                    <div>
                        <span><?= $rivi['asiakas'] ?></span>    
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= $rivi['osoite'] ?></span>    
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= $rivi['tarvike'] ?></span>    
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= $rivi['merkki'] ?></span>    
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= $rivi['määrä'] ?></span>    
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= $rivi['toimittaja'] ?></span>    
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>