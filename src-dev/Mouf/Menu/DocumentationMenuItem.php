<?php
/*
 * Copyright (c) 2012 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */
namespace Mouf\Menu;

use Mouf\Html\Widgets\Menu\MenuItem;

use Mouf\Utils\Common\ConditionInterface\ConditionInterface;
use Mouf\Utils\I18n\Fine\Translate\LanguageTranslationInterface;
use Mouf\MoufCache;
use Mouf\Composer\ComposerService;

/**
 * This class represent the documentation menu of Mouf.
 * This menu is generated dynamically based on the packages installed.
 *
 */
class DocumentationMenuItem extends MenuItem {
	
	private $computed = false;
	
	/**
	 * The cache that will store the sub menu-items.
	 * @var MoufCache
	 */
	private $cache;
	
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct("Documentation");
		$this->cache = new MoufCache();
	}

	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Html\Widgets\Menu\MenuItem::getChildren()
	 */
	public function getChildren() {
		return $this->getMenuTree();
	}
	
	
	/**
	 * Adds the packages menu to the menu.
	 */
	private function getMenuTree() {
		
		if ($this->computed) {
			return parent::getChildren();
		}
		$this->computed = true;
		
		$children = $this->cache->get('documentationMenuItem');
		
		if ($children) {
			return $children;
		}
			
		if (isset($_REQUEST['selfedit']) && $_REQUEST['selfedit'] == 'true') {
			$selfedit = true;
		} else {
			$selfedit = false;
		}
		
		$composerService = new ComposerService($selfedit);
		
		$packages = $composerService->getLocalPackages();
		
		$tree = array();
	
		// Let's fill the menu with the packages.
		foreach ($packages as $package) {
			$name = $package->getName();
			if (strpos($name, '/') === false) {
				continue;
			}
			list($vendorName, $packageName) = explode('/', $name);
			
			$items = explode('.', $packageName);
			array_unshift($items, $vendorName);

			$node =& $tree;
			foreach ($items as $str) {
				if (!isset($node["children"][$str])) {
					$node["children"][$str] = array();
				}
				$node =& $node["children"][$str];
			}
			$node['package'] = $package;
		}
	
		$this->walkMenuTree($tree, '', $this);
		
		// Short lived cache (3 minutes).
		$this->cache->set('documentationMenuItem', parent::getChildren(), 180);
		
		return parent::getChildren();
	}
	
	private function walkMenuTree($node, $path, MenuItem $parentMenuItem) {
		if (isset($node["children"]) && !empty($node["children"])) {
			// If there is a package and there are subpackages in the same name...
			if (isset($node["package"])) {
				$menuItem = new MenuItem();
				$menuItem->setLabel("<em>Main package</em>");
				$menuItem->setUrl(ROOT_URL."doc/view/".$path);
				$parentMenuItem->addMenuItem($menuItem);
			}
	
			foreach ($node['children'] as $key=>$array) {
				$menuItem = new MenuItem();
				$menuItem->setLabel($key);
				$parentMenuItem->addMenuItem($menuItem);
				if ($path == '') {
					$pathTmp = $key;
				} elseif (strpos($path, '/') === false) {
					$pathTmp = $path.'/'.$key;
				} else {
					$pathTmp = $path.'.'.$key;
				}
				//$pathTmp = $path.'.'.$key;
				//$pathTmp = str_replace(array('/.', '/Misc'), '/', $pathTmp);
				$this->walkMenuTree($array, $pathTmp, $menuItem);
			}
		} else {
			$parentMenuItem->setUrl(ROOT_URL."doc/view/".$path);
		}
	}
}
?>