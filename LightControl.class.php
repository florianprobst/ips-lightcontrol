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
	* @var LightControlVariableProfile
	* @access private
	*/
	private $variableProfiles = array();

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
	public function __construct($parentId, $archiveId, $price_per_kwh, $prefix = "LC_", $debug = false){
		$this->parentId = $parentId;
		$this->archiveId = $archiveId;
		$this->debug = $debug;
		$this->prefix = $prefix;
		
		//create variable profiles
		array_push($this->variableProfiles, new LightControlVariableProfile($this->prefix . "Watthours", self::tFLOAT, "", " Wh", NULL, $this->debug));
		array_push($this->variableProfiles, new LightControlVariableProfile("~HTMLBox", self::tFLOAT, "", "", NULL, $this->debug));
		array_push($this->variableProfiles, new LightControlVariableProfile($this->prefix . "Hours", self::tFLOAT, "", " h", NULL, $this->debug));
		$this->statistics = new LightControlVariable($this->prefix . "Statistics", self::tSTRING, $this->parentId, $this->variableProfiles[1], false, NULL, $this->debug);
	}
	
	/**
	* addLight
	*
	* @param integer $instanceId the light controlling device ips instance id
	* @param string $type the device model/type name (e.g.: HM-LC-Sw1-FM)
	* @param float $watts the power consumption in watts of the light source (e.g. three 3.5 watt LED spots connected to this light source mean 10.5 watts)
	* @return boolean true if register was successful
	* @access public
	*/
	public function addLight($instanceId, $type, $watts = 0){
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
		$this->registerLightSource($light);
		return true;
	}
	
	/**
	* registerLightSource
	*
	* @return boolean true if register was successful
	* @access private
	*/
	private function registerLightSource($light){
		if(!($light instanceof ILightSource))
		throw new Exception("Parameter \$light is not of type ILightSource");
		
		//add new light source to list, create variables and reference them to light source		
		$tmp = array(
			"device" => $light,
			"runtime" => new LightControlVariable($this->prefix . "Runtime_" . $light->getInstanceId(), self::tFLOAT, $this->parentId, $this->variableProfiles[2], false, $this->archiveId, $this->debug),
			"energy_counter" => new LightControlVariable($this->prefix . "Energy_Counter_" . $light->getInstanceId(), self::tFLOAT, $this->parentId, $this->variableProfiles[0], false, $this->archiveId, $this->debug),
			"last_on" => new LightControlVariable($this->prefix . "Last_On_" . $light->getInstanceId(), self::tFLOAT, $this->parentId, NULL, false, $this->archiveId, $this->debug)
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
	* checks the status of all light sources and turns them off if necessary
	*
	* @access public
	*/
	public function update(){
		foreach($this->lightsources as &$p){
			//current counter value from power meter (warning: depending on manufacturer / model this value
			//can be resetted to 0 when the device was disconnected.
			$current = $p["device"]->getEnergyCounterWattHours();
			
			//last read value stored to ips
			$last = $p["energy_counter_last_read"]->getValue();
			
			//the energy counter value we want to have
			$counter = $p["energy_counter"]->getValue();
			
			if($current < $last){
				//counter was reset (maybe power failure)
				$last = 0;
			}
			
			//calculate incremental value between last counter read and current counter read
			$increment = $current - $last;
			
			//add increment to the counter
			$counter += $increment;
			
			//save last read value to ips variable
			$p["energy_counter_last_read"]->setValue($current);
			
			//save counter value to ips variable
			$counter = $p["energy_counter"]->setValue($counter);
		}
		
		//now we have to create the statistics
		$this->statistics->setValue($this->createHTML());
	}
	
	public function switchLightOn($instanceId){
		foreach ($this->lightsources as &$light) {
			if($light["device"]->getInstanceId() == $instanceId){
				$light["device"]->switchOn();
			}
		} 
	}
	
	public function switchLightOff($instanceId){
		foreach ($this->lightsources as &$light) {
			if($light["device"]->getInstanceId() == $instanceId){
				$light["device"]->switchOff();
			}
		} 
	}
	
	public function dimLight($instanceId, $level){
		foreach ($this->lightsources as &$light) {
			if($light["device"]->getInstanceId() == $instanceId){
				$light["device"]->dim($level);
			}
		} 
	}
	
	public function statusChanged($event){
		
	}
	
	/**
	* creates an html string containing the statistics table for all light sources
	*
	* @access private
	*/
	private function createHTML(){
		$doc = new DOMDocument();
		
		$html = "<html><head></head>";
		$html .= "</html>";
		
		$doc->loadHTML($html);
		$val = $doc->saveHTML();
		return $val;
	}
	
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
		}
		
		//delete statistics variable
		echo "delete statistics variable for light control\n";
		$this->statistics->delete();
		
		//delete all profiles
		echo "delete light control variable profiles\n";
		$this->variableProfiles[0]->delete();
		$this->variableProfiles[2]->delete();
		
		//delete events
		echo "TODO remove light control events\n";
		
		echo "LightControl uninstall successful\n";
	}
}
?>