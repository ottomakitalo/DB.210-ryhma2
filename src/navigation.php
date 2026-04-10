<?php
$page = basename($_SERVER['PHP_SELF']);
require_once('kayttajarooli.php');
?>

<div class="navigation-container content-container">
    <ul class="navigation-list">
        <li><a href="index.php" class="<?= $page === 'index.php' ? "active" : ""?>">Etusivu</a></li>
        <li><a href="hinnasto.php" class="<?= $page === 'hinnasto.php' ? "active" : ""?>">Hinnasto</a></li>
        <?php if (isset($_SESSION['rooli']) && $_SESSION['rooli'] === 'admin'): ?>
        <li><a href="laskut.php" class="<?= str_starts_with($page, 'lasku') || str_starts_with($page, 'muokkaa') ? "active" : ""?>">Laskut</a></li>
        <li><a href="asiakkaat.php" class="<?= $page === 'asiakkaat.php' ? "active" : ""?>">Asiakkaat</a></li>
        <li><a href="turvallisuusraportti.php" class="<?= str_starts_with($page, 'turvallisuusraportti') ? "active" : ""?>">Turvallisuusraportti</a></li>
        <li><a href="historia.php" class="<?= $page === 'historia.php' ? "active" : ""?>">Historia</a></li>
        <?php endif; ?>
    </ul>
</div>