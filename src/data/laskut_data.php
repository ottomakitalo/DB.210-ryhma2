<?php
require 'db.php';

// Hae laskut
$laskut = [];
$q = pg_query($yhteys,
"SELECT l.id, l.annettu_pvm, l.era_pvm, l.maksettu_status,
        a.nimi AS asiakas, k.osoite AS kohde,
        ts.tyotyyppi, urakkahinta
 FROM lasku l
 JOIN asiakas a ON a.id = l.asiakas_id
 JOIN tyosuoritus ts ON ts.id = l.tyosuoritus_id
 JOIN tyokohde k ON k.id = ts.tyokohde_id
 ORDER BY l.annettu_pvm DESC"
);

while ($row = pg_fetch_assoc($q)) {
    $yhteensa = $row['tyotyyppi'] === 'urakka' ? $row['urakkahinta'] : '---';
    $laskut[$row['id']] = [
        'id' => $row['id'],
        'asiakas' => $row['asiakas'],
        'kohde'   => $row['kohde'],
        'tyyppi'  => ($row['tyotyyppi'] === 'tunti' ? 'Tuntityö' : 'Urakka'),
        'pvm'     => date('d.m.Y', strtotime($row['annettu_pvm'])),
        'erapvm'  => date('d.m.Y', strtotime($row['era_pvm'])),
        'yhteensä' => $yhteensa
    ];
}
?>