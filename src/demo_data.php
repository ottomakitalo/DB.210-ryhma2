<?php
session_start();

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
        'asiakas' => $nykyinenAsiakas,
        'kohde' => $nykyinenKohde,
        'työtyyppi' => $tyotyyppi,
        'urakka-alennus' => $urakkaAlennus,
        'tuntityöt' => $valitutTyöt,
        'tarvikkeet' => $valitutTarvikkeet,
        'yhteensä' => $summa,
    ];
}

if(isset($_POST['luo_lasku'])) {
    $_SESSION['laskut'][] = [
        'asiakas' => $_SESSION['laskutiedotArviosta']['asiakas'],
        'kohde' => $_SESSION['laskutiedotArviosta']['kohde'],
        'tyyppi' => $_SESSION['laskutiedotArviosta']['työtyyppi'],
        'urakka-alennus' => $_SESSION['laskutiedotArviosta']['urakka-alennus'],
        'pvm' => date('d.m.Y'),
        'erapvm' => date('d.m.Y', strtotime('+1 month')),
        'yhteensä' => $_SESSION['laskutiedotArviosta']['yhteensä'],       
    ];
}
?>