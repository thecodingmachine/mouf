<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Controllers;

use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller managing the list of packages included in the Mouf plugins directory.
 *
 * @Component
 */
class PackageController extends Controller implements DisplayPackageListInterface {

	public $selfedit;
	
	/**
	 * The active MoufManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $moufManager;
	
	/**
	 * The template used by the main page for mouf.
	 *
	 * @Property
	 * @Compulsory
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @Property
	 * @Compulsory
	 * @var HtmlBlock
	 */
	public $contentBlock;
	
	/**
	 * The service that will take actions to be performed to install.
	 * 
	 * @Property
	 * @Compulsory
	 * @var MultiStepActionService
	 */
	public $multiStepActionService;
	
	/**
	 * The package to enable/disable.
	 *
	 * @var MoufPackage
	 */
	public $package;
	
	/**
	 * The origin (if any) of the package to enable/disable. 
	 * 
	 * @var string
	 */
	public $origin;
	
	/**
	 * The list of packages found on the system.
	 *
	 * @var array<MoufPackage>
	 */
	public $moufPackageList;
	
	/**
	 * The root of the hierarchy of packages
	 * 
	 * @var MoufGroupDescriptor
	 */
	public $moufPackageRoot;
	
	
	/**
	 * The list of dependencies to the selected package.
	 *
	 * @var array<MoufPackage>
	 */
	public $moufDependencies;
	
	
	/**
	 * The list of packages that are incompatible with the current enable/update and that should be proposed
	 * for an updage.
	 *
	 * @var array<MoufIncompatiblePackageException>
	 */
	protected $toProposeUpgradePackage;
	
	/**
	 * The list of packages that will be upgraded in the process of the install.
	 *
	 * @var array<scope, <group/name, MoufPackage>>
	 */
	protected $upgradePackageList;
	
	public $validationMsg;
	public $validationPackageList;
	
	
	/**
	 * A list of all instances to delete with the package.
	 * 
	 *
	 * @var array<instancename, classname>
	 */
	public $toDeleteInstance;
	
	/**
	 * 
	 * @var MoufPackageManager
	 */
	public $packageManager;
	
	/**
	 * Displays the list of component files
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 * @param string $validation The validation message to display (either null, or enable or disable).
	 * @param array<string> $packageList The array of packages enabled or disabled.
	 */
	public function defaultAction($selfedit = "false", $validation = null, $packageList = null) {
		// TODO: CHANGE THE PACKAGE CONTROLLER SO WE CAN VIEW FROM THE APP SCOPE THE PACKAGES THAT ARE REQUESTED ON THE ADMIN SCOPE VIA A <scope>admin</scope> declaration.
		
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->validationMsg = $validation;
		$this->validationPackageList = $packageList;
				
		$this->packageManager = new MoufPackageManager();
		
		$this->moufPackageRoot = $this->packageManager->getOrderedPackagesList();
		
		$this->moufPackageList = $this->packageManager->getPackagesList();
		// Packages are almost sorted correctly.
		// However, we should make a bit of sorting to transform this:
		// javascript/jit
		// javascript/jquery/jquery
		// javascript/prototype
		// into this:
		// javascript/jit
		// javascript/prototype
		// javascript/jquery/jquery
		// (directories at the end)
		// Furthermore, we will sort packages with different version numbers by version number.
		// So we will sort by group, then package, then version:
		uasort($this->moufPackageList, array($this, "comparePackageGroup"));
		$this->contentBlock->addFile(ROOT_PATH."src/views/packages/displayPackagesList.php", $this);
		$this->template->toHtml();	
	}
	
	public function comparePackageGroup(MoufPackage $package1, MoufPackage $package2) {
		$group1 = $package1->getDescriptor()->getGroup();
		$group2 = $package2->getDescriptor()->getGroup();
		$cmp = strcmp($group1, $group2);
		if ($cmp == 0) {
			$nameCmp = strcmp($package1->getDescriptor()->getName(), $package2->getDescriptor()->getName());
			if ($nameCmp != 0) {
				return $nameCmp;
			} else {
				return -MoufPackageDescriptor::compareVersionNumber($package1->getDescriptor()->getVersion(), $package2->getDescriptor()->getVersion());
			} 
		} else 
			return $cmp;
	}
	
	/**
	 * Action that is run to enable a package.
	 *
	 * Format of the $upgradeList array: array[scope][]["group"=>xxx,"name"=>xxx,"version"=>xxx,"origin"=>xxx]
	 *
	 * @Action
	 * @Logged
	 * @param string $group
	 * @param string $name
	 * @param string $version
	 * @param string $selfedit
	 * @param string $confirm
	 * @param string $origin
	 * @param array $upgradeList
	 */
	public function enablePackage($group, $name, $version, $selfedit = "false", $confirm="false", $origin = null, array $upgradeList = array()) {
		// First, let's find the list of depending packages.
		$this->selfedit = $selfedit;
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		$scope = ($selfedit=='true')?MoufManager::SCOPE_ADMIN:MoufManager::SCOPE_APP;
		
		$this->packageManager = new MoufPackageManager();
		
		if ($origin == null) {
			$this->package = $this->packageManager->getPackageByDefinition($group, $name, $version);
		} else {
			// TODO: move $packageDownloadService as a property
			$packageDownloadService = MoufAdmin::getPackageDownloadService();
			$packageDownloadService->setMoufManager($this->moufManager);
			$this->origin = $origin;
			$this->package = $packageDownloadService->getRepository($origin)->getPackage($group, $name, $version);
		}

		// Let's get the list of all packages that will be upgraded in the process (requested by the user).
		$this->upgradePackageList = array();
		
		// Let's start by admin scope, and then, let's go to the app scope.
		$myUpgradeList = array();
		if (isset($upgradeList['admin'])) {
			$myUpgradeList['admin'] = $upgradeList['admin'];
		}
		if (isset($upgradeList['app'])) {
			$myUpgradeList['app'] = $upgradeList['app'];
		}
		
		foreach ($myUpgradeList as $myScope => $upgradeInnerList) {
			foreach ($upgradeInnerList as $upgrade) {
				if (isset($upgrade['origin']) && $upgrade['origin'] != "") {
					// TODO: move $packageDownloadService as a property
					$packageDownloadService = MoufAdmin::getPackageDownloadService();
					$packageDownloadService->setMoufManager($this->moufManager);
					$this->upgradePackageList[$myScope][$upgrade['group']."/".$upgrade['name']] = $packageDownloadService->getRepository($upgrade['origin'])->getPackage($upgrade['group'], $upgrade['name'], $upgrade['version']);
				} else {
					$this->upgradePackageList[$myScope][$upgrade['group']."/".$upgrade['name']] = $this->packageManager->getPackageByDefinition($upgrade['group'], $upgrade['name'], $upgrade['version']);
				}
			}
		}

		/*
		 * List of packages that should be proposed as an upgrade (as an array of MoufIncompatiblePackageException).
		 */ 
		$this->toProposeUpgradePackage = array();
		
		
		// For each package that was requested to be upgraded, let's check if the packages depending on it are still compatible.
		
		// Let's start with the package we try to install (in case this is an upgrade)
		$toProposeUpgrade = $this->packageManager->getParentPackagesRequiringUpdate($this->package, $this->upgradePackageList, $scope);
		$this->toProposeUpgradePackage = array_merge($this->toProposeUpgradePackage, $toProposeUpgrade);
		
		foreach ($this->upgradePackageList as $myScope=>$innerList) {
			foreach ($innerList as $toUpgradePackage) {
				/* @var $toUpgradePackage MoufPackage */
				$toProposeUpgrade = $this->packageManager->getParentPackagesRequiringUpdate($toUpgradePackage, $this->upgradePackageList, $myScope);
				$this->toProposeUpgradePackage = array_merge($this->toProposeUpgradePackage, $toProposeUpgrade);		
			}
		}
		
		
		//$this->moufDependencies = $this->packageManager->getDependencies($this->package);
		try {
			$this->moufDependencies = $this->packageManager->getDependencies($this->package, $scope, $this->upgradePackageList);
		} catch (MoufIncompatiblePackageException $ex) {
			$this->toProposeUpgradePackage[] = $ex;
		} catch (MoufProblemInDependencyPackageException $exProblem) {
			foreach ($exProblem->exceptions as $exception) {
				if ($exception instanceof MoufIncompatiblePackageException) {
					$this->toProposeUpgradePackage[] = $exception;
				} else {
					throw $exProblem;
				}
			}
		}
		
		// TODO: aggréger les MoufIncompatiblePackageException dans un objet plus intélligent.
		// TODO: aggréger les MoufIncompatiblePackageException 
		// TODO: aggréger les MoufIncompatiblePackageException 
		// TODO: aggréger les MoufIncompatiblePackageException 
		// TODO: aggréger les MoufIncompatiblePackageException 
		// TODO: aggréger les MoufIncompatiblePackageException 
		
		
		
		//var_dump($this->moufDependencies); exit;
				
		if (((!empty($this->moufDependencies[MoufManager::SCOPE_APP]) || !empty($this->moufDependencies[MoufManager::SCOPE_ADMIN])) && $confirm=="false") || $this->toProposeUpgradePackage) {
			$this->contentBlock->addFile(ROOT_PATH."src/views/packages/displayConfirmPackagesEnable.php", $this);
			$this->template->toHtml();
		} else {
			
			if (!array_search($this->package, $this->moufDependencies)) {
				$this->moufDependencies[$scope][] = $this->package;
			}

			
			// Upgrading goes first (TODO: check this).
			foreach ($myUpgradeList as $myScope=>$innerList) {
				foreach ($innerList as $upgradeOrder) {
					// special case: let's not make the upgrade if this is the main package to be installed (don't do it twice).
					if ($upgradeOrder['group'] == $group && $upgradeOrder['name'] == $name && $upgradeOrder['version'] == $version) {
						continue;
					}
					
					// Let's download if needed.
					if ($upgradeOrder['origin'] != null && $upgradeOrder['origin'] != "") {
						$this->multiStepActionService->addAction("downloadPackageAction", array(
								"repositoryUrl"=>$upgradeOrder['origin'],
								"group"=>$upgradeOrder['group'],
								"name"=>$upgradeOrder['name'],
								"version"=>$upgradeOrder['version']
								), $selfedit == "true");
					}
					// Now, let's perform the upgrade (this is handled by the enablePackageAction action).
					$this->multiStepActionService->addAction("enablePackageAction", array(
								"packageFile"=>$upgradeOrder['group']."/".$upgradeOrder['name']."/".$upgradeOrder['version']."/package.xml",
								"scope"=>$myScope), $selfedit == "true");
					
					// Now, let's see if there are specific installation steps.
					$thePackage = $this->packageManager->findPackage($upgradeOrder['group'], $upgradeOrder['name'], $upgradeOrder['version'], $this->moufManager);
	
					// FIXME: THIS IS AN UPGRADE!!!! NOT AN INSTALL!!!!!!!!!!
					// FIXME: THIS IS AN UPGRADE!!!! NOT AN INSTALL!!!!!!!!!!
					// FIXME: THIS IS AN UPGRADE!!!! NOT AN INSTALL!!!!!!!!!!
					// FIXME: THIS IS AN UPGRADE!!!! NOT AN INSTALL!!!!!!!!!!
					// FIXME: THIS IS AN UPGRADE!!!! NOT AN INSTALL!!!!!!!!!!
					// WE SHOULD FIND SOMETHING MORE CLEVER TO PERFORM THE UPGRADE!!!!
					$installSteps = $thePackage->getInstallSteps();
					if ($installSteps) {
						foreach ($installSteps as $installStep) {
							if ($installStep['type'] == 'file') {
								$this->multiStepActionService->addAction("redirectAction", array(
									"packageFile"=>$upgradeOrder['group']."/".$upgradeOrder['name']."/".$upgradeOrder['version']."/package.xml",
									"redirectUrl"=>ROOT_URL."plugins/".$upgradeOrder['group']."/".$upgradeOrder['name']."/".$upgradeOrder['version']."/".$installStep['file'],
									"scope"=>$myScope), $selfedit == "true");
							} elseif ($installStep['type'] == 'url') {
								$this->multiStepActionService->addAction("redirectAction", array(
									"packageFile"=>$upgradeOrder['group']."/".$upgradeOrder['name']."/".$upgradeOrder['version']."/package.xml",
									"redirectUrl"=>ROOT_URL.$installStep['url'],
									"scope"=>$myScope), $selfedit == "true");
							} else {
								throw new Exception("Unknown type during install process.");
							}
						}
					}
				}
			}
			
			// Let's start by admin scope, and then, let's go to the app scope.
			$moufDependencies = array();
			if (isset($this->moufDependencies['admin'])) {
				$moufDependencies['admin'] = $this->moufDependencies['admin'];
			}
			if (isset($this->moufDependencies['app'])) {
				$moufDependencies['app'] = $this->moufDependencies['app'];
			}
			
			foreach ($moufDependencies as $myScope=>$innerList) {
				foreach ($innerList as $dependency) {
					/* @var $dependency MoufPackage */
					if ($dependency->getCurrentLocation() != null) {
						$repository = $dependency->getCurrentLocation();
						$this->multiStepActionService->addAction("downloadPackageAction", array(
								"repositoryUrl"=>$repository->getUrl(),
								"group"=>$dependency->getDescriptor()->getGroup(),
								"name"=>$dependency->getDescriptor()->getName(),
								"version"=>$dependency->getDescriptor()->getVersion()
								), $selfedit == "true");
					}
					$this->multiStepActionService->addAction("enablePackageAction", array(
								"packageFile"=>$dependency->getDescriptor()->getPackageXmlPath(),
								"scope"=>$myScope), $selfedit == "true");
					
					// Now, let's see if there are specific installation steps.
					$installSteps = $dependency->getInstallSteps();
					if ($installSteps) {
						foreach ($installSteps as $installStep) {
							
							if ($installStep['type'] == 'file') {
								$this->multiStepActionService->addAction("redirectAction", array(
									"packageFile"=>$dependency->getDescriptor()->getPackageXmlPath(),
									"scope"=>$myScope,
									"redirectUrl"=>ROOT_URL."plugins/".$dependency->getDescriptor()->getGroup()."/".$dependency->getDescriptor()->getName()."/".$dependency->getDescriptor()->getVersion()."/".$installStep['file']), $selfedit == "true");
							} elseif ($installStep['type'] == 'url') {
								$this->multiStepActionService->addAction("redirectAction", array(
									"packageFile"=>$dependency->getDescriptor()->getPackageXmlPath(),
									"scope"=>$myScope,
									"redirectUrl"=>ROOT_URL.$installStep['url']), $selfedit == "true");
							} else {
								throw new Exception("Unknown type during install process.");
							}
						}
					}
				}
			}
			
			$url = ROOT_URL."mouf/packages/?selfedit=".$selfedit."&validation=enable";
			$msg = "Packages successfully enabled: ";
			$msgArr = array();
			foreach ($moufDependencies as $myScope=>$innerList) {
				foreach ($innerList as $moufDependency) {
					$url.= "&packageList[]=".$moufDependency->getDescriptor()->getPackageDirectory();
					$msgArr[] = $moufDependency->getDescriptor()->getPackageDirectory();
				}
			}
			$msg .= implode(", ", $msgArr);
			$this->multiStepActionService->setFinalUrlRedirect($url);
			$this->multiStepActionService->setConfirmationMessage($msg);
						
			$this->multiStepActionService->executeActions($selfedit == "true");
			//header($url);	
		}
	}
	
	/**
	 * Action that is run to disable a package.
	 *
	 * @Action
	 * @Logged
	 * @param string $name The path to the package.xml file relative to the plugins directory.
	 * @param string $selfedit
	 * @param string $confirm
	 */
	public function disablePackage($group, $name, $version, $selfedit = "false", $confirm="false") {

		// First, let's find the list of depending packages.
		$this->selfedit = $selfedit;
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
	
		$this->packageManager = new MoufPackageManager();
		$this->package = $this->packageManager->getPackageByDefinition($group, $name, $version);
		//$this->moufDependencies = $this->packageManager->getDependencies($this->package);
		
		$this->moufDependencies = $this->packageManager->getInstalledPackagesUsingThisPackage($this->package, $this->moufManager);
		
		//$dependencies = $this->packageManager->getChildren($this->package);
		//$this->moufDependencies = array();
		// Let's only keep the packages that are already installed from this list:
		/*foreach ($dependencies as $moufDependency) {
			$enabled = $this->moufManager->isPackageEnabled($moufDependency->getDescriptor()->getPackageXmlPath());
			if ($enabled) {
				$this->moufDependencies[] = $moufDependency;
			}
		}*/
		
		// Let's add the package to be removed to the list of package.
		$this->moufDependencies[] = $this->package;
		
		// Now, let's find the list of instances that are part of the packages to be removed.
		// For each instance, let's find the class and the package it belongs to.
		$componentsList = MoufReflectionProxy::getEnhancedComponentsList($this->selfedit == "true");
		
		$instancesList = $this->moufManager->getInstancesList();
		$pluginsDirectory = $this->moufManager->getFullPathToPluginsDirectory();
		
		$this->toDeleteInstance = array();
		$fullPathToPluginsDirectory = $this->moufManager->getFullPathToPluginsDirectory();
		
		foreach ($instancesList as $instanceName=>$className) {
			if (isset($componentsList[$className])) {
				// FIXME: we use the file name to know if a component class is part of a package.
				// But we could also have components extending classes of the package we are disabling.
				// We should put warnings for those. For instance, if we implemented Splash Controllers,
				// and if we disable Splash, we will have some problems with the instances of controllers.
				$fileName = $componentsList[$className]["filename"];
				foreach ($this->moufDependencies as $dependency) {
					if ($this->isPartOfPackage($fileName, $dependency, $fullPathToPluginsDirectory)) {
						$this->toDeleteInstance[$instanceName] = $className;
					}
				}
			}
		}
		
		
		if ((count($this->moufDependencies)>1 || count($this->toDeleteInstance)>0) && $confirm=="false") {
			$this->contentBlock->addFile(ROOT_PATH."src/views/packages/displayConfirmPackagesDisable.php", $this);
			$this->template->toHtml();	
		} else {
			/*if (!array_search($this->package, $this->moufDependencies)) {
				$this->moufDependencies[] = $this->package;
			}*/

			foreach ($this->toDeleteInstance as $instance=>$className) {
				$this->moufManager->removeComponent($instance);
			}
			
			foreach ($this->moufDependencies as $dependency) {
				//var_dump($dependency->getDescriptor()->getPackageXmlPath());
				$this->moufManager->removePackageByXmlFile($dependency->getDescriptor()->getPackageXmlPath());
			}//exit;
			$this->moufManager->rewriteMouf();
						
			$url = "Location: ".ROOT_URL."mouf/packages/?selfedit=".$selfedit."&validation=disable";
			foreach ($this->moufDependencies as $moufDependency) {
				$url.= "&packageList[]=".$moufDependency->getDescriptor()->getPackageDirectory();
			}
			header($url);	
		}
	}
	
	/**
	 * Action that is run to upgrade/downgrade a package.
	 *
	 * @Action
	 * @Logged
	 * @param string $group
	 * @param string $name
	 * @param string $version
	 * @param string $selfedit
	 * @param string $origin
	 */
	public function upgradePackage($group, $name, $version, $selfedit = "false", $origin = null) {
		//throw new Exception("Sorry, upgrading packages is not supported yet.");
		$this->enablePackage($group, $name, $version, $selfedit, "false", $origin,
			array("app"=>array(0=>array("group"=>$group,"name"=>$name,"version"=>$version,"origin"=>$origin))));
	}
	
	/**
	 * Returns true if the file "filename" is part of the package "$package".
	 *
	 * @param string $filename
	 * @param MoufPackage $package
	 * @return bool
	 */
	private function isPartOfPackage($filename, MoufPackage $package, $pluginsDirectory) {
		
		$packageFile = realpath($pluginsDirectory."/".$package->getDescriptor()->getPackageXmlPath());
		$dirPackage = dirname($packageFile);
		
		if (strpos($filename, $dirPackage) === 0) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Display the rows of buttons below the package list.
	 * 
	 * @param MoufPackage $package The package to display
	 * @param string $enabledVersion The version of that package that is currently enabled, if any.
	 */
	function displayPackageActions(MoufPackage $package, $enabledVersion) {
		$packageXmlPath = $package->getDescriptor()->getPackageXmlPath();
		$isPackageEnabled = $this->moufManager->isPackageEnabled($packageXmlPath);
		
		
		if ($enabledVersion !== false && $enabledVersion != $package->getDescriptor()->getVersion()) {
			echo "<form action='upgradePackage' method='POST'>";
			echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
			//echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
			echo "<input type='hidden' name='group' value='".htmlentities($package->getDescriptor()->getGroup())."' />";
			echo "<input type='hidden' name='name' value='".htmlentities($package->getDescriptor()->getName())."' />";
			echo "<input type='hidden' name='version' value='".htmlentities($package->getDescriptor()->getVersion())."' />";
			if (MoufPackageDescriptor::compareVersionNumber($package->getDescriptor()->getVersion(), $enabledVersion) > 0) {
				echo "<button>Upgrade to this package</button>";
			} else {
				echo "<button>Downgrade to this package</button>";
			}
			echo "</form>";
		} else if (!$isPackageEnabled) {
			echo "<form action='enablePackage' method='POST'>";
			echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
			echo "<input type='hidden' name='group' value='".htmlentities($package->getDescriptor()->getGroup())."' />";
			echo "<input type='hidden' name='name' value='".htmlentities($package->getDescriptor()->getName())."' />";
			echo "<input type='hidden' name='version' value='".htmlentities($package->getDescriptor()->getVersion())."' />";
			//echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
			echo "<button>Enable</button>";
			echo "</form>";
		} else {
			echo "<form action='disablePackage' method='POST'>";
			echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
			echo "<input type='hidden' name='group' value='".htmlentities($package->getDescriptor()->getGroup())."' />";
			echo "<input type='hidden' name='name' value='".htmlentities($package->getDescriptor()->getName())."' />";
			echo "<input type='hidden' name='version' value='".htmlentities($package->getDescriptor()->getVersion())."' />";		
			//echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
			echo "<button>Disable</button>";
			echo "</form>";
		}
		
	}

	/**
	 * Returns the list of versions for the package $package that are compatible with $requestedVersions.
	 * The local and remote repositories are searched.
	 * If a package is available in several places, it will be returned from the
	 * local repository first, and if not found from the remote repositories, in order
	 * of appearance.
	 * 
	 * @param MoufDependencyDescriptor $requestedVersions
	 * @param MoufManager $moufManager
	 * @throws Exception
	 * @return array<string, MoufPackage>
	 */
	public function getCompatibleVersionsForPackage(MoufDependencyDescriptor $requestedVersions) {
		return $this->packageManager->getCompatibleVersionsForPackage($requestedVersions, $this->moufManager);
	}
	
}
?>