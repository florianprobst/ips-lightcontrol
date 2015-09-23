<?
//Enth�lt die "globale" Konfiguration des EnergyManagers und wird von den anderen IPS-EnergyManager-Scripten aufgerufen.
//Hier werden auch die Instanz-IDs aller zu �berwachenden Stromz�hler angegeben.

require_once("../webfront/user/ips-lightcontrol/LightControl.class.php");

$parentId = 11805 /*[System\Skripte\LightControl\Variables]*/; //Ablageort f�r erstellte Variablen
$price_per_kwh = 0.2378; // Preis pro Kilowattstunde deines Stromanbieters
$debug = true;
$prefix = "LC_";
$archive_id = 18531 /*[Archiv]*/; //Instanz ID des IPS-Archivs in welchem die Werte des Stromz�hlers geloggt werden sollen.

//Erg�nze alle IDs der zu �berwachenden Stromz�hler von Homematic (Typ HM_ES_PMSw1_PL) im nachfolgenden Array
$id_array_homematic_actuator_HM_LC_Sw1_FM = [
12536 /*[Hardware\Erdgeschoss\Flur\Light Treppenhaus UG\Kellertreppe]*/,
18344 /*[Hardware\Erdgeschoss\Flur\Light Treppenhaus UG\Untergeschoss]*/
];

//ab hier nichts mehr �ndern
$lightcontrol = new LightControl($parentId, $archive_id, $price_per_kwh, $prefix, $debug);

foreach($id_array_homematic_actuator_HM_LC_Sw1_FM as &$id){
	$lightcontrol->registerLightSource(new HomeMaticHM_LC_Sw1_FM($id));
}
?>