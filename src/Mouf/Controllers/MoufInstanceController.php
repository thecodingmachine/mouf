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
 * This controller displays the (not so) basic details page.
 *
 * @Component
 */
class MoufInstanceController extends AbstractMoufInstanceController {

	/**
	 * @Action
	 * @Logged
	 *
	 * @param string $name the name of the component to display
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function defaultAction($name, $selfedit = false) {
		$this->initController($name, $selfedit);
		
		$this->template->addJsFile(ROOT_URL."src/views/displayComponent.js");
		
		//$this->template->addContentFunction(array($this, "displayComponentView"));
		$this->contentBlock->addFile(dirname(__FILE__)."/../../src/views/displayComponent.php", $this);
		$this->template->toHtml();	
	}
	
	/**
	 * Displays the component details view
	 *
	 */
	/*public function displayComponentView() {
		include(dirname(__FILE__)."/../../src/views/displayComponent.php");
	}*/
	
	/**
	 * Displays the dependency graph around the component passed in parameter.
	 * 
	 * @Action
	 * @Logged
	 *
	 * @param string $name
	 * @param string $selfedit
	 */
	public function displayGraph($name, $selfedit = false) {
		$this->initController($name, $selfedit);
		
		$template = $this->template;
		$this->template->addHeadHtmlElement(new HtmlJSJit());
		$this->template->addJsFile(ROOT_URL."src/views/displayGraph.js");
		$template->addContentFile(dirname(__FILE__)."/../views/displayGraph.php", $this);
		$template->toHtml();
	}
	
	
	/**
	 * Action that saves the component.
	 *
	 * @Action
	 * @Logged
	 * @param string $originalInstanceName The name of the instance
	 * @param string $instanceName The new name of the instance (if it was renamed)
	 * @param string $delete Whether the instance should be deleted or not
	 * @param string $selfedit Self edit mode
	 * @param string $createNewInstance If "true", a new instance should be created and attached to the saved component instance.
	 * @param string $bindToProperty The name of the property the new instance will be bound to.
	 * @param string $newInstanceName The name of new instance to create and attach to the saved object
	 * @param string $instanceClass The type of the new instance to create and attach to the saved object
	 * @param string $newInstanceKey The key of the new instance (if it is part of an associative array)
	 * @param string $duplicateInstance If "true", a copy of the instance will be created. This copy will be named after the $newInstanceName param.
	 * @param string $weak
	 * @throws Exception
	 */
	public function saveComponent($originalInstanceName, $instanceName, $delete, $selfedit, $newInstanceName=null, $createNewInstance=null, $bindToProperty=null, $instanceClass=null, $newInstanceKey=null, $duplicateInstance=null, $weak=false) {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		// Should we delete the component instead of save it?
		if ($delete) {
			$this->moufManager->removeComponent($originalInstanceName);
			$this->moufManager->rewriteMouf();
			
			header("Location: ".ROOT_URL."mouf/mouf/?selfedit=".$selfedit);
			return;
		}
		
		// Renames the component if needed.
		if ($originalInstanceName != $instanceName) {
			$this->moufManager->renameComponent($originalInstanceName, $instanceName);
		}
		
		$this->instanceName = $instanceName;
		$this->className = $this->moufManager->getInstanceType($instanceName);
		$this->reflectionClass = MoufReflectionProxy::getClass($this->className, $selfedit=="true");
		$this->properties = Moufspector::getPropertiesForClass($this->reflectionClass);
		//$this->properties = Moufspector::getPropertiesForClass($this->className);
		
		$this->moufManager->unsetAllParameters($instanceName);
		
		$this->moufManager->setInstanceWeakness($instanceName, (bool) $weak);
		
		foreach ($this->properties as $property) {
			if ($property->hasType()) {
				//$varTypes = $property->getAnnotations("var");
				//$varTypeAnnot = $varTypes[0];
				//$varType = $varTypeAnnot->getType();
				$varType = $property->getType();
				$lowerVarType = strtolower($varType);
				
				$propertyType = "";
				
				if ($lowerVarType == "string" || $lowerVarType == "bool" || $lowerVarType == "boolean" || $lowerVarType == "int" || $lowerVarType == "integer" || $lowerVarType == "double" || $lowerVarType == "float" || $lowerVarType == "real" || $lowerVarType == "mixed") {
					$value = get($property->getName());
					$type = get("moufpropertytype_".$property->getName());
					if ($lowerVarType == "bool" || $lowerVarType == "boolean") {
						if ($value == "true") {
							$value = true;
						}
					}
					if ($property->isPublicFieldProperty()) {
						$this->moufManager->setParameter($instanceName, $property->getName(), $value, $type);
					} else {
						$this->moufManager->setParameterViaSetter($instanceName, $property->getMethodName(), $value, $type);
					}
				} else if ($lowerVarType == "array") {
					$recursiveType = $property->getSubType();
					$isAssociative = $property->isAssociativeArray();
					if ($recursiveType == "string" || $recursiveType == "bool" || $recursiveType == "boolean" || $recursiveType == "int" || $recursiveType == "integer" || $recursiveType == "double" || $recursiveType == "float" || $recursiveType == "real" || $recursiveType == "mixed") {
						if ($isAssociative) {
							$keys = get("moufKeyFor".$property->getName());
							$tmpValues = get($property->getName());
							
							$values = array();
							if (is_array($tmpValues)) {
								for ($i=0,$count=count($tmpValues); $i<$count; $i++) {
									$values[$keys[$i]] = $tmpValues[$i];
								}
							}
						} else {
							$values = get($property->getName());
						}
						if ($property->isPublicFieldProperty()) {
							$this->moufManager->setParameter($instanceName, $property->getName(), $values);
						} else {
							$this->moufManager->setParameterViaSetter($instanceName, $property->getMethodName(), $values);
						}
					} else {
						if ($isAssociative) {
							$keys = get("moufKeyFor".$property->getName());
							$tmpValues = get($property->getName());
							
							$values = array();
							if (is_array($tmpValues)) {
								for ($i=0, $count=count($tmpValues); $i<$count; $i++) {
									$values[$keys[$i]] = $tmpValues[$i];
								}
							}
						} else {
							$values = get($property->getName());
						}

						if (is_array($values)) {
							foreach ($values as $key=>$value) {
								if ($originalInstanceName == $value) {
									// In the special case of a renaming with a recursion inside the object renamed, we must rename to the new name, not the old one. 
									$values[$key] = $instanceName;
								}
                                                                if(empty($value)){
                                                                    unset($values[$key]);
                                                                }
							}
						}
						
						if ($property->isPublicFieldProperty()) {
							$this->moufManager->bindComponents($instanceName, $property->getName(), $values);
						} else {
							$this->moufManager->bindComponentsViaSetter($instanceName, $property->getMethodName(), $values);
						}
					}
				} else {
					$value = get($property->getName());
					if ($value == "") {
						$value = null;
					} else if ($originalInstanceName == $value) {
						// In the special case of a renaming with a recursion inside the object renamed, we must rename to the new name, not the old one. 
						$value = $instanceName;
					}
						
					if ($property->isPublicFieldProperty()) {
						$this->moufManager->bindComponent($instanceName, $property->getName(), $value);
					} else {
						$this->moufManager->bindComponentViaSetter($instanceName, $property->getMethodName(), $value);
					}
				}
				
				
			} else {
				if ($property->isPublicFieldProperty()) {
					// No @var annotation
					throw new Exception("Error while saving, no @var annotation for property ".$property->getName());
				} else {
					throw new Exception("Error while saving, no @param annotation for setter ".$property->getMethodName());
				}
			}
		}
				
		// Ok, component was saved. Now, were we requested to create a new instance?
		if ($createNewInstance == "true") {
			// TODO: check $newInstanceName not empty (or accept anonymous objects).
			$this->moufManager->declareComponent($newInstanceName, $instanceClass);
			
			// Now, let's bind that new instance to the old one.
			/*foreach ($this->properties as $property) {
				// Find the right property
				if ($bindToProperty == $property->getName()) {
					// Ok, we bind to property "property".
					// Is it an array or not?
					if ($property->getType() == "array") {
						// TODO
						// Insert depending on position and associative array! (first, position must be passed!)
					} else {
						// This is not an array. Hooray!
						if ($property->isPublicFieldProperty()) {
							$this->moufManager->bindComponent($instanceName, $property->getName(), $newInstanceName);
						} else {
							$this->moufManager->bindComponentViaSetter($instanceName, $property->getMethodName(), $newInstanceName);
						}
					}
				}
			}*/
			
			$this->moufManager->rewriteMouf();
			
			$this->defaultAction($newInstanceName, $selfedit);
			return;
		}
		
		// Let's duplicate the component.
		if ($duplicateInstance == "true") {
			$this->moufManager->duplicateInstance($instanceName, $newInstanceName);
			$this->moufManager->rewriteMouf();
			
			$this->defaultAction($newInstanceName, $selfedit);
			return;
		}
		
		$this->moufManager->rewriteMouf();
		
		header("Location: .?name=".plainstring_to_htmlprotected($instanceName)."&selfedit=".plainstring_to_htmlprotected($selfedit));
		//$this->defaultAction($instanceName, $selfedit);	
	}
	
	/**
	 * Displays the toolbox at the right of the property field, with the image showing whether this comes from request, session, config, etc...
	 * 
	 * @param MoufPropertyDescriptor $property
	 */ 
	function displayFieldToolboxButton(MoufPropertyDescriptor $property) {
		$defaultType = $this->getTypeForProperty($property);
		echo '<span>';
		$hideSession = ' style="display:none" ';
		$hideConfig = ' style="display:none" ';
		$hideRequest = ' style="display:none" ';
		if ($defaultType == "session") {
			$hideSession = '';
		} elseif ($defaultType == "config") {
			$hideConfig = '';
		} elseif ($defaultType == "request") {
			$hideRequest = '';
		}
		echo '<span class="sessionmarker" '.$hideSession.'>session</span> ';
		echo '<span class="configmarker" '.$hideConfig.'>config</span>';
		echo '<span class="requestmarker" '.$hideRequest.'>request</span>';
		echo '<a onclick="onPropertyOptionsClick(\''.$property->getName().'\')" href="javascript:void(0)" ><img src="'.ROOT_URL.'src/views/images/bullet_wrench.png" alt="Options" /></a>';
		echo '</span>';
	}
}
?>