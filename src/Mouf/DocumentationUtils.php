<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012-2014 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf;

use Mouf\Html\Widgets\Menu\MenuItem;
/**
 * This class contains utility functions related to documentation.
 * 
 * @author David Negrier
 */
class DocumentationUtils {
	
	public static $readMeFiles=array('index.md', 'README.md', 'readme.md', 'README.html', 'README.txt', 'README', 'README.mdown', 'index.html', 'index.htm');
	
	/**
	 * Returns an array containing the full path to all composer.json files related to packages related to this class.
	 * A package is related to this class if the class is part of the package, or if one of the class/interface/trait
	 * that it extends/implements/use is part of that package.
	 * 
	 * @param string $className
	 * @return string[]
	 */
	public static function getRelatedPackages($className) {
		$classes = class_parents($className);
		array_unshift($classes, $className);
		
		$interfaces = class_implements($className);
		
		$traits = class_uses($className);
		
		$itemNames = array_merge($classes, $interfaces, $traits);
		
		// An array containing the name of all composer.json files related to this project.
		$relatedPackages = [];
		
		foreach ($itemNames as $class) {
			$classDescriptor = new \ReflectionClass($class);
			$file = $classDescriptor->getFileName();
			
			// Let's find the package this class/interface/trait belongs to.
			$composerJsonFile = self::getRelatedPackage($file);
			$relatedPackages[$composerJsonFile] = $composerJsonFile;
		}
		
		return array_values($relatedPackages);
	}
	
	/**
	 * Finds the package this file is related to.
	 * Returns the path to the composer.json file of that package (or null if no package is found).
	 * 
	 * @param string $fileName
	 * @return string
	 */
	private static function getRelatedPackage($fileName) {
		$dir = dirname($fileName);
		$rootPath = rtrim(ROOT_PATH, "/\\");
		
		while ($dir && $dir != $rootPath) {
			if (file_exists($dir.'/composer.json')) {
				return $dir.'/composer.json';
			}
			$dir = dirname($dir);
		}
		if (file_exists($dir.'/composer.json')) {
			return $dir.'/composer.json';
		}
		return null;
	}
	
	/**
	 * Returns an array of doc pages with the format:
	 * 	[
	 *   		{
	 *   			"title": "Using FINE",
	 *   			"url": "using_fine.html"
	 *   		},
	 *   		{
	 *   			"title": "Date functions",
	 *   			"url": "date_functions.html"
	 *   		},
	 *   		{
	 *   			"title": "Currency functions",
	 *   			"url": "currency_functions.html"
	 *   		}
	 *   	]
	 *
	 * @param array $extra The "extra" section of the composer.json
	 * @param string $packagePath The path to the root of the package 
	 */
	public static function getDocPages($extra, $packagePath) {
		$docArray = array();
	
		// Let's find if there is a README file.
	
		foreach (DocumentationUtils::$readMeFiles as $readme) {
			if (file_exists($packagePath.$readme)) {
				$docArray[] = array("title"=> "Read me",
						"url"=>$readme
				);
				break;
			}
		}
	
		if (isset($extra['mouf']['doc']) && is_array($extra['mouf']['doc'])) {
			$docArray = array_merge($docArray, $extra['mouf']['doc']);
		}
		return $docArray;
	}
	
	public static function fillMenu($menu, array $docPages, $packageName) {
		$children = array();
		foreach ($docPages as $docPage) {
			/* @var $docPage MoufDocumentationPageDescriptor */
				
			if (!isset($docPage['title'])) {
				continue;
			}
				
			$menuItem = new MenuItem();
			$menuItem->setLabel($docPage['title']);
			if (isset($docPage['url'])) {
				$menuItem->setUrl(ROOT_URL."doc/view/".$packageName."/".$docPage['url']);
			}
			$children[] = $menuItem;
				
			if (isset($docPage['children'])) {
				self::fillMenu($menuItem, $docPage['children'], $packageName);
			}
		}
		$menu->setChildren($children);
	}
}