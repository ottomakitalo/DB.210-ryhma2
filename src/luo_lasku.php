<?php
if ($_SESSION['rooli'] !== 'admin' && $_SESSION['rooli'] !== 'käyttäjä') {
    header("Location: index.php");
    exit();
}

$myyntihintakerroin = 1.25;

if(isset($_POST['luo_hinta-arvio'])) {
    $nettosumma = 0;
    $alvsumma = 0;
    $kotitalousVahennys = 0;

    $tuntityotNetto = 0;
    $tuntityotAlv = 0;
    $valitutTyot = [];
    foreach($kaikki_tehtavat as $id => $tuntityo) {
        $kesto = intval($_POST[$tuntityo['tehtava']]);
        $alennusprosentti = intval($_POST[$tuntityo['tehtava'] . '-alennus']);

        if($kesto > 0) {
            $tuntityoNetto = ($kesto * $tuntityo['tuntihinta']) * (1 - ($alennusprosentti / 100));
            $tuntityoAlv = $tuntityoNetto * 0.24;
            
            $tuntityoYhteensa = $tuntityoNetto + $tuntityoAlv;
            
            $tuntityotNetto += $tuntityoNetto;
            $tuntityotAlv += $tuntityoAlv;

            $valitutTyot[] = [
                'id'       => $id,
                'tyyppi'   => $tuntityo['tehtava'],
                'kesto'    => $kesto,
                'alennus'  => $alennusprosentti,
                'yhteensä' => $tuntityoYhteensa,
            ];
        }
    }

    $tarvikkeetNetto = 0;
    $tarvikkeetAlv = 0;
    $valitutTarvikkeet = [];
    foreach($kaikki_tarvikkeet as $id => $tarvike) {
        $kappaleMaara = intval($_POST[$tarvike['tarvike']]);
        $alennusprosentti = intval($_POST[$tarvike['tarvike'] . '-alennus']);

        if($kappaleMaara > 0) {
            $tarvikeNetto = ($kappaleMaara * ($tarvike['hinta'] * $myyntihintakerroin)) * (1 - ($alennusprosentti / 100));
            $tarvikeAlv = $tarvikeNetto * ($tarvike['alv'] / 100);

            $tarvikeYhteensa = $tarvikeNetto + $tarvikeAlv;

            $tarvikkeetNetto += $tarvikeNetto;
            $tarvikkeetAlv += $tarvikeAlv;

            $valitutTarvikkeet[] = [
                'id'       => $id,
                'tarvike'  => $tarvike['tarvike'],
                'määrä'    => $kappaleMaara,
                'yksikkö'  => $tarvike['yksikkö'],
                'alennus'  => $alennusprosentti,
                'alv'      => $tarvike['alv'],
                'yhteensä' => $tarvikeYhteensa,
            ];
        }
    }

    list($asiakasId, $tyokohdeId) = explode(':', $_POST['tyokohde']);

    $tyotyyppi = $_POST['tyotyyppi'];
    $urakkahinta = NULL;
    $urakkaAlennus = NULL;
    if($tyotyyppi === 'urakka') {
        $urakkahinta = intval($_POST['urakkahinta']);
        $urakkaAlennus = intval($_POST['urakka-alennus']);

        $urakkaNetto = $urakkahinta * (1 - $urakkaAlennus / 100);
        $urakkaAlv = $urakkaNetto * 0.24;

        $urakkaYhteensa = $urakkaNetto + $urakkaAlv;

        $nettosumma = $urakkaNetto + $tarvikkeetNetto;
        $alvsumma = $tarvikkeetAlv + $urakkaAlv;
        $kotitalousVahennys = $urakkaYhteensa;
    }

    else {
        $nettosumma = $tuntityotNetto + $tarvikkeetNetto;
        $alvsumma = $tuntityotAlv + $tarvikkeetAlv;
        $kotitalousVahennys = $tuntityotNetto + $tuntityotAlv;
    }

    unset($_SESSION['laskutiedot']);
    $_SESSION['laskutiedot'] = [
        'asiakas'        => $asiakasId,
        'kohde'          => $tyokohdeId,
        'työtyyppi'      => $tyotyyppi,
        'urakka-alennus' => $urakkaAlennus,
        'tuntityöt'      => $valitutTyot,
        'tarvikkeet'     => $valitutTarvikkeet,
        'nettosumma'     => $nettosumma,
        'alvsumma'       => $alvsumma,
        'yhteensä'       => $nettosumma + $alvsumma,
        'kt-vähennys'    => $kotitalousVahennys
    ];
}

if(isset($_POST['luo_lasku'])) {
    $laskutiedot = $_SESSION['laskutiedot'];

    $laskuValmis = (int)!empty($_POST['valmis']);
    $tyotyyppi = $laskutiedot['työtyyppi'];
    $puolitettuLasku = !empty($_POST['tuplalasku']) && $tyotyyppi === 'urakka' && $laskuValmis;
    
    $annettuPvm = $laskuValmis ? date('Y-m-d') : NULL;
    $eraPvm = $laskuValmis ? date('Y-m-d', strtotime('+2 weeks', strtotime($annettuPvm))) : NULL;
    $yhteensa = $puolitettuLasku ? (float)$laskutiedot['yhteensä'] / 2 : (float)$laskutiedot['yhteensä'];
    $tyosuoritusId = createLaskuWithTyosuoritus($yhteys, $laskutiedot, $annettuPvm, $eraPvm, $laskuValmis, $yhteensa);

    if($puolitettuLasku) {
        $annettuPvm = date('Y-m-d', strtotime('first day of january next year'));
        $eraPvm = date('Y-m-d', strtotime('+2 weeks', strtotime($annettuPvm)));
        $asiakasId = $laskutiedot['asiakas'];
        createNewLasku($yhteys, $annettuPvm, $eraPvm, $laskuValmis, $yhteensa, $asiakasId, $tyosuoritusId);
    }
    
    unset($_SESSION['laskutiedot']);
    header("Location: laskut.php");
    exit();
}

/**
 * Luo uusi lasku tietokantaan yhdistettynä uuteen työsuoritukseen
 */
function createLaskuWithTyosuoritus($yhteys, $laskutiedot, $annettuPvm, $eraPvm, $laskuValmis, $yhteensa) { 
    $tyotyyppi = $laskutiedot['työtyyppi'];
    $urakkahinta = $tyotyyppi === 'urakka' ? $laskutiedot['nettosumma'] : NULL;
    $urakkaAlennus = $laskutiedot['urakka-alennus'];
    $tyokohdeId = $laskutiedot['kohde'];
    $tyosuoritusId = createNewTyosuoritus($yhteys, $tyotyyppi, $urakkahinta, $urakkaAlennus, $tyokohdeId);
    
    $tuntityot = $laskutiedot['tuntityöt'];
    $tarvikkeet = $laskutiedot['tarvikkeet'];
    fillTyosuoritus($yhteys, $tyosuoritusId, $tuntityot, $tarvikkeet);
    
    $asiakasId = $laskutiedot['asiakas'];
    createNewLasku($yhteys, $annettuPvm, $eraPvm, $laskuValmis, $yhteensa, $asiakasId, $tyosuoritusId);

    return $tyosuoritusId;
}

/**
 * Luo uusi työsuoritus tietokantaan
 */
function createNewTyosuoritus($yhteys, $tyotyyppi, $urakkahinta, $urakkaAlennus, $tyokohdeId) {
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM tyosuoritus"
    );
    $row = pg_fetch_assoc($result);
    $tyosuoritusId = $row['id'];    
    
    $updateTyosuoritus = pg_query_params(
        $yhteys,
        "INSERT INTO tyosuoritus (id, tyotyyppi, urakkahinta, urakka_alennus, tyokohde_id)
        VALUES ($1, $2, $3, $4, $5)",
        [$tyosuoritusId, $tyotyyppi, $urakkahinta, $urakkaAlennus, $tyokohdeId]
    );
        
    if(!$updateTyosuoritus) {
        die("Työsuorituksen lisäys epäonnistui: " . pg_last_error($yhteys));
    }
            
    return $tyosuoritusId;
}

/**
 * Täytä työsuoritus valituilla tuntitöillä ja tarvikkeilla
 */
function fillTyosuoritus($yhteys, $tyosuoritusId, $tuntityot, $tarvikkeet) {
    foreach($tuntityot as $id => $tuntityo) {
        $tyotehtavaId = $tuntityo['id'];
        $tunnit = $tuntityo['kesto'];
        $alennus = $tuntityo['alennus'];
        
        createNewTehtävä($yhteys, $tyosuoritusId, $tyotehtavaId, $tunnit, $alennus);
    }

    foreach($tarvikkeet as $id => $tarvike) {
        $tarvikeId = $tarvike['id'];
        $maara = $tarvike['määrä'];
        $alennus = $tarvike['alennus'];

        createNewTarvikkeet($yhteys, $tyosuoritusId, $tarvikeId, $maara, $alennus);
    }    
}

/**
 * Luo uusi valittu tehtävä (tuntityö) tietokantaan
 */
function createNewTehtävä($yhteys, $tyosuoritusId, $tyotehtavaId, $tunnit, $alennus) {    
    $updateTehtava = pg_query_params(
        $yhteys,
        "INSERT INTO tehtavat (tyosuoritus_id, tyotehtava_id, tunnit, alennus)
        VALUES ($1, $2, $3, $4)",
        [$tyosuoritusId, $tyotehtavaId, $tunnit, $alennus]
    );
        
    if(!$updateTehtava) {
        die("Tehtävän lisäys epäonnistui: " . pg_last_error($yhteys));
    }
}

/**
 * Luo uusi valittu tarvike tietokantaan
 */
function createNewTarvikkeet($yhteys, $tyosuoritusId, $tarvikeId, $maara, $alennus) {    
    $updateTarvike = pg_query_params(
        $yhteys,
        "INSERT INTO tarvikkeet (tyosuoritus_id, tarvike_id, maara, alennus)
        VALUES ($1, $2, $3, $4)",
        [$tyosuoritusId, $tarvikeId, $maara, $alennus]
    );
        
    if(!$updateTarvike) {
        die("Tarvikkeen lisäys epäonnistui: " . pg_last_error($yhteys));
    }
}

/**
 * Luo uusi lasku tietokantaan
 */
function createNewLasku($yhteys, $annettuPvm, $eraPvm, $laskuValmis, $yhteensa, $asiakasId, $tyosuoritusId) {
    $result = pg_query($yhteys,
        "SELECT COALESCE(MAX(id),0)+1 AS id FROM lasku"
    );
    $row = pg_fetch_assoc($result);
    $laskuId = $row['id'];

    $luotuPvm = date('Y-m-d');
    $maksettuStatus = 0;
    
    $updateLasku = pg_query_params(
        $yhteys,
        "INSERT INTO lasku (id, valmis, luotu_pvm, annettu_pvm, era_pvm, maksettu_status, yhteensa, asiakas_id, tyosuoritus_id)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)",
        [$laskuId, $laskuValmis, $luotuPvm, $annettuPvm, $eraPvm, $maksettuStatus, $yhteensa, $asiakasId, $tyosuoritusId]
    );

        
    if(!$updateLasku) {
        die("Laskun lisäys epäonnistui: " . pg_last_error($yhteys));
    }

    return $laskuId;
}
?>
