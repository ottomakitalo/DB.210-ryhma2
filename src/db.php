<?php
//Lisää tietokannan tiedot tähän 
//Huom!! ohjelma ei toimi ilman tietoja
$yhteys_tiedot = "dbname=sxhero user=sxhero password=";

// Yhteyden luominen
$yhteys = pg_connect($yhteys_tiedot);

if (!$yhteys) {
    die("Tietokantayhteyden luominen epäonnistui.");
}