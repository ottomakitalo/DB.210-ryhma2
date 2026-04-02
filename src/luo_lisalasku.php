<?php
// Koodin luomisessa käytetty apuna Copilotia

session_start();
if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

require 'db.php';

$alkuperainen = $_POST['id'];

// hae alkuperäinen lasku
$q = pg_query_params($yhteys,
    "SELECT * FROM lasku WHERE id = $1",
    [$alkuperainen]
);
$alkp = pg_fetch_assoc($q);
if (!$alkp) die("Laskua ei löytynyt");

// hae edellinen lisälasku
$q2 = pg_query_params($yhteys,
    "SELECT * FROM lisalasku
     WHERE alkp_id = $1
     ORDER BY id DESC LIMIT 1",
    [$alkuperainen]
);
$ed = pg_fetch_assoc($q2);

$edellinen_id = $ed ? $ed['id'] : null;

// laskun antopäivä on tänään ja eräpäivä 2vk päästä
$annettu = date('Y-m-d');
$era = date('Y-m-d', strtotime('+14 days', strtotime($annettu)));

$laskutuslisa = 5.0;

// laske mones lisälasku (0 -> muistutus, 1+ -> karhu)
$maara = $ed
    ? 1 + (int)pg_fetch_result(pg_query_params($yhteys,
        "SELECT COUNT(*) FROM lisalasku WHERE alkp_id = $1",
        [$alkuperainen]
    ),0)
    : 1;

// viivästyskorko vain karhulaskuihin
$viivastys = 0;
if ($maara >= 2) {
    $summa = (float)$alkp['yhteensa'];
    $era_pvm = new DateTime($alkp['era_pvm']);
    $nyt = new DateTime();
    $paivia = max(0, $era_pvm->diff($nyt)->days);

    $viivastys = ($summa * 0.16 * $paivia) / 365.0;
}

$summa = $laskutuslisa + $viivastys;

// luo uusi lisälasku ID
$newId = pg_fetch_result(pg_query($yhteys,
    "SELECT COALESCE(MAX(id),0)+1 FROM lisalasku"
), 0);

// lisää tauluun
$ok = pg_query_params($yhteys,
    "INSERT INTO lisalasku
    (id, annettu_pvm, era_pvm, maksettu_pvm, edellinen_id, alkp_id)
     VALUES ($1, $2, $3, NULL, $4, $5)",
    [$newId, $annettu, $era, $edellinen_id, $alkuperainen]
);

if (!$ok) {
    die("Lisälaskun luonti epäonnistui: " . pg_last_error($yhteys));
}

header("Location: lasku.php?id=" . $alkuperainen);
exit;
?>