<?php 
require 'db.php';
require_once('navigation.php');

if (isset($_POST['lisaa_tyokohde'])) {

    $asiakasId = intval($_POST['asiakas_id']);
    $osoite    = trim($_POST['osoite']);

    // Generate new id for työkohde, takes the current biggest id and adds 1
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM tyokohde"
    );
    $row = pg_fetch_assoc($result);
    $nextId = $row['id'];

    // Insert into the database
    
    $update = pg_query_params(
        $yhteys,
        "INSERT INTO tyokohde (id, osoite, asiakas_id)
         VALUES ($1, $2, $3)",
        [$nextId, $osoite, $asiakasId]
    );

    //Checking if the insert succeeded
   if (!$update || pg_affected_rows($update) === 0) {
        die("Työkohteen lisäys epäonnistui: " . pg_last_error($yhteys));
    }


    // Reload page
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

require 'data/asiakkaat_data.php';
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


<?php foreach($asiakkaat as $asiakas): ?>

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
            <input type="hidden" name="asiakas_id" value="<?= $asiakas['id'] ?>">
            <input type="text" name="osoite" placeholder="Osoite" required>
            <button type="submit" name="lisaa_tyokohde">Lisää työkohde</button>
        </form>
    </td>
</tr>

<?php endforeach; ?>
</table>

</body>
</html>