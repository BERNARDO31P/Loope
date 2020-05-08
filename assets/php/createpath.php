<?php

// Diese Funktion dient dazu, einen nicht existierenden Ordner zu erstellen
function createPath($path)
{
    if (is_dir($path)) return true;
    $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
    $return = createPath($prev_path);
    $output = FALSE;
    if ($return && is_writable($prev_path)) {
        $output = mkdir($path);
        chown($path, "www-data");
        chgrp($path, "www-data");
    }
    return $output;
}