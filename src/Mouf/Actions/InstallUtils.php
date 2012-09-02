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
 * This utility class should be used during package install processes
 * @author david
 */
class InstallUtils {
	
	public static $INIT_APP = 1;
	public static $INIT_ADMIN = 2;
		
	public static function init($initMode) {
		require_once dirname(__FILE__).'/../direct/utils/check_rights.php';
		require_once dirname(__FILE__).'/../reflection/MoufReflectionClass.php';
		
		if ($initMode == self::$INIT_APP) {
			require_once dirname(__FILE__).'/../../Mouf.php';
			require_once dirname(__FILE__).'/../MoufPackageManager.php';
		} else {
			require_once dirname(__FILE__).'/../MoufManager.php';
			MoufManager::initMoufManager();
			require_once dirname(__FILE__).'/../MoufAdmin.php';
			require_once dirname(__FILE__).'/../MoufPackageManager.php';
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
		header("Location: ".ROOT_URL."mouf/install/installStepDone?selfedit=".(($selfEdit)?"true":"false"));
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
	
	public static function createInstance($class, $moufManager){
		$instance = $moufManager->createInstance($class);
		$instance->setName(self::getInstanceName($class, $moufManager));
		return $instance;
	}
	
	public static function getInstanceName($class, $moufManager, $adds = ""){
		$name = lcfirst($class).$adds;
		$instanceName = $name;
		$i = 2;
		while($moufManager->instanceExists($instanceName)){
			$instanceName = $name . $i;
			$i++;
		}
	
		return $instanceName;
	}
}