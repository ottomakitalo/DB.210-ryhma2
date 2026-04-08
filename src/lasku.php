<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maksa_lasku'])) {
    if ($id === null) {
        echo "Laskua ei löytynyt.";
        exit;
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

<div class="content-container">
<a href="laskut.php" class="link-button">Takaisin laskuihin</a>
<h2>Lasku <?= htmlspecialchars($id) ?></h2>

<p><strong>Asiakas:</strong> <?= $lasku['asiakas'] ?></p>
<p><strong>Työkohde:</strong> <?= $lasku['kohde'] ?></p>
<p><strong>Tyyppi:</strong> <?= $lasku['tyyppi'] ?></p>
<p><strong>Päiväys:</strong> <?= $lasku['pvm'] ?: $lasku['luotu'] ?></p>
<p><strong>Eräpäivä:</strong> <?= $lasku['erapvm'] ?: '' ?></p>
<p><strong>Summa:</strong> <?= number_format($lasku['yhteensä'], 2, ',', ' ') ?></p>
<p><strong>Status:</strong> <?= $lasku['status'] ?></p>
<?php if($lasku['maksettu_pvm'] !== ''): ?>
    <p><strong>Maksettu pvm:</strong> <?= $lasku['maksettu_pvm'] ?></p>
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
            <td><?= $tyotehtava['alennus'] . ' %' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
<p>Ei työtehtäviä.</p>
<?php endif; ?>
<?php if($lasku['erapvm'] === '' && $lasku['tyyppi'] !== 'Urakka'): ?>
    <p><a href="<?= $hrefTyotehtavat ?>" class="link-button">Muokkaa työtehtäviä</a></p>
<?php endif; ?>

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
                <td><?= $tarvike['alennus'] . ' %' ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
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
        echo "<p><span style='color:red;font-weight:bold;'>Perintä käynnissä</span></p>";
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

    <table>
        <tr>
            <th>Lisälasku</th>
            <th>Antopäivä</th>
            <th>Eräpäivä</th>
            <th>Summa</th>
            <th>Tyyppi</th>
        </tr>

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

        // Laske summa 
        $laskutuslisa = 5.0;
        $viivastys = 0;

        if (!empty($r['edellinen_id'])) {
            $alkuperainen_summa = (float)$lasku['yhteensä'];
            $era_pvm = new DateTime($lasku['erapvm']);
            $nyt = new DateTime();
            $paivia = max(0, $era_pvm->diff($nyt)->days);

            $viivastys = ($alkuperainen_summa * 0.16 * $paivia) / 365.0;
        }

        $summa = $laskutuslisa + $viivastys;
        $tyyppi = $r['edellinen_id'] ? "Karhulasku" : "Muistutuslasku";
        ?>

        <tr>
            <td><?= $jarjestys ?></td>
            <td><?= date('d.m.Y', strtotime($r['annettu_pvm'])) ?></td>
            <td><?= date('d.m.Y', strtotime($r['era_pvm'])) ?></td>
            <td><?= number_format($summa, 2, ',', ' ') ?> €</td>
            <td><?= $tyyppi ?></td>
        </tr>

    <?php endwhile; ?>
    </table>
<?php
}
?>

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
    // Muutetaan eräpäivä DateTime-muotoon
    $era = DateTime::createFromFormat('d.m.Y', $lasku['erapvm']);
    $nyt = new DateTime();

    // Näytetään nappi vain, jos eräpäivä on mennyt
    if ($era < $nyt) {
        $naytaLisapainike = true;
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