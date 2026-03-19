<?php
if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require 'db.php';

//Hae asiakkaat
$asiakkaat = [];
$q = pg_query($yhteys, "SELECT id, nimi, osoite FROM asiakas ORDER BY nimi;");

while ($row = pg_fetch_array($q)) {
    $id = $row["id"];

    //Kyseisen asiakkaan työkohteet

    $kohde_haku = pg_query_params($yhteys, 
    "SELECT 
        t.osoite AS osoite,
        s.tyotyyppi AS tyotyyppi,
        s.urakkahinta AS urakkahinta
    FROM tyokohde t
    LEFT JOIN tyosuoritus s ON s.tyokohde_id = t.id
    WHERE t.asiakas_id = $1
    ORDER BY t.osoite;",
    [$id]);

    $tyokohteet = [];
    while ($kohde = pg_fetch_array($kohde_haku)) {
        $tyokohteet[] = [
            'osoite' => $kohde['osoite'],
            'tyotyyppi' => $kohde['tyotyyppi'],
            'urakkahinta' => $kohde['urakkahinta']
        ];
    }


    //Asiakkaan tallennus
    $asiakkaat[] = [
        'id' => $row['id'],
        'nimi' => $row['nimi'],
        'osoite'=> $row['osoite'],
        'tyokohteet'=> $tyokohteet
    ];
}