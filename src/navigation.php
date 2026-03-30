<?php
$page = basename($_SERVER['PHP_SELF']);
require_once('kayttajarooli.php');
?>

<div class="navigation-container content-container">
    <ul class="navigation-list">
        <li><a href="index.php" class="<?php $page === 'index.php' ? "active" : ""?>">Etusivu</a></li>
        <?php if (isset($_SESSION['rooli']) && ($_SESSION['rooli'] === 'admin' || $_SESSION['rooli'] === 'käyttäjä')): ?>
        <li><a href="laskut.php" class="<?php $page === 'laskut.php' ? "active" : ""?>">Laskut</a></li>
        <li><a href="asiakkaat.php" class="<?php $page === 'asiakkaat.php' ? "active" : ""?>">Asiakkaat</a></li>
        <li><a href="laskuluettelo.php" class="<?php $page === 'laskuluettelo.php' ? "active" : ""?>">Laskuluettelo</a></li>
        <li><a href="turvallisuusraportti.php" class="<?php $page === 'turvallisuusraportti.php' ? "active" : ""?>">Turvallisuusraportti</a></li>
        <?php endif; ?>
    </ul>
</div>