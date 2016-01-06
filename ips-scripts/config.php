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