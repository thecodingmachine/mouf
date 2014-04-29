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

use Mouf\MoufInstanceDescriptor;

use Mouf\MoufException;

use Mouf\MoufManager;


/**
 * This utility class should be used during package install processes
 * @author david
 */
class InstallUtils {
	
	public static $INIT_APP = 1;
	public static $INIT_ADMIN = 2;
		
	public static function init($initMode) {
		require_once dirname(__FILE__).'/../../direct/utils/check_rights.php';
		
		$rootPath = self::findRootPath(getcwd()."/");
		if ($initMode == self::$INIT_APP) {
			
			require_once $rootPath."mouf/Mouf.php";
		} else {
			if (file_exists($rootPath."vendor/mouf/mouf/mouf/Mouf.php")) {
				require_once $rootPath."vendor/mouf/mouf/mouf/Mouf.php";
			} else {
				// We are installing a package in selfedit mode.
				require_once $rootPath."mouf/Mouf.php";
			}
		}
		
		/*if ($initMode == self::$INIT_APP) {
			require_once dirname(__FILE__).'/../../../../../../mouf/Mouf.php';
			//require_once dirname(__FILE__).'/../MoufPackageManager.php';
		} else {
			require_once dirname(__FILE__).'/../../../mouf/Mouf.php';
			//require_once dirname(__FILE__).'/../MoufManager.php';
			//MoufManager::initMoufManager();
			//require_once dirname(__FILE__).'/../MoufAdmin.php';
			//require_once dirname(__FILE__).'/../MoufPackageManager.php';
		}*/
	}
	
	/**
	 * Finds the root_path from the current working directory (that should be the directory containing install.php)
	 * Nice thing with this technique: if this is a package part of Mouf dependencies, the ROOT_PATH is mouf and not the project.
	 */
	private static function findRootPath($cwd) {
		if (file_exists($cwd."/mouf/Mouf.php")) {
			return $cwd;
		} else {
			return self::findRootPath($cwd."../");
		}
	}
	
	/**
	 * Redirects the user to the end of the install procedure.
	 * The current install process will be validated.
	 * 
	 * This function writes a header "Location:" to perform the redirect.
	 * Therefore, to be effective, nothing should have been outputed.
	 */
	public static function continueInstall($selfEdit = false) {
		// Let's try to get the URL right based on the context.
		
		header("Location: ".MOUF_URL."installer/installTaskDone?selfedit=".(($selfEdit)?"true":"false")."#toinstall");
	}
	
	public static function massCreate($classes, $moufManager){
		foreach ($classes as $class) {
			$settings = null;
			if (($index = strpos($class, "{")) !== false){
				$className = substr($class, 0, $index);
				$settings = substr($class, $index);
				$class = $className;
			}
		
			if ($settings){
				$settings = json_decode($settings);
				$instance = self::createInstance($class, $moufManager);
				$adds = "";
				foreach ($settings as $prop => $value) {
					/* @var $instance MoufInstanceDescriptor */
					$propName = str_replace("allow", "", $prop);
					$adds .= $value === true ? $propName : ($value === false ?"No".$propName : "");
					$instance->getProperty($prop)->setValue($value);
					$instance->setName(self::getInstanceName($class, $moufManager, $adds));
				}
			}else{
				$instance = self::createInstance($class, $moufManager);
			}
		}
	}
	
	/**
	 * Returns the instance $instanceName or creates it if it does not exist.
	 * Throws an exception if the instance exist and is not of the requested class.
	 * 
	 * @param string $instanceName
	 * @param string $className The name of the class of the instance to create. Set it to null if you want to create an instance by PHP code.
	 * @param MoufManager $moufManager
	 * @return MoufInstanceDescriptor
	 */
	public static function getOrCreateInstance($instanceName, $className, MoufManager $moufManager) {
		if (strpos($className, "\\") === 0) {
			$className = substr($className,1);
		}
		if ($moufManager->instanceExists($instanceName)) {
			$instance = $moufManager->getInstanceDescriptor($instanceName);
			if ($className != null && $instance->getClassName() != $className) {
				throw new MoufException("Invalid instance while installing package. The existing '$instanceName' instance should be a '$className'. Instead, we found an instance of the '{$instance->getClassName()}' class.");
			}
		} else {
			if ($className != null) {
				$instance = $moufManager->createInstance($className);
			} else {
				$instance = $moufManager->createInstanceByCode();
			}
			$instance->setName($instanceName);
		}
		return $instance;
	}
	
	public static function createInstance($class, $moufManager){
		$instance = $moufManager->createInstance($class);
		$instance->setName(self::getInstanceName($class, $moufManager));
		return $instance;
	}
	
	public static function getInstanceName($class, $moufManager, $adds = ""){
		$index = strrpos($class, '\\');
		$name = substr($class, $index + 1);
		$name = lcfirst($name).$adds;
		$instanceName = $name;
		$i = 2;
		while($moufManager->instanceExists($instanceName)){
			$instanceName = $name . $i;
			$i++;
		}
	
		return $instanceName;
	}
}