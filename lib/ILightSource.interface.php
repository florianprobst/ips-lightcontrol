<?
/**
 * Interface for light source device connected to IP-Symcon
 *
 * This interface describes the methods all power meter device abstraction layers
 * must implement
 * 
 * @link https://github.com/florianprobst/ips-lightsource project website
 * 
 * @author Florian Probst <florian.probst@gmx.de>
 * 
 * @license GNU
 * GNU General Public License, version 3
 */

/**
* interface ILightSource
*/
interface ILightSource{
	/**
	* getInstanceId
	* 
	* @return int IP-Symcon instance id of the light source device
	* @access public
	*/
	public function getInstanceId();
	
	/**
	* getName
	* 
	* @return string name / description of the light source
	* @access public
	*/
	public function getName();
	
	/**
	* isOn
	* 
	* @return boolean returns if light source is switched on
	* @access public
	*/
	public function isOn();
	
	/**
	* getDeviceManufacturer
	* 
	* @return string device manufacturer
	* @access public
	*/
	public function getDeviceManufacturer();
	
	/**
	* getDeviceModel
	* 
	* @return string device model
	* @access public
	*/
	public function getDeviceModel();
	
	/**
	* setDeviceManufacturer
	* 
	* @param string $manufacturer name of the device manufacturer
	* @access public
	*/
	public function setDeviceManufacturer($manufacturer);
	
	/**
	* setDeviceModel
	* 
	* @param string $model name of the device model
	* @access public
	*/
	public function setDeviceModel($model);
	
	/**
	* switchOn
	* 
	* @access public
	*/
	public function switchOn();
	
	/**
	* switchOff
	* 
	* @access public
	*/
	public function switchOff();
}
?>