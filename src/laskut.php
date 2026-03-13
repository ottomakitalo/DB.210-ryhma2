<?php
require 'db.php';
require_once('navigation.php');


$asiakkaat = [];

// The following code for parsing the data from the database was created with guidance from copilot 

// Getting all the customers and their work sites.
$q = pg_query($yhteys,
    "SELECT id, nimi, osoite FROM asiakas ORDER BY id");

while ($row = pg_fetch_assoc($q)) {
    $asiakkaat[$row['id']] = [
        'asiakas' => $row['nimi'],
        'osoite' => $row['osoite'],
        'tyokohteet' => []
    ];
}

$q2 = pg_query($yhteys,
    "SELECT id, osoite, asiakas_id FROM tyokohde ORDER BY id");

while ($row = pg_fetch_assoc($q2)) {
    $asiakkaat[$row['asiakas_id']]['tyokohteet'][$row['id']] = [
        'osoite' => $row['osoite']
    ];
}


$tuntityohinnat = [];
$q = pg_query($yhteys,
    "SELECT nimi, tuntihinta FROM tyotehtava ORDER BY id");

while ($row = pg_fetch_assoc($q)) {
    $tuntityohinnat[$row['nimi']] = (float)$row['tuntihinta'];
}


$tarvikkeet = [];
$q = pg_query($yhteys,
    "SELECT tv.id, tv.nimi, tv.yksikko, tv.sis_hinta, ty.alv_prosentti
     FROM tarvike tv
     JOIN tyyppi ty ON ty.nimi = tv.tyyppi_nimi
     ORDER BY tv.id");

while ($row = pg_fetch_assoc($q)) {
    $tarvikkeet[$row['id']] = [
        'tarvike' => $row['nimi'],
        'yksikkö' => $row['yksikko'],
        'hinta'   => (float)$row['sis_hinta'],
        'alv'     => (float)$row['alv_prosentti'] * 100
    ];
}


$laskut = [];

$q = pg_query($yhteys,
"SELECT l.id, l.annettu_pvm, l.era_pvm, l.maksettu_status,
        a.nimi AS asiakas, k.osoite AS kohde,
        ts.tyotyyppi, urakkahinta
 FROM lasku l
 JOIN asiakas a ON a.id = l.asiakas_id
 JOIN tyosuoritus ts ON ts.id = l.tyosuoritus_id
 JOIN tyokohde k ON k.id = ts.tyokohde_id
 ORDER BY l.annettu_pvm DESC"
);


while ($row = pg_fetch_assoc($q)) {
    $yhteensa = $row['tyotyyppi'] === 'urakka' ? $row['urakkahinta'] : '---';
    $laskut[] = [
        'asiakas' => $row['asiakas'],
        'kohde'   => $row['kohde'],
        'tyyppi'  => ($row['tyotyyppi'] === 'tunti' ? 'Tuntityö' : 'Urakka'),
        'pvm'     => date('d.m.Y', strtotime($row['annettu_pvm'])),
        'erapvm'  => date('d.m.Y', strtotime($row['era_pvm'])),
        'yhteensä' => $yhteensa
    ];
}

require_once('luo_lasku.php');

// T1
if (isset($_POST['lisaa_tyokohde'])) {

    $asiakasId = intval($_POST['asiakas_id']);
    $osoite    = trim($_POST['osoite']);

    // Generate new id for työkohde, takes the current biggest id and adds 1
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM tyokohde"
    );
    $row = pg_fetch_assoc($result);
    $nextId = $row['id'];

    // Insert into the database
    
    $update = pg_query_params(
        $yhteys,
        "INSERT INTO tyokohde (id, osoite, asiakas_id)
         VALUES ($1, $2, $3)",
        [$nextId, $osoite, $asiakasId]
    );

    //Checking if the insert succeeded
    if ($update && (pg_affected_rows($update)>0))
        $msg = "Työkohde lisätty.";
    else
        die("Työkohteen lisäys epäonnistui: " . pg_last_error($yhteys));

    // Reload page
    header("Location: ".$_SERVER['PHP_SELF']."?t1ok=1");
    exit;
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
    
    <h2>Lisää uusi työkohde (T1)</h2>
    <form method="post" class="lisaa-tyokohde">

        <h4>Asiakas</h4>
        <div>
            <select name="asiakas_id" required>
                <option value="">Valitse asiakas</option>
                <?php foreach($asiakkaat as $asiakasid => $asiakas): ?>
                    <option value="<?= $asiakasid ?>">
                        <?= htmlspecialchars($asiakas['asiakas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <h4>Työkohteen osoite</h4>
        <input type="text" name="osoite" required>

        <br><br>
        <button type="submit" name="lisaa_tyokohde">Lisää työkohde</button>
    </form>

    <h2>Luo tuntityölasku</h2>
    <h3>Hinta-arvio</h3>
    <form method="post" class="hinta-arvio">
        <h4>Työkohde</h4>
        <div>
            <select name="tyokohde" required>
                <option value="">Valitse työkohde</option>
                <?php foreach($asiakkaat as $asiakasid => $asiakas) {
                    foreach($asiakas['tyokohteet'] as $työkohdeid => $työkohde) {
                        $value = $asiakasid . ':' . $työkohdeid;
                        $label = $asiakas['asiakas'] . ' - ' . $työkohde['osoite'];
                        echo "<option value=\"$value\">$label</option>";
                    }
                }?>
            </select>
        </div>

        <h4>Työtyyppi</h4>
        <div class="tyotyyppi-container">
            <div>
                <input type="radio" name="tyotyyppi" value="tunti" id="tunti" required>
                <label for="tunti">Tuntityö</label>
            </div>
            <div>
                <input type="radio" name="tyotyyppi" value="urakka" id="urakka" required>
                <label for="urakka">Urakka</label>
            </div>
        </div>

        <h5>Urakka</h5>
        <div class="urakkahinta-container">
            <div>
                <span>Urakkahinta:</span>
                <input
                    class="urakkahinta-input" 
                    type="number" 
                    name="urakkahinta" 
                    placeholder="0"
                    min="0">
                <span>€</span>
            </div>
            <div>
                <span>Alennusprosentti:</span>
                <input 
                    class="alennus-input" 
                    type="number" 
                    name="urakka-alennus" 
                    placeholder="0" 
                    min="0"
                    max="100">
                <span>%</span>
            </div>   
        </div>     

        <h4>Tuntityöt</h4>
        <table>
            <tr>
                <th>Tuntityötyyppi</th>
                <th>Tunnit</th>
                <th>Alennusprosentti</th>
            </tr>

            <?php foreach($tuntityohinnat as $id => $tyo): ?>
            <tr>
                <td><?= $id ?></td>
                <td>
                    <div>
                        <input
                            class="tunti-input" 
                            type="number" 
                            name="<?= $id ?>" 
                            placeholder="0"
                            min="0">
                        <span>h</span>
                    </div>
                </td>
                <td>
                    <div>
                        <input 
                            class="alennus-input" 
                            type="number" 
                            name="<?= $id ?>-alennus" 
                            placeholder="0" 
                            min="0"
                            max="100">
                        <span>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h4>Tarvikkeet</h4>
        <table>
            <tr>
                <th>Tarvike</th>
                <th>Määrä</th>
                <th>Alennusprosentti</th>
            </tr>

            <?php foreach($tarvikkeet as $id => $tarvike): ?>
            <tr>
                <td><?= $tarvike['tarvike'] ?></td>
                <td>
                    <div>
                        <input
                            class="tarvike-input" 
                            type="number" 
                            name="<?= $tarvike['tarvike'] ?>" 
                            placeholder="0"
                            min="0">
                        <span><?= $tarvike['yksikkö'] ?></span>
                    </div>
                </td>
                <td>
                    <div>
                        <input 
                            class="alennus-input" 
                            type="number" 
                            name="<?= $tarvike['tarvike'] ?>-alennus" 
                            placeholder="0" 
                            min="0"
                            max="100">
                        <span>%</span>
                    </div>
                <td>
            </tr>
            <?php endforeach; ?>
        </table>

        <button type="submit" name="luo_hinta-arvio">Luo hinta-arvio</button>
    </form>

    <span>Arvio: <?= $summa ?></span>
    
    <?php if($summa != ''): ?>
    <h3>Luo lasku arviosta</h3>
    <form method="post" class="luo-lasku">
        <div class="flex-container">
            <div>
                <span class="tieto-label">Asiakas:</span>
                <span><?= $nykyinenAsiakas ?></span>
            </div>
    
            <div>
                <span class="tieto-label">Kohde:</span>
                <span><?= $nykyinenKohde ?></span>
            </div>

            <div>
                <span class="tieto-label">Työtyyppi:</span>
                <span><?= $tyotyyppi ?></span>
            </div>
    
            <div>
                <span class="tieto-label">Valitut työt:</span>
                <div class="flex-container">
                    <?php foreach($valitutTyöt as $id => $työ): ?>
                    <span><?= $työ['kesto'] . 'h ' . $työ['tyyppi'] ?></span>
                    <?php endforeach ?>
                </div>
            </div>
    
            <div>
                <span class="tieto-label">Valitut tarvikkeet:</span>
                <div class="flex-container">
                    <?php foreach($valitutTarvikkeet as $id => $tarvike): ?>
                    <span><?= $tarvike['määrä'] . ' ' . $tarvike['tarvike']['yksikkö'] . ' ' . $tarvike['tarvike']['tarvike'] ?></span>
                    <?php endforeach ?>
                </div>
            </div>        
        </div>
        
        <button type="submit" name="luo_lasku">Luo lasku</button>
    </form>
    <?php endif; ?>

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
            <td><?= $id ?></td>
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