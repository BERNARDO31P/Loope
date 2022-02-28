# Loopé

Loopé ist eine Dateisuchmaschine, welche mit einem Webserver und PHP funktioniert.

## Installation

1. Damit Loopé korrekt funktionieren kann, sind folgende Pakete notwendig:

- libapache2-mod-php
- php-zip
- php-xml
- php-mbstring
- unzip
- screen
- composer
- convmv

Diese Pakete kann man z.B. mit `apt install [Paketname]` installieren.

2. Nachdem alles installiert wurde, kann man den gesamten Inhalt der bereitgestellten ZIP-Datei ins Apache Webverzeichnis entpacken.
3. PHP Biblioteken, welche notwendig sind, kann man jetzt nachträglich mit Composer installieren.

## Konfiguration

* Crawler Konfigurationsdatei: `assets/config/crawler.ini`
* Suche: `assets/config/search.ini`

Die Konfigurationsdateien sind selbsterklärend und besitzen Kommentare/Erklärungen.

## Hilfestellung

Falls die Dateinamen nicht korrekt kodiert werden: `convmv -f iso-8859-1 -t utf8 --replace --notest -r [VERZEICHNIS]`

## Benutzung/Crawler

Mit `screen -S daemon php crawler.php` kann man den Crawler im Hintergrund laufen lassen. 

Möchte man screen mit cronjob ersetzen, muss hierbei darauf geachtet werden, dass der Crawler mit einem Argument starten muss. Hier ein Beispiel: `10 * * * * php crawler.php cron >/dev/null 2>&1` somit wird das Script alle 10 Minuten nur einmal ausgeführt, anstatt permanent.

## Benutzung/Suche

Loopé besitzt bereits einige Zeichen, welche die Suche vereinfachen. Wenn man ein Wort mit Anführungszeichen sucht, werden nur Ergebnisse angezeigt, welche das Wort enthalten. Mit einem Minus vor einem Wort bewirkt man das Gegenteil. (Wie Google)

## Schlusswort

Sobald der Crawler die Dateien indexiert hat, kann man den Server mit einem Browser aufrufen.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)
