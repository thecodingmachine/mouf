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
 * An action that enables a package.
 * If another version of the package is already present, the other version will be disabled before installing this version.
 * 
 * @author david
 * @Component
 */
class EnablePackageAction implements MoufActionProviderInterface {
	/**
	 * Executes the action passed in parameter.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 */
	public function execute(MoufActionDescriptor $actionDescriptor) {
		if ($actionDescriptor->selfEdit == true) {
			$moufManager = MoufManager::getMoufManager();
		} else {
			$moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$fileName = $actionDescriptor->params['packageFile'];
		if (strpos($fileName, "/") === 0) {
			$fileName = substr($fileName, 1);
		}
		
		if (!file_exists(ROOT_PATH."plugins/".$fileName)) {
			throw new MoufException("Unable to enable package: the file plugins/".$fileName." does not exist.");
		}
		
		$moufManager->addPackageByXmlFileWithCheck($fileName, $actionDescriptor->params['scope']);
		$moufManager->rewriteMouf();
		return new MoufActionDoneResult();
	}
	
	/**
	 * Returns the text describing the action.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 */
	public function getName(MoufActionDescriptor $actionDescriptor) {
		return "Enabling package ".$actionDescriptor->params['packageFile']." in ".$actionDescriptor->params['scope']." scope.";
	}
	
}