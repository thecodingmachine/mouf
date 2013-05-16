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
		
		return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "Your php settings are ok.");
	}

}