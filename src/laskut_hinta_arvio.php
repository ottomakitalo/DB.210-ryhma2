<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin') {
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
    <link rel="stylesheet" href="styles/laskut_hinta_arvio.css">
</head>

<body>
    <div class="content-container laskut_luo_hinta_arvio">
        <a href="laskut.php" class="link-button">Takaisin laskuihin</a>
        <h2>Hinta-arvio</h2>
        <p>Tältä sivulta voit luoda hinta-arvion arvioidulle työlle valituilla tuntitöillä ja tarvikkeilla. Hinta-arvion luomisen jälkeen pääset esikatselemaan laskua ja luomaan uuden laskun kyseisestä hinta-arviosta.</p>
        <form method="post" action="laskut_luo_lasku.php" class="hinta-arvio">
            <div class="tyokohde-container">
                <h3>Työkohde</h3>
                <select name="tyokohde" required>
                    <option value="">Valitse työkohde</option>
                    <?php foreach($asiakkaat as $id => $asiakas) {
                        foreach($asiakas['tyokohteet'] as $id => $tyokohde) {
                            $value = $asiakas['id'] . ':' . $tyokohde['id'];
                            $label = $asiakas['nimi'] . ' - ' . $tyokohde['osoite'];
                            echo "<option value=\"$value\">".htmlspecialchars($label)."</option>";
                        }
                    }?>
                </select>
            </div>

            <div class="tyotyyppi-container">
                <h3>Työtyyppi</h3>
                <div class="tyotyyppi-inputs" id="tyotyyppi-inputs">
                    <div>
                        <input type="radio" name="tyotyyppi" value="tunti" id="tunti" required>
                        <label for="tunti">Tuntityö</label>
                    </div>
                    <div>
                        <input type="radio" name="tyotyyppi" value="urakka" id="urakka" required>
                        <label for="urakka">Urakka</label>
                    </div>
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

            <div class="tuntityot-tarvikkeet-container">
                <div class="flex-container">
                    <h3>Tuntityöt</h3>
                    <table>
                        <tr>
                            <th>Tuntityötyyppi</th>
                            <th>Tunnit</th>
                            <th class="työ-alennus-column">Alennus-%</th>
                        </tr>

                        <?php foreach($kaikki_tehtavat as $id => $tuntityo): ?>
                        <tr>
                            <td><?= htmlspecialchars($tuntityo['tehtava']) ?></td>
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
                </div>
                
                <div class="flex-container">
                    <h3>Tarvikkeet</h3>
                    <table>
                        <tr>
                            <th>Tarvike</th>
                            <th>Määrä</th>
                            <th>Alennus-%</th>
                        </tr>

                        <?php foreach($kaikki_tarvikkeet as $id => $tarvike): ?>
                        <tr>
                            <td><?= htmlspecialchars($tarvike['tarvike']) ?></td>
                            <td>
                                <div>
                                    <input
                                        class="tarvike-input" 
                                        type="number" 
                                        name="<?= $tarvike['tarvike'] ?>" 
                                        placeholder="0"
                                        min="0">
                                    <span><?= htmlspecialchars($tarvike['yksikkö']) ?></span>
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
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="submit-button-container">
                <button type="submit" name="luo_hinta-arvio">Luo hinta-arvio</button>
            </div>
        </form>
    </div>
    <script>
        const urakkaSelection = document.getElementById('urakkahinta-container');
        const tyotyyppiContainer = document.getElementById('tyotyyppi-inputs');
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