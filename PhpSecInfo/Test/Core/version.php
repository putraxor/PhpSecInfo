<?php
/**
 * Test class for PHP Version
 *
 * @package PhpSecInfo
 * @author Glenn S Crystal <glenn@gcosoftware.com>
 */

/**
 * require the PhpSecInfo_Test_Curl class
 */
require_once('PhpSecInfo/Test/Test_Core.php');

/**
 * Test class for PHP Version
 *
 * Checks the current PHP Version against EOL Versions.
 *
 *
 * @package PhpSecInfo
 * @author Glenn S Crystal <glenn@gcosoftware.com>
 */
class PhpSecInfo_Test_Core_Version extends PhpSecInfo_Test_Core
{

	/**
	 * This should be a <b>unique</b>, human-readable identifier for this test
	 *
	 * @public string
	 */
	public $test_name = "version_number";

	public $recommended_value = '5.6.15';
	public $last_eol_value = '5.4.45';
	
	private $_message_ok = "You are running a current stable version of PHP!";
	

	function _retrieveCurrentValue() {
		$this->current_value = PHP_VERSION;
//		$this->current_value = '5.6.15';
	}
	
	
	/**
	 * Attempts to fetch from GCO Software's server the latest versions of PHP
	 * if CURL is not installed or we can not reach the server the latest
	 * recommended value is returned instead
	 *
	 * @return string
	 */
	 public function _retrieveCurrentVersions() {
	 	if (!function_exists('curl_init')) {
				// Override the OK Message - Even if this passes we can't be 100% sure they are accurate since we didn't fetch the latest version
				$this->_message_ok = "You are running a current stable version of PHP!
						<br /><strong>NOTE:</strong> CURL was unable to fetch the latest PHP Versions from the internet. This test may not be accurate if
						PhpSecInfo is not up to date.";
				
				return array( 'stable' => $this->recommended_value, 'eol' => $this->last_eol_value);
	 	}
	 	
	 		// Attempt to fetch from server
	 		$uri = 'https://www.gcosoftware.com/current_version.php?system=php';
	 		$ch = curl_init();
	 		$timeout = 5;
	 		
	 		// CURL
			curl_setopt($ch, CURLOPT_URL, $uri);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($ch);
			
			// Detect CURL error and return local value
			if ($data === false) {
				// Close CURL connection
				curl_close($ch);
				
				// Override the OK Message - Even if this passes we can't be 100% sure they are accurate since we didn't fetch the latest version
				$this->_message_ok = "You are running a current stable version of PHP!
						<br /><strong>NOTE:</strong> CURL was unable to fetch the latest PHP Versions from the internet. This test may not be accurate if
						PhpSecInfo is not up to date.";
				
				return array( 'stable' => $this->recommended_value, 'eol' => $this->last_eol_value);
			}
			
			// Close CURL
			curl_close($ch);
			
			// Decode json
			$json = json_decode($data);
			
			// to array
			$versions = array( 'stable' => $json->stable, 'eol' => $json->eol);
			
			// Update local recommended value (it is used elsewhere and we don't want to modify that code just yet)
			$this->recommended_value = $json->stable;
			
			
			return $versions;
	 }

	/**
	 * Checks to see if the current PHP version is acceptable
	 * @return integer
	 *
	 */
	function _execTest() {
		// Get versions
		$version = $this->_retrieveCurrentVersions();
		
		if ( version_compare($this->current_value, $version['stable'], '>=') ) {
			return PHPSECINFO_TEST_RESULT_OK;
		} else if ( version_compare($this->current_value, $version['stable'], '<') && version_compare($this->current_value, $version['eol'], '>') ) {
			return PHPSECINFO_TEST_RESULT_NOTICE;
		} else {
			return PHPSECINFO_TEST_RESULT_WARN;
		}

	}



	/**
	 * Set the messages specific to this test
	 *
	 */
	function _setMessages() {
		parent::_setMessages();
		
		// HACK: Force to grab current versions - this will fetch the latest version
		$this->_retrieveCurrentVersions();
		
		$this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', $this->_message_ok);
		$this->setMessageForResult(PHPSECINFO_TEST_RESULT_WARN, 'en', "You are running a version of PHP that has reached End of Life for support.  You should upgrade to the latest version of PHP immediately.");
		$this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'You are running a version of PHP that is near End of Life for support.  You should begin to migrate to the latest version of PHP as soon as possible.');

	}

}