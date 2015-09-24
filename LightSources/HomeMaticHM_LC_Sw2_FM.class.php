<?
/**
 * Implementation of the HomeMatic Wireless Switch Actuator, 2-channel model HM-LC-Sw2-FM
 * 
 * This class supports the HomeMatic Wireless Switch Actuator, 2-channel model HM-LC-Sw2-FM device connected to IP-Symcon.
 * 
 * The 2-channel switch has the same layout as the 1-channel actuator with just one difference: it has 3 instead of 2 channels.
 * That's why we just extend the 1-channel actuator class in this file, but there is no need to change anything further.
 *
 * This model has 3 channels:
 * * Channel 0 - MAINTENANCE:	contains HomeMatic maintenance variables (we dont need them)
 * * Channel 1 - ACTUATOR for light source 1:		contains light control variables (that's what we want)
 *		-> variable 'STATE' = this HomeMatic variable is responsible for turning the light on or off
 * * Channel 2 - ACTUATOR for light source 2:		contains light control variables (that's what we want)
 *		-> variable 'STATE' = this HomeMatic variable is responsible for turning the light on or off
 * 
 * The light source name of this model will be read from CHANNEL 1/2 "Actuator" since it's most likely
 * that the IPS user names the channel 1/2 according to the light he wants to switch on or off.
 * This class searches for the channel 1/2 device using the unique homematic actuator serial number.
 *
 * @link https://github.com/florianprobst/ips-lightcontrol project website
 * 
 * @author Florian Probst <florian.probst@gmx.de>
 * 
 * @license GNU
 * GNU General Public License, version 3
 */
 
 require_once 'HomeMaticHM_LC_Sw1_FM.class.php';
 
 /**
* class HomeMaticHM_LC_Sw2_FM
* 
* @uses HomeMaticHM_LC_Sw1_FM as parent class
*/
class HomeMaticHM_LC_Sw2_FM extends HomeMaticHM_LC_Sw1_FM{
	public function __construct($lightSourceInstanceId){
		parent::__construct($lightSourceInstanceId);
	}
}
?>