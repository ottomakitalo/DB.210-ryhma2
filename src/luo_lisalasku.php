<?php
//Koodin luonnissa käytetty apuna Copilotia
session_start();
if ($_SESSION['rooli'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';
require 'lisalasku_funktiot.php';

$alkuperainen = $_POST['id'];

// Hae alkuperäinen lasku
$q = pg_query_params(
    $yhteys,
    "SELECT * FROM lasku WHERE id = $1",
    [$alkuperainen]
);

$alkp = pg_fetch_assoc($q);
if (!$alkp) {
    die("Laskua ei löytynyt");
}

// Hae viimeisin lisälasku
$q2 = pg_query_params(
    $yhteys,
    "SELECT * FROM lisalasku
     WHERE alkp_id = $1
     ORDER BY id DESC
     LIMIT 1",
    [$alkuperainen]
);

$ed = pg_fetch_assoc($q2);
$edellinen_id = $ed ? $ed['id'] : null;

// Lisälaskun antopäivä ja eräpäivä

// Antopäivä
$annettu = date('Y-m-d');

// eräpäivä = 14 päivää antopäivästä
$era = date('Y-m-d', strtotime('+14 days', strtotime($annettu)));

// Laske mones lisälasku nyt on

$maara = $ed
    ? 1 + (int)pg_fetch_result(
        pg_query_params(
            $yhteys,
            "SELECT COUNT(*) FROM lisalasku WHERE alkp_id = $1",
            [$alkuperainen]
        ),
        0
      )
    : 1;

/*
$maara merkitys:
1 = muistutus
2 = 1. karhulasku
3 = 2. karhulasku
jne.
*/

$laskutuslisa = $maara * 5.0;

$viivastys = 0.0;

if ($maara >= 2) {
    $alkuperainen_summa = (float)$alkp['yhteensa'];

    $era_pvm = new DateTime($alkp['era_pvm']);
    $karhu_pvm = new DateTime($annettu);

    // päivät eräpäivästä karhulaskun antopäivään
    $paivia = max(0, $era_pvm->diff($karhu_pvm)->days);

    $viivastys = ($alkuperainen_summa * 0.16 * $paivia) / 365.0;
}

// Uusi id lisälaskulle
$newId = pg_fetch_result(
    pg_query(
        $yhteys,
        "SELECT COALESCE(MAX(id), 0) + 1 FROM lisalasku"
    ),
    0
);

// Lisälaskun tallennus tietokantaan
$ok = pg_query_params(
    $yhteys,
    "INSERT INTO lisalasku
     (id, annettu_pvm, era_pvm, edellinen_id, alkp_id)
     VALUES ($1, $2, $3, $4, $5)",
    [$newId, $annettu, $era, $edellinen_id, $alkuperainen]
);

if (!$ok) {
    die("Lisälaskun luonti epäonnistui: " . pg_last_error($yhteys));
}

header("Location: lasku.php?id=" . $alkuperainen);
exit();