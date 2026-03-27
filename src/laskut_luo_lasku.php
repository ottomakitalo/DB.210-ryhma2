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

require_once('luo_lasku.php');

$laskutiedot = $_SESSION['laskutiedot'] ?? [];
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
    <a href="laskut.php">Takaisin hinta-arvioon</a>
    <?php if(empty($laskutiedot)): ?>
    <p><strong>Hinta-arviota ei olemassa. Luo ensin hinta-arvio.</strong></p>
    <?php else: ?>
    <h2>Luo lasku</h2>
    <h3>Lasku</h3>
    <form method="post" class="luo-lasku">
        <div class="laskuarvio-container flex-container">
            <div>
                <span class="tieto-label">Asiakas:</span>
                <span><?= $asiakkaat[$laskutiedot['asiakas']]['nimi'] ?></span>
            </div>
    
            <div>
                <span class="tieto-label">Kohde:</span>
                <span><?= $asiakkaat[$laskutiedot['asiakas']]['tyokohteet'][$laskutiedot['kohde']]['osoite'] ?></span>
            </div>

            <div>
                <span class="tieto-label">Työtyyppi:</span>
                <span><?= $laskutiedot['työtyyppi'] ?></span>
            </div>

            <?php if(!empty($laskutiedot['tuntityöt']) || $laskutiedot['työtyyppi'] === 'urakka'): ?>
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
                            <?php foreach($laskutiedot['tuntityöt'] as $id => $tuntityo): ?>
                            <tr>
                                <td>
                                    <div>
                                        <span><?= $tuntityo['tyyppi'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $tuntityo['kesto'] ?></span>
                                        <span>h</span>
                                    </div>
                                </td>
                                <?php if($laskutiedot['työtyyppi'] === 'tunti'): ?>
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
                                        <span><?= $tuntityo['yhteensä'] ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <?php endif ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                        <?php if($laskutiedot['työtyyppi'] === 'urakka'): ?>
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
                                        <span><?= $laskutiedot['urakka-alennus'] . ' %' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= $laskutiedot['nettosumma'] ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        
            <?php if(!empty($laskutiedot['tarvikkeet'])): ?>
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
    
                        <?php foreach($laskutiedot['tarvikkeet'] as $id => $tarvike): ?>
                        <tr>
                            <td><div><span><?= $tarvike['tarvike'] ?></span></div></td>
                            <td>
                                <div>
                                    <span><?= $tarvike['määrä'] ?></span>
                                    <span><?= $tarvike['yksikkö'] ?></span>
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
                                    <span><?= $tarvike['yhteensä'] ?></span>
                                    <span>€</span>
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
                <span><?= $laskutiedot['nettosumma'] . ' €' ?></span>
            </div>

            <div>
                <span class="tieto-label">Alv:</span>
                <span><?= $laskutiedot['alvsumma'] . ' €' ?></span>
            </div>

            <div>
                <span class="tieto-label">Yhteensä:</span>
                <span><?= ($laskutiedot['nettosumma'] + $laskutiedot['alvsumma']) . ' €' ?></span>
            </div>

            <div>
                <span class="tieto-label">Kotitalousvähennys:</span>
                <span><?= $laskutiedot['kt-vähennys'] . ' €' ?></span>
            </div>
            </div>        
        </div>

        <div class="submit-button-container">
            <button type="submit" name="luo_lasku">Luo lasku</button>
            <div>
                <input type="checkbox" name="valmis" value="valmis" id="valmis">
                <label for="valmis">Valmis laskutettavaksi</label>
            </div>
            <?php if($laskutiedot['työtyyppi'] === 'urakka'): ?>
            <div>
                <input type="checkbox" name="tuplalasku" value="tuplalasku" id="tuplalasku" disabled>
                <label for="tuplalasku">Puolita lasku kahteen osaan</label>
            </div>
            <?php endif; ?>
        <div>
    </form>
    <?php endif; ?>
    <script>
        const valmisCheckbox = document.getElementById('valmis');
        const tuplalaskuCheckbox = document.getElementById('tuplalasku');
        
        if(valmisCheckbox !== null && tuplalaskuCheckbox !== null) {
            valmisCheckbox.addEventListener('change', () => {
                tuplalaskuCheckbox.disabled = !valmisCheckbox.checked
                if(!valmisCheckbox.checked) {
                    tuplalaskuCheckbox.checked = false;
                }
            });
        }
    </script>
</body>
</html>