<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf;

/**
 * This class contains utility functions
 * 
 * @author David Negrier
 */
class MoufUtils {
	
	/**
	 * Registers a new menuItem instance to be displayed in Mouf main menu.
	 * 
	 * @param string $instanceName
	 * @param string $label
	 * @param string $url
	 * @param string $parentMenuItemName The parent menu item instance name. 'mainMenu' is the main menu item, and 'moufSubMenu' is the 'Mouf' menu 
	 * @param float $priority The position of the menu
	 */
	public static function registerMenuItem($instanceName, $label, $url, $parentMenuItemName = 'moufSubMenu', $priority = 50) {
		$moufManager = MoufManager::getMoufManager();

		if ($moufManager->instanceExists($instanceName)) {
			return;
		}
		
		$moufManager->declareComponent($instanceName, 'Mouf\\Html\\Widgets\\Menu\\MenuItem', true);
		$moufManager->setParameterViaSetter($instanceName, 'setLabel', $label);
		$moufManager->setParameterViaSetter($instanceName, 'setUrl', $url);
		$moufManager->setParameterViaSetter($instanceName, 'setPriority', $priority);
		if (strpos($url, "javascript:") !== 0) {
			$moufManager->setParameterViaSetter($instanceName, 'setPropagatedUrlParameters', array (
			  0 => 'selfedit',
			));
		}

		$parentMenuItem = MoufManager::getMoufManager()->getInstance($parentMenuItemName);
		/* @var $parentMenuItem MenuItem */
		$parentMenuItem->addMenuItem(MoufManager::getMoufManager()->getInstance($instanceName));		
	}
	

	/**
	 * Registers a new menuItem instance to be displayed in Mouf main menu.
	 * 
	 * @param string $instanceName
	 * @param string $label
	 * @param string $url
	 * @param string $parentMenuItemName The parent menu item instance name. 'mainMenu' is the main menu item, and 'moufSubMenu' is the 'Mouf' menu 
	 * @param float $priority The position of the menu
	 */
	public static function registerMainMenu($instanceName, $label, $url, $parentMenuItemName = 'moufSubMenu', $priority = 50) {
		$moufManager = MoufManager::getMoufManager();

		if ($moufManager->instanceExists($instanceName)) {
			return;
		}
		
		$moufManager->declareComponent($instanceName, 'Mouf\\Html\\Widgets\\Menu\\MenuItem', true);
		$moufManager->setParameterViaSetter($instanceName, 'setLabel', $label);
		$moufManager->setParameterViaSetter($instanceName, 'setUrl', $url);
		$moufManager->setParameterViaSetter($instanceName, 'setPriority', $priority);
		if (strpos($url, "javascript:") !== 0) {
			$moufManager->setParameterViaSetter($instanceName, 'setPropagatedUrlParameters', array (
			  0 => 'selfedit',
			));
		}

		$parentMenuItem = MoufManager::getMoufManager()->getInstance($parentMenuItemName);
		/* @var $parentMenuItem Menu */
		$parentMenuItem->addChild(MoufManager::getMoufManager()->getInstance($instanceName));		
	}
	
	/**
	 * Registers a new menuItem instance to be displayed in Mouf main menu that triggers
	 * a popup to choose an instance.
	 *
	 * @param string $instanceName
	 * @param string $label
	 * @param string $url
	 * @param string $type
	 * @param string $parentMenuItemName The parent menu item instance name. 'mainMenu' is the main menu item, and 'moufSubMenu' is the 'Mouf' menu
	 * @param float $priority The position of the menu
	 */
	public static function registerChooseInstanceMenuItem($instanceName, $label, $url, $type, $parentMenuItemName = 'moufSubMenu', $priority = 50) {
		$moufManager = MoufManager::getMoufManager();
	
		if ($moufManager->instanceExists($instanceName)) {
			return;
		}
	
		$moufManager->declareComponent($instanceName, 'Mouf\\Menu\\ChooseInstanceMenuItem', true);
		$moufManager->setParameterViaSetter($instanceName, 'setLabel', $label);
		$moufManager->setParameterViaSetter($instanceName, 'setUrl', $url);
		$moufManager->setParameterViaSetter($instanceName, 'setType', $type);
		$moufManager->setParameterViaSetter($instanceName, 'setPriority', $priority);
		
		$parentMenuItem = MoufManager::getMoufManager()->getInstance($parentMenuItemName);
		/* @var $parentMenuItem MenuItem */
		$parentMenuItem->addMenuItem(MoufManager::getMoufManager()->getInstance($instanceName));
	}
	
	/**
	 * Check rights and exits execution if the user is not logged in Mouf UI.
	 * This method is very useful in "direct" ajax calls from the Mouf UI.
	 */
	public static function checkRights() {
		include __DIR__."/../direct/utils/check_rights.php";
	}
	
	/**
	 * Returns the list of all autoloaded namespaces:
	 * 
	 * [
	 * 	{"namespace"=> "Mouf", "directory"=>"src/"},
	 * 	{"namespace"=> "Mouf", "directory"=>"src2/"}
	 * ]
	 * 
	 * @return array<int, array<string, string>>
	 */
	public static function getAutoloadNamespaces() {
		// FIXME: adapt this to PSR-4!
		$composer = json_decode(file_get_contents(__DIR__."/../../../../../composer.json"), true);
		
		if (isset($composer["autoload"]["psr-0"])) {
			$autoload = $composer["autoload"]["psr-0"];
		}
                else {
                    return null;
                }
		
		if (self::isAssoc($autoload)) {
			return self::unfactorizeAutoload(array($autoload));
		} else {
			return self::unfactorizeAutoload($autoload);
		}		
	}
	
	/**
	 * Returns the list of all autoloaded namespaces:
	 * 
	 * [
	 * 	{"namespace"=> "Mouf", "directory"=>"src/"},
	 * 	{"namespace"=> "Mouf", "directory"=>"src2/"}
	 * ]
	 * 
	 * @return array<int, array<string, string>>
	 */
	public static function getAutoloadNamespaces2() {
		// FIXME: adapt this to PSR-4!
		$composer = json_decode(file_get_contents(__DIR__."/../../../../../composer.json"), true);
		
		$psr = isset($composer["autoload"]["psr-0"]) ? "0" : (isset($composer["autoload"]["psr-4"]) ? "4" : null);
		if ($psr !== null) {
			$autoload = $composer["autoload"]["psr-$psr"];
		}
		else {
			return null;
		}
		
		if (self::isAssoc($autoload)) {
			$ret = self::unfactorizeAutoload(array($autoload));
		} else {
			$ret = self::unfactorizeAutoload($autoload);
		}
		
		$ret['psr'] = $psr;
		
		return $ret;
	}
	
	/**
	 * Takes in parameter an array like 
	 * [{ "Mouf": "src/" }] or [{ "Mouf": ["src/", "src2/"] }] .
	 * returns
	 * [
	 * 	{"namespace"=> "Mouf", "directory"=>"src/"},
	 * 	{"namespace"=> "Mouf", "directory"=>"src2/"}
	 * ]
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function unfactorizeAutoload($autoload) {
		$result  = array();
		foreach ($autoload as $namespaceItem) {
			foreach ($namespaceItem as $namespace => $directories) {
				if (!is_array($directories)) {
					$result[] = array(
						"namespace" => $namespace,
						"directory" => trim($directories, '/\\').'/'
					);
				} else {
					foreach ($directories as $dir) {
						$result[] = array(
								"namespace" => $namespace,
								"directory" => trim($dir, '/').'/'
						);
					}
				}
			}
		}
		return $result;
	} 
	
	/**
	 * Returns if an array is associative or not.
	 *
	 * @param array $arr
	 * @return boolean
	 */
	private static function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	/**
	 * Returns the URL (including the ROOT_URL) from a file URL.
	 * 
	 * @param string $filePath
	 * @return string
	 */
	public static function getUrlPathFromFilePath($filePath, $relativeToRootUrl = false) {
		$dir = $filePath;
		$rootPath = ROOT_PATH;
		
		if (strpos($dir, ROOT_PATH) === 0) {
			$dirUrl = ROOT_URL.substr(ROOT_PATH, strlen(__DIR__)+1);
		} else {
			// The directory URL is below the ROOT_URL. Let's try to find it (not 100% success rate)
			$rootPaths = explode(DIRECTORY_SEPARATOR, trim($rootPath, DIRECTORY_SEPARATOR));
			$thisDirs = explode(DIRECTORY_SEPARATOR, trim($dir, DIRECTORY_SEPARATOR));
		
			for ($i=0; $i<count($rootPaths); $i++) {
				if ($rootPaths[$i] != $thisDirs[$i]) {
					break;
				}
			}
		
			$nbSkip = count($rootPaths) - $i;
			$urls = explode('/', trim(ROOT_URL, '/'));
		
			if ($nbSkip > count($urls)) {
				throw new Exception('The directory "'.$dir.'" is almost certainly out of reach from the web.');
			}
		
			$dirUrls = array();
			for ($j=0; $j < count($urls)- $nbSkip; $j++) {
				$dirUrls[] = $urls[$j];
			}
			for ($k = $i; $k<count($thisDirs); $k++) {
				$dirUrls[] = $thisDirs[$k];
			}
			$dirUrl = '/'.implode('/', $dirUrls);
			
			if ($relativeToRootUrl) {
				$dirUrl = ltrim(str_repeat('/..', count($urls)).$dirUrl, '/');
			}
		}
		return $dirUrl;
	}
}