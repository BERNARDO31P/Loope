<!DOCTYPE html>
<html lang="de">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta charset="UTF-8"/>

    <title>Search</title>
    <link rel="shortcut icon" type="image/x-icon" href="./assets/img/favicon.ico">

    <link rel="stylesheet" type="text/css" href="./assets/css/fontawesome.min.css"/>
    <link rel="stylesheet" type="text/css" href="assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="assets/css/search.css" id="style"/>

    <script>
        // Dies speichert ab, mit welcher URL die Suche aufgerufen wurde
        let pageUrl = "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/" ?>";
    </script>
</head>
<body>
    <div id="header" class="content"></div>
    <div id="main" class="content">
        <div id="logo"><a href="./index.php"><img src="./assets/img/logo.png" alt="Loopé"/></a></div>
        <div id="searchbar">
            <i class="fas fa-search"></i>
            <div class="search-divider"></div>
            <label for="search"></label>
            <input id="search" name="search" placeholder="Suche" onfocus="this.placeholder=''"
                   onblur="this.placeholder='Suche'" tabindex="1" autocapitalize="off" autocomplete="off"
                   autocorrect="off" list="suggestions"/>
            <datalist id="suggestions"></datalist>
            <i id="cleaner" class="fas fa-times"></i>
        </div>
        <div id="buttons">
            <button id="searchbutton">Loopé-Suche</button>
            <button id="luckybutton">Auf gut Glück!</button>
        </div>
    </div>
    <div id="footer" class="content">
        <p>© 2020 Loopé - Alle Rechte vorbehalten.</p>
    </div>

    <script rel="script" type="text/javascript" src="./assets/js/jquery-3.4.1.min.js"></script>
    <script rel="script" type="text/javascript" src="./assets/js/script.js"></script>

    <?php

    // Dieser Codeabschnitt dient dazu, dass die Suche auch über GET funktioniert
    if (isset($_GET['search']) && $_GET['search'] != "") {
        ?>
        <script>
            getSearch("<?= addslashes($_GET['search']) ?>", <?= $_GET['page'] ?? 1 ?>);
        </script>
        <?php
    }
    ?>
</body>
</html>