<?php
require 'db.php';
require_once('navigation.php');
require_once 'lisalasku_funktiot.php';

if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once('data/lasku_data.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['laskuta'])) {
    if ($id === null) {
        echo "Laskua ei löytynyt.";
        exit();
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maksa_lasku'])) {
    if ($id === null) {
        echo "Laskua ei löytynyt.";
        exit();
    }

    $maksettu_pvm = date('Y-m-d');

    $res = pg_query_params(
        $yhteys,
        'UPDATE lasku SET maksettu_pvm = $1, maksettu_status = $2 WHERE id = $3',
        [$maksettu_pvm, 1, $id]
    );

    if ($res) {
        header("Location: lasku.php?id=" . urlencode($id));
        exit();
    } else {
        echo "Päivitys epäonnistui.";
        exit();
    }
}

$myyntihintakerroin = 1.25;

$page = basename($_SERVER['PHP_SELF']);
$laskuPolku = "lasku.php?id=" . $id;
$tyotehtavatPolku = "muokkaa_tyotehtavia.php?id=" . $id;
$tarvikkeetPolku = "muokkaa_tarvikkeita.php?id=" . $id;


$hrefTyotehtavat = "";
$hrefTarvikkeet = "";
switch($page) {
    case "muokkaa_tyotehtavia.php":
        $hrefTyotehtavat = $laskuPolku;
        $hrefTarvikkeet = $tarvikkeetPolku;
        break;

    case "muokkaa_tarvikkeita.php":
        $hrefTyotehtavat = $tyotehtavatPolku;
        $hrefTarvikkeet = $laskuPolku;
        break;        

    default:
        $hrefTyotehtavat = $tyotehtavatPolku;
        $hrefTarvikkeet = $tarvikkeetPolku;
}

$nettosumma = 0;
$alvsumma = 0;
$kotitalousVahennys = 0;

$tuntityotNetto = 0;
$tuntityotAlv = 0;
$tunnitYhteensa = 0;
foreach($tyotehtavat as $tuntityo) {
    $kesto = $tuntityo['tunnit'];
    $alennusprosentti = $tuntityo['alennus'];

    if($kesto > 0) {
        $tunnitYhteensa += $kesto;

        $tuntityoNetto = ($kesto * $tuntityo['tuntihinta']) * (1 - ($alennusprosentti / 100));
        $tuntityoAlv = $tuntityoNetto * 0.24;
        
        $tuntityoYhteensa = $tuntityoNetto + $tuntityoAlv;
        
        $tuntityotNetto += $tuntityoNetto;
        $tuntityotAlv += $tuntityoAlv;
    }
}

$tarvikkeetNetto = 0;
$tarvikkeetAlv = 0;
foreach($tarvikkeet as $tarvike) {
    $kappaleMaara = $tarvike['maara'];
    $alennusprosentti = $tarvike['alennus'];

    if($kappaleMaara > 0) {
        $tarvikeNetto = ($kappaleMaara * ($tarvike['hinta'] * $myyntihintakerroin)) * (1 - ($alennusprosentti / 100));
        $tarvikeAlv = $tarvikeNetto * ($tarvike['alv'] / 100);

        $tarvikeYhteensa = $tarvikeNetto + $tarvikeAlv;

        $tarvikkeetNetto += $tarvikeNetto;
        $tarvikkeetAlv += $tarvikeAlv;
    }
}

$tyotyyppi = $lasku['tyyppi'];
$urakkahinta = NULL;
$urakkaAlennus = NULL;
if($tyotyyppi === 'Urakka') {
    $urakkahinta = $lasku['urakkahinta'];
    $urakkaAlennus = $lasku['urakka_alennus'];

    $urakkaNetto = $urakkahinta * (1 - $urakkaAlennus / 100);
    $urakkaAlv = $urakkaNetto * 0.24;

    $urakkaYhteensa = $urakkaNetto + $urakkaAlv;

    $nettosumma = $urakkaNetto + $tarvikkeetNetto;
    $alvsumma = $tarvikkeetAlv + $urakkaAlv;
    $kotitalousVahennys = $urakkaYhteensa;
}

else {
    $nettosumma = $tuntityotNetto + $tarvikkeetNetto;
    $alvsumma = $tuntityotAlv + $tarvikkeetAlv;
    $kotitalousVahennys = $tuntityotNetto + $tuntityotAlv;
}
?>


<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Lasku <?= $id ?></title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/taulu.css">
    <link rel="stylesheet" href="styles/laskut.css">
</head>
<body>

<div class="content-container lasku">
<a href="laskut.php" class="link-button">Takaisin laskuihin</a>
<h2>Lasku <?= htmlspecialchars($id) ?></h2>

<p><strong>Asiakas:</strong> <?= $lasku['asiakas'] ?></p>
<p><strong>Työkohde:</strong> <?= $lasku['kohde'] ?></p>
<p><strong>Tyyppi:</strong> <?= $lasku['tyyppi'] ?></p>
<p><strong>Päiväys:</strong> <?= $lasku['pvm'] ?: $lasku['luotu'] ?></p>
<p><strong>Eräpäivä:</strong> <?= $lasku['erapvm'] ?: '-' ?></p>
<p><strong>Status:</strong> <?= $lasku['status'] ?></p>
<?php if($lasku['maksettu_pvm'] !== ''): ?>
    <p><strong>Maksettu pvm:</strong> <?= $lasku['maksettu_pvm'] ?></p>
<?php endif; ?>

<h3>Työerittely</h3>
<?php if($tyotehtavat !== [] || $lasku['tyyppi'] === 'Urakka'): ?>
<div class="yhteenveto-container flex-container">
    <table>
        <thead>
            <tr>
                <th>Työtyyppi</th>
                <th>Tunnit</th>
                <th>Alv-%</th>
                <th>Alennus-%</th>
                <th>Nettosumma</th>
                <th>Alv</th>
                <th>Yhteensä</th>
            </tr>
        </thead>

        <tbody>
            <?php
            foreach($tyotehtavat as $tuntityo):
            $tuntityoNetto = ($tuntityo['tunnit'] * $tuntityo['tuntihinta']) * (1 - ($tuntityo['alennus'] / 100));
            $tuntityoAlv = $tuntityoNetto * 0.24;
            ?>
            <tr>
                <td>
                    <div>
                        <span><?= $tuntityo['tehtava'] ?></span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= $tuntityo['tunnit'] ?></span>
                        <span>h</span>
                    </div>
                </td>
                <?php if($lasku['tyyppi'] === 'Tuntityö'): ?>
                <td>
                    <div>
                        <span> 24 % </span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= $tuntityo['alennus'] . ' %' ?></span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= number_format($tuntityoNetto, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= number_format($tuntityoAlv, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= number_format($tuntityoNetto + $tuntityoAlv, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <td>
                    <div>
                        <span><?= $lasku['tyyppi'] === 'Urakka' ? 'Urakka' : 'Yhteensä:' ?></span>
                    </div>
                </td>
                <td>
                    <?php if($lasku['tyyppi'] === 'Tuntityö'): ?>
                    <div>
                        <span><?= $tunnitYhteensa ?></span>
                        <span>h</span>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($lasku['tyyppi'] === 'Urakka'): ?>
                    <div>
                        <span><?= '24 %' ?></span>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($lasku['tyyppi'] === 'Urakka'): ?>
                    <div>
                        <span><?= $lasku['urakka_alennus'] . ' %' ?></span>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <div>
                        <span><?= number_format($lasku['tyyppi'] === 'Urakka' ? $lasku['urakkahinta'] : $tuntityotNetto, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= number_format($lasku['tyyppi'] === 'Urakka' ? $lasku['urakkahinta'] * 0.24 : $tuntityotAlv, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= number_format($lasku['tyyppi'] === 'Urakka' ? $lasku['urakkahinta'] + ($lasku['urakkahinta'] * 0.24) : $tuntityotNetto + $tuntityotAlv, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
<?php else: ?>
<p>Ei työtehtäviä.</p>
<?php endif; ?>
<?php if($lasku['erapvm'] === '' && $lasku['tyyppi'] !== 'Urakka'): ?>
    <p><a href="<?= $hrefTyotehtavat ?>" class="link-button">Muokkaa työtehtäviä</a></p>
<?php endif; ?>

<h3>Tarvikkeet</h3>
<?php if($tarvikkeet !== []): ?>
<div class="yhteenveto-container flex-container">
    <table>
        <tr>
            <th>Tarvike</th>
            <th>Määrä</th>
            <th>Alv-%</th>
            <th>Alennus-%</th>
            <th>Nettosumma</th>
            <th>Alv</th>
            <th>Yhteensä</th>
        </tr>

        <?php 
        foreach($tarvikkeet as $tarvike):
            $tarvikeNetto = ($tarvike['maara'] * ($tarvike['hinta'] * $myyntihintakerroin)) * (1 - ($tarvike['alennus'] / 100));
            $tarvikeAlv = $tarvikeNetto * ($tarvike['alv'] / 100);
            ?>
        <tr>
            <td><div><span><?= $tarvike['tarvike'] ?></span></div></td>
            <td>
                <div>
                    <span><?= $tarvike['maara'] ?></span>
                    <span><?= $tarvike['yksikko'] ?></span>
                </div>
            </td>
            <td>
                <div>
                    <span><?= $tarvike['alv'] . ' %' ?></span>
                </div>
            </td>
            <td>
                <div>
                    <span><?= $tarvike['alennus'] . ' %' ?></span>
                </div>
            </td>
            <td>
                <div>
                    <span><?= number_format($tarvikeNetto, 2, ',', ' ') ?></span>
                    <span>€</span>
                </div>
            </td>
            <td>
                <div>
                    <span><?= number_format($tarvikeAlv, 2, ',', ' ') ?></span>
                    <span>€</span>
                </div>
            </td>
            <td>
                <div>
                    <span><?= number_format($tarvikeNetto + $tarvikeAlv, 2, ',', ' ') ?></span>
                    <span>€</span>
                </div>
            </td>
        </tr>
        <?php  ?>
        <?php endforeach; ?>
        <tfoot>
            <tr>
                <td>
                    <div>
                        <span>Yhteensä:</span>
                    </div>
                </td>
                <td></td>
                <td></td>
                <td></td>
                <td>
                    <div>
                        <span><?= number_format($tarvikkeetNetto, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= number_format($tarvikkeetAlv, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
                <td>
                    <div>
                        <span><?= number_format($tarvikkeetNetto + $tarvikkeetAlv, 2, ',', ' ') ?></span>
                        <span>€</span>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
<?php else: ?>
    <p>Ei tarvikkeita.</p>
<?php endif; ?>
<?php if($lasku['erapvm'] === '' && $lasku['tyyppi'] !== 'Urakka'): ?>
    <p><a href="<?= $hrefTarvikkeet ?>" class="link-button">Lisää tarvikkeita</a></p>
<?php endif; ?>

<h3>Lisälaskut</h3>

<?php
// Perinnän tila
if ($lasku['lisalaskuja'] > 0) {
    if ($lasku['status'] === 'Avoinna') {
        echo "<p><span style='color:#E68200;font-weight:bold;'>Perintä käynnissä</span></p>";
    } else {
        echo "<p><span style='color:green;font-weight:bold;'>Perintä valmis</span></p>";
    }

}
?>

<?php
$q_lisalaskut = pg_query_params(
    $yhteys,
    "SELECT id, annettu_pvm, era_pvm, maksettu_pvm, edellinen_id
     FROM lisalasku
     WHERE alkp_id = $1
     ORDER BY id",
    [$id]
);

if (!$q_lisalaskut) {
    echo "<p>Lisälaskujen haku epäonnistui.</p>";
} elseif (pg_num_rows($q_lisalaskut) == 0) {
    echo "<p>Ei lisälaskuja.</p>";
} else {
?>

    <table class="lisalasku-table">
        <tr>
            <th>Lisälasku</th>
            <th>Antopäivä</th>
            <th>Eräpäivä</th>
            <th>Summa (€)</th>
            <th>Tyyppi</th>
        </tr>

        
    <?php
    $viimeisinLisalaskuSumma = 0.0;
    ?>

    <!-- tehty copilotin avustuksella -->
    <?php while ($r = pg_fetch_assoc($q_lisalaskut)): ?>

        <?php
        // Laske lisälaskun järjestysnumero
        $jarjestys = pg_fetch_result(
            pg_query_params(
                $yhteys,
                "SELECT COUNT(*) 
                FROM lisalasku 
                WHERE alkp_id = $1 AND id <= $2",
                [$id, $r['id']]
            ),
            0,
            0
        );

        $summa = laskeLisalaskunSumma(
            (float)$lasku['yhteensä'],
            $lasku['erapvm'],
            $r['annettu_pvm'],
            $jarjestys
        );

        $viimeisinLisalaskuSumma = $summa;

        $tyyppi = $r['edellinen_id'] ? "Karhulasku" : "Muistutuslasku";
        ?>

        <tr>
            <td><?= $jarjestys ?></td>
            <td><?= date('d.m.Y', strtotime($r['annettu_pvm'])) ?></td>
            <td class="<?= $viivastynyt ?>"><?= date('d.m.Y', strtotime($r['era_pvm'])) ?></td>
            <td><?= number_format($summa, 2, ',', ' ') ?></td>
            <td><?= $tyyppi ?></td>
        </tr>

    <?php endwhile; ?>
    </table>
<?php
}
?>

<h3>Laskun hintaerittely</h3>
<div class="summat-container flex-container">
    <table>
        <tr>
            <th>Nettosumma</th>
            <th>Alv</th>
            <th>Yhteensä</th>
            <?php if ($lasku['lisalaskuja'] > 0): ?>
            <th>Yhteensä + erääntymismaksut</th>
            <?php endif; ?> 
            <th>Kotitalousvähennys</th>        
        </tr>
        <tr>
            <td><?= number_format($nettosumma, 2, ',', ' ') . ' €' ?></td>
            <td><?= number_format($alvsumma, 2, ',', ' ') . ' €' ?></td>
            <td><?= number_format(($nettosumma + $alvsumma), 2, ',', ' ') . ' €' ?></td>
            <?php if ($lasku['lisalaskuja'] > 0): ?>
            <td><?= number_format(($nettosumma + $alvsumma) + $viimeisinLisalaskuSumma, 2, ',', ' ') . ' €' ?></td>
            <?php endif; ?>
            <td><?= number_format($kotitalousVahennys, 2, ',', ' ') . ' €' ?></td>        
        </tr>                    
    </table>
</div>


<?php if($lasku['maksettu_pvm'] === '' && $lasku['erapvm'] !== ''): ?>
    <form method="post" action="lasku.php?id=<?= $id ?>">
        <div class="submit-button-container">
            <button type="submit" name="maksa_lasku">Merkitse maksetuksi</button>
            <div>
                <input type="checkbox" name="valmis" value="valmis" id="valmis" required>
                <label for="valmis">Lasku maksettu</label>
            </div>
        </div>
    </form>
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
<?php endif; ?>


<?php

$naytaLisapainike = false;

if ($lasku['status'] === 'Avoinna' && !empty($lasku['erapvm'])) {

    // Onko alkuperäinen lasku on erääntynyt?
    $alkpEra = DateTime::createFromFormat('d.m.Y', $lasku['erapvm']);
    $tanaan = new DateTime();
    $tanaan->setTime(0, 0, 0);

    if ($alkpEra < $tanaan) {

        if ($lasku['lisalaskuja'] == 0) {
            $naytaLisapainike = true;
        }

        // Jos on lisälaskuja, tarkistetaan onko edellisen lisälaskun erapv mennyt jo
        else {
            $q = pg_query_params(
                $yhteys,
                "SELECT era_pvm 
                 FROM lisalasku 
                 WHERE alkp_id = $1 
                 ORDER BY id DESC 
                 LIMIT 1",
                [$id]
            );

            if ($r = pg_fetch_assoc($q)) {
                $viimeisinEra = new DateTime($r['era_pvm']);
                if ($viimeisinEra < $tanaan) {
                    $naytaLisapainike = true;
                }
            }
        }
    }
}

?>

<?php if ($naytaLisapainike): ?>
<form action="luo_lisalasku.php" method="post" class="lisalasku_form">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit">Luo muistutus- / karhulasku</button>
</form>
<?php endif; ?>

</div>
</body>
</html>
