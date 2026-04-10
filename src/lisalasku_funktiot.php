
<?php
/**
 * Laskee lisälaskun summan
 * Käytetty apuna Copilotia
 * @param float  $alkuperainenSumma   Alkuperäisen laskun summa
 * @param string $alkuperainenEraPvm  Alkuperäisen laskun eräpäivä (Y-m-d)
 * @param string $lisalaskuAnnettuPvm Lisälaskun antopäivä (Y-m-d)
 * @param int    $jarjestys           Lisälaskun numero, ensimmäinen on muistutus ja siitä eteenpäin karhuja
 *
 * @return float Lisälaskun summa
 */
function laskeLisalaskunSumma(
    float $alkuperainenSumma,
    string $alkuperainenEraPvm,
    string $lisalaskuAnnettuPvm,
    int $jarjestys
): float {

    // Laskutuslisä kertyy kumulatiivisesti
    $laskutuslisa = $jarjestys * 5.0;

    // Viivästyskorko vain karhulaskuihin
    $viivastys = 0.0;

    if ($jarjestys >= 2) {
        $era = new DateTime($alkuperainenEraPvm);
        $annettu = new DateTime($lisalaskuAnnettuPvm);

        $paivia = max(0, $era->diff($annettu)->days);
        $viivastys = ($alkuperainenSumma * 0.16 * $paivia) / 365.0;
    }

    return $laskutuslisa + $viivastys;
}
