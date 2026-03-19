<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}


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
        <div class="tyotyyppi-container" id="tyotyyppi-container">
            <div>
                <input type="radio" name="tyotyyppi" value="tunti" id="tunti" required>
                <label for="tunti">Tuntityö</label>
            </div>
            <div>
                <input type="radio" name="tyotyyppi" value="urakka" id="urakka" required>
                <label for="urakka">Urakka</label>
            </div>
        </div>

        <div class="urakkahinta-selection" id="urakkahinta-selection" style="display:none">
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

    <span>Hinta-arvio: <?= $summa ?></span>
    <span>Kotitalousvähennys: <?= $kt_vahennys ?></span>
    
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
    <script>
        const urakkaSelection = document.getElementById('urakkahinta-selection');
        const tyotyyppiContainer = document.getElementById('tyotyyppi-container');

        tyotyyppiContainer.addEventListener('change', (e) => {
            if(e.target.value === 'urakka') {
                urakkaSelection.style.display = 'block';
            }
            else if(e.target.value === 'tunti') {
                urakkaSelection.style.display = 'none';
            }
        })
    </script>
</body>
</html>