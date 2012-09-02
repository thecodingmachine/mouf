<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
// Rewrites the MoufRequire file from the MoufComponents file, and the admin too.


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	//require_once '../../Mouf.php';
	require_once '../../MoufComponents.php';
	require_once '../../MoufUniversalParameters.php';
	$selfedit = "false";
} else {
	require_once '../MoufManager.php';
	MoufManager::initMoufManager();
	require_once '../../MoufUniversalParameters.php';
	MoufManager::switchToHidden();
	//require_once '../MoufAdmin.php';
	require_once '../MoufAdminComponents.php';
	$selfedit = "true";
}

require_once '../MoufPackageManager.php';

$moufManager = MoufManager::getMoufManager();
$packagesXmlFiles = $moufManager->listEnabledPackagesXmlFiles();

$errorList = array();

foreach ($packagesXmlFiles as $packageXmlFile) {
	$packageManager = new MoufPackageManager("../../plugins");
	$package = $packageManager->getPackage($packageXmlFile);
        $extensions = $package->getExtension();
        $phpVersion = $package->getPhpVersion();
	$dependencies = $package->getDependenciesAsDescriptors();
	
	$found = false;

        //*Checking the version of php*/
         if(strcmp(($phpVersion), "")!=0){

            $currentVersion = str_replace("-", ".", PHP_VERSION);
            $currentVersion = explode("." , $currentVersion);

            if (version_compare(PHP_VERSION, $phpVersion, '<')) {
                $errorList[] = "The current version of PHP, ".$currentVersion[0].".".$currentVersion[1].".".$currentVersion[2]." is outdated for this purpose. You need to install PHP ".$phpVersion." or higher. ";
            }
        }

        /*Checking if the php extension is activated*/
        if (!empty($extensions)) {
	        foreach ($extensions as $extension) {
	            if(!extension_loaded ($extension)) {
	                $errorList[] = "The extension '".$extension."' must be enabled.";
	            }
	        }
        }


	foreach ($dependencies as $dependency) {
		$tooLate = false;
		/* @var $dependency MoufDependencyDescriptor */
		// Let's test if each dependency is available, and in the first part of the dependencies.



                //$errorList[] = PHP_VERSION;

		foreach ($packagesXmlFiles as $packageXmlFileCheck) {
			if ($packageXmlFileCheck == $packageXmlFile) {
				// After current package, we are too late, we should change the order of the packages. 
				$tooLate = true;
			}

			$installedPackageDescriptor = MoufPackageDescriptor::getPackageDescriptorFromPackageFile($packageXmlFileCheck);
			if ($dependency->getGroup() == $installedPackageDescriptor->getGroup()
				&& $dependency->getName() == $installedPackageDescriptor->getName()) {


				if (!$dependency->isCompatibleWithVersion($installedPackageDescriptor->getVersion())) {
					$errorList[] = "For package ".$installedPackageDescriptor->getGroup()."/".$installedPackageDescriptor->getName().", installed version is ".$installedPackageDescriptor->getVersion().".
									However, the package ".$package->getDescriptor()->getGroup()."/".$package->getDescriptor()->getName()."/".$package->getDescriptor()->getVersion()."
									requires the version of this package to be ".$dependency->getVersion().".<br/>";
				}  else {
					if ($tooLate) {
						$errorList[] = "The package ".$package->getDescriptor()->getGroup()."/".$package->getDescriptor()->getName()."/".$package->getDescriptor()->getVersion()."
								requires the package ".$installedPackageDescriptor->getGroup()."/".$installedPackageDescriptor->getName()."/".$installedPackageDescriptor->getVersion().".
								This package is indeed included, but too late! Therefore, the dependency might not be satisfied. <a href='".ROOT_URL."mouf/direct/reorderDependencies.php?selfedit=$selfedit'>Click here to correct package order problems.</a><br/>";
					} else {
						$found = true;
					}
				}
			}
		}
		
		if (!$found) {
			$errorList[] = "Unable to find package ".$dependency->getGroup()."/".$dependency->getName().", version ".$dependency->getVersion().".
							This package is requested by package ".$package->getDescriptor()->getGroup()."/".$package->getDescriptor()->getName()."/".$package->getDescriptor()->getVersion().".<br/>";
		} else {
			$found = false;
		}
	}
	
}

if ($errorList) {
	$jsonObj['code'] = "error";
	$jsonObj['html'] = implode($errorList, "");
} else {
	$jsonObj['code'] = "ok";
	$jsonObj['html'] = "All packages dependencies are satisfied.";
}

echo json_encode($jsonObj);
