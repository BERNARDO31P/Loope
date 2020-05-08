<?php

// Versucht die Konfigurationsdatei zu laden
try {
    $config = parse_ini_file(__DIR__ . "/assets/config/crawler.ini", true);
    if (!$config) throw new Exception();
} catch (Exception $e) {
    die ("\e[31m[ERROR]\e[39m Konfigurationsdatei nicht gefunden. Stellen Sie sicher, dass sie sich im Standardverzeichnis befindet.\n");
}

ini_set('exif.decode_unicode_motorola', 'UCS-2LE');

// Erkennung des Betriebssystems und Architektur, damit die richtigen Binärdateien geladen werden
if (PHP_OS_FAMILY === "Windows") {
    if (PHP_INT_SIZE === 8) {
        $pdf = __DIR__ . "/assets/lib/bin64/pdftotext.exe";
    } else {
        $pdf = __DIR__ . "/assets/lib/bin32/pdftotext.exe";
    }
} elseif (PHP_OS_FAMILY === "Linux") {
    if (PHP_INT_SIZE === 8) {
        $pdf = __DIR__ . "/assets/lib/bin64/pdftotext";
    } else {
        $pdf = __DIR__ . "/assets/lib/bin32/pdftotext";
    }
} else {
    die ("\e[31m[ERROR]\e[39m Der Crawler läuft zurzeit nur unter Windows und Linux.\n");
}

// Falls die Binärdatei nicht ausgeführt werden kann
if (!is_executable($pdf)) {
    chmod($pdf, "0755");
}

require_once __DIR__ . "/assets/php/converter.class.php";
require_once __DIR__ . "/assets/php/createpath.php";

$converter = new converter();
$db = array();
$indexdb = __DIR__ . "/assets/db/index.json";

/*
 * Diese Funktion liefert eine Liste aller Dateien in einem Verzeichnis
 * Sollte sich ein weiteres Verzeichnis in dem vorherigen Verzeichnis befinden, wird die Funktion rekursiv aufgerufen
 * Falls es sich um eine einzelne Datei handelt, werden alle Informationen über sie gespeichert
 */
function dirToArray($dir)
{
    global $converter, $db;
    $result = array();
    $cdir = scandir($dir);

    foreach ($cdir as $key => $value) {
        if (!in_array($value, array(".", ".."))) {
            $file = $dir . DIRECTORY_SEPARATOR . $value;
            if (is_readable($file))
                if (is_dir($file))
                    // Recursion falls es ein Unterordner ist
                    $result[$value] = dirToArray($file);
                else {

                    // Zeichenkodierung von allen Datei Informationen
                    $fileInfo = array_map(function ($info) {
                        return mb_convert_encoding($info, 'UTF-8');
                    }, pathinfo($file));

                    try {
                        // Falls die Datei bereits indexiert wurde, werden die alten Werte in eine Variable gespeichert
                        if (isset($db[$dir][$fileInfo['basename']])) {
                            $oldFile = $db[$dir][$fileInfo['basename']];
                            // Falls die Datei nicht verändert wurde, wird die Datei nicht ausgelesen, alte Informationen werden wiederverwendet
                            if ($oldFile['lastChange'] == filemtime($file)) {
                                $fileInfo = $oldFile;
                            } else {
                                throw new Exception();
                            }
                        } else {
                            throw new Exception();
                        }
                        // Falls entweder die Informationen nicht mehr aktuell sind oder die Datei noch nie indexiert wurde
                    } catch (Exception $e) {
                        $fileInfo['size'] = filesize($file);
                        $fileInfo['lastChange'] = filemtime($file);
                        $fileInfo['content'] = mb_convert_encoding($converter->convertToText($file), 'UTF-8');
                    }

                    $result[$fileInfo['basename']] = $fileInfo;
                }
        }
    }
    return $result;
}

echo "\e[32m[SUCCESS]\e[39m Der crawler wurde erfolgreich gestartet.\n";

// Hier wird definiert ob der Crawler als Cronjob oder als PHP-Script ausgeführt werden soll
$cron = FALSE;
if (isset($argv[1]) && $argv[1] == "cron") $cron = TRUE;

while (sleep(5) || TRUE) {
    echo "\e[33m[INFO]\e[39m Daten werden gesammelt.\n";

    $db = array();

    // Die bereits gesammelten Daten werden neugeladen
    if (file_exists($indexdb)) {
        if (is_readable($indexdb)) {
            while (TRUE) {
                $file = fopen($indexdb, "r");
                if ($file !== FALSE) break;
                sleep(0.1);

            }
            $db = json_decode(fread($file, filesize($indexdb)), TRUE);
            fclose($file);
        } else {
            die ("\e[31m[ERROR]\e[39m Keine Rechte die index Datenbank zu lesen.\n");
        }
    }

    $data = array();

    // Schleife um jeden zu indexierenden Ordner zu indexieren
    foreach ($config['main']['ordner'] as $ordner) {
        if (file_exists($ordner)) {
            $data[$ordner] = dirToArray($ordner);
        } else {
            echo "\e[31m[ERROR]\e[39m Verzeichnis: " . $ordner . " nicht gefunden.\n";
        }
    }

    // Temporäre Datei, um Probleme bei langer Schreibzeit zu verhindern
    $tmp = tmpfile();
    fwrite($tmp, json_encode($data));

    $success = TRUE;
    while ($success) {
        if (createPath(dirname($indexdb))) {
            // Die Temporäre Datei ersetzt die richtige Datei, was um einiges schneller geht
            $success = copy(stream_get_meta_data($tmp)['uri'], $indexdb) ? FALSE : TRUE;
        } else break;
    }

    fclose($tmp);

    echo "\e[32m[SUCCESS]\e[39m Die Daten wurden erfolgreich gespeichert.\n";

    // Falls der Crawler als Cronjob läuft, wird die Schleife unterbrochen.
    if ($cron) break;
}