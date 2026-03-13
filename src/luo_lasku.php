<?php
session_start();

$summa = '';
$nykyinenAsiakas = '';
$nykyinenKohde = '';
$tyotyyppi = '';
$valitutTyöt = [];
$valitutTarvikkeet = [];

if(isset($_POST['luo_hinta-arvio'])) {
    $summa = 0;

    $valitutTyöt = [];
    foreach($tuntityohinnat as $tuntityö => $tuntityohinta) {
        $kesto = intval($_POST[$tuntityö]);
        $alennusprosentti = intval($_POST[$tuntityö . '-alennus']);

        if($kesto > 0) {
            $summa += (($kesto * $tuntityohinta) * (1 - ($alennusprosentti / 100)));

            $valitutTyöt[] = [
                'tyyppi' => $tuntityö,
                'kesto' => $kesto,
                'alennus' => $alennusprosentti
            ];
        }
    }

    $valitutTarvikkeet = [];
    foreach($tarvikkeet as $id => $tarvike) {
        $määrä = intval($_POST[$tarvike['tarvike']]);
        $alennusprosentti = intval($_POST[$tarvike['tarvike'] . '-alennus']);

        if($määrä > 0) {
            $summa += ($määrä * $tarvike['hinta']) * (1 - $alennusprosentti / 100);
            $valitutTarvikkeet[] = [
                'tarvike' => $tarvike,
                'määrä' => $määrä,
                'alennus' => $alennusprosentti,
            ];
        }
    }

    list($asiakasId, $työkohdeId) = explode(':', $_POST['tyokohde']);

    $tyotyyppi = $_POST['tyotyyppi'];
    if($tyotyyppi === 'urakka') {
        $urakkahinta = intval($_POST['urakkahinta']);
        $urakkaAlennus = intval($_POST['urakka-alennus']);

        $summa = $urakkahinta * (1 - $urakkaAlennus / 100);
    }
    
    $nykyinenAsiakas = $asiakkaat[$asiakasId]['asiakas'];
    $nykyinenKohde = $asiakkaat[$asiakasId]['tyokohteet'][$työkohdeId]['osoite'];

    unset($_SESSION['laskutiedotArviosta']);
    $_SESSION['laskutiedotArviosta'] = [
        'asiakas' => $asiakasId,
        'kohde' => $työkohdeId,
        'työtyyppi' => $tyotyyppi,
        'urakka-alennus' => $urakkaAlennus,
        'tuntityöt' => $valitutTyöt,
        'tarvikkeet' => $valitutTarvikkeet,
        'yhteensä' => $summa,
    ];
}

if(isset($_POST['luo_lasku'])) {

    // Create a new työsuoritus
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM tyosuoritus"
    );
    $row = pg_fetch_assoc($result);
    $tyosuoritusId = $row['id'];    

    $tyotyyppi = $_SESSION['laskutiedotArviosta']['työtyyppi'];
    $urakkahinta = $tyotyyppi == 'urakka' ? $_SESSION['laskutiedotArviosta']['yhteensä'] : NULL;
    $tyokohde_id = $_SESSION['laskutiedotArviosta']['kohde'];
    
    $updateTyosuoritus = pg_query_params(
        $yhteys,
        "INSERT INTO tyosuoritus (id, tyotyyppi, urakkahinta, tyokohde_id)
         VALUES ($1, $2, $3, $4)",
        [$tyosuoritusId, $tyotyyppi, $urakkahinta, $tyokohde_id]
    );

    if ($updateTyosuoritus && (pg_affected_rows($updateTyosuoritus)>0))
        $msg = "Työsuoritus lisätty.";
    else
        die("Työsuorituksen lisäys epäonnistui: " . pg_last_error($yhteys));
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;

    // Create a new lasku
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM lasku"
    );
    $row = pg_fetch_assoc($result);
    $laskuId = $row['id'];

    $valmis = 0;
    $annettu_pvm = date('Y-m-d');
    $era_pvm = date('Y-m-d', strtotime('+1 month'));
    $maksettu_status = 0;
    $asiakas_id = $_SESSION['laskutiedotArviosta']['asiakas'];
    
    $updateLasku = pg_query_params(
        $yhteys,
        "INSERT INTO lasku (id, valmis, annettu_pvm, era_pvm, maksettu_status, asiakas_id, tyosuoritus_id)
         VALUES ($1, $2, $3, $4, $5, $6, $7)",
        [$laskuId, $valmis, $annettu_pvm, $era_pvm, $maksettu_status, $asiakas_id, $tyosuoritusId]
    );

    if ($updateLasku && (pg_affected_rows($updateLasku)>0))
        $msg = "Työkohde lisätty.";
    else
        die("Laskun lisäys epäonnistui: " . pg_last_error($yhteys));

    header("Location: ".$_SERVER['PHP_SELF']);
}
?>