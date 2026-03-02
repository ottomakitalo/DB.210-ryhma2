<?php
session_start();

if(isset($_POST['reset'])) {
    unset($_SESSION['asiakkaat']);
    unset($_SESSION['laskut']);
}

if(!isset($_SESSION['asiakkaat'])) {

    $_SESSION['asiakkaat'] = [
        1 => [
            "asiakas" => "Matti Meikäläinen",
            "osoite" => "Matinkatu 5",
            "tyokohteet" => [
                ["osoite" => "Katu 1", "tyosuoritus" => "Keittiöremontti"]
            ]
        ],
        2 => [
            "asiakas" => "Teppo Testaaja",
            "osoite" => "Teponkatu 2",
            "tyokohteet" => []
        ]
    ];
}

if(!isset($_SESSION['laskut'])) {
    $_SESSION['laskut'] = [
        1 => [
            "asiakas" => "Matti Meikäläinen",
            "kohde" => "Katu 1",
            "tyyppi" => "Tuntityö",
            "pvm" => "1.1.2026",
            "erapvm" => "12.1.2026",
            "yhteensä" => 850,
        ],
        2 => [
            "asiakas" => "Testi 2",
            "kohde" => "Kesämökki",
            "tyyppi" => "Urakka",
            "pvm" => "5.1.2026",
            "erapvm" => "1.2.2026",
            "yhteensä" => 2400,
        ]
    ];
}

if(isset($_POST['lisaa_tyokohde'])) {

    $id = $_POST['asiakas_id'];

    $_SESSION['asiakkaat'][$id]['tyokohteet'][] = [
        "osoite" => $_POST['osoite'],
        "tyosuoritus" => $_POST['tyosuoritus']
    ];
}

$tuntityohinnat = [
    "suunnittelu" => 55,
    "tyo" => 45,
    "aputyo" => 35,
];

$tarvikkeet = [
    1 => [
        "tarvike" => "usb-kaapeli",
        "yksikkö" => "kpl",
        "hinta" => 5,
        "alv" => 24,
    ],
    2 => [
        "tarvike" => "sähköjohto",
        "yksikkö" => "m",
        "hinta" => 1.25,
        "alv" => 24,
    ],
    3 => [
        "tarvike" => "pistorasia",
        "yksikkö" => "kpl",
        "hinta" => 12.5,
        "alv" => 24,
    ],
    4 => [
        "tarvike" => "maakaapeli",
        "yksikkö" => "m",
        "hinta" => 5,
        "alv" => 24,
    ],
    5 => [
        "tarvike" => "sähkökeskus",
        "yksikkö" => "kpl",
        "hinta" => 375,
        "alv" => 24,
    ],
    6 => [
        "tarvike" => "opaskirja",
        "yksikkö" => "kpl",
        "hinta" => 10,
        "alv" => 10,
    ],
];



$asiakkaat = $_SESSION['asiakkaat'];
$laskut = $_SESSION['laskut'];


if(isset($_POST['luo_hinta-arvio'])) {
    $summa = 0;


    $valitutTyöt = [];
    foreach($tuntityohinnat as $tuntityö => $tuntityohinta) {
        $kesto = (floatval($_POST[$tuntityö]) ?? 0);

        if($kesto > 0) {
            $summa += $kesto * $tuntityohinta;

            $valitutTyöt[] = [
                'tyyppi' => $tuntityö,
                'kesto' => $kesto,
            ];
        }
    }

    $valitutTarvikkeet = [];
    foreach($tarvikkeet as $id => $tarvike) {
        $määrä = floatval($_POST[$tarvike['tarvike']]) ?? 0;

        if($määrä > 0) {
            $summa += $määrä * $tarvike['hinta'];
            $valitutTarvikkeet[] = [
                'tarvike' => $tarvike,
                'määrä' => $määrä
            ];
        }
    }

    list($asiakasId, $työkohdeId) = explode(':', $_POST['tyokohde']);
    
    $nykyinenAsiakas = $asiakkaat[$asiakasId]['asiakas'];
    $nykyinenKohde = $asiakkaat[$asiakasId]['tyokohteet'][$työkohdeId]['osoite'];

    unset($_SESSION['laskutiedotArviosta']);
    $_SESSION['laskutiedotArviosta'] = [
        'asiakas' => $nykyinenAsiakas,
        'kohde' => $nykyinenKohde,
        'tuntityöt' => $valitutTyöt,
        'tarvikkeet' => $valitutTarvikkeet,
        'yhteensä' => $summa,
    ];
}

if(isset($_POST['luo_lasku'])) {
    $_SESSION['laskut'][] = [
        "asiakas" => $_SESSION['laskutiedotArviosta']['asiakas'],
        "kohde" => $_SESSION['laskutiedotArviosta']['kohde'],
        "tyyppi" => "Tuntityö",
        "pvm" => date('d.m.Y'),
        "erapvm" => date('d.m.Y', strtotime('+1 month')),
        "yhteensä" => $_SESSION['laskutiedotArviosta']['yhteensä'],       
    ];
}
?>