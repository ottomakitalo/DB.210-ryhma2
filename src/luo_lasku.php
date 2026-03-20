<?php
if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

$nettosumma = '';
$alv_summa = '';
$kt_vahennys = '';
$urakkahinta = '';
$urakkaAlennus = '';
$tarvikeYhteensä = '';
$nykyinenAsiakas = '';
$nykyinenKohde = '';
$tyotyyppi = '';
$valitutTyöt = [];
$valitutTarvikkeet = [];

$myyntihintakerroin = 1.25;

if(isset($_POST['luo_hinta-arvio'])) {
    $nettosumma = 0;
    $kt_vahennys = 0;
    $alv_summa = 0;

    $tuntityötNetto = 0;
    $tuntityötAlv = 0;
    $valitutTyöt = [];
    foreach($tuntityohinnat as $id => $tuntityö) {
        $kesto = intval($_POST[$tuntityö['nimi']]);
        $alennusprosentti = intval($_POST[$tuntityö['nimi'] . '-alennus']);

        if($kesto > 0) {
            $tuntityöNetto = ($kesto * $tuntityö['hinta']) * (1 - ($alennusprosentti / 100));
            $tuntityöAlv = $tuntityöNetto * 0.24;
            
            $tuntityöYhteensä = $tuntityöNetto + $tuntityöAlv;
            
            $tuntityötNetto += $tuntityöNetto;
            $tuntityötAlv += $tuntityöAlv;

            $valitutTyöt[] = [
                'tyyppi'   => $tuntityö['nimi'],
                'kesto'    => $kesto,
                'alennus'  => $alennusprosentti,
                'yhteensä' => $tuntityöYhteensä,
            ];
        }
    }

    $tarvikkeetNetto = 0;
    $tarvikkeetAlv = 0;
    $valitutTarvikkeet = [];
    foreach($tarvikkeet as $id => $tarvike) {
        $määrä = intval($_POST[$tarvike['tarvike']]);
        $alennusprosentti = intval($_POST[$tarvike['tarvike'] . '-alennus']);

        if($määrä > 0) {
            $tarvikeNetto = ($määrä * ($tarvike['hinta'] * $myyntihintakerroin)) * (1 - $alennusprosentti / 100);
            $tarvikeAlv = $tarvikeNetto * ($tarvike['alv'] / 100);

            $tarvikeYhteensä = $tarvikeNetto + $tarvikeAlv;

            $tarvikkeetNetto += $tarvikeNetto;
            $tarvikkeetAlv += $tarvikeAlv;

            $valitutTarvikkeet[] = [
                'id' => $id,
                'tarvike' => $tarvike['tarvike'],
                'määrä' => $määrä,
                'yksikkö' => $tarvike['yksikkö'],
                'alennus' => $alennusprosentti,
                'alv' => $tarvike['alv'],
                'yhteensä' => $tarvikeYhteensä,
            ];
        }
    }

    list($asiakasId, $työkohdeId) = explode(':', $_POST['tyokohde']);

    $tyotyyppi = $_POST['tyotyyppi'];
    if($tyotyyppi === 'urakka') {
        $urakkahinta = intval($_POST['urakkahinta']);
        $urakkaAlennus = intval($_POST['urakka-alennus']);

        $urakkaNetto = $urakkahinta * (1 - $urakkaAlennus / 100);
        $urakkaAlv = $urakkaNetto * 0.24;

        $urakkaYhteensä = $urakkaNetto + $urakkaAlv;

        $nettosumma = $urakkaNetto + $tarvikkeetNetto;
        $alv_summa = $tarvikkeetAlv + $urakkaAlv;
        $kt_vahennys = $urakkaYhteensä;
    }

    else {
        $nettosumma = $tuntityötNetto + $tarvikkeetNetto;
        $alv_summa = $tuntityötAlv + $tarvikkeetAlv;
        $kt_vahennys = $tuntityötNetto + $tuntityötAlv;
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
        'yhteensä' => $nettosumma,
        'kt-vähennys' => $kt_vahennys
    ];
}

if(isset($_POST['luo_lasku'])) {
    $tyotyyppi = $_SESSION['laskutiedotArviosta']['työtyyppi'];
    $lasku_valmis = !empty($_POST['valmis']);
    $tuplalasku = !empty($_POST['tuplalasku']) && $työtyyppi == 'urakka' && $lasku_valmis;
    
    $urakkahinta = $tyotyyppi == 'urakka' ? $_SESSION['laskutiedotArviosta']['yhteensä'] : NULL;
    $tyokohde_id = $_SESSION['laskutiedotArviosta']['kohde'];

    $tyosuoritusId = createNewTyösuoritus($yhteys, $tyotyyppi, $urakkahinta, $tyokohde_id);

    $annettu_pvm = $lasku_valmis ? date('Y-m-d') : NULL;
    $era_pvm = $lasku_valmis ? date('Y-m-d', strtotime('+2 weeks', strtotime($annettu_pvm))) : NULL;
    $asiakas_id = $_SESSION['laskutiedotArviosta']['asiakas'];

    createNewLasku($yhteys, $annettu_pvm, $era_pvm, $lasku_valmis, $asiakas_id, $tyosuoritusId);

    if($tuplalasku) {
        $tyosuoritusId = createNewTyösuoritus($yhteys, $tyotyyppi, $urakkahinta, $tyokohde_id);

        $annettu_pvm = date('Y-m-d', strtotime('first day of january next year'));
        $era_pvm = date('Y-m-d', strtotime('+2 weeks', strtotime($annettu_pvm)));

        createNewLasku($yhteys, $annettu_pvm, $era_pvm, $lasku_valmis, $asiakas_id, $tyosuoritusId);
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
}

function createNewTyösuoritus($yhteys, $tyotyyppi, $urakkahinta, $tyokohde_id) {
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM tyosuoritus"
    );
    $row = pg_fetch_assoc($result);
    $tyosuoritusId = $row['id'];    
    
    $updateTyosuoritus = pg_query_params(
        $yhteys,
        "INSERT INTO tyosuoritus (id, tyotyyppi, urakkahinta, tyokohde_id)
        VALUES ($1, $2, $3, $4)",
        [$tyosuoritusId, $tyotyyppi, $urakkahinta, $tyokohde_id]
    );

    if ($updateTyosuoritus && (pg_affected_rows($updateTyosuoritus)>0)) {
        $msg = "Työsuoritus lisätty.";
    }

    else {
        die("Työsuorituksen lisäys epäonnistui: " . pg_last_error($yhteys));        
    }

    return $tyosuoritusId;
}

function createNewLasku($yhteys, $annettu_pvm, $era_pvm, $lasku_valmis, $asiakas_id, $tyosuoritusId) {
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM lasku"
    );
    $row = pg_fetch_assoc($result);
    $laskuId = $row['id'];

    $luotu_pvm = date('Y-m-d');
    $maksettu_status = 0;
    
    $updateLasku = pg_query_params(
        $yhteys,
        "INSERT INTO lasku (id, valmis, luotu_pvm, annettu_pvm, era_pvm, maksettu_status, asiakas_id, tyosuoritus_id)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)",
        [$laskuId, $lasku_valmis, $luotu_pvm, $annettu_pvm, $era_pvm, $maksettu_status, $asiakas_id, $tyosuoritusId]
    );

    if ($updateLasku && (pg_affected_rows($updateLasku)>0))
        $msg = "Työkohde lisätty.";
    else
        die("Laskun lisäys epäonnistui: " . pg_last_error($yhteys));        
}
?>
