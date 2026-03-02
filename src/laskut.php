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
    <form method="post">
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

        <div>
            <label for="suunnittelu">Suunnittelutunnit: </label>
            <input 
                class="hinta-arvio-tunti-input" 
                type="number" 
                name="suunnittelu" 
                placeholder="0" 
                min="0">
        </div>

        <div>
            <label for="tyo">Työtunnit: </label>
            <input 
                class="hinta-arvio-tunti-input" 
                type="number" 
                name="tyo" 
                placeholder="0"
                min="0">
        </div>

        <div>
            <label for="aputyo">Aputyötunnit: </label>
            <input 
                class="hinta-arvio-tunti-input" 
                type="number" 
                name="aputyo" 
                placeholder="0"
                min="0">
        </div>

        <div class="tarvikkeet-container">
            <legend class="tarvikkeet-legend">Tarvikkeet:</legend>
            <?php foreach($tarvikkeet as $id => $tarvike): ?>
            <div>
                <label for="<?= $tarvike['tarvike'] ?>"><?= $tarvike['tarvike'] ?></label>
                <input
                    class="hinta-arvio-tarvike-input" 
                    type="number" 
                    name="<?= $tarvike['tarvike'] ?>" 
                    placeholder="0"
                    min="0">
                <?= $tarvike['yksikkö'] ?>
            </div>
            <?php endforeach; ?>
            </div>

        <button type="submit" name="luo_hinta-arvio">Luo hinta-arvio</button>
    </form>

    <span>Arvio: <?= $summa ?></span>
    
    <h3>Luo lasku arviosta</h3>
    <form method="post">
        <div class="flex-container">
            <div>
                <span class="lasku-arvio-label">Asiakas:</span>
                <span><?= $nykyinenAsiakas ?></span>
            </div>
    
            <div>
                <span class="lasku-arvio-label">Kohde:</span>
                <span><?= $nykyinenKohde ?></span>
            </div>
    
            <div>
                <span class="lasku-arvio-label">Valitut työt:</span>
                <div class="flex-container">
                    <?php foreach($valitutTyöt as $id => $työ): ?>
                    <span><?= $työ['kesto'] . 'h ' . $työ['tyyppi'] ?></span>
                    <?php endforeach ?>
                </div>
            </div>
    
            <div>
                <span class="lasku-arvio-label">Valitut tarvikkeet:</span>
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
    <table border="1" cellpadding="8">
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