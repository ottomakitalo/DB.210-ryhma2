<?php
$page = basename($_SERVER['PHP_SELF']);
?>

<div>
    <ul>
        <li>
            <a href="index.php" class="<?= $page == 'index.php' ? "active" : ""?>">Etusivu</a>
        </li>

        <li>
            <a href="laskut.php" class="<?= $page == 'laskut.php' ? "active" : ""?>">Laskut</a>
        </li>

        <li>
            <a href="asiakkaat.php" class="<?= $page == 'asiakkaat.php' ? "active" : ""?>">Asiakkaat</a>
        </li>
    </ul>
</div>