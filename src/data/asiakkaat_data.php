<?php
if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

//Fetch customers
$asiakkaat = [];
$q = pg_query($yhteys, 
    "SELECT id, nimi, osoite 
    FROM asiakas 
    ORDER BY nimi;"
    );


//Go through each asiakas one by one
while ($row = pg_fetch_assoc($q)) {

    $asiakasId = $row['id'];

    //Customers work sites
    $kohde_haku = pg_query_params(
        $yhteys,
        "SELECT id, osoite
         FROM tyokohde
         WHERE asiakas_id = $1
         ORDER BY osoite;",
        [$asiakasId]
    );

    $tyokohteet = [];

    while ($kohde = pg_fetch_assoc($kohde_haku)) {

        $tyokohdeId = $kohde['id'];

        // Curent work site's tasks
        $suoritus_haku = pg_query_params(
            $yhteys,
            "SELECT tyotyyppi, urakkahinta
             FROM tyosuoritus
             WHERE tyokohde_id = $1;",
            [$tyokohdeId]
        );

        $suoritukset = [];
        while ($s = pg_fetch_assoc($suoritus_haku)) {
            $suoritukset[] = [
                'tyotyyppi'   => $s['tyotyyppi'],
                'urakkahinta' => $s['urakkahinta'],
            ];
        }

        // Save work site into a list
        $tyokohteet[$tyokohdeId] = [
            'id'          => $tyokohdeId,
            'osoite'      => $kohde['osoite'],
            'suoritukset' => $suoritukset
        ];
    }

    // Save asiakas and their work sites
    $asiakkaat[$asiakasId] = [
        'id'         => $asiakasId,
        'nimi'       => $row['nimi'],
        'osoite'     => $row['osoite'],
        'tyokohteet' => $tyokohteet
    ];

}