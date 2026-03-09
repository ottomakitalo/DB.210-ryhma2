<?php 
require 'db.php';
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


<?php
// 1) Hakee asiakkaat tietokannasta
$asiakkaat_tulos = pg_query($yhteys, "SELECT id, nimi, osoite FROM asiakas ORDER BY nimi;");

while ($asiakas = pg_fetch_assoc($asiakkaat_tulos)) {

    echo "<tr>";
    echo "<td>" . htmlspecialchars($asiakas['nimi']) . "</td>";
    echo "<td>" . htmlspecialchars($asiakas['osoite']) . "</td>";

    echo "<td>";

    // Asiakkaan työmaat
    $kohteet_tulos = pg_query_params(
        $yhteys,
        "SELECT osoite FROM tyokohde WHERE asiakas_id = $1 ORDER BY osoite",
        array($asiakas['id'])
    );

    while ($kohde = pg_fetch_assoc($kohteet_tulos)) {
        echo htmlspecialchars($kohde['osoite']) . "<br>";
    }

    echo "</td>";
    echo "</tr>";
}
?>

</table>

</body>
</html>
