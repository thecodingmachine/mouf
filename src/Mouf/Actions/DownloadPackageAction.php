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
 * An action that download a package.
 * 
 * @author david
 * @Component
 */
class DownloadPackageAction implements MoufActionProviderInterface {
	
	/**
	 * @Property
	 * @Compulsory
	 * @var MoufPackageDownloadService
	 */
	public $packageDownloadService;
	
	/**
	 * Executes the action passed in parameter.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 * @return MoufActionResultInterface
	 */
	public function execute(MoufActionDescriptor $actionDescriptor) {
		
		if ($actionDescriptor->selfEdit == true) {
			$moufManager = MoufManager::getMoufManager();
		} else {
			$moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		$this->packageDownloadService->setMoufManager($moufManager);
		
		$repositoryUrl = $actionDescriptor->params['repositoryUrl'];
		$group = $actionDescriptor->params['group'];
		if (strpos($group, "/") === 0) {
			$group = substr($group, 1);
		}
		$name = $actionDescriptor->params['name'];
		$version = $actionDescriptor->params['version'];
		
		$repository = $this->packageDownloadService->getRepository($repositoryUrl);
		
		if ($repository == null) {
			throw new MoufException("Unable to find repository pointing to URL ".$repositoryUrl);
		}
		
		$this->packageDownloadService->downloadAndUnpackPackage($repository, $group, $name, $version);
		
		return new MoufActionDoneResult();
	}
	
	/**
	 * Returns the text describing the action.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 */
	public function getName(MoufActionDescriptor $actionDescriptor) {
		return "Downloading package ".$actionDescriptor->params['group']."/".$actionDescriptor->params['name']."/".$actionDescriptor->params['version']." from repository ".$actionDescriptor->params['repositoryUrl'];
	}
	
}