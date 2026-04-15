<?php
if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

// Hae laskut
$laskut = [];
$q = pg_query($yhteys,
"SELECT l.id, l.luotu_pvm, l.annettu_pvm, l.era_pvm, 
l.maksettu_pvm,l.maksettu_status, yhteensa,
        a.nimi AS asiakas, k.osoite AS kohde,
        ts.tyotyyppi, urakkahinta, urakka_alennus
 FROM lasku l
 JOIN asiakas a ON a.id = l.asiakas_id
 JOIN tyosuoritus ts ON ts.id = l.tyosuoritus_id
 JOIN tyokohde k ON k.id = ts.tyokohde_id
 ORDER BY l.id DESC"
);

while ($row = pg_fetch_assoc($q)) {
    $laskut[$row['id']] = [
        'id' => $row['id'],
        'asiakas' => $row['asiakas'],
        'kohde'   => $row['kohde'],
        'tyyppi'  => ($row['tyotyyppi'] === 'tunti' ? 'Tuntityö' : 'Urakka'),
        'maksettu_pvm' => !empty($row['maksettu_pvm']) ? date('d.m.Y', strtotime($row['maksettu_pvm'])) : '',
        'status'  => ($row['maksettu_status'] == 't') ? 'Maksettu' : 'Avoinna',
        'luotu'   => date('d.m.Y', strtotime($row['luotu_pvm'])),
        'pvm'     => !empty($row['annettu_pvm']) ? date('d.m.Y', strtotime($row['annettu_pvm'])) : '',
        'erapvm'  => !empty($row['era_pvm']) ? date('d.m.Y', strtotime($row['era_pvm'])) : '',
        'urakkahinta' => $row['urakkahinta'],
        'urakka_alennus' => (float)$row['urakka_alennus'],
        'yhteensä' => $row['yhteensa']
    ];

    // hae viimeisin lisälasku 
    $q_ll = pg_query_params(
        $yhteys,
        "SELECT id, annettu_pvm, era_pvm, edellinen_id
        FROM lisalasku
        WHERE alkp_id = $1
        ORDER BY id DESC
        LIMIT 1",
        [$row['id']]
    );

    $lisalasku_summa = null;
    $lisalasku_erapvm = null;

    // Käytetty apuna copilotia
    if ($q_ll && pg_num_rows($q_ll) > 0) {
        $ll = pg_fetch_assoc($q_ll);

        // Lasketaan summa
        $laskutuslisa = 5.0;
        $viivastys = 0;

        // tarkista onko karhulasku vai ensimmäinen muistutus
        if (!empty($ll['edellinen_id'])) {

            $summa = (float)$row['yhteensa'];  // alkuperäisen laskun summa
            $era_pvm = new DateTime($row['era_pvm']);
            $nyt = new DateTime();
            $paivia = max(0, $era_pvm->diff($nyt)->days);

            $viivastys = ($summa * 0.16 * $paivia) / 365.0;
        }

        // kokonaislisä
        $lisalasku_summa = $laskutuslisa + $viivastys;
        $lisalasku_erapvm = $ll['era_pvm'];
    }

    // lisälaskujen määrä
    $laskut[$row['id']]['lisalaskuja'] = (int)pg_fetch_result(
        pg_query_params($yhteys,
            "SELECT COUNT(*) FROM lisalasku WHERE alkp_id = $1",
            [$row['id']]
        ), 0
    );

    // viimeisin lisälasku
    $laskut[$row['id']]['lisalasku_summa'] = $lisalasku_summa;
    $laskut[$row['id']]['lisalasku_erapvm'] = $lisalasku_erapvm;


}
?>