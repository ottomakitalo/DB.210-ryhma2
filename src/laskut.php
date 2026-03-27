<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require_once('data/asiakkaat_data.php');
require_once('data/tyotehtavat_data.php');
require_once('data/tarvikkeet_data.php');
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
    <form method="post" action="laskut_luo_lasku.php" class="hinta-arvio">
        <h4>Työkohde</h4>
        <div>
            <select name="tyokohde" required>
                <option value="">Valitse työkohde</option>
                <?php foreach($asiakkaat as $id => $asiakas) {
                    foreach($asiakas['tyokohteet'] as $id => $tyokohde) {
                        $value = $asiakas['id'] . ':' . $tyokohde['id'];
                        $label = $asiakas['nimi'] . ' - ' . $tyokohde['osoite'];
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

            <?php foreach($kaikki_tehtavat as $id => $tuntityo): ?>
            <tr>
                <td><?= $tuntityo['tehtava'] ?></td>
                <td>
                    <div>
                        <input
                            class="tunti-input" 
                            type="number" 
                            name="<?= $tuntityo['tehtava'] ?>" 
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
                            name="<?= $tuntityo['tehtava'] ?>-alennus" 
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

            <?php foreach($kaikki_tarvikkeet as $id => $tarvike): ?>
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
        });
    </script>
</body>
</html>