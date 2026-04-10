<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin') {
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
    <link rel="stylesheet" href="styles/taulu.css">
</head>
<body>
    <div class="content-container">
        <h2>Laskut</h2>
        <a href="laskut_hinta_arvio.php" class="link-button">Siirry laskun luontiin</a>
        <h3>Laskuluettelo</h3>
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
                <th>Viimeisin maksamaton lisälasku</th>
            </tr>
            <!-- Käytetty Copilotia apuna -->
            <?php foreach($laskut as $id => $lasku): ?>
                <?php
                $styleEra = "";
                if ($lasku['status'] === 'Avoinna' && !empty($lasku['erapvm'])) {
                    $era = DateTime::createFromFormat('d.m.Y', $lasku['erapvm']);
                    //Eräpäivä punaiseksi, jos se on mennyt jo               
                    if ($era < new DateTime()) {
                        $styleEra = 'style="color:red;font-weight:bold"';
                    }
                }
            ?>
            <tr>
                <td><a href="lasku.php?id=<?= $id ?>" class="link-button"><?= $id ?></a></td>
                <td><?= $lasku['asiakas'] ?></td>
                <td><?= $lasku['kohde'] ?></td>
                <td><?= $lasku['tyyppi'] ?></td>
                <td><?= $lasku['pvm'] ?: $lasku['luotu'] ?></td> 
                <td <?= $styleEra ?>>
                    <?= !empty($lasku['erapvm']) ? $lasku['erapvm'] : '-' ?>
                </td>
                <td><?= $lasku['status'] ?></td>
                <td><?= number_format($lasku['yhteensä'], 2, ',', ' ') ?> €</td>

                <td>
                    <?php if ($lasku['status'] === 'Maksettu'): ?>
                            -
                        <?php elseif ($lasku['lisalaskuja'] == 0): ?>
                            -
                        <?php else: ?>

                        <?php
                        $ll = $lasku['lisalaskuja'];

                        if ($ll == 1) {
                            echo "Muistutuslasku";
                        } else {
                            $karhu_nro = $ll - 1;
                            echo $karhu_nro . ". karhulasku";
                        }

                        // summa ja erä
                        if ($ll > 0) {
                            echo "<br>" . number_format($lasku['lisalasku_summa'], 2, ',', ' ') . " €";
                            echo "<br><span style='color:#E68200;font-weight:bold;'>Eräpäivä: " 
                                    . date('d.m.Y', strtotime($lasku['lisalasku_erapvm'])) 
                                    . "</span>";
                        }
                        ?>


                    <?php endif; ?>
                </td>

            </tr>
            <?php endforeach; ?>    
        </table>
    </div>
</body>
</html>