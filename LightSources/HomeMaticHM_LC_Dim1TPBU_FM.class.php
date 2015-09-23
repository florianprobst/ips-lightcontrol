<?
/**
 * Implementation of the HomeMatic Wireless Dim Actuator, 1-channel model HM-LC-Dim1TPBU-FM
 * 
 * This class supports the HomeMatic Wireless Dim Actuator, 1-channel model HM-LC-Dim1TPBU-FM device connected to IP-Symcon.
 * 
 * This model has 2 channels:
 * * Channel 0 - MAINTENANCE:	contains HomeMatic maintenance variables (we dont need them)
 * * Channel 1 - ACTUATOR:		contains light source control variables (that's what we want)
 *		-> variable 'LEVEL' = this HomeMatic variable is responsible for turning the light on or off or dim it to a specific relative level
 * 
 * The light source name of this model will be read from CHANNEL 1 "Actuator" since it's most likely
 * that the IPS user names the channel 1 according to the light he wants to switch on or off.
 * This class searches for the channel 1 device using the unique homematic actuator serial number.
 *
 * @link https://github.com/florianprobst/ips-lightcontrol project website
 * 
 * @author Florian Probst <florian.probst@gmx.de>
 * 
 * @license GNU
 * GNU General Public License, version 3
 */

require_once 'AbstractLightSource.class.php';

/**
* class HomeMaticHM_LC_Sw1_FM
* 
* @uses AbstractLightSource as parent class
*/
class HomeMaticHM_LC_Dim1TPBU_FM extends AbstractLightSource{
	/**
	* device manufacturer
	* @const MANUFACTURER
  * @access private
	*/
	const MANUFACTURER = "HomeMatic";
	
	/**
	* device model
	* @const MODEL
  * @access private
	*/
	const MODEL = "HM-LC-Dim1TPBU-FM";
	
	/**
	* IPS module Id
	* 
	* a unique ID that IP-Symcon serves for each module type / manufacturer combination
	* 
	* @const MODULE_ID
  * @access private
	*/
	const MODULE_ID = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
	
	/**
  * HomeMatic unique serial number / id without channel
  *
  * @var string
  * @access private
  */
	private $address;
	
	/**
	* LightSource control variables id
	*
	* @var boolean
	* @access private
	*/
	private $controlVariable;
	
	/**
	* Constructor
	* 
	* @param int $lightSourceInstanceId IP-Symcon instance id of the light source device (in this case channel 1 of the device)
	* @throws Exception if the parameter \$lightSourceInstanceId is not of type 'integer'
	* @throws Exception if the devices ModuleID is not a HomeMatic Device ModuleID'
	* @return HomeMaticHM_LC_Sw1_FM|null the object or null if an error occured
	* @access public
	*/
	public function __construct($lightSourceInstanceId){
		parent::__construct($lightSourceInstanceId, "UNDEFINED", self::MANUFACTURER, self::MODEL);
		
		//first we check if it's an HomeMatic Device
		$instance = IPS_GetInstance($this->instanceId);
		if($instance["ModuleInfo"]["ModuleID"] != self::MODULE_ID)
			throw new Exception("The device ModuleID does not match a HomeMatic Device. Please check if the IPS device instanceId is a HM-ES-PMSw1-Pl Device Channel 2");

		$tmpAddress = $this->getAddress($this->instanceId);
		$this->address = substr($tmpAddress,0,strlen($tmpAddress)-2);

		$this->name = IPS_GetName($this->instanceId);
		
		$this->controlVariable = @IPS_GetObjectIDByName ('LEVEL', $this->instanceId);
		if(!isset($this->controlVariable))
			throw new Exception("The device does not contain the light control variable 'LEVEL' which is necessary to switch the device on or off");
		
		//if no exception was thrown everything should be fine.
	}
	
	/**
	* getAddress
	* 
	* @param int $instanceId the instance id of the homematic device
	* @return string unique homematic address / serial number / id + channel
	* @access private
	*/
	private function getAddress($instanceId){
		$conf = IPS_GetConfiguration($instanceId);
		$json = json_decode($conf, true);
		return $json["Address"];
	}
	
	/**
	* isOn
	* 
	* @return boolean returns if light source is switched on
	* @access public
	*/
	public function isOn(){
		$level = GetValueFloat($this->controlVariable);
		if($level == 0)
			return false;
		return true;
	}
	
	/**
	* getLevel
	*
	* @return float returns the lights relative dim level (0 off, 1 = 100%)
	* @access public
	*/
	public function getLevel(){
		return GetValueFloat($this->controlVariable);
	}
	
	/**
	* switchOn
	* 
	* @access public
	*/
	public function switchOn(){
		HM_WriteValueFloat($this->getInstanceId(), 'LEVEL', 1);
	}
	
	/**
	* switchOff
	* 
	* @access public
	*/
	public function switchOff(){
		HM_WriteValueFloat($this->getInstanceId(), 'LEVEL', 0);
	}
	
	
	/**
	* dim
	* 
	* @param float $level relative dim level from 0 (off) to 1 (100%)
	* @access public
	*/
	public function dim($level){
		HM_WriteValueFloat($this->getInstanceId(), 'LEVEL', $level);
	}
}
?>