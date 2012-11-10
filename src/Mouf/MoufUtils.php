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
}