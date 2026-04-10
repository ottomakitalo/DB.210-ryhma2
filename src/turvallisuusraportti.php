<?php
require 'db.php';
require_once('navigation.php');

if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once('data/tarvikkeet_data.php');

$tarvikkeet = $kaikki_tarvikkeet;
// Järjestä tarvikkeet ensin toimittajan nimen perusteella ja sitten tarvikkeen nimen perusteella
uasort($tarvikkeet, function($a, $b) { 
    $toimittajaVertailu = strcasecmp($a['toimittaja'], $b['toimittaja']);

    if($toimittajaVertailu === 0) {
        return strcasecmp($a['tarvike'], $b['tarvike']);
    }

    return $toimittajaVertailu;
});
?>


<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Turvallisuusraportti</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/turvallisuusraportti.css">
    <link rel="stylesheet" href="styles/taulu.css">
</head>
<body>
    <div class="content-container turvallisuusraportti_luonti" >
        <h2>Luo turvallisuusraportti tarvikkeesta</h2>
        <p>Tältä sivulta voit luoda turvallisuusraportin valituista tarvikkeista.</p>
        <p>Turvallisuusraportti sisältää tiedon siitä, kenelle asiakkalle kyseisiä tarvikkeita on lähetetty sekä mihin työkohteisiin.</p>
        <form method="post" action="turvallisuusraportti_tulos.php" class="luo-turvallisuusraportti">
            <div class="flex-container">
                <table>
                    <tr>
                        <th></th>
                        <th>Toimittaja</th>
                        <th>Tarvike</th>
                        <th>Merkki</th>
                    </tr>

                    <?php foreach($tarvikkeet as $id => $tarvike): ?>
                    <tr class="tarvike-rivi">
                        <td>
                            <div>
                                <input 
                                    type="checkbox" 
                                    name="tarvikkeet[]" 
                                    value="<?= $id ?>">
                        </div>
                        </td>
                        <td>
                            <div>
                                <span><?= $tarvike['toimittaja'] ?></span>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span><?= $tarvike['tarvike'] ?></span>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span><?= $tarvike['merkki'] ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="submit-button-container">
                <button type="submit" name="luo-turvallisuusraportti">Luo turvallisuusraportti</button>
            </div>
        </form>
    </div>
<script>
    document.querySelectorAll('.tarvike-rivi').forEach(rivi => {
        rivi.addEventListener('click', (e) => {
            if(e.target.type === 'checkbox') return;

            const checkbox = e.currentTarget.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
            } 
        });
    });
</script>
</body>
</html>