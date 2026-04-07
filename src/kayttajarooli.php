<?php
session_start();

// Oletusroolina vieras
$_SESSION['rooli'] = $_SESSION['rooli'] ?? 'vieras';

if (isset($_POST['rooli'])) {
    $_SESSION['rooli'] = $_POST['rooli'];
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <title>Tmi Sähkötärsky</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/kayttajarooli.css">
</head>

<body>
<div class="rooli-container">
<p class = "rooli">Nykyinen rooli: <?php echo $_SESSION['rooli'] ?? 'ei asetettu'; ?></p>

<form method="post" class="rooli-valikko">
    <select name="rooli" onchange="this.form.submit()">
        <option value="">Vaihda rooli</option>
        <option value="vieras">Vieras</option>
        <option value="tavarantoimittaja">Tavarantoimittaja</option>
        <option value="admin">Admin</option>
    </select>
</form>
</div>
</body>
</html>
