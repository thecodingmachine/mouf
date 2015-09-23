<?php
namespace Mouf\Validator;

use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;
use Mouf\MoufException;

/**
 * Check that there is no loop on constructor parameters
 */
class CheckConstructorLoopValidator implements MoufStaticValidatorInterface {

	/**
	 * Check all constructor arguments to detect a loop
	 *
	 * @return MoufValidatorResult
	 */
	public static function validateClass() {
		$moufManager = MoufManager::getMoufManager();
		
		try {
            $moufManager->checkConstructorLoop();
			return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "No loop detected in constructor arguments.");
		}
		catch(MoufException $e) {
			return new MoufValidatorResult(MoufValidatorResult::ERROR, '<b>'.$e->getMessage().'</b><br /><br />
			                 Please check yours instances to refactor your code and change your code architecture.<br />
			                 The other ugly solution is to make a setter for one of this parameter to remove it from constructor argument');
		}
	}

}