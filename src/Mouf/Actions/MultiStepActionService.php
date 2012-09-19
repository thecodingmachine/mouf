<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Actions;

/**
 * This service manages actions that are run in multiple steps (like downloading a set
 * of packages, enabling them and running the install scripts...)
 * 
 * @author David
 * @Component
 */
class MultiStepActionService {
	
	/**
	 * The file that contains the list of all actions to be executed.
	 * Relative to the ROOT_PATH directory. Do not start with a slash.
	 * 
	 * @Property
	 * @Compulsory
	 * @var string
	 */
	public $actionsStoreFile = "moufRunningActions.php";
	
	/**
	 * This list of actions descriptor.
	 * @var array(array("instanceName", "params"))
	 */
	private $actionsDescriptorList;
	
	/**
	 * The URL to redirect at the end of the process.
	 * 
	 * @var string
	 */
	private $finalUrlRedirect;
	
	/**
	 * The message to display at the end of the process.
	 * 
	 * @var string
	 */
	private $confirmationMessage;
	
	/**
	 * Returns the URL to redirect at the end of the process.
	 * 
	 * @return string
	 */
	public function getFinalUrlRedirect() {
		$this->loadActionsDescriptor();
		return $this->finalUrlRedirect;
	}
	
	/**
	 * Sets the URL to redirect at the end of the process.
	 */
	public function setFinalUrlRedirect($url) {
		$this->loadActionsDescriptor();
		$this->finalUrlRedirect = $url;
		$this->save();
	}
	
	/**
	 * Returns the confirmation message.
	 * 
	 * @return string
	 */
	public function getConfirmationMessage() {
		$this->loadActionsDescriptor();
		return $this->confirmationMessage;
	}
	
	/**
	 * Sets the confirmation message.
	 */
	public function setConfirmationMessage($confirmationMessage) {
		$this->loadActionsDescriptor();
		$this->confirmationMessage = $confirmationMessage;
		$this->save();
	}
	
	private function loadActionsDescriptor() {
		if ($this->actionsDescriptorList == null) {
			if (file_exists(ROOT_PATH.$this->actionsStoreFile)) {
				include ROOT_PATH.$this->actionsStoreFile;
				$this->actionsDescriptorList = $actions;
				if (isset($finalUrl)) {
					$this->finalUrlRedirect = $finalUrl;
				}
				if (isset($confirmationMessage)) {
					$this->confirmationMessage = $confirmationMessage;
				}
			} else {
				$this->actionsDescriptorList = array();
			}
		}
	}
	
	/**
	 * Adds a new action to the list of actions to be executed.
	 * 
	 * @param string $instanceName
	 * @param mixed $params (Must be serializable)
	 */
	public function addAction($instanceName, $params, $selfEdit=false) {
		$this->loadActionsDescriptor();
		$this->actionsDescriptorList[] = array("actionProvider"=>$instanceName, "params"=>$params, "status"=>"todo", "selfEdit"=>$selfEdit);
		$this->save();
	}
	
	/**
	 * Returns the list of actions.
	 * 
	 * @return array<MoufActionDescriptor>
	 */
	public function getActionsList() {
		$this->loadActionsDescriptor();
		$array = array();
		foreach ($this->actionsDescriptorList as $actionDescriptorArr) {
			$array[] = new MoufActionDescriptor($actionDescriptorArr['actionProvider'], $actionDescriptorArr['params'], $actionDescriptorArr['status'], $actionDescriptorArr['selfEdit']);
		}
		return $array;
	}
	
	/**
	 * Returns the next action to be executed, or null if there are no more actions to be executed.
	 * 
	 * @return MoufActionDescriptor
	 */
	public function getNextAction() {
		$this->loadActionsDescriptor();
		$array = array();
		foreach ($this->actionsDescriptorList as $actionDescriptorArr) {
			if ($actionDescriptorArr["status"] == 'todo') {
				return new MoufActionDescriptor($actionDescriptorArr['actionProvider'], $actionDescriptorArr['params'], $actionDescriptorArr['status'], $actionDescriptorArr['selfEdit']);
			}
		}
		return null;
	}
	
	/**
	 * Returns true if there are remaining actions to be performed. False otherwise.
	 * @return bool
	 */
	public function hasRemainingAction() {
		return $this->getNextAction() != null;
	}
	
	/**
	 * Executes the next action to be executed.
	 * //Any output will be considered as a warning, any exception as an error.
	 * 
	 * Returns the result from the action that was exectuted, or null if there was no action to execute.
	 * 
	 * @return MoufActionResultInterface
	 */
	public function executeNextAction() {

		$this->loadActionsDescriptor();
		$array = array();
		foreach ($this->actionsDescriptorList as $key=>$actionDescriptorArr) {
			if ($actionDescriptorArr["status"] == 'todo') {
				$actionDescriptor = new MoufActionDescriptor($actionDescriptorArr['actionProvider'], $actionDescriptorArr['params'], $actionDescriptorArr['status'], $actionDescriptorArr['selfEdit']);
				try {
					$actionResult = $actionDescriptor->execute();
					if ($actionResult->getStatus() == "done") {
						$this->actionsDescriptorList[$key]['status'] = "done";
						$this->save();
					}
					return $actionResult;
				} catch (Exception $e) {
					$this->actionsDescriptorList[$key]['status'] = "error";
					$this->save();
					throw $e;
				}
			}
		}
		return null;
	}
	
	/**
	 * If the call to executeNextAction did not validate the action directly, some action needs to be taken.
	 * At the end of those actions, a call to validateCurrentAction will validate the action.
	 * 
	 */
	public function validateCurrentAction() {
		$this->loadActionsDescriptor();
		$array = array();
		foreach ($this->actionsDescriptorList as $key=>$actionDescriptorArr) {
			if ($actionDescriptorArr["status"] == 'todo') {
				$this->actionsDescriptorList[$key]['status'] = "done";
				$this->save();
				return;
			}
		}
		return;
	}
	
	
	/**
	 * Creates the actionsStoreFile.
	 */
	private function save() {
		if (!is_writable(dirname(ROOT_PATH.$this->actionsStoreFile)) || (file_exists(ROOT_PATH.$this->actionsStoreFile) && !is_writable(ROOT_PATH.$this->actionsStoreFile))) {
			$dirname = realpath(dirname(ROOT_PATH.$this->actionsStoreFile));
			$filename = basename(ROOT_PATH.$this->actionsStoreFile);
			throw new MoufException("Error, unable to write file ".$dirname."/".$filename);
		}
		$this->loadActionsDescriptor();
		
		$fp = fopen(ROOT_PATH.$this->actionsStoreFile, "w");
		fwrite($fp, "<?php\n");
		fwrite($fp, "/**\n");
		fwrite($fp, " * This is a file automatically generated by the Mouf framework. Do not modify it, as it could be overwritten.\n");
		fwrite($fp, " * It contains the list of actions to be exectued as part of an install process.\n");
		fwrite($fp, " * Do not modify it, as it could be overwritten.\n");
		fwrite($fp, " */\n");
		fwrite($fp, "\$actions=".var_export($this->actionsDescriptorList, true).";\n");
		fwrite($fp, "\$finalUrl=".var_export($this->finalUrlRedirect, true).";\n");
		fwrite($fp, "\$confirmationMessage=".var_export($this->confirmationMessage, true).";\n");
		fwrite($fp, "?>");
		fclose($fp);
	}
	
	/**
	 * Redirects to the user to the page that will start performing the actions.
	 * @param bool $selfEdit
	 */
	public function executeActions($selfEdit) {
		header("Location: ".ROOT_URL."install/?selfedit=".($selfEdit==true?"true":"false"));
	}
	
	/**
	 * Removes any actions to be performed.
	 * 
	 */
	public function purgeActions() {
		if (file_exists(ROOT_PATH.$this->actionsStoreFile)) {
			unlink(ROOT_PATH.$this->actionsStoreFile);
		}
	}
}