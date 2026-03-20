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
    "SELECT id, nimi, tuntihinta FROM tyotehtava ORDER BY id");

while ($row = pg_fetch_assoc($q)) {
    $tuntityohinnat[$row['id']] = [
    'nimi'   => $row['nimi'],    
    'hinta'  => (float)$row['tuntihinta'],
    ];
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

    <h2>Luo lasku</h2>
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

        <div class="urakkahinta-container" id="urakkahinta-container" style="display:none">
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
                <th class="työ-alennus-column">Alennusprosentti</th>
            </tr>

            <?php foreach($tuntityohinnat as $id => $tyo): ?>
            <tr>
                <td><?= $tyo['nimi'] ?></td>
                <td>
                    <div>
                        <input
                            class="tunti-input" 
                            type="number" 
                            name="<?= $tyo['nimi'] ?>" 
                            placeholder="0"
                            min="0">
                        <span>h</span>
                    </div>
                </td>
                <td class="työ-alennus-column">
                    <div>
                        <input 
                            class="alennus-input" 
                            type="number" 
                            name="<?= $tyo['nimi'] ?>-alennus" 
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

        <div class="submit-button-container">
            <button type="submit" name="luo_hinta-arvio">Luo hinta-arvio</button>
        </div>
    </form>
    
    <?php if($summa != ''): ?>
    <h3>Lasku</h3>
    <form method="post" class="luo-lasku">
        <div class="laskuarvio-container flex-container">
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

            <?php if(!empty($valitutTyöt) || $tyotyyppi == 'urakka'): ?>
            <div class="yhteenveto-container flex-container">
                <span class="tieto-label">Työerittely:</span>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th>Työtyyppi</th>
                                <th>Tunnit</th>
                                <th>Alv-prosentti</th>
                                <th>Alennusprosentti</th>
                                <th>Summa</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach($valitutTyöt as $id => $työ): ?>
                            <tr>
                                <td>
                                    <div>
                                        <span><?= $työ['tyyppi'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $työ['kesto'] . ' h' ?></span>
                                    </div>
                                </td>
                                <?php if($tyotyyppi == 'tunti'): ?>
                                <td>
                                    <div>
                                        <span> 24 % </span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $työ['alennus'] . ' %' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $työ['yhteensä'] . ' €' ?></span>
                                    </div>
                                </td>
                                <?php endif ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                        <?php if($tyotyyppi == 'urakka'): ?>
                        <tfoot>
                            <tr>
                                <td>
                                    <div>
                                        <span>urakka</span>
                                    </div>
                                </td>
                                <td></td>
                                <td>
                                    <div>
                                        <span>24 %</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $urakkaAlennus . ' %' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $urakkahinta . ' €' ?></span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        
            <?php if(!empty($valitutTarvikkeet)): ?>
            <div class="yhteenveto-container flex-container">
                <span class="tieto-label">Tarvikkeet:</span>
                <div>
                    <table>
                        <tr>
                            <th>Tarvike</th>
                            <th>Määrä</th>
                            <th>Alv-prosentti</th>
                            <th>Alennusprosentti</th>
                            <th>Summa</th>
                        </tr>
    
                        <?php foreach($valitutTarvikkeet as $id => $tarvike): ?>
                        <tr>
                            <td>
                                <div>
                                    <span><?= $tarvike['tarvike'] ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span><?= $tarvike['määrä'] . ' ' . $tarvike['yksikkö'] ?></span>
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
                                    <span><?= $tarvike['yhteensä'] . ' €' ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div>
                <span class="tieto-label">Hinta-arvio:</span>
                <span><?= $summa ?></span>
            </div>

            <div>
                <span class="tieto-label">Kotitalousvähennys:</span>
                <span><?= $kt_vahennys ?></span>
            </div>
            </div>        
        </div>

        <div class="submit-button-container">
            <button type="submit" name="luo_lasku">Luo lasku</button>
            <div>
                <input type="checkbox" name="valmis" value="valmis" id="valmis">
                <label for="valmis">Valmis laskutettavaksi</label>
            </div>
        <div>
    </form>
    <?php endif; ?>
    <script>
        const urakkaSelection = document.getElementById('urakkahinta-container');
        const tyotyyppiContainer = document.getElementById('tyotyyppi-container');
        const alennusInputs = document.querySelectorAll('.työ-alennus-column');

        tyotyyppiContainer.addEventListener('change', (e) => {
            if(e.target.value === 'urakka') {
                urakkaSelection.style.display = '';

                alennusInputs.forEach(el => {
                    el.style.display ='none'
                });
            }
            else if(e.target.value === 'tunti') {
                urakkaSelection.style.display = 'none';

                alennusInputs.forEach(el => {
                    el.style.display =''
                });
            }
        })
    </script>
</body>
</html>