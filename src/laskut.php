<?php
require_once('navigation.php');
require_once('demo_data.php');
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
        <div>
            <label for="tyokohde">Työkohde: </label>
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

        <h4>Tuntityöt:</h4>
        <div>
            <?php foreach($tuntityohinnat as $id => $tyo): ?>
            <div class="value-input-container">
                <div>
                    <label for="<?= $id ?>"><?= $id ?></label>
                    <input
                        class="tunti-input" 
                        type="number" 
                        name="<?= $id ?>" 
                        placeholder="0"
                        min="0">
                    <span>h</span>
                </div>
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
            </div>
            <?php endforeach; ?>
        </div>

        <h4>Tarvikkeet:</h4>
        <div class="tarvikkeet-container">
            <?php foreach($tarvikkeet as $id => $tarvike): ?>
            <div class="value-input-container">
                <div>
                    <label for="<?= $tarvike['tarvike'] ?>"><?= $tarvike['tarvike'] ?></label>
                    <input
                        class="tarvike-input" 
                        type="number" 
                        name="<?= $tarvike['tarvike'] ?>" 
                        placeholder="0"
                        min="0">
                    <span><?= $tarvike['yksikkö'] ?></span>
                </div>
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
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" name="luo_hinta-arvio">Luo hinta-arvio</button>
    </form>

    <span>Arvio: <?= $summa ?></span>
    
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