<?
/**
 * Basic abstract class that implements the interface ILightSource
 *
 * This is a base implementation of the interface ILightSource for light source devices.
 * Each different light source device (e.g. different manufacturer or model) has to implement the
 * interface ILightSource. This abstract class supports basic IPS methods that should fit for all
 * IP-Symcon devices. (e.g. handling instanceId, etc.)
 * 
 * @link https://github.com/florianprobst/ips-lightcontrol project website
 * 
 * @author Florian Probst <florian.probst@gmx.de>
 * 
 * @license GNU
 * GNU General Public License, version 3
 */

require_once 'ILightSource.interface.php';

/**
* abstract class AbstractLightSource
* 
* @uses ILightSource as interface
*/
abstract class AbstractLightSource implements ILightSource{
	
	/**
  * IP-Symcon instance id of the light source device
  *
  * @var int
  * @access protected
  */
	protected $instanceId;
	
	/**
  * light source name
  *
  * @var string
  * @access protected
  */
	protected $name;
	
	/**
  * light source device manufacturer
  *
  * @var string
  * @access private
  */
	private $manufacturer;
	
	/**
  * light source device model
  *
  * @var string
  * @access private
  */
	private $model;
	
	/**
	* unknown device manufacturer
	* @const UNKNOWN_MANUFACTURER
  * @access private
	*/
	const UNKNOWN_MANUFACTURER = "UNKNOWN";
	
	/**
	* unknown device manufacturer
	* @const UNKNOWN_MODEL
  * @access private
	*/
	const UNKNOWN_MODEL = "UNKNOWN";
	
	/**
	* Constructor
	* 
	* @param int $instanceId IP-Symcon instance id of the light source device
	* @throws Exception if the parameter \$instanceId is not of type 'integer'
	* @return AbstractLightSource|null the object or null if an error occured
	* @access public
	*/
	public function __construct($instanceId, $name, $manufacturer = self::UNKNOWN_MANUFACTURER, $model = self::UNKNOWN_MODEL){
		if(!is_int($instanceId))
			throw new Exception("Parameter \$instanceId is not of type 'integer'.");
		$this->instanceId = $instanceId;
		$this->name = $name;
		$this->setDeviceManufacturer($manufacturer);
		$this->setDeviceModel($model);
	}
	
	/**
	* getInstanceId
	* 
	* @return int IP-Symcon instance id of the light source device
	* @access public
	*/
	public function getInstanceId(){
		return $this->instanceId;
	}
	
	/**
	* getName
	* 
	* @return string name / description of the light source
	* @access public
	*/
	public function getName(){
		return $this->name;
	}
	
	/**
	* isOn
	* 
	* @return boolean returns if light source is switched on
	* @access public
	*/
	abstract public function isOn();
	
	/**
	* getDeviceManufacturer
	* 
	* @return string device manufacturer
	* @access public
	*/
	public function getDeviceManufacturer(){
		return $this->manufacturer;
	}
	
	/**
	* getDeviceModel
	* 
	* @return string device model
	* @access public
	*/
	public function getDeviceModel(){
		return $this->model;
	}
	
	/**
	* setDeviceManufacturer
	* 
	* @param string $manufacturer name of the device manufacturer
	* @access public
	*/
	public function setDeviceManufacturer($manufacturer){
		$this->manufacturer = $manufacturer;
	}
	
	/**
	* setDeviceModel
	* 
	* @param string $model name of the device model
	* @access public
	*/
	public function setDeviceModel($model){
		$this->model = $model;
	}
	
	/**
	* switchOn
	* 
	* @access public
	*/
	abstract public function switchOn();
	
	/**
	* switchOff
	* 
	* @access public
	*/
	abstract public function switchOff();
}
?>