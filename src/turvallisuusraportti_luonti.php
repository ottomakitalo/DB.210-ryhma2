<?php
if(isset($_POST['luo-turvallisuusraportti'])) {
    unset($_SESSION['turvallisuusraportti']);
    unset($_SESSION['tarvikkeet']);

    $tarvikkeet = $_POST['tarvikkeet'] ?? [];
    $tarvikeIdt = implode(',', $tarvikkeet);

    $turvallisuusraportti = [];
    $tarvikkeetLista = [];

    if(!empty($tarvikeIdt)) {
        $q = pg_query($yhteys,
            "SELECT ak.nimi AS asiakas, tk.osoite, SUM(tarvikkeet.maara) AS maara, tv.nimi AS tarvike, tv.merkki, tv.toimittaja
            FROM tarvike tv
            JOIN tarvikkeet ON tv.id = tarvikkeet.tarvike_id
            JOIN tyosuoritus ts ON ts.id = tarvikkeet.tyosuoritus_id
            JOIN tyokohde tk ON tk.id = ts.tyokohde_id
            JOIN asiakas ak ON ak.id = tk.asiakas_id
            WHERE tv.id IN ($tarvikeIdt)
            GROUP BY ak.nimi, tk.osoite, tv.nimi, tv.merkki, tv.toimittaja
            ORDER BY ak.nimi");
    
    
        while ($row = pg_fetch_assoc($q)) {
            $turvallisuusraportti[] = [
                'asiakas'    => $row['asiakas'],
                'osoite'     => $row['osoite'],
                'tarvike'    => $row['tarvike'],
                'merkki'     => $row['merkki'],
                'määrä'      => $row['maara'],
                'toimittaja' => $row['toimittaja']
            ];

            $tarvikkeetLista[] = $row['tarvike'];
        }
    }
    
    natcasesort($tarvikkeetLista);

    $_SESSION['turvallisuusraportti'] = $turvallisuusraportti;
    $_SESSION['tarvikkeet'] = $tarvikkeetLista;
}
?>