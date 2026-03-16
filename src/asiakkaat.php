<?php 
require 'db.php';
require 'data/asiakkaat_data.php';
require_once('navigation.php');
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Asiakkaat</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/asiakkaat.css">
</head>

<body>

<table border="1" width="100%">
<tr>
    <th>Asiakas</th>
    <th>Osoite</th>
    <th>Työkohteet</th>
</tr>


<?php foreach($asiakkaat as $id => $asiakas): ?>

<tr>
    <td><?= $asiakas['nimi'] ?></td>
    <td><?= $asiakas['osoite'] ?></td>

    <td>
        <div class="tyokohde-container">
            <?php foreach($asiakas['tyokohteet'] as $t): ?>  
                <span>
                    📍 <?= $t['osoite'] ?>
                    <?php if (!empty($t['tyotyyppi'])): ?>
                        – <?= $t['tyotyyppi'] ?>
                        <?php if (!empty($t['urakkahinta'])): ?>
                            (<?= $t['urakkahinta'] ?> €)
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
        <form method="post">
            <input type="hidden" name="asiakas_id" value="<?= $id ?>">
            <input type="text" name="osoite" placeholder="Osoite" required>
            <button type="submit" name="lisaa_tyokohde">Lisää työkohde</button>
        </form>
    </td>
</tr>

<?php endforeach; ?>
</table>

</body>
</html>