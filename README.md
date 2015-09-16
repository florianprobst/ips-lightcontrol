# ips-lightcontrol
Lichtsteuerung f�r IP-Symcon

[![Release](https://img.shields.io/github/release/florianprobst/ips-lightcontrol.svg?style=flat-square)](https://github.com/florianprobst/ips-lightcontrol/releases/latest)
[![License](https://img.shields.io/badge/license-LGPLv3-brightgreen.svg?style=flat-square)](https://github.com/florianprobst/ips-lightcontrol/blob/master/LICENSE)

## Aufgabe des Skripts
Dieses Skript erm�glicht die Verkn�pfung mehrerer Lichtquellen mit Schaltern/Tastern, Sensoren und Bewegungsmeldern.

## Weiterf�hrende Informationen
Das Skript legt selbstst�ndig ben�tigte IPS-Variablen und Variablenprofile unterhalb des Skriptes an.
Durch das Speichern der Werte in IPS-Variablen wird Logging und das Anbinden von IPS-Events erm�glicht.
Zur besseren Auffindbarkeit und eindeutigen Zuordnung werden alle Variablenprofile mit einem Pr�fix angelegt. 
Standardm�ssig lautet das `LC_`.

## Installation

1. Dieses Repository im IP-Symcon Unterordner `webfront/user/` klonen. Bsp.: `C:\IP-Symcon\webfront\user\ips-lightcontrol` oder alternativ als zip-Datei herunterladen und in den `IP-Symcon/webfront/user` Unterordner entpacken.
2. In der IP-Symcon Verwaltungskonsole eine Kategorie `Lightcontrol` und eine Unterkategorie `Variables` erstellen (Namen und Ablageorte sind frei w�hlbar)
3. Unterhalb der Kategorie `Lightcontrol` sind mehrere Skripte manuell anzulegen. Diese sind u.a. die Konfiguration, als auch diverse Skripte zum Ausf�hren von Aktionen. Die anzulegenden Skripte befinden sich im Unterordner `ips-scripts` und k�nnen per copy&paste in die IPS-Console eingetragen werden. Alternativ sind die Skripte auch weiter unten direkt beschrieben.

#### Struktur in der IP-Symcon Console nach Installation
(siehe dazu auch Screenshot unten)
* Speedport (Kategorie)
* Variables (Kategorie)
* - diverse automatisch generierte Statusvariablen nach erstem Statusupdate
* Config (script)

## IP-Symcon Console - anzulegende Skripte
###config script
Enth�lt die "globale" Konfiguration und wird von den anderen IPS-Lightcontrol-Scripten aufgerufen.
```php
<?
//Enth�lt die "globale" Konfiguration
?>
```

###update status script
Ein im Interval aufgerufenes Skript zur Steuerung der Zust�nde ("Zeitschaltuhr f�r Lichtquellen") etc.
```php
<?
//
$config_script = 41641 /*[System\Skripte\Lightcontrol\Config]*/; //instanz id des ip-symcon config skripts

require_once(IPS_GetScript($config_script)['ScriptFile']);
}
?>
```