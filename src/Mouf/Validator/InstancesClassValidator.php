<?php
namespace Mouf\Validator;

use Mouf\MoufManager;

/**
 * Validates that all instances are assigned to a class that does exist.
 */
class InstancesClassValidator implements MoufStaticValidatorInterface {

	/**
	 * Runs the validation of the class.
	 * Returns a MoufValidatorResult explaining the result.
	 *
	 * @return MoufValidatorResult
	 */
	public static function validateClass() {
		$moufManager = MoufManager::getMoufManager();
		
		$instancesList = $moufManager->getInstancesList();
		$selfedit = isset($_GET['selfedit'])?$_GET['selfedit']:"";
		
		$errors = array();
		foreach ($instancesList as $instanceName=>$className) {
			if (!class_exists($className)) {
				$errors[] = "<li>".$instanceName." - class not found: <strong>".$className."</strong> : <a href='".MOUF_URL."mouf/deleteInstance?instanceName=".urlencode($instanceName)."&selfedit=".$selfedit."&returnurl=".urlencode(MOUF_URL."validate/?selfedit=".$selfedit)."' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a></li>";
			}
		}

		if ($errors) {
			$msg = "The following instances are erroneous. They are pointing to a class that no longer exist. You should delete those to avoid any problem, or fix your class.<br/><ul>";
			$msg .= implode("\n", $errors);
			$msg .= "</ul>";
			return new MoufValidatorResult(MoufValidatorResult::ERROR, $msg);
		} else {
			return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "All your instances are associated with existing classes.");
		}
		
		
		
	}

}