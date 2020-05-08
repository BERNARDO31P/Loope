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
    if (in_array($_GET['dir'], $config['main']['ordner'])) {
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
        } else {
            die("Datei nicht gefunden");
        }
    } else {
        die ("Keine Berechtigung");
    }
} else {
    die ("Etwas hat nicht ganz geklappt");
}