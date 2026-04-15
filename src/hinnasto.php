<?php
require 'db.php';
require_once('navigation.php');
require_once('data/tarvikkeet_data.php');
require_once('data/tyotehtavat_data.php');

// Piilota automaattiset virheilmoitukset ja tallenna virheviestit omaan muuttujaan
ini_set('display_errors', 0);
error_reporting(E_ALL);

$virheviesti = '';

// Funtio tehty Copilotin avustuksella
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paivita_hinnasto']) 
    && ($_SESSION['rooli'] === 'admin' || $_SESSION['rooli'] === 'tavarantoimittaja')) {

    $ok = true;

    if (!isset($_FILES['tiedosto']) || $_FILES['tiedosto']['error'] !== UPLOAD_ERR_OK) {
        $virheviesti = "Tiedoston lataus epäonnistui.";
        $ok = false;
    }

    $tiedosto = $_FILES['tiedosto']['tmp_name'];
    $xml = @simplexml_load_file($tiedosto);
    if ($xml === false) {
        $virheviesti = "XML-tiedoston lukeminen epäonnistui.";
        $ok = false;
    }

    $uudet_tarvikkeet = [];
    $toimittaja = '';

    $toimittaja = (string)($xml->toimittaja->toim_nimi ?? '');
    if ($toimittaja === '') {
        $virheviesti = "Toimittajaa ei löytynyt XML-tiedostosta.";
        $ok = false;
    }

    foreach ($xml->tarvike as $t) {
        $tt = isset($t->ttiedot) ? $t->ttiedot : $t;
        $uudet_tarvikkeet[] = [
            'id' => (int)$tt->id,
            'nimi' => trim((string)$tt->nimi),
            'merkki' => trim((string)$tt->merkki),
            'sis_hinta' => (float)$tt->hinta,
            'yksikko' => trim((string)$tt->yksikko),
            'tyyppi_nimi' => trim((string)$tt->tyyppi)
        ];
    }

    if (!pg_query($yhteys, 'BEGIN')) {
        $virheviesti = "Tietokantavirhe: transaktion aloitus epäonnistui.";
        $ok = false;
    }
    
    if ($ok) {
        $res = pg_query_params($yhteys, 'SELECT * FROM tarvike WHERE toimittaja = $1', array($toimittaja));
        if ($res === false) {
            $virheviesti = "Tietokantavirhe: tarvikkeiden hakeminen epäonnistui.";
            $ok = false;
        } else {
            $olemassa = [];
            while ($row = pg_fetch_assoc($res)) {
                $olemassa[(int)$row['id']] = $row;
            }

            // Haetaan seuraava historia id
            $result_hist = pg_query($yhteys, "SELECT COALESCE(MAX(id),0)+1 AS id FROM tarvike_historia");
            if ($result_hist === false) {
                $virheviesti = "Tietokantavirhe: historia ID:n hakeminen epäonnistui.";
                $ok = false;
            } else {
                $rowh = pg_fetch_assoc($result_hist);
                $histora_id = $rowh['id'];
                $poistettu_pvm = date('Y-m-d');
            }

            foreach ($uudet_tarvikkeet as $tarvike) {
                $id = $tarvike['id'];
                if (isset($olemassa[$id])) {
                    // Tarkistetaan, onko jokin kenttä muuttunut
                    $vanha = $olemassa[$id];
                    $muuttunut = (
                        trim((string)$vanha['nimi']) !== $tarvike['nimi'] ||
                        trim((string)$vanha['merkki']) !== $tarvike['merkki'] ||
                        (float)$vanha['sis_hinta'] !== (float)$tarvike['sis_hinta'] ||
                        trim((string)$vanha['yksikko']) !== $tarvike['yksikko'] ||
                        trim((string)$vanha['tyyppi_nimi']) !== $tarvike['tyyppi_nimi']
                    );

                    // Jos jokin kenttä on muuttunut, tallenna vanha rivi historiaan ja päivitä uusi tieto samaan id:hen
                    if ($muuttunut) {
                        // Tallennetaan vanha rivi historiaan
                        $h = pg_query_params(
                            $yhteys,
                            'INSERT INTO tarvike_historia (id, nimi, merkki, toimittaja, sis_hinta, yksikko, poistettu_pvm, tyyppi_nimi) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)',
                            [$histora_id, $vanha['nimi'], $vanha['merkki'], $vanha['toimittaja'], $vanha['sis_hinta'], $vanha['yksikko'], $poistettu_pvm, $vanha['tyyppi_nimi']]
                        );
                        if ($h === false) {
                            $virheviesti = "Virhe tarvike_historia-taulun päivityksessä: " . pg_last_error($yhteys);
                            $ok = false;
                            break;
                        }

                        // Siirretään laskuille liitetyt rivit tarvikkeet->historia_tarvikkeet
                        $qtar = pg_query_params(
                            $yhteys,
                            'SELECT tyosuoritus_id, maara, alennus FROM tarvikkeet WHERE tarvike_id = $1',
                            [$id]
                        );
                        if ($qtar === false) {
                            $virheviesti = "Tietokantavirhe: tarvikkeet-haku epäonnistui: " . pg_last_error($yhteys);
                            $ok = false;
                            break;
                        }

                        while ($tarrow = pg_fetch_assoc($qtar)) {
                            $insertHist = pg_query_params(
                                $yhteys,
                                'INSERT INTO historia_tarvikkeet (historia_id, tyosuoritus_id, maara, alennus) VALUES ($1, $2, $3, $4)',
                                [$histora_id, $tarrow['tyosuoritus_id'], $tarrow['maara'], $tarrow['alennus']]
                            );
                            if ($insertHist === false) {
                                $virheviesti = "Virhe historia_tarvikkeet-taulun päivityksessä: " . pg_last_error($yhteys);
                                $ok = false;
                                break 2;
                            }
                        }

                        // Poistetaan vanhat viittaukset tarvikkeeseen
                        $u = pg_query_params(
                            $yhteys,
                            'DELETE FROM tarvikkeet WHERE tarvike_id=$1',
                            [$id]
                        );
                        if ($u === false) {
                            $virheviesti = "Virhe tarvikkeet-taulun päivityksessä: " . pg_last_error($yhteys);
                            $ok = false;
                            break;
                        }

                        $histora_id++;
                    }

                    // Päivitetään (uusi versio jää samaan id:hen)
                    $r = pg_query_params(
                        $yhteys,
                        'UPDATE tarvike SET nimi=$1, merkki=$2, sis_hinta=$3, yksikko=$4, tyyppi_nimi=$5 WHERE id=$6 AND toimittaja=$7',
                        [$tarvike['nimi'], $tarvike['merkki'], $tarvike['sis_hinta'], $tarvike['yksikko'], $tarvike['tyyppi_nimi'], $id, $toimittaja]
                    );
                    if ($r === false) {
                        $virheviesti = "Virhe tarvike-taulun päivityksessä: " . pg_last_error($yhteys);
                        $ok = false;
                        break;
                    }
                    unset($olemassa[$id]);
                } else {
                    $r = pg_query_params(
                        $yhteys,
                        'INSERT INTO tarvike (id, nimi, merkki, toimittaja, sis_hinta, yksikko, varasto, tyyppi_nimi) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)',
                        [$id, $tarvike['nimi'], $tarvike['merkki'], $toimittaja, $tarvike['sis_hinta'], $tarvike['yksikko'], 0, $tarvike['tyyppi_nimi']]
                    );
                    if ($r === false) {
                        $virheviesti = "Virhe tarvike-taulun päivityksessä: " . pg_last_error($yhteys);
                        $ok = false;
                        break;
                    }
                }
            }

            if ($ok) {
                // Siirrä XML:ssä poistetut tarvikkeet historiaan ja poista ne tarvike-taulusta
                foreach ($olemassa as $id => $row) {
                    $h = pg_query_params(
                        $yhteys,
                        'INSERT INTO tarvike_historia (id, nimi, merkki, toimittaja, sis_hinta, yksikko, poistettu_pvm, tyyppi_nimi) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)',
                        [$histora_id, $row['nimi'], $row['merkki'], $row['toimittaja'], $row['sis_hinta'], $row['yksikko'], $poistettu_pvm, $row['tyyppi_nimi']]
                    );
                    if ($h === false) {
                        $virheviesti = "Virhe tarvike_historia-taulun päivityksessä: " . pg_last_error($yhteys);
                        $ok = false;
                        break;
                    }
                    
                    $qtar = pg_query_params(
                        $yhteys,
                        'SELECT tyosuoritus_id, maara, alennus FROM tarvikkeet WHERE tarvike_id = $1',
                        [$id]
                    );
                    if ($qtar === false) {
                        $virheviesti = "Tietokantavirhe: tarvikkeet-haku epäonnistui: " . pg_last_error($yhteys);
                        $ok = false;
                        break;
                    }

                    while ($tarrow = pg_fetch_assoc($qtar)) {
                        $insertHist = pg_query_params(
                            $yhteys,
                            'INSERT INTO historia_tarvikkeet (historia_id, tyosuoritus_id, maara, alennus) VALUES ($1, $2, $3, $4)',
                            [$histora_id, $tarrow['tyosuoritus_id'], $tarrow['maara'], $tarrow['alennus']]
                        );
                        if ($insertHist === false) {
                            $virheviesti = "Virhe historia_tarvikkeet-taulun päivityksessä: " . pg_last_error($yhteys);
                            $ok = false;
                            break 2;
                        }
                    }

                    // Poistetaan vanhat viittaukset tarvikkeeseen
                    $u = pg_query_params(
                        $yhteys,
                        'DELETE FROM tarvikkeet WHERE tarvike_id=$1',
                        [$id]
                    );
                    if ($u === false) {
                        $virheviesti = "Virhe tarvikkeet-taulun päivityksessä: " . pg_last_error($yhteys);
                        $ok = false;
                        break;
                    }

                    $histora_id++;

                    $d = pg_query_params($yhteys, 'DELETE FROM tarvike WHERE id=$1 AND toimittaja=$2', [$id, $toimittaja]);
                    if ($d === false) {
                        $virheviesti = "Virhe tarvike-taulun päivityksessä: " .  pg_last_error($yhteys);
                        $ok = false;
                        break;
                    }
                }
            }
        }
        if ($ok) {
            pg_query($yhteys, 'COMMIT');
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            pg_query($yhteys, 'ROLLBACK');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Hinnasto</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/taulu.css">
</head>

<body>
    <div class="content-container hinnasto">
        <h2>Työtehtävät</h2>
        <table>
            <tr>
                <th>Tehtävä</th>
                <th>Tuntihinta (ennen alv)</th>
                <th>Tuntihinta (sis. 24 % alv)</th>
            </tr>
            <?php foreach ($kaikki_tehtavat as $tehtava): ?>
            <tr>
                <td><?= htmlspecialchars($tehtava['tehtava']) ?></td>
                <td><?= htmlspecialchars(number_format($tehtava['tuntihinta'], 2, ',', ' ')) ?> €</td>
                <td><?= htmlspecialchars(number_format($tehtava['tuntihinta'] * 1.24, 2, ',', ' ')) ?> €</td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>Tarvikkeet</h2>
        <h5>Ulosmenohinta lasketaan 25 % voittoprosentilla.</h5>
        <h5>Alennusprosentti vähennetään alvittomasta hinnasta.</h5>

        <?php if ($_SESSION['rooli'] === 'admin' || $_SESSION['rooli'] === 'tavarantoimittaja'): ?>
        <h2>Päivitä Hinnasto XML-tiedostosta</h2>
        <form method="post" enctype="multipart/form-data">
            <input 
                class="tiedosto-input" 
                type="file"
                name="tiedosto" 
                accept=".xml"
                required
            >    
            <button type="submit" name="paivita_hinnasto">
                Päivitä hinnasto
            </button>
        </form>
        <h5 class="virheviesti"><?= htmlspecialchars($virheviesti) ?></h5>
        <?php endif; ?>

        <table>
            <tr>
                <th>Tarvike</th>
                <th>Merkki</th>
                <th>Toimittaja</th>
                <th>Sisäänottohinta</th>
                <th>Ulosmenohinta</th>
                <th>Varastossa</th>
                <th>Alv</th>
            </tr>
            <?php foreach ($kaikki_tarvikkeet as $tarvike): ?>
            <tr>
                <td><?= htmlspecialchars($tarvike['tarvike']) ?></td>
                <td><?= htmlspecialchars($tarvike['merkki']) ?></td>
                <td><?= htmlspecialchars($tarvike['toimittaja']) ?></td>
                <td><?= htmlspecialchars(number_format($tarvike['hinta'], 2, ',', ' ')) ?> €</td>
                <td><?= htmlspecialchars(number_format($tarvike['hinta'] * 1.25, 2, ',', ' ')) ?> €</td>
                <td><?= htmlspecialchars($tarvike['varasto']) . ' '. htmlspecialchars($tarvike['yksikkö']) ?></td>
                <td><?= htmlspecialchars($tarvike['alv']) ?> %</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>