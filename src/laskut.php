<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once('data/laskut_data.php');
require_once 'lisalasku_funktiot.php';
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
    <div class="content-container laskuluettelo">
        <h2>Laskut</h2>
        <p>Tältä sivulta löydät kaikki laskut laskuluettelosta. Siirtyäksesi tietyn laskun tietoihin voit painaa kyseisen laskun numeroa.</p>
        <p>Luodaksesi uuden laskun, paina alla olevaa "Siirry laskun luontiin"-nappia. Laskun luonnin sivulta voit luoda uuden laskun luomalla ensin hinta-arvion kyseiselle työlle.</p>
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
                    $eraClass = "";

                    if ($lasku['status'] === 'Avoinna' && !empty($lasku['erapvm'])) {
                        $era = DateTime::createFromFormat('d.m.Y', $lasku['erapvm']);
                        $tanaan = new DateTime();
                        $tanaan->setTime(0, 0, 0);

                        // oranssi jos lisälaskuja
                        if ($lasku['lisalaskuja'] > 0) {
                            $eraClass = "erapvm-oranssi";
                        } else if ($era < $tanaan) {
                            // Menneet eräpäivät punaisena, jos ei olla vielä annettu lisälaskuja
                            $eraClass = "erapvm-punainen";
                        }

                }
            ?>
            <tr>
                <td><a href="lasku.php?id=<?= $id ?>" class="link-button"><?= $id ?></a></td>
                <td><?= $lasku['asiakas'] ?></td>
                <td><?= $lasku['kohde'] ?></td>
                <td><?= $lasku['tyyppi'] ?></td>
                <td><?= $lasku['pvm'] ?: $lasku['luotu'] ?></td> 
                <td class="<?= $eraClass ?>">
                    <?= !empty($lasku['erapvm']) ? $lasku['erapvm'] : '-' ?>
                </td>
                <td><?= $lasku['status'] ?></td>
                <td><?= number_format($lasku['yhteensä'], 2, ',', ' ') ?> €</td>

                <td>
                <?php
                if ($lasku['status'] === 'Avoinna' && $lasku['lisalaskuja'] > 0) {

                    $jarjestys = $lasku['lisalaskuja'];

                    // Hae uusimman lisälaskun päivämäärät
                    $q = pg_query_params(
                        $yhteys,
                        "SELECT annettu_pvm, era_pvm
                        FROM lisalasku
                        WHERE alkp_id = $1
                        ORDER BY id DESC
                        LIMIT 1",
                        [$id]
                    );

                    if ($r = pg_fetch_assoc($q)) {

                        $uusinSumma = laskeLisalaskunSumma(
                            (float)$lasku['yhteensä'],
                            $lasku['erapvm'],
                            $r['annettu_pvm'],
                            $jarjestys
                        );

                        // Tyyppiteksti
                        if ($jarjestys == 1) {
                            echo "Muistutuslasku<br>";
                        } else {
                            echo ($jarjestys - 1) . ". karhulasku<br>";
                        }

                        // Uusin lisälasku
                        echo number_format($uusinSumma, 2, ',', ' ') . " € ";

                        // Kokonaissumma (alkuperäinen + lisälasku)
                        $kokonais = $lasku['yhteensä'] + $uusinSumma;
                        echo "(yht. " . number_format($kokonais, 2, ',', ' ') . " €)<br>";
                        
                        $eraLisalasku = new DateTime($r['era_pvm']);
                        $tanaan = new DateTime();
                        $tanaan->setTime(0, 0, 0);
                        
                        // Oletuksena eräpäivä oranssi
                        $eraClass = "erapvm-oranssi";

                        // Erääntyneet eräpäivät punaiseksi
                        if ($eraLisalasku < $tanaan) {
                            $eraClass = "erapvm-punainen";
                        }

                        echo "<span class='$eraClass'>Eräpäivä: "
                        . date('d.m.Y', strtotime($r['era_pvm']))
                        . "</span>";
                    }

                } else {
                    echo "-";
                }
                ?>
                </td>

            </tr>
            <?php endforeach; ?>    
        </table>
    </div>
</body>
</html>