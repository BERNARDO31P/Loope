<?php

if ((isset($_GET['dir']) && $_GET['dir'] != "") && (isset($_GET['file']) && $_GET['file'] != "")) {

    // Versucht die Konfigurationsdatei zu laden
    try {
        $config = parse_ini_file(__DIR__ . "/../config/crawler.ini", true);
        if (!$config) throw new Exception();
    } catch (Exception $e) {
        die ("Konfigurationsdatei nicht gefunden. Stellen Sie sicher, dass sie sich im Standardverzeichnis befindet.\n");
    }

    $file = $_GET['dir'] . DIRECTORY_SEPARATOR . $_GET['file'];

    // Falls er angeforderte Ordner nicht indexiert wird, gehe ich von einem unberechtigten Zugriff aus
    foreach ($config['main']['ordner'] as $ordner) {

        // TODO: Genauere Überprüfung -> lib64 ist trotzdem TRUE wenn nur lib TRUE sein darf
        if (strpos($_GET['dir'], $ordner) !== FALSE) {
            if (file_exists($file)) {
                // Alle nötigen Download Header
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $_GET['file'] . '"');
                header('Content-Transfer-Encoding: binary');
                header('Connection: Keep-Alive');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                echo file_get_contents($file);
                exit();
            } else {
                die("Datei nicht gefunden");
            }
        }
    }
    die ("Keine Berechtigung");
} else {
    die ("Etwas hat nicht ganz geklappt");
}