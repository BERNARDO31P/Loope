<?php

header('Content-Type: application/json');
require_once __DIR__ . "/createpath.php";

$suggdb = __DIR__ . "/../db/suggestion.json";
$indexdb = __DIR__ . "/../db/index.json";

if (isset($_POST['search']) && $_POST['search'] != "") {
    $results = array("files" => array(), "pictures" => array());
    $words = array_map(function ($info) {
        return trim($info);
    }, array_filter(explode(" ", $_POST['search'])));
    $num = count($words);
    $picformat = array("jpg", "jpeg", "jpe", "png");
}

// Diese Funktion dient dazu, Datenbanken auszulesen
function getData($file)
{
    $handle = NULL;
    $size = filesize($file);
    if (is_readable($file) && file_exists($file) && $size > 0) {
        while (TRUE) {
            $handle = fopen($file, "r");
            if ($handle !== FALSE) break;
            sleep(0.1);
        }
        $output = fread($handle, $size);
        fclose($handle);
    }
    return $output ?? "";
}

// Diese Funktion dient dazu, Daten in Datenbanken zu speichern
function writeData($file, $data)
{
    $handle = NULL;
    if (createPath(dirname($file))) {
        if (is_writable($file) || !file_exists($file)) {
            while (TRUE) {
                $handle = fopen($file, "w");
                if ($handle !== FALSE) break;
                sleep(0.1);
            }
            fwrite($handle, $data);
            fclose($handle);
        }
    }
}

/*
 * Diese Funktion dient dazu, unnötige Informationen zu entfernen.
 * Wenn ein Eintrag in einem Array leer ist, wird dieser entfernt.
 */
function array_filter_recursive($input){
    foreach ($input as &$value){
        if (is_array($value)){
            $value = array_filter_recursive($value);
        }
    }
    return array_filter($input);
}

/*
 * Diese Funktion überprüft ob der Vorschautext über 200 Zeicher ist
 *
 * Falls dies zutrifft und der Suchtext darin vorkommt, werden 50 Zeichen vorher Punkte gesetzt
 * Falls dies zutrifft und der Suchtext kommt nicht vor, werden 198 Zeichen danach Punkte gesetzt
 * Sonst -> Nichts tun
 */
function prepareContent($word, $string)
{
    $pos = abs(strripos($string, $word) - strlen($string));

    if ($pos < 1950 && $pos > 50 && strlen($string) > 250) {
        $string = ".." . substr($string, -abs($pos) - 50, 195) . "..";
    } elseif (strlen($string) > 200) {
        $string = substr($string, 0, 195) . "..";
    }
    return $string;
}

/*
 * Diese Funktion berechnet die Relevanz für einen Benutzer, der Wert kann 100 übersteigen
 *
 * Dies ist eine Recursive Funktion, falls $info eine Datei ist, wird folgendes überprüft:
 * - Ist der Suchbegriff, der Dateiname?
 * - Kommt der Suchbegriff im Dateinamen vor?
 * - Kommt der Suchbegriff im Inhalt vor?
 *
 * Der Suchbegriff wird bei jedem Leerzeichen getrennt, d.h. jedes Wort wird einzeln gesucht
 *
 * Am Schluss werden die Ergebnisse nach Relevanz sortiert
 */
function prepareData($array)
{
    // Damit die Variablen nicht bei jedem Aufruf der Funktion neu erstellt werden
    GLOBAL $results, $words, $num, $picformat;

    foreach ($array as $folder => $info) {

        if (!is_array($info)) {
            $array['relevanz'] = 0;
            $array['notFound'] = array();

            /*
             * Relevanz Berechnung
             * Es wird geprüft ein Wort von der Suche im Dateiname und im Inhalt vorkommt
             */
            foreach ($words as $word) {
                $tword = trim($word, '"-');

                if ($array['basename'] == $tword) {
                    $array['relevanz'] += 100 / $num;
                } elseif (stripos($array['basename'], $tword) !== FALSE) {
                    $array['relevanz'] += 75 / $num;
                } elseif (stripos($array['filename'], $tword) !== FALSE) {
                    $array['relevanz'] += 50 / $num;
                } elseif (stripos($array['content'], $tword) !== FALSE && $array['content'] != "Dieses Dateiformat wird für eine Vorschau nicht unterstützt.") {
                    $array['relevanz'] += 25 / $num;
                } elseif (substr($word, 0, 1) == '"' && substr($word, -1) == '"') {
                    $array['relevanz'] = 0;
                    break;
                } else {
                    array_push($array['notFound'], $tword);
                }

                /*
                 * "Falls ein Minus vor dem Wort steht und es in der Datei gefunden wird, wird die Datei ignoriert
                 * "" nicht in der Datei gefunden wird, wird das Wort aus den nicht gefundenen Wörtern entfernt
                 */
                if (substr($word, 0, 1) == '-' && !in_array($tword, $array['notFound'])) {
                    $array['relevanz'] = 0;
                    break;
                } elseif (substr($word, 0, 1) == '-' && in_array($tword, $array['notFound'])) {
                    array_pop($array['notFound']);
                }

                $array['content'] = preg_replace('/' . $tword . '/i', '<b>$0</b>', $array['content']);
            }

            // Falls eine Datei gar keine Relevanz hat, wird diese ignoriert
            if ($array['relevanz'] != 0) {
                if (!in_array($array['extension'], $picformat)) {
                    $array['content'] = mb_convert_encoding(prepareContent(trim($words[0], '"-'), $array['content']), 'UTF-8');
                    array_push($results['files'], $array);
                } else {
                    array_push($results['pictures'], $array);
                }
                break;
            }
        } else {
            prepareData($info);
        }
    }

    // Relevanz Sortierung
    usort($results['files'], function ($a, $b) {
        return $b['relevanz'] <=> $a['relevanz'];
    });

    return $results;
}

/*
 * search && page = Suchanfrage
 * search = Auf gut Glück!
 * sugg = Vorschlaganfrage
 */
if (isset($_POST['search']) && $_POST['search'] != "" && isset($_POST['page']) && $_POST['page'] != "") {

    // Versucht die Konfigurationsdatei zu laden
    try {
        $config = parse_ini_file(__DIR__ . "/../config/search.ini", true);
        if (!$config) throw new Exception();
    } catch (Exception $e) {
        die ("\e[31m[ERROR]\e[39m Konfigurationsdatei nicht gefunden. Stellen Sie sicher, dass sie sich im Standardverzeichnis befindet.\n");
    }

    $data = json_decode(getData($indexdb), TRUE);
    $suggs = json_decode(getData($suggdb), TRUE);

    $searchdb = __DIR__ . "/../db/searches/." . $_POST['search'] . "." . $_POST['page'];

    // Eine Suchabfrage wird immer abgespeichert
    $suggs[$_POST['search']] = ($suggs[$_POST['search']] ?? 0) + 1;
    writeData($suggdb, json_encode($suggs));


    // Falls die gleiche Suche in den letzten 5min gemacht wurde, wird diese vom Cache geladen, sonst neuberechnet
    if (file_exists($searchdb) && (time() - filemtime($searchdb)) < $config['main']['interval'] * 60 && $config['main']['cache'] == "on") {
        $output = json_decode(getData($searchdb), TRUE);
    } else {
        $data = array_filter_recursive(prepareData($data));

        // Maximal 20 Einträge pro Seite
        $chunksf = isset($data['files']) ? array_chunk($data['files'], 20) : array();
        $pagesf = count($chunksf);

        // Maximal 40 Bilder pro Seite
        $chunksp = isset($data['pictures']) ? array_chunk($data['pictures'], 40) : array();
        $pagesp = count($chunksp);

        $output['results'] = isset($data['files']) ? count($data['files']) : 0;
        $output['pages'] = $pagesf;
        $output['info']['files'] = ($pagesf > 0) ? $chunksf[$_POST['page'] - 1] : "";
        $output['info']['pictures'] = ($pagesp > 0) ? $chunksp[$_POST['page'] - 1] : "";

        writeData($searchdb, json_encode($output));
    }

    echo json_encode($output);
} elseif (isset($_POST['search']) && $_POST['search'] != "") {
    $data = json_decode(getData($indexdb), TRUE);
    $data = prepareData($data);

    if (count($data) > 0) {
        $output['info'] = $data['files'][0];
        $output['info']['url'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/assets/php/download.php?dir=" . $output['info']['dirname'] . "&file=" . $output['info']['basename'];
    } else {
        $output['error'] = "Keine Ergebnisse";
    }
    echo json_encode($output);
} elseif (isset($_POST['sugg']) && $_POST['sugg'] != "") {
    $data = json_decode(getData($suggdb), TRUE) ?? array();
    $output = array();

    arsort($data);
    foreach ($data as $sugg => $count) {
        // Falls irgendwelche Vorschläge gleich beginnen wie die Vorschlaganfrage, werden diese ausgegeben
        if (strpos($sugg, $_POST['sugg']) === 0) {
            $output[$sugg] = $count;
        }

        // Maximal 6 Vorschläge
        if (count($output) > 5) break;

    }

    echo "{\"info\": " . json_encode($output) . "}";
} else {
    die("{\"error\": \"Keine Berechtigung\"}");
}