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

require_once('luo_lasku.php');

$laskutiedot = $_SESSION['laskutiedot'] ?? [];
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Laskut</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/laskut_luo_lasku.css">
</head>

<body>
    <div class="content-container laskut_luo_arvio">
        <a href="laskut_hinta_arvio.php" class="link-button">Takaisin hinta-arvioon</a>
        <?php if(empty($laskutiedot)): ?>
        <p><strong>Hinta-arviota ei olemassa. Luo ensin hinta-arvio.</strong></p>
        <?php else: ?>
        <h2>Luo lasku</h2>
        <p>Tältä sivulta voit esikatsella luomaasi hinta-arviota sekä luoda laskun siitä.</p>
        <p>Mikäli "Valmis laskutettavaksi"-valintaa ei ole valittu, laskua ei laiteta laskutettavaksi vielä.
            Tällöin laskulla ei ole alku- ja eräpäivää asetettuna. Mikäli kyseessä on tuntityölasku, voidaan myös tuntitöitä muokata ja tarvikkeita lisätä ennen itse laskun laskuttamista.
            Lasku voidaan myöhemmin asettaa laskutettavaksi laskun omalta sivulta laskuluettelosta.</p>
        <p>Mikäli urakkalasku on asetettu valmiiksi, voidaan se myös halutessa puolittaa kahteen osaan, jolloin toinen laskuista laskutetaan heti ja toinen ensi vuoden ensimmäisenä päivänä.</p>
        <h3>Lasku</h3>
        <form method="post" class="luo-lasku">
            <div class="laskuarvio-container flex-container">
                <div class="perustiedot-container flex-container">
                    <div>
                        <span class="tieto-label">Asiakas:</span>
                        <span><?= htmlspecialchars($asiakkaat[$laskutiedot['asiakas']]['nimi']) ?></span>
                    </div>
            
                    <div>
                        <span class="tieto-label">Kohde:</span>
                        <span><?= htmlspecialchars($asiakkaat[$laskutiedot['asiakas']]['tyokohteet'][$laskutiedot['kohde']]['osoite']) ?></span>
                    </div>

                    <div>
                        <span class="tieto-label">Työtyyppi:</span>
                        <span><?= htmlspecialchars($laskutiedot['työtyyppi'] === 'urakka' ? 'Urakka' : 'Tuntityö') ?></span>
                    </div>
                </div>

                <?php if(!empty($laskutiedot['tuntityöt']) || $laskutiedot['työtyyppi'] === 'urakka'): ?>
                <div class="yhteenveto-container flex-container">
                    <span class="tieto-label">Työerittely:</span>
                    <table>
                        <thead>
                            <tr>
                                <th>Työtyyppi</th>
                                <th>Tunnit</th>
                                <th>Alv-%</th>
                                <th>Alennus-%</th>
                                <th>Nettosumma</th>
                                <th>Alv</th>
                                <th>Yhteensä</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $tunnitYhteensa = 0;
                            $tuntityotNetto = 0;
                            $tuntityotAlv = 0;
                            $tuntityotYhteensa = 0;
                            foreach($laskutiedot['tuntityöt'] as $id => $tuntityo): 
                            $tunnitYhteensa += $tuntityo['kesto'];
                            $tuntityotNetto += $tuntityo['nettosumma'];
                            $tuntityotAlv += $tuntityo['alvsumma'];
                            $tuntityotYhteensa += $tuntityo['yhteensä'];?>
                            <tr>
                                <td>
                                    <div>
                                        <span><?= htmlspecialchars($tuntityo['tyyppi']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= htmlspecialchars($tuntityo['kesto']) ?></span>
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
                                        <span><?= number_format($tuntityo['nettosumma'], 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= number_format($tuntityo['alvsumma'], 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= number_format($tuntityo['yhteensä'], 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <td>
                                    <div>
                                        <span><?= $laskutiedot['työtyyppi'] === 'urakka' ? 'urakka' : 'Yhteensä:' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if($laskutiedot['työtyyppi'] === 'tunti'): ?>
                                    <div>
                                        <span><?= $tunnitYhteensa ?></span>
                                        <span>h</span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($laskutiedot['työtyyppi'] === 'urakka'): ?>
                                    <div>
                                        <span><?= '24 %' ?></span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($laskutiedot['työtyyppi'] === 'urakka'): ?>
                                    <div>
                                        <span><?= $laskutiedot['urakka-alennus'] . ' %' ?></span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <span><?= number_format($laskutiedot['työtyyppi'] === 'urakka' ? $laskutiedot['urakkaNetto'] : $tuntityotNetto, 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= number_format($laskutiedot['työtyyppi'] === 'urakka' ? $laskutiedot['urakkaAlv'] : $tuntityotAlv, 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= number_format($laskutiedot['työtyyppi'] === 'urakka' ? $laskutiedot['nettosumma'] : $tuntityotYhteensa, 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            
                <?php if(!empty($laskutiedot['tarvikkeet'])): ?>
                <div class="yhteenveto-container flex-container">
                    <span class="tieto-label">Tarvikkeet:</span>
                    <table>
                        <tr>
                            <th>Tarvike</th>
                            <th>Määrä</th>
                            <th>Alv-%</th>
                            <th>Alennus-%</th>
                            <th>Nettosumma</th>
                            <th>Alv</th>
                            <th>Yhteensä</th>
                        </tr>
    
                        <?php 
                        $tarvikkeetNetto = 0;
                        $tarvikkeetAlv = 0;
                        $tarvikkeetYhteensa = 0;
                        foreach($laskutiedot['tarvikkeet'] as $id => $tarvike):
                            $tarvikkeetNetto += $tarvike['nettosumma'];
                            $tarvikkeetAlv += $tarvike['alvsumma'];
                            $tarvikkeetYhteensa += $tarvike['yhteensä'];
                            ?>
                        <tr>
                            <td><div><span><?= htmlspecialchars($tarvike['tarvike']) ?></span></div></td>
                            <td>
                                <div>
                                    <span><?= htmlspecialchars($tarvike['määrä']) ?></span>
                                    <span><?= htmlspecialchars($tarvike['yksikkö']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span><?= htmlspecialchars($tarvike['alv']) . ' %' ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span><?= $tarvike['alennus'] . ' %' ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span><?= number_format($tarvike['nettosumma'], 2, ',', ' ') ?></span>
                                    <span>€</span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span><?= number_format($tarvike['alvsumma'], 2, ',', ' ') ?></span>
                                    <span>€</span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span><?= number_format($tarvike['yhteensä'], 2, ',', ' ') ?></span>
                                    <span>€</span>
                                </div>
                            </td>
                        </tr>
                        <?php  ?>
                        <?php endforeach; ?>
                        <tfoot>
                            <tr>
                                <td>
                                    <div>
                                        <span>Yhteensä:</span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>
                                    <div>
                                        <span><?= number_format($tarvikkeetNetto, 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= number_format($tarvikkeetAlv, 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span><?= number_format($tarvikkeetYhteensa, 2, ',', ' ') ?></span>
                                        <span>€</span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>

                <div class="summat-container flex-container">
                    <span class="tieto-label">Laskun hintaerittely:</span>
                    <table>
                        <tr>
                            <th>Nettosumma</th>
                            <th>Alv</th>
                            <th>Kotitalousvähennys</th>        
                            <th>Yhteensä</th>
                        </tr>
                        <tr>
                            <td><?= number_format($laskutiedot['nettosumma'], 2, ',', ' ') . ' €' ?></td>
                            <td><?= number_format($laskutiedot['alvsumma'], 2, ',', ' ') . ' €' ?></td>
                            <td><?= number_format($laskutiedot['kt-vähennys'], 2, ',', ' ') . ' €' ?></td>        
                            <td><?= number_format(($laskutiedot['nettosumma'] + $laskutiedot['alvsumma']), 2, ',', ' ') . ' €' ?></td>
                        </tr>                    
                    </table>
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
            </div>
        </form>
        <?php endif; ?>
    </div>
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