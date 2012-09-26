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
 * This controller displays the JIT graph of the component.
 *
 * @Component
 */
class MoufDisplayGraphController extends AbstractMoufInstanceController {
	
	/**
	 * Displays the dependency graph around the component passed in parameter.
	 * 
	 * @Action
	 * @Logged
	 *
	 * @param string $name
	 * @param string $selfedit
	 */
	public function defaultAction($name, $selfedit = false) {
		$this->initController($name, $selfedit);
		
		$template = $this->template;
		$this->template->addHeadHtmlElement(new HtmlJSJit());
		$this->template->addJsFile(ROOT_URL."src/views/displayGraph.js");
		$template->addContentFile(dirname(__FILE__)."/../views/displayGraph.php", $this);
		$template->toHtml();
	}
	
	
	/**
	 * Returns the Jit graph from the rootNode and downward.
	 *
	 * @param string $rootNode
	 * @return array JSON message as a PHP array
	 */
	protected function getJitJson($rootNode) {
		return $this->getJitJsonRecursive($rootNode, array());
	}

	/**
	 * Returns the Jit graph from the rootNode and downward.
	 *
	 * @param string $rootNode
	 * @return array JSON message as a PHP array
	 */
	/*private function getJitJsonAllInstances() {
		$instances = array_keys($this->moufManager->getInstancesList());
		$allJson = array();
		
		if (is_array($instances)) {
			foreach ($instances as $instance) {
				$allJson[] = $this->getJitJsonNode($instance);
			}
		
		}
		return $allJson;
	}*/
	
	/**
	 * Builds the Json message (as a PHP array) for JIT to display the tree.
	 *
	 * @param string $nodeToAdd The instance to add
	 * @param array $nodesList The Json message so far
	 * @return array The Json message with the current node (and its children) added.
	 */
	private function getJitJsonRecursive($nodeToAdd, $nodesList) {
		$node = $this->getJitJsonNode($nodeToAdd);
		$nodesList[] = $node;
		
		$componentsList = $this->getComponentsListBoundToInstance($nodeToAdd);
		
		foreach ($componentsList as $component) {
			// Let's check if we have already passed this component:
			$alreadyDone = false;
			foreach ($nodesList as $traversedNode) {
				if ($traversedNode["id"] == $component) {
					$alreadyDone = true;
					break;
				}
			}
			
			if (!$alreadyDone) {
				$nodesList = $this->getJitJsonRecursive($component, $nodesList);
			}
			
		}
		
		
		
		
		
		
		$componentsList = $this->moufManager->getOwnerComponents($nodeToAdd);

		
		
		foreach ($componentsList as $component) {
			// Let's check if we have already passed this component:
			$alreadyDone = false;
			foreach ($nodesList as $traversedNode) {
				if ($traversedNode["id"] == $component) {
					$alreadyDone = true;
					break;
				}
			}
			
			if (!$alreadyDone) {
				$nodesList = $this->getJitJsonRecursive($component, $nodesList);
			}
			
		}
		
		
		
		
		
		
		
		
		
		
		
		
		return $nodesList;
	}

	/**
	 * Returns a PHP array representing a node that will be used by JIT to build a visual representation.
	 *
	 */
	private function getJitJsonNode($instanceName) {
		$node = array();
		
		$node["id"] = $instanceName;
		$node["name"] = $instanceName;
		// We can set some data (dimension, other keys...) but we will keep tht to 0 for now.
		
		$adjacencies = array();
						
		$componentsList = $this->getComponentsListBoundToInstance($instanceName);
		
		foreach ($componentsList as $component) {
			$adjacency = array();
			$adjacency['nodeTo'] = $component;
			// We can set some data (weight...) but we will keep tht to 0 for now.
			
			$data = array();
			$data['$type'] = "arrow";
			$data['$direction'] = array($instanceName, $component);
			
			/*            "data": {
                "$type":"arrow",
                "$direction": ["node4", "node3"],
                "$dim":25,
                "$color":"#dd99dd",
                "weight": 1
			
            }*/
			$adjacency['data'] = $data;
			
			$adjacencies[] = $adjacency;
		}
		
		$node["adjacencies"] = $adjacencies;
		
		return $node;        
	}
	
	/**
	 * Returns the list of components that this component possesses bindings on.
	 *
	 * @param string $instanceName
	 * @return array<string>
	 */
	private function getComponentsListBoundToInstance($instanceName) {
		$componentsList = array();
		$boundComponents = $this->moufManager->getBoundComponents($instanceName);

		if (is_array($boundComponents)) {
			foreach ($boundComponents as $property=>$components) {
				if (is_array($components)) {
					$componentsList = array_merge($componentsList, $components);
				} else {
					$componentsList[] = $components;
				}
			}
		}
		return $componentsList;
	}
}
?>