# ips-lightcontrol
Lichtsteuerung für IP-Symcon

[![Github Release](https://img.shields.io/github/release/florianprobst/ips-lightcontrol.svg?style=flat-square)](https://github.com/florianprobst/ips-lightcontrol/releases/latest)
[![License](https://img.shields.io/badge/license-LGPLv3-brightgreen.svg?style=flat-square)](https://github.com/florianprobst/ips-lightcontrol/blob/master/LICENSE)

## Aufgabe des Skripts
Dieses Skript ermöglicht die Verknüpfung mehrerer Lichtquellen mit Schaltern/Tastern, Sensoren und Bewegungsmeldern.

## Weiterführende Informationen
Das Skript legt selbstständig benötigte IPS-Variablen und Variablenprofile unterhalb des Skriptes an.
Durch das Speichern der Werte in IPS-Variablen wird Logging und das Anbinden von IPS-Events ermöglicht.
Zur besseren Auffindbarkeit und eindeutigen Zuordnung werden alle Variablenprofile mit einem Präfix angelegt. 
Standardmässig lautet das `LC_`.

## Installation

1. Dieses Repository im IP-Symcon Unterordner `webfront/user/` klonen. Bsp.: `C:\IP-Symcon\webfront\user\ips-lightcontrol` oder alternativ als zip-Datei herunterladen und in den `IP-Symcon/webfront/user` Unterordner entpacken.
2. In der IP-Symcon Verwaltungskonsole eine Kategorie `Lightcontrol` und eine Unterkategorie `Variables` erstellen (Namen und Ablageorte sind frei wählbar)
3. Unterhalb der Kategorie `Lightcontrol` ist ein Konfigurationsskript manuell anzulegen. Dieses befindet sich sich im Unterordner `assets` und kann per copy&paste in die IPS-Console eingetragen werden. Alternativ ist das Skript auch weiter unten direkt beschrieben und kann von dort kopiert werden.

#### Struktur in der IP-Symcon Console nach Installation
(siehe dazu auch Screenshot unten)
* Variables (Kategorie)
* - diverse automatisch generierte Statusvariablen nach erstem Statusupdate
* Config (script)

## IP-Symcon Console - anzulegende Skripte
###config script
Enthält die "globale" Konfiguration und wird von den anderen IPS-Lightcontrol-Scripten aufgerufen.
```php
<?
//Enthält die "globale" Konfiguration des EnergyManagers und wird von den anderen IPS-EnergyManager-Scripten aufgerufen.
//Hier werden auch die Instanz-IDs aller zu überwachenden Stromzähler angegeben.
require_once("../webfront/user/ips-lightcontrol/LightControl.class.php");

$configId = 15642 /*[System\IPS-LightControl\config]*/; //ID dieser Config-Datei ($_IPS['SELF'] nicht genutzt, da dies auf einem RaspberryPI/Linux System derzeit funktioniert)
$parentId = 11058 /*[System\IPS-LightControl\Variables]*/; //Ablageort für automatisch erstellte Variablen und Scripte
$price_per_kwh = 0.2378; // Preis pro Kilowattstunde deines Stromanbieters
$debug = true;
$prefix = "LC_";
$archive_id = 34760 /*[Archive]*/; //Instanz ID des IPS-Archivs in welchem die Werte des Stromzählers geloggt werden sollen.

//ab hier nichts mehr ändern
$lightcontrol = new LightControl($configId, $parentId, $archive_id, $price_per_kwh, $prefix, $debug);
$lightcontrol->addLight(15499 /*[Hardware\Treppenhaus\Licht EG]*/,"HM-LC-Sw1-FM", 6.0, 120);
$lightcontrol->addLight(14630 /*[Hardware\Treppenhaus\Licht UG]*/,"HM-LC-Sw1-FM", 6.0, 120);
$lightcontrol->addLight(25075 /*[Hardware\Erdgeschoss\Wohnzimmer\UP-Lichtschalter\Zimmerlicht]*/, "HM-LC-Sw2-FM", 15.0, 0);
$lightcontrol->addLight(11367 /*[Hardware\Erdgeschoss\Wohnzimmer\UP-Lichtschalter\Zimmerlicht Fensterbank]*/, "HM-LC-Sw2-FM", 174.0, 0);
$lightcontrol->addLight(42246 /*[Hardware\Erdgeschoss\Schlafzimmer\Zimmerlicht]*/, "HM-LC-Dim1TPBU-FM", 4.5, 0);
?>
```