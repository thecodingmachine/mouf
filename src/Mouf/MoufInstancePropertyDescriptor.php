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
 * This object represent an Mouf property of some instance, declared in the Mouf framework.
 * TODO: split this class in 3 subclasses (and do not forget to migrate the unit tests)
 * 
 * @author David Negrier
 */
class MoufInstancePropertyDescriptor {
	
	/**
	 * The MoufManager instance owning the component.
	 * @var MoufManager
	 */
	private $moufManager;
	
	/**
	 * The instance this property is part of.
	 * @var MoufInstanceDescriptor
	 */
	private $instanceDescriptor;
	
	/**
	 * The name of the property or of the setter.
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The property descriptor describing this property
	 * 
	 * @var MoufPropertyDescriptor
	 */
	private $propertyDescriptor;
	
	/**
	 * The constructor should exclusively be used by MoufManager.
	 * Use MoufManager::getInstanceDescriptor and MoufManager::createInstance to get instances of this class.
	 *
	 * @param MoufManager $moufManager
	 * @param unknown_type $name
	 */
	public function __construct(MoufManager $moufManager, MoufInstanceDescriptor $instanceDescriptor, $name) {
		$this->moufManager = $moufManager;
		$this->instanceDescriptor = $instanceDescriptor;
		
		// Is this a setter or a property or a constructor parameter? Let's find out!
		$propertyDescriptor = $instanceDescriptor->getClassDescriptor()->getMoufProperty($name);
		// If this is not a public field name or a setter name or a constructor parameter, this might be the name of the private property of a setter.
		// Let's find out: 
		if ($propertyDescriptor == null) {
			$setterName = MoufPropertyDescriptor::getSetterNameForPropertyName($name);
			$propertyDescriptor = $instanceDescriptor->getClassDescriptor()->getMoufProperty($setterName);
			if ($propertyDescriptor == null) {
				throw new MoufException("Could not find property '$name' for class '".$instanceDescriptor->getClassName()."'");
			}
			$name = $setterName;
		}

		$this->name = $name;
		$this->propertyDescriptor = $propertyDescriptor;
	}
	
	/**
	 * Sets the value for this property.
	 * The value can be a primitive type, an array of primitive types, a MoufInstanceDescriptor or an array of MoufInstanceDescriptors, depending on the type of the parameter.
	 * 
	 * Pass MoufInstanceDescriptors as $value to bind this instance to other instances.
	 * 
	 * @param mixed $value
	 */
	public function setValue($value) {
		if ($this->propertyDescriptor->isPrimitiveType()) {
			if (($value instanceof MoufInstanceDescriptor) || is_array($value)) {
				throw new MoufException("You passed an array or a MoufInstanceDescriptor to MoufInstanceProperty::setValue, but the property '{$this->name}' instance '".$this->instanceDescriptor->getIdentifierName()."' of class '".$this->instanceDescriptor->getClassName()."' is supposed to take a primitive type in argument.");
			}
			if ($this->propertyDescriptor->isPublicFieldProperty()) {
				$this->moufManager->setParameter($this->instanceDescriptor->getIdentifierName(), $this->name, $value);
			} elseif ($this->propertyDescriptor->isSetterProperty()) {
				$this->moufManager->setParameterViaSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $value);
			} elseif ($this->propertyDescriptor->isConstructor()) {
				$this->moufManager->setParameterViaConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $value, "primitive");
			} else {
				throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
			}
		} elseif ($this->propertyDescriptor->getType() == "array") {
			if (!empty($value) && !is_array($value)) {
				throw new MoufException("In MoufInstanceProperty::setValue, the property '{$this->name}' instance '".$this->instanceDescriptor->getIdentifierName()."' of class '".$this->instanceDescriptor->getClassName()."' is supposed to be an array (or null).");
			}
			if ($this->propertyDescriptor->isArrayOfPrimitiveTypes()) {
				// This is an array of primitive types
				if ($this->propertyDescriptor->isPublicFieldProperty()) {
					$this->moufManager->setParameter($this->instanceDescriptor->getIdentifierName(), $this->name, $value);
				} elseif ($this->propertyDescriptor->isSetterProperty()) {
					$this->moufManager->setParameterViaSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $value);
				} elseif ($this->propertyDescriptor->isConstructor()) {
					$this->moufManager->setParameterViaConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $value, "primitive");
				} else {
					throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
				}
			} else {
				// This is an array of objects
				$names = array();
				foreach ($value as $item) {
					if (!($item instanceof MoufInstanceDescriptor)) {
						throw new MoufException("In MoufInstanceProperty::setValue, the property '{$this->name}' instance '".$this->instanceDescriptor->getIdentifierName()."' of class '".$this->instanceDescriptor->getClassName()."' is supposed to be an array of MoufInstanceDescriptors (or null).");
					}
					/* @var $item MoufInstanceDescriptor */
					$names[] = $item->getName();
				}
				if ($this->propertyDescriptor->isPublicFieldProperty()) {
					$this->moufManager->bindComponents($this->instanceDescriptor->getIdentifierName(), $this->name, $names);
				} elseif ($this->propertyDescriptor->isSetterProperty()) {
					$this->moufManager->bindComponentsViaSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $names);
				} elseif ($this->propertyDescriptor->isConstructor()) {
					$this->moufManager->setParameterViaConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $names, "object");
				} else {
					throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
				}
			}
			
		} else {
			if ($value == null) {
				if ($this->propertyDescriptor->isPublicFieldProperty()) {
					$this->moufManager->bindComponent($this->instanceDescriptor->getIdentifierName(), $this->name, null);
				} elseif ($this->propertyDescriptor->isSetterProperty()) {
					$this->moufManager->bindComponentViaSetter($this->instanceDescriptor->getIdentifierName(), $this->name, null);
				} elseif ($this->propertyDescriptor->isConstructor()) {
					$this->moufManager->setParameterViaConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), null, "object");
				} else {
					throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
				}
			} else {
				// This is a class or interface
				if (!($value instanceof MoufInstanceDescriptor)) {
					throw new MoufException("In MoufInstanceProperty::setValue, the property '{$this->name}' instance '".$this->instanceDescriptor->getIdentifierName()."' of class '".$this->instanceDescriptor->getClassName()."' is supposed to be a MoufInstanceDescriptor (or null).");
				}
				if ($this->propertyDescriptor->isPublicFieldProperty()) {
					$this->moufManager->bindComponent($this->instanceDescriptor->getIdentifierName(), $this->name, $value->getIdentifierName());
				} elseif ($this->propertyDescriptor->isSetterProperty()) {
					$this->moufManager->bindComponentViaSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $value->getIdentifierName());
				} elseif ($this->propertyDescriptor->isConstructor()) {
					$this->moufManager->setParameterViaConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $value->getIdentifierName(), "object");
				} else {
					throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
			}
			}
		}
		return $this;
	}
	
	/**
	 * Returns the value for this property.
	 * The value returned can be a primitive type, an array of primitive types, a MoufInstanceDescriptor or an array of MoufInstanceDescriptors, depending on the type of the parameter.
	 *
	 * @return mixed
	 */
	public function getValue() {
		if ($this->propertyDescriptor->isPrimitiveType()) {
			if ($this->propertyDescriptor->isPublicFieldProperty()) {
				return $this->moufManager->getParameter($this->instanceDescriptor->getIdentifierName(), $this->name);
			} elseif ($this->propertyDescriptor->isSetterProperty()) {
				return $this->moufManager->getParameterForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
			} elseif ($this->propertyDescriptor->isConstructor()) {
				return $this->moufManager->getParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
			} else {
				throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
			}
		} elseif ($this->propertyDescriptor->getType() == "array") {
			if ($this->propertyDescriptor->isArrayOfPrimitiveTypes()) {
				// This is an array of primitive types
				if ($this->propertyDescriptor->isPublicFieldProperty()) {
					return $this->moufManager->getParameter($this->instanceDescriptor->getIdentifierName(), $this->name);
				} elseif ($this->propertyDescriptor->isSetterProperty()) {
					return $this->moufManager->getParameterForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
				} elseif ($this->propertyDescriptor->isConstructor()) {
					return $this->moufManager->getParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
				} else {
					throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
				}
			} else {
				// This is an array of objects
				if ($this->propertyDescriptor->isPublicFieldProperty()) {
					$arrayOfString = $this->moufManager->getBoundComponentsOnProperty($this->instanceDescriptor->getIdentifierName(), $this->name);
				} elseif ($this->propertyDescriptor->isSetterProperty()) {
					$arrayOfString = $this->moufManager->getBoundComponentsOnSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
				} elseif ($this->propertyDescriptor->isConstructor()) {
					$arrayOfString = $this->moufManager->getParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
				} else {
					throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
				}
				
				
				if ($arrayOfString !== null){//KEVIN : getBoundComponentsOn[Property | Setter] may return null, avoid PHP WARNING by testing
					$arrayOfDescriptors = array(); 
					foreach ($arrayOfString as $key=>$instanceName) {
						if ($instanceName != null) {
							$arrayOfDescriptors[$key] = $this->moufManager->getInstanceDescriptor($instanceName);
						} else {
							$arrayOfDescriptors[$key] = null;
						}
					}
				}else{
					$arrayOfDescriptors = null;
				}
				return $arrayOfDescriptors;
			}
				
		} else {
			// This is an array of objects
			if ($this->propertyDescriptor->isPublicFieldProperty()) {
				$instanceName = $this->moufManager->getBoundComponentsOnProperty($this->instanceDescriptor->getIdentifierName(), $this->name);
			} elseif ($this->propertyDescriptor->isSetterProperty()) {
				$instanceName = $this->moufManager->getBoundComponentsOnSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
			} elseif ($this->propertyDescriptor->isConstructor()) {
				$instanceName = $this->moufManager->getParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
			} else {
				throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
			}
			
			if ($instanceName != null) {
				return $this->moufManager->getInstanceDescriptor($instanceName);
			} else {
				return null;
			}
		}
	
	}
	
	/**
	 * Returns metadata for this property
	 * 
	 * @param array $array
	 * @throws MoufException
	 */
	public function setMetaData($array) {
		// TODO!
		throw new Exception("Not implemented yet");
	}
	
	/**
	 * Returns metadata for this property
	 * 
	 * @return string
	 */
	public function getMetaData() {
		if ($this->propertyDescriptor->isPublicFieldProperty()) {
			return $this->moufManager->getParameterMetadata($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			return $this->moufManager->getParameterMetadataForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			return $this->moufManager->getParameterMetadataForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
		} else {
			throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
		}
	}
	
	/**
	 * Sets the parameter "origin" (where the value that feeds the parameter comes from).
	 * Can be one of "string|config|request|session"
	 *
	 * @param string $origin
	 * @throws MoufException
	 * @return MoufInstancePropertyDescriptor Returns $this for chaining.
	 */
	public function setOrigin($origin) {
		if ($this->propertyDescriptor->isPublicFieldProperty()) {
			$this->moufManager->setParameterType($this->instanceDescriptor->getIdentifierName(), $this->name, $origin);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			$this->moufManager->setParameterTypeForSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $origin);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			$this->moufManager->setParameterTypeForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $origin);
		} else {
			throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
		}
		return $this;
	}
	
	/**
	 * Returns the parameter "origin" (where the value that feeds the parameter comes from).
	 * Can be one of "string|config|request|session"
	 * 
	 * @return string
	 */
	public function getOrigin() {
		if ($this->propertyDescriptor->isPublicFieldProperty()) {
			return $this->moufManager->getParameterType($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			return $this->moufManager->getParameterTypeForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			return $this->moufManager->getParameterTypeForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
		} else {
			throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
		}
	}
	
	/**
	 * Returns the property descriptor associated to this MoufInstanceProperty
	 * 
	 * @return MoufPropertyDescriptor
	 */
	public function getPropertyDescriptor() {
		return $this->propertyDescriptor;
	}
}