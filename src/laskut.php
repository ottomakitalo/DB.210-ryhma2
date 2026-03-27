<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

// unset($_SESSION['laskutiedot']);

require_once('data/asiakkaat_data.php');
require_once('data/tyotehtavat_data.php');
require_once('data/tarvikkeet_data.php');

require_once('luo_lasku.php');
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
    
    <?php if(!empty($_SESSION['laskutiedot'])): ?>
    <h3>Lasku</h3>
    <form method="post" class="luo-lasku">
        <div class="laskuarvio-container flex-container">
            <div>
                <span class="tieto-label">Asiakas:</span>
                <span><?= $asiakkaat[$_SESSION['laskutiedot']['asiakas']]['nimi'] ?></span>
            </div>
    
            <div>
                <span class="tieto-label">Kohde:</span>
                <span><?= $asiakkaat[$_SESSION['laskutiedot']['asiakas']]['tyokohteet'][$_SESSION['laskutiedot']['kohde']]['osoite'] ?></span>
            </div>

            <div>
                <span class="tieto-label">Työtyyppi:</span>
                <span><?= $_SESSION['laskutiedot']['työtyyppi'] ?></span>
            </div>

            <?php if(!empty($_SESSION['laskutiedot']['tuntityöt']) || $_SESSION['laskutiedot']['työtyyppi'] == 'urakka'): ?>
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
                            <?php foreach($_SESSION['laskutiedot']['tuntityöt'] as $id => $tuntityo): ?>
                            <tr>
                                <td>
                                    <div>
                                        <span><?= $tuntityo['tyyppi'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $tuntityo['kesto'] . ' h' ?></span>
                                    </div>
                                </td>
                                <?php if($_SESSION['laskutiedot']['tuntityöt'] == 'tunti'): ?>
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
                                        <span><?= $tuntityo['yhteensä'] . ' €' ?></span>
                                    </div>
                                </td>
                                <?php endif ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                        <?php if($_SESSION['laskutiedot']['työtyyppi'] == 'urakka'): ?>
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
                                        <span><?= $_SESSION['laskutiedot']['urakka-alennus'] . ' %' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $_SESSION['laskutiedot']['nettosumma'] . ' €' ?></span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        
            <?php if(!empty($_SESSION['laskutiedot']['tarvikkeet'])): ?>
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
    
                        <?php foreach($_SESSION['laskutiedot']['tarvikkeet'] as $id => $tarvike): ?>
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
                <span class="tieto-label">Nettosumma:</span>
                <span><?= $_SESSION['laskutiedot']['nettosumma'] . ' €' ?></span>
            </div>

            <div>
                <span class="tieto-label">Alv:</span>
                <span><?= $_SESSION['laskutiedot']['alvsumma'] . ' €' ?></span>
            </div>

            <div>
                <span class="tieto-label">Yhteensä:</span>
                <span><?= $_SESSION['laskutiedot']['nettosumma'] + $_SESSION['laskutiedot']['alvsumma'] . ' €' ?></span>
            </div>

            <div>
                <span class="tieto-label">Kotitalousvähennys:</span>
                <span><?= $_SESSION['laskutiedot']['kt-vähennys'] . ' €' ?></span>
            </div>
            </div>        
        </div>

        <div class="submit-button-container">
            <button type="submit" name="luo_lasku">Luo lasku</button>
            <div>
                <input type="checkbox" name="valmis" value="valmis" id="valmis">
                <label for="valmis">Valmis laskutettavaksi</label>
            </div>
            <?php if($_SESSION['laskutiedot']['työtyyppi'] == 'urakka'): ?>
            <div>
                <input type="checkbox" name="tuplalasku" value="tuplalasku" id="tuplalasku" disabled>
                <label for="tuplalasku">Puolita lasku kahteen osaan</label>
            </div>
            <?php endif; ?>
        <div>
    </form>
    <?php endif; ?>
    <script>
        const urakkaSelection = document.getElementById('urakkahinta-container');
        const tyotyyppiContainer = document.getElementById('tyotyyppi-container');
        const alennusInputs = document.querySelectorAll('.työ-alennus-column');

        const valmisCheckbox = document.getElementById('valmis');
        const tuplalaskuCheckbox = document.getElementById('tuplalasku');

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

        valmisCheckbox.addEventListener('change', () => {
            tuplalaskuCheckbox.disabled = !valmisCheckbox.checked
            if(!valmisCheckbox.checked) {
                tuplalaskuCheckbox.checked = false;
            }
        });
    </script>
</body>
</html>