<?php
namespace Mouf\Validator;

/**
 * Validate php settings
 */
class SettingsValidator implements MoufStaticValidatorInterface {

	/**
	 * Runs the validation of the class.
	 * Returns a MoufValidatorResult explaining the result.
	 *
	 * @return MoufValidatorResult
	 */
	public static function validateClass() {
		if (extension_loaded('suhosin')) {
			$blacklist = ini_get('suhosin.executor.include.blacklist'); 
			$whitelist = ini_get('suhosin.executor.include.whitelist');
			$blacklistArray = array();
			$whitelistArray = array();
			
			if ($blacklist) {
				$blacklistArray = explode(',', $blacklist);
			}
			if ($whitelist) {
				$whitelistArray =  explode(',', $whitelist);
			}
			
			if (array_search('phar', $blacklistArray) !== false) {
				return new MoufValidatorResult(MoufValidatorResult::ERROR, "The phar extension is blacklisted by Suhosin. Please edit your php.ini file and add 'phar' to the 'suhosin.executor.include.whitelist'.");
			}
			if (array_search('phar', $whitelistArray) === false) {
				return new MoufValidatorResult(MoufValidatorResult::ERROR, "The phar extension is blacklisted by Suhosin. Please edit your php.ini file and add 'phar' to the 'suhosin.executor.include.whitelist'.");
			}
				
		}		
		
		if (extension_loaded('apc')) {
			$shm_size = self::return_bytes(ini_get('apc.shm_size'));
			if ($shm_size < 128 * 1024 *1024) {
				return new MoufValidatorResult(MoufValidatorResult::WARN, "Your APC cache settings are a bit low. This might slow down Mouf. Please edit your php.ini file and change the value of <strong>apc.shm_size</strong> to 256M for example");
			}
		}
		
		if (extension_loaded('Zend OPcache')) {
			$op_validate_timestamp = ini_get('opcache.validate_timestamps');
			if($op_validate_timestamp == 0) {
				return new MoufValidatorResult(MoufValidatorResult::ERROR, "Your opcache settings do not revalidate files automatically. If it is a development environment, please edit your php.ini file and change the value of <strong>opcache.validate_timestamps</strong> to 1.");
			}
						
			if (ini_get('opcache.save_comments') == 0) {
				return new MoufValidatorResult(MoufValidatorResult::ERROR, "Your opcache settings do not store comments therefore annotations. Please edit your php.ini file and change the value of <strong>opcache.save_comments</strong> to 1.");
			}
		}
		
		return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "Your php settings are ok.");
	}
	
	private static function return_bytes($val) {
   	 $val = trim($val);
   	 $last = strtolower($val[strlen($val)-1]);
    	switch($last) {
       	 	// Le modifieur 'G' est disponible depuis PHP 5.1.0
        	case 'g':
           		$val *= 1024;
        	case 'm':
            	$val *= 1024;
        	case 'k':
            	$val *= 1024;
    	}

    	return $val;
	}
}