<?
/**
* LightControl class
*
* This class manages all power meters (their counters, current consumption, power costs, etc.).
*
* TODO: power failure methods, keep switch on, reporting, etc.
*
* @link https://github.com/florianprobst/ips-lightcontrol project website
*
* @author Florian Probst <florian.probst@gmx.de>
*
* @license GNU
* GNU General Public License, version 3
*/

require_once 'LightSources/ILightSource.interface.php';
require_once 'LightSources/HomeMaticHM_LC_Sw1_FM.class.php';
require_once 'LightSources/HomeMaticHM_LC_Sw2_FM.class.php';
require_once 'LightSources/HomeMaticHM_LC_Dim1TPBU_FM.class.php';
require_once 'lib/LightControlVariable.class.php';
require_once 'lib/LightControlVariableProfile.class.php';
require_once 'lib/LightControlTriggerEvent.class.php';
require_once 'lib/LightControlTimerEvent.class.php';
require_once 'lib/LightControlScript.class.php';

/**
* class LightControl
*
* @uses ILightSource as light source interface
*
*/
class LightControl{
	/**
	* array of managed light devices and their variables
	*
	* @var ILightSource
	* @access private
	*/
	private $lightsources = array();

	/**
	* parent object id for all variables created by this script
	*
	* @var integer
	* @access private
	*/
	private $parentId;
	
	/**
	* config script id for LightControl
	*
	* @var integer
	* @access private
	*/
	private $configId;

	/**
	* variable name prefix to identify variables and variable profiles created by this script
	*
	* @var string
	* @access private
	*/
	private $prefix;

	/**
	* debug: enables / disables debug information
	*
	* @var boolean
	* @access private
	*/
	private $debug;

	/**
	* array of all LightControl variable profiles
	*
	* @var variableProfiles
	* @access private
	*/
	private $variableProfiles = array();
	
	/**
	* array of all scripts created by LightControl
	*
	* @var scripts
	* @access private
	*/
	private $scripts = array();
	
	/**
	* array of all events created by LightControl
	*
	* @var events
	* @access private
	*/
	private $events = array();

	/**
	* instance id of the archive control (usually located in IPS\core)
	*
	* @var integer
	* @access private
	*/
	private $archiveId;
	
	/**
	* price per kilo watt hour
	*
	* @var float
	* @access private
	*/
	private $price_per_kwh;
	
	/**
	* statistics variable: contains html to present the statistics and data from all light sources
	* handled by this class
	*
	* @var LightControlVariable
	* @access private
	*/
	private $statistics;

	/**
	* IPS - datatype boolean
	* @const tBOOL
	* @access private
	*/
	const tBOOL = 0;

	/**
	* IPS - datatype integer
	* @const tINT
	* @access private
	*/
	const tINT = 1;

	/**
	* IPS - datatype float
	* @const tFLOAT
	* @access private
	*/
	const tFLOAT = 2;

	/**
	* IPS - datatype string
	* @const tSTRING
	* @access private
	*/
	const tSTRING = 3;
	
	/**
	* Constructor
	*
	* @param integer $parentId set the parent object for all items this script creates
	* @param integer $archiveId instance id of the archive control (usually located in IPS\core)
	* @param string $prefix the variable name prefix to identify variables and variable profiles created by this script
	* @param boolean $debug enables / disables debug information
	* @access public
	*/
	public function __construct($configId, $parentId, $archiveId, $price_per_kwh, $prefix = "LC_", $debug = false){
		$this->configId = $configId;
		$this->parentId = $parentId;
		$this->archiveId = $archiveId;
		$this->debug = $debug;
		$this->prefix = $prefix;
		$this->price_per_kwh = $price_per_kwh;
		
		//create variable profiles
		array_push($this->variableProfiles, new LightControlVariableProfile($this->prefix . "Watthours", self::tFLOAT, "", " Wh", NULL, $this->debug));
		array_push($this->variableProfiles, new LightControlVariableProfile("~HTMLBox", self::tFLOAT, "", "", NULL, $this->debug));
		array_push($this->variableProfiles, new LightControlVariableProfile($this->prefix . "Seconds", self::tFLOAT, "", " s", NULL, $this->debug));
		$this->statistics = new LightControlVariable($this->prefix . "Statistics", self::tSTRING, $this->parentId, $this->variableProfiles[1], false, NULL, $this->debug);
		
		//script contents
		$script_includes = '<?require_once(IPS_GetScript('. $this->configId . ')["ScriptFile"]);';
		$script_state_changed_event = $script_includes . '$lightcontrol->statusChanged($_IPS["VARIABLE"]);?>';
		$script_recurring_state_check = $script_includes . '$lightcontrol->checkLightsState()?>';
		$script_auto_off = $script_includes . '$lightcontrol->autoOff($_IPS["EVENT"]);?>';
		$script_uninstall = $script_includes . '$lightcontrol->uninstall();?>';
		
		//create scripts
		array_push($this->scripts, new LightControlScript($this->parentId, $this->prefix . "state_changed_event", $script_state_changed_event, $this->debug));
		array_push($this->scripts, new LightControlScript($this->parentId, $this->prefix . "recurring_state_check", $script_recurring_state_check, $this->debug));
		array_push($this->scripts, new LightControlScript($this->parentId, $this->prefix . "auto_off", $script_auto_off, $this->debug));
		array_push($this->scripts, new LightControlScript($this->parentId, $this->prefix . "USE_CAREFULLY_uninstall_light_control", $script_uninstall, $this->debug));
		
		//create events
		array_push($this->events, new LightControlTimerEvent($this->getScriptByName("recurring_state_check")->getInstanceId(), $this->prefix ."check_lights_state", 240, $this->debug));
		
	}
	
	/**
	* getScriptByName
	*
	* @return LightControlScript if found else false
	* @access private
	*/
	private function getScriptByName($name){
		foreach($this->scripts as &$s){
			if($s->getName() == $this->prefix . $name){
				return $s;
			}
		}
		return false;
	}
	
	/**
	* addLight
	*
	* @param integer $instanceId the light controlling device ips instance id
	* @param string $type the device model/type name (e.g.: HM-LC-Sw1-FM)
	* @param float $watts the power consumption in watts of the light source (e.g. three 3.5 watt LED spots connected to this light source mean 10.5 watts)
	* @param integer $auto_off this light should be automatically turned off after the given seconds. Default 0 keeps the light on until its switched of manually
	* @return boolean true if register was successful
	* @access public
	*/
	public function addLight($instanceId, $type, $watts = 0, $auto_off = 0){
		//check if type is valid
		switch($type){
			case HomeMaticHM_LC_Sw1_FM::MODEL:
				$light = new HomeMaticHM_LC_Sw1_FM($instanceId);
				break;
			case HomeMaticHM_LC_Sw2_FM::MODEL:
				$light = new HomeMaticHM_LC_Sw2_FM($instanceId);
				break;
			case HomeMaticHM_LC_Dim1TPBU_FM::MODEL:
				$light = new HomeMaticHM_LC_Dim1TPBU_FM($instanceId);
				break;
			default:
				throw new Exception("addLightSource parameter \$type wants to register a '$type' device for light control, but that device type is not supported!");
				break;
		}
		$light->setDeviceWattConsumption($watts);
		$this->registerLightSource($light, $auto_off);
		
		return true;
	}
	
	/**
	* registerLightSource
	*
	* @return boolean true if register was successful
	* @access private
	*/
	private function registerLightSource($light, $auto_off){
		if(!($light instanceof ILightSource))
		throw new Exception("Parameter \$light is not of type ILightSource");
		
		//add new light source to list, create variables and reference them to light source		
		$tmp = array(
			"device" => $light,
			"runtime" => new LightControlVariable($this->prefix . "Runtime_" . $light->getInstanceId(), self::tFLOAT, $this->parentId, $this->variableProfiles[2], false, $this->archiveId, 0, $this->debug),
			"energy_counter" => new LightControlVariable($this->prefix . "Energy_Counter_" . $light->getInstanceId(), self::tFLOAT, $this->parentId, $this->variableProfiles[0], true, $this->archiveId, 1, $this->debug),
			"last_on" => new LightControlVariable($this->prefix . "Last_On_" . $light->getInstanceId(), self::tINT, $this->parentId, NULL, false, $this->archiveId, 0, $this->debug),
			"event_state_changed" => new LightControlTriggerEvent($this->getScriptByName("state_changed_event")->getInstanceId(), $light->getControlVariable(), LightControlTriggerEvent::tCHANGE, $this->prefix . "state_changed_" . $light->getControlVariable(), $this->debug),
			"event_auto_off" => new LightControlTimerEvent($this->getScriptByName("auto_off")->getInstanceId(),  $this->prefix . "auto_off_" . $light->getInstanceId(), $auto_off, $this->debug),
			"auto_off" => $auto_off
		);
		array_push($this->lightsources, $tmp);
		
		return true;
	}
	
	/**
	* returns all light sources registered with this class
	*
	* @return array containing all light sources
	* @access public
	*/
	public function getLightSources(){
		return $this->lightsources;
	}
	
	/**
	* switchLightOn
	*
	* @param integer \$instanceId
	* @return returns true if successful
	* @access public
	*/
	public function switchLightOn($instanceId){
		$light = $this->getLight($instanceId);
		if(!$light){
			throw new Exception("Light with instanceId $instanceId was not found!");
		}
		return $light["device"]->switchOn();
	}
	
	/**
	* switchLightOff
	*
	* @param integer \$instanceId
	* @return returns true if successful
	* @access public
	*/
	public function switchLightOff($instanceId){
		$light = $this->getLight($instanceId);
		if(!$light){
			throw new Exception("Light with instanceId $instanceId was not found!");
		}
		return $light["device"]->switchOff();
	}
	
	/**
	* dimLight
	*
	* @param integer \$instanceId
	* @return returns true if successful
	* @access public
	*/
	public function dimLight($instanceId, $level){
		$light = $this->getLight($instanceId);
		if(!$light){
			throw new Exception("Light with instanceId $instanceId was not found!");
		}
		return $light["device"]->dim($level);
	}
	
	/**
	* LightIsOn
	*
	* @param integer \$instanceId
	* @return returns true if light is on
	* @access public
	*/
	public function LightIsOn($instanceId){
		$light = $this->getLight($instanceId);
		if(!$light){
			throw new Exception("Light with instanceId $instanceId was not found!");
		}
		return $light["device"]->isOn();
	}
	
	/**
	* statusChanged
	* will be called when an attached light changes its status (on/off/dim)
	* updates counters
	*
	* @param integer \$instanceId
	* @access public
	*/
	public function statusChanged($controlId){
		$instanceId = IPS_GetParent($controlId); //the control variable (STATE, LEVEL) triggers the event, but we need the light instance id (ACTUATOR) 
		$light = $this->getLight($instanceId);
		if(!$light){
			throw new Exception("Light with instanceId $instanceId was not found!");
		}
		if($light["device"]->isOn()){
			//the light has been switched on
			$light["last_on"]->setValue(time());
			if($light["auto_off"] > 0){
				$light["event_auto_off"]->activate();
			}
		}else{
			//the light has been switched off
			$laston = $light["last_on"]->getValue();
			$runtime = $light["runtime"]->getValue() + (time() - $laston); //seconds
			$light["runtime"]->setValue($runtime);
			
			//now calculate power consumption
			$watt_hours = round($runtime/3600,0) * $light["device"]->getDeviceWattConsumption();
			$light["energy_counter"]->setValue($watt_hours);
			
			if($light["auto_off"] > 0){
				$light["event_auto_off"]->disable();
			}
		}
	}
	
	/**
	* checkLightsState
	* checks all attached lights states (on/off).
	* if a light which should be off is on, this method turns it off (means all auto_off > 0 lights).
	* this can happen if the communication to the light actuator failed.
	*
	* @access public
	*/
	public function checkLightsState(){
		foreach($this->lightsources as &$ls){
			if($ls["auto_off"] > 0){
				if($ls["device"]->isOn()){
					$on_time = time() - $ls["last_on"]->getValue(); //seconds
					if($on_time >= $ls["auto_off"] + 10){	//light is still on, but it should be off 10 seconds ago
						$ls["device"]->switchOff();	//so turn it off
					}
				}
			}
		}
	}
	
	/**
	* autoOff
	* triggered by auto off timer events which want to turn off their attached light
	*
	* @param integer \$timerEventId id of the event which called this method
	* @access public
	*/
	public function autoOff($timerEventId){
		$name = IPS_GetName($timerEventId);
		$pos = strrpos($name, "_");
		$instanceId = substr($name, $pos + 1);
		return $this->switchLightOff($instanceId);
	}
	
	/**
	* getLight
	*
	* @param integer \$instanceId
	* @return returns an array containing all light sources information (ILightSource, variables, events) or false if not found
	* @access private
	*/
	private function getLight($instanceId){
		foreach ($this->lightsources as &$light) {
			if($light["device"]->getInstanceId() == $instanceId){
				return $light;
			}
		}
		return false;
	}
	
	/**
	* updates the statistics variable
	*
	* @access public
	*/
	public function updateStatistics(){
		$html = $this->createHTML();
		$this->statistics->setValue($html);
		
		
		
		
	}
	
	/**
	* creates an html string containing the statistics table for all light sources
	*
	* @access private
	*/
	private function createHTML(){
		$doc = new DOMDocument();
		
		$html = "<html><head></head><body>";
		
		$html .= "<table width='100%'><tr><th width='40%'>Lichtquelle</th><th width='15%'>Leistung</th><th width='15%'>Laufzeit</th><th width='15%'>Verbrauch</th><th width='15%'>Kosten</th></tr>";
		
		$i = 0;
		$totalwatts = 0;
		$totalruntime = 0;
		$totalconsumption = 0;
		$totalcosts = 0;
		foreach ($this->lightsources as &$light) {
			if(++$i % 2 == 0) {
				$bgcolor = "#90D4C0";
			}else{
				$bgcolor = "#9E90D4";
			}
			$name = $light["device"]->getName();
			$watts = $light["device"]->getDeviceWattConsumption();
			$runtime = round($light["runtime"]->getValue() / 3600, 2);
			$consumption = round($light["energy_counter"]->getValue() / 1000, 2);
			$costs = round($consumption * $this->price_per_kwh, 2);
			
			$totalwatts += $watts;
			$totalruntime += $runtime;
			$totalconsumption += $consumption;
		 	$totalcosts += $costs;
		 	
		 	$html .= "<tr style='background-color:$bgcolor'><td>$name</td><td>$watts Watt</td><td>$runtime h</td><td>$consumption kW/h</td><td>$costs EUR</td></tr>";
		} 
		$html .= "<tr><td><b>Insgesamt $i Lichtquellen</b></td><td><b>$totalwatts Watt</b></td><td><b>$totalruntime h</b></td><td><b>$totalconsumption kW/h</b></td><td><b>$totalcosts EUR</b></td></tr></table>";
		
		$html .= "</body></html>";
		
		$doc->loadHTML($html);
		$val = $doc->saveHTML();
		return $val;
	}
	
	/**
	* uninstalls the light control script by removing all created variables and events attached
	* to the light source devices
	*
	* @return true if uninstall was successful
	* @access public
	*/
	public function uninstall(){
		echo "LightControl uninstall procedure called\nBegin uninstall";
		//delete all variables
		foreach($this->lightsources as &$ls){
			echo "Remove variables for light source '". $ls["device"]->getName() . "'\n";
			echo "--> delete variable '" . $ls["runtime"]->getName() . "'\n";
			$ls["runtime"]->delete();
			echo "--> delete variable '" . $ls["energy_counter"]->getName() . "'\n";
			$ls["energy_counter"]->delete();
			echo "--> delete variable '" . $ls["last_on"]->getName() . "'\n";
			$ls["last_on"]->delete();
			echo "--> delete event '" . $ls["event_state_changed"]->getName() . "'\n";
			$ls["event_state_changed"]->delete();
			echo "--> delete event '" . $ls["event_auto_off"]->getName() . "'\n";
			$ls["event_auto_off"]->delete();
		}
		
		//delete statistics variable
		echo "delete statistics variable for light control\n";
		$this->statistics->delete();
		
		//delete all profiles
		echo "delete light control variable profiles\n";
		$this->variableProfiles[0]->delete();	//watt hours
		$this->variableProfiles[2]->delete();	//hours
		
		//delete events
		echo "delete light control events\n";
		foreach($this->events as &$e){
			echo "delete event ". $e->getName() . "\n";
			$e->delete();
		}
		
		//delete scripts
		echo "delete automatically created scripts\n";
		foreach($this->scripts as &$s){
			if($s->getName() != $this->prefix . "USE_CAREFULLY_uninstall_light_control"){
			echo "delete script ". $s->getName() . "\n";
			$s->delete();
			}
		}
		
		echo "---------------------------------\n";
		echo "LightControl uninstall successful\n";
		echo "---------------------------------\n";
		echo "ATTENTION: PLEASE DELETE the config script and uninstall script manually!\n";
		
		return true;
	}
}
?>