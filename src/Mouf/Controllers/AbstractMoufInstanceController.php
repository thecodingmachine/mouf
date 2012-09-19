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
 * This abstract controller helps performing basic operations to display a detail instance page
 * (or any page that looks loke the detail instance page, with the right menu, etc...) 
 *
 */
abstract class AbstractMoufInstanceController extends Controller {

	public $instanceName;
	public $className;
	/**
	 * List of properties for this class.
	 * 
	 * @var array<MoufPropertyDescriptor>
	 */
	public $properties;
	public $reflectionClass;
	public $selfedit;
	public $weak;
	/**
	 * Whether the instance can be declared weak or not.
	 * An instance that has noone referencing it cannot be weak.
	 * @var bool
	 */
	public $canBeWeak;
	
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
	 * This function initiates the class variables of the controller according to the parameters passed.
	 * It will also configure the template to have the correct entry, especially in the right menu thazt is context dependent.
	 * 
	 * 
	 * @param string $name the name of the component to display
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	protected function initController($name, $selfedit) {
		$this->instanceName = $name;
		$this->selfedit = $selfedit;
		/*$this->instance = MoufManager::getMoufManager()->getInstance($name);
		$this->className = MoufManager::getMoufManager()->getInstanceType($this->instanceName);*/
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->className = $this->moufManager->getInstanceType($this->instanceName);		
		$this->reflectionClass = MoufReflectionProxy::getClass($this->className, $selfedit=="true");
		$this->properties = Moufspector::getPropertiesForClass($this->reflectionClass);
		$this->weak = $this->moufManager->isInstanceWeak($this->instanceName);
		
		// Init the right menu:
		$extendedActions = $this->reflectionClass->getAnnotations("ExtendedAction");
		if (!empty($extendedActions)) {
			$items = array();
			foreach ($extendedActions as $extendedAction) {
				$menuItem = new MenuItem($extendedAction->getName(), ROOT_URL.$extendedAction->getUrl());
				$menuItem->setPropagatedUrlParameters(array("selfedit", "name"));
				$items[] = $menuItem;
			}
			$specialActionsMenuItem = new MenuItem("Special actions", null, $items);
						
			//$menu = new Menu($items);
			MoufAdmin::getSpecialActionsMenu()->addChild($specialActionsMenuItem);
			
			//$this->template->addRightHtmlElement($menuItems);	
		}
		
		$viewPropertiesMenuItem = new MenuItem("View properties", ROOT_URL."mouf/instance/");
		$viewPropertiesMenuItem->setPropagatedUrlParameters(array("selfedit", "name"));
		$viewDependencyGraphMenuItem = new MenuItem("View dependency graph", "mouf/displayGraph/");
		$viewDependencyGraphMenuItem->setPropagatedUrlParameters(array("selfedit", "name"));
		$commonMenuItem = new MenuItem("Common", null, array($viewPropertiesMenuItem, $viewDependencyGraphMenuItem));
		MoufAdmin::getInstanceMenu()->addChild($commonMenuItem);
		/*$this->template->addRightHtmlElement(new SplashMenu(
			array(
			new SplashMenuItem("<b>Common</b>", null, null),
			new SplashMenuItem("View properties", ROOT_URL."mouf/instance/?name=".$name, null, array("selfedit")),
			new SplashMenuItem("View dependency graph", "mouf/displayGraph/?name=".$name, null, array("selfedit")))));
		$this->template->addRightFunction(array($this, "displayComponentParents"));
		*/
		$this->displayComponentParents();
	}
	
	/**
	 * Displays the list of components directly referencing this component.
	 *
	 */
	public function displayComponentParents() {
		$componentsList = $this->moufManager->getOwnerComponents($this->instanceName);
		
		$this->canBeWeak = false;
		if (!empty($componentsList)) {
			$this->canBeWeak = true;
			$children = array();
			foreach ($componentsList as $component) {
				$child = new MenuItem($component, ROOT_URL.'mouf/mouf/displayComponent?name='.urlencode($component));
				$child->setPropagatedUrlParameters(array("selfedit"));
				$children[] = $child;
			}
			$referredByMenuItem = new MenuItem('Referred by instances:', null, $children);
			MoufAdmin::getInstanceMenu()->addChild($referredByMenuItem);
		}
	}
	
	/**
	 * Returns the value set for the instance passed in parameter... or the default value if the value is not set.
	 *
	 * @param MoufPropertyDescription $property
	 * @return mixed
	 */
	protected function getValueForProperty(MoufPropertyDescriptor $property) {
		if ($property->isPublicFieldProperty()) {
			$propertyName =  $property->getName();
			if ($this->moufManager->hasParameter($this->instanceName, $propertyName)) {
				$defaultValue = $this->moufManager->getParameter($this->instanceName, $propertyName);
			} else {
				$defaultValue = $this->reflectionClass->getProperty($propertyName)->getDefault();
			}
		} else {
			// This is a setter.
			$propertyName =  $property->getName();
			if ($this->moufManager->hasParameterForSetter($this->instanceName, $property->getMethodName())) {
				$defaultValue = $this->moufManager->getParameterForSetter($this->instanceName, $property->getMethodName());
			} else {
				// TODO: return a default value. We could try to find it using a getter maybe...
				// Or a default value for the setter? 
				return null;
			}
			
		}
		return $defaultValue;
	}
	
	/**
	 * Returns the type set for the instance passed in parameter.
	 * Type is one of string|config|request|session
	 *
	 * @param MoufPropertyDescription $property
	 * @return mixed
	 */
	protected function getTypeForProperty(MoufPropertyDescriptor $property) {
		if ($property->isPublicFieldProperty()) {
			$propertyName =  $property->getName();
			if ($this->moufManager->hasParameter($this->instanceName, $propertyName)) {
				$defaultValue = $this->moufManager->getParameterType($this->instanceName, $propertyName);
			} else {
				return "string";
			}
		} else {
			// This is a setter.
			$propertyName =  $property->getName();
			if ($this->moufManager->hasParameterForSetter($this->instanceName, $property->getMethodName())) {
				$defaultValue = $this->moufManager->getParameterTypeForSetter($this->instanceName, $property->getMethodName());
			} else {
				return "string";
			}
			
		}
		return $defaultValue;
	}
	
	/**
	 * Returns the metadata for the instance passed in parameter.
	 *
	 * @param MoufPropertyDescription $property
	 * @return array
	 */
	protected function getMetadataForProperty(MoufPropertyDescriptor $property) {
		if ($property->isPublicFieldProperty()) {
			$propertyName =  $property->getName();
			if ($this->moufManager->hasParameter($this->instanceName, $propertyName)) {
				$defaultValue = $this->moufManager->getParameterMetadata($this->instanceName, $propertyName);
			} else {
				return array();
			}
		} else {
			// This is a setter.
			$propertyName =  $property->getName();
			if ($this->moufManager->hasParameterForSetter($this->instanceName, $property->getMethodName())) {
				$defaultValue = $this->moufManager->getParameterMetadataForSetter($this->instanceName, $property->getMethodName());
			} else {
				return array();
			}
			
		}
		return $defaultValue;
	}
	
	/**
	 * Returns the value set for the instance passed in parameter... or the default value if the value is not set.
	 *
	 * @param MoufPropertyDescription $property
	 * @return mixed
	 */
	protected function getValueForPropertyByName($propertyName) {
		foreach ($this->properties as $property) {
			if ($property->getName() == $propertyName) {
				return $this->getValueForProperty($property);
			}
		}
	}
	
	/**
	 * Returns all components that are from the baseClass (or base interface) type.
	 * The call is performed through the ReflectionProxy.
	 * 
	 * @param string $baseClass
	 * @return array<string>
	 */
	protected function findInstances($baseClass) {
		return MoufReflectionProxy::getInstances($baseClass, $this->selfedit=="true");
	}
	
}
?>