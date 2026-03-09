<?php

$yhteys_tiedot = "dbname=sxhero user=sxhero password=WBOvZ9sx7Wb0Wzq";

// Try to open the connection.
$yhteys = pg_connect($yhteys_tiedot);

if (!$yhteys) {
    die("Tietokantayhteyden luominen epäonnistui.");
}