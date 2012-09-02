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
 * A simple object describing an action to be executed by the MultiStepActionService.
 * 
 * @author david
 */
class MoufActionDescriptor {
	
	/**
	 * The action provider (the instanceName of a class implementing the MoufActionProviderInterface).
	 * 
	 * @var string
	 */
	public $actionProviderName;
	
	/**
	 * The parameters to be passed to the action provider (must be serializable)
	 * 
	 * @var mixed
	 */
	public $params;
	
	/**
	 * The status (one of: "todo", "done", "error")
	 * 
	 * @var string
	 */
	public $status;
	
	/**
	 * Whether this action is to be executed in selfedit mode or not.
	 * 
	 * @var bool
	 */
	public $selfEdit;
	
	public function __construct($actionProviderName, $params, $status, $selfEdit = false) {
		$this->actionProviderName = $actionProviderName;
		$this->params = $params;
		$this->status = $status;
		$this->selfEdit = $selfEdit;
	}
	
	/**
	 * Runs the action!
	 * The action should not return anything. It can throw an error in case a problem is detected.
	 * @return MoufActionResultInterface
	 */
	public function execute() {
		// An action is always executed in the MoufAdmin scope.
		$moufManager = MoufManager::getMoufManager();
		$actionProvider = $moufManager->getInstance($this->actionProviderName);
		/* @var $actionProvider MoufActionProvider */
		return $actionProvider->execute($this);
	}
	
	/**
	 * Returns the name for the action.
	 * @return string
	 */
	public function getName() {
		// An action is always executed in the MoufAdmin scope.
		$moufManager = MoufManager::getMoufManager();
		$actionProvider = $moufManager->getInstance($this->actionProviderName);
		/* @var $actionProvider MoufActionProvider */
		return $actionProvider->getName($this);
	}
}