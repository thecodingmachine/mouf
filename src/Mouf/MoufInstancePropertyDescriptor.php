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
 * This object represent a Mouf property of some instance, declared in the Mouf framework.
 * TODO: split this class in 3 subclasses (and do not forget to migrate the unit tests)
 * 
 * @author David Negrier
 */
class MoufInstancePropertyDescriptor {
	
	/**
	 * The MoufContainer instance owning the component.
	 * @var MoufContainer
	 */
	private $moufContainer;
	
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
	
	private $orphanType;
	
	/**
	 * The constructor should exclusively be used by MoufContainer.
	 * Use MoufContainer::getInstanceDescriptor and MoufContainer::createInstance to get instances of this class.
	 *
	 * @param MoufContainer $moufContainer
	 * @param MoufInstanceDescriptor $instanceDescriptor
	 * @param MoufPropertyDescriptor $propertyDescriptor Note: this can be null if the property is "orphan".
	 * @param string $orphanType If this property is orphan, this is the type of the property: "publicproperty", "setter" or "constructorargument".
	 * @param string $orphanName If this property is orphan, this is the name of the property
	 */
	public function __construct(MoufContainer $moufContainer, MoufInstanceDescriptor $instanceDescriptor, MoufPropertyDescriptor $propertyDescriptor = null, $orphanType = null, $orphanName = null) {
		$this->moufContainer = $moufContainer;
		$this->instanceDescriptor = $instanceDescriptor;
		
		// Is this a setter or a property or a constructor parameter? Let's find out!
		//$propertyDescriptor = $instanceDescriptor->getClassDescriptor()->getMoufProperty($name);
		// If this is not a public field name or a setter name or a constructor parameter, this might be the name of the private property of a setter.
		// Let's find out: 
		/*if ($propertyDescriptor == null) {
			$setterName = MoufPropertyDescriptor::getSetterNameForPropertyName($name);
			$propertyDescriptor = $instanceDescriptor->getClassDescriptor()->getMoufProperty($setterName);
			if ($propertyDescriptor == null) {
				throw new MoufException("Could not find property '$name' for class '".$instanceDescriptor->getClassName()."'");
			}
			$name = $setterName;
		}*/

		if ($propertyDescriptor) {
			if ($propertyDescriptor->isSetterProperty()) {
				$this->name = $propertyDescriptor->getMethodName();
			} else {
				$this->name = $propertyDescriptor->getName();
			}
		} else {
			$this->orphanType = $orphanType;
			$this->name = $orphanName;
		}
		
		$this->propertyDescriptor = $propertyDescriptor;
	}
	
	/**
	 * Sets the value for this property.
	 * The value can be a primitive type, an array of primitive types, a MoufInstanceDescriptor or an array of MoufInstanceDescriptors, depending on the type of the parameter.
	 * 
	 * Pass MoufInstanceDescriptors as $value to bind this instance to other instances.
	 * 
	 * @param string|array|MoufInstanceDescriptor $value
	 */
	public function setValue($value) {
		// TODO: add a series of validation first.
		// We could build the validations with Validators.

		$isInstance = false;
		$isValue = false;
		
		$toStore = $this->toMoufContainerArray($value, $isInstance, $isValue);

		if ($isValue) {
			$origin = $this->getOrigin();
			if (empty($origin)) {
				$origin = "string";
			}
			
			if ($this->propertyDescriptor->isPublicFieldProperty()) {
				$this->moufContainer->unsetParameter($this->instanceDescriptor->getIdentifierName(), $this->name);
				$this->moufContainer->setParameter($this->instanceDescriptor->getIdentifierName(), $this->name, $toStore, $origin);
			} elseif ($this->propertyDescriptor->isSetterProperty()) {
				$this->moufContainer->unsetParameterForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
				$this->moufContainer->setParameterViaSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $toStore, $origin);
			} elseif ($this->propertyDescriptor->isConstructor()) {
				$this->moufContainer->unsetParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
				$this->moufContainer->setParameterViaConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $toStore, "primitive", $origin);
			} else {
				throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
			}
		} else {
			if ($this->propertyDescriptor->isPublicFieldProperty()) {
				$this->moufContainer->unsetParameter($this->instanceDescriptor->getIdentifierName(), $this->name);
				$this->moufContainer->bindComponents($this->instanceDescriptor->getIdentifierName(), $this->name, $toStore);
			} elseif ($this->propertyDescriptor->isSetterProperty()) {
				$this->moufContainer->unsetParameterForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
				$this->moufContainer->bindComponentsViaSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $toStore);
			} elseif ($this->propertyDescriptor->isConstructor()) {
				$this->moufContainer->unsetParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
				$this->moufContainer->setParameterViaConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $toStore, "object");
			} else {
				throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
			}
		}
		return $this;
	}
	
	private function toMoufContainerArray($value, &$isInstance, &$isValue) {
		if ($value === null) {
			$toStore = null;
		} elseif ($value instanceof MoufInstanceDescriptor) {
			$toStore = $value->getIdentifierName();
			$isInstance = true;
		} elseif (is_array($value)) {
			// Let's find if there is a MoufInstanceDescriptor in this array...
			$toStore = array();
			foreach ($value as $key=>$val) {
				$toStore[$key] = $this->toMoufContainerArray($val, $isInstance, $isValue);
				if ($isInstance && $isValue) {
					throw new MoufException("Invalid value passed to setValue. You passed a mix of MoufInstanceDescriptor and other values in the array.");
				}
			}
		} else {
			if (is_object($value)) {
				throw new MoufException("Invalid value passed to setValue. You passed a '".get_class($value)."' object to setValue. Primitive type of instance of MoufInstanceDescriptor class expected.");
			}
			$toStore = $value;
			$isValue = true;
		}
		
		return $toStore;
	}

	/**
	 * Takes in parameter a return from the getBoundComponentsXXX methods and cast that to a MoufInstanceDescriptor
	 * or an array of MoufInstanceDescriptor.
	 * 
	 * @param string[]|string $instanceNames
	 * @return NULL|multitype:NULL Ambigous <\Mouf\MoufInstanceDescriptor, \Mouf\array<string,> |Ambigous <\Mouf\MoufInstanceDescriptor, \Mouf\array<string,>
	 */
	private function toInstanceDescriptor($instanceNames) {
		if ($instanceNames === null) {
			return null;
		}
		if (is_array($instanceNames)) {
			$moufContainer = $this->moufContainer;
			return self::array_map_deep($instanceNames, function($instanceName) use ($moufContainer) {
				if ($instanceName != null) {
					return $moufContainer->getInstanceDescriptor($instanceName);
				} else {
					return null;
				}
			});
			/*$arrayOfDescriptors = array();
			foreach ($instanceNames as $key=>$instanceName) {
				if ($instanceName != null) {
					$arrayOfDescriptors[$key] = $this->moufContainer->getInstanceDescriptor($instanceName);
				} else {
					$arrayOfDescriptors[$key] = null;
				}
			}
			return $arrayOfDescriptors;*/
		} else {
			return $this->moufContainer->getInstanceDescriptor($instanceNames);
		}
		
	}
	
	private static function array_map_deep($array, $callback) {
		$new = array();
		if( is_array($array) ) foreach ($array as $key => $val) {
			if (is_array($val)) {
				$new[$key] = self::array_map_deep($val, $callback);
			} else {
				$new[$key] = call_user_func($callback, $val);
			}
		}
		else $new = call_user_func($callback, $array);
		return $new;
	}
	
	/**
	 * Returns the value for this property.
	 * The value returned can be a primitive type, an array of primitive types, a MoufInstanceDescriptor or an array of MoufInstanceDescriptors, depending on the type of the parameter.
	 *
	 * @return string|MoufInstanceDescriptor|null
	 */
	public function getValue() {
		
		if ($this->propertyDescriptor->isPublicFieldProperty()) {
			// Let's try to see if it is a "value":
			$param = $this->moufContainer->getParameter($this->instanceDescriptor->getIdentifierName(), $this->name);
			if ($param !== null) {
				return $param;
			}
			
			$instanceName = $this->moufContainer->getBoundComponentsOnProperty($this->instanceDescriptor->getIdentifierName(), $this->name);

			return $this->toInstanceDescriptor($instanceName);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			// Let's try to see if it is a "value":
			$param = $this->moufContainer->getParameterForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
			if ($param !== null) {
				return $param;
			}
			
			$instanceName = $this->moufContainer->getBoundComponentsOnSetter($this->instanceDescriptor->getIdentifierName(), $this->name);

			return $this->toInstanceDescriptor($instanceName);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			// Let's try to see if it is a "value":
			$argumentType = $this->moufContainer->isConstructorParameterObjectOrPrimitive($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
			$param = $this->moufContainer->getParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
				
			if ($argumentType == "primitive") {
				return $param;
			} elseif ($argumentType == "object") {
				return $this->toInstanceDescriptor($param);
			} else {
				// Not set: return null
				return null;
			}
			
		} else {
			throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
		}
		
	
	}
	
	/**
	 * Returns true if the value is set in the IOC container.
	 * If the value is not set, the default PHP value for the property is used (if any).
	 * 
	 * @return boolean
	 */
	public function isValueSet() {
		if ($this->propertyDescriptor->isPublicFieldProperty()) {
			return $this->moufContainer->isParameterSet($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			return $this->moufContainer->isParameterSetForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			return $this->moufContainer->isParameterSetForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
		} else {
			throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
		}
	}
	
	/**
	 * Completely unset this value from the DI container.
	 * If the value is not set, the default PHP value for the property is used (if any).
	 *
	 * @return boolean
	 */
	public function unsetValue() {
		if ($this->isOrphan()) {
			if ($this->orphanType == "publicproperty") {
				return $this->moufManager->unsetParameter($this->instanceDescriptor->getIdentifierName(), $this->name);
			} elseif ($this->orphanType == "setter") {
				return $this->moufManager->unsetParameterForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
			} elseif ($this->orphanType == "constructorargument") {
				return $this->moufManager->unsetParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->name);
			} else {
				throw new MoufException("Unsupported orphan type: it is not a public field nor a setter nor a constructor...");
			}
		}
		
		if ($this->propertyDescriptor->isPublicFieldProperty()) {
			return $this->moufContainer->unsetParameter($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			return $this->moufContainer->unsetParameterForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			return $this->moufContainer->unsetParameterForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
		} else {
			throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
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
		throw new \Exception("Not implemented yet");
	}
	
	/**
	 * Returns metadata for this property
	 * 
	 * @return string
	 */
	public function getMetaData() {
		if ($this->propertyDescriptor->isPublicFieldProperty()) {
			return $this->moufContainer->getParameterMetadata($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			return $this->moufContainer->getParameterMetadataForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			return $this->moufContainer->getParameterMetadataForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
		} else {
			throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
		}
	}
	
	/**
	 * Sets the parameter "origin" (where the value that feeds the parameter comes from).
	 * Can be one of "string|config|request|session|php"
	 *
	 * @param string $origin
	 * @throws MoufException
	 * @return MoufInstancePropertyDescriptor Returns $this for chaining.
	 */
	public function setOrigin($origin) {
		//if ($this->propertyDescriptor->isPrimitiveType()) {
			if ($this->propertyDescriptor->isPublicFieldProperty()) {
				$this->moufContainer->setParameterType($this->instanceDescriptor->getIdentifierName(), $this->name, $origin);
			} elseif ($this->propertyDescriptor->isSetterProperty()) {
				$this->moufContainer->setParameterTypeForSetter($this->instanceDescriptor->getIdentifierName(), $this->name, $origin);
			} elseif ($this->propertyDescriptor->isConstructor()) {
				$this->moufContainer->setParameterTypeForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex(), $origin);
			} else {
				throw new MoufException("Unsupported property type: it is not a public field nor a setter nor a constructor...");
			}
		/*} else {
			// FIXME: TODO: support origin for binds
			throw new MoufException("config or other origins is NOT YET SUPPORTED for instance binding.");
		}*/
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
			return $this->moufContainer->getParameterType($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isSetterProperty()) {
			return $this->moufContainer->getParameterTypeForSetter($this->instanceDescriptor->getIdentifierName(), $this->name);
		} elseif ($this->propertyDescriptor->isConstructor()) {
			return $this->moufContainer->getParameterTypeForConstructor($this->instanceDescriptor->getIdentifierName(), $this->propertyDescriptor->getParameterIndex());
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
	
	/**
	 * Returns "true" if the instance property is "orphan".
	 * A property is orphan if it points to a public property / setter / constructor argument that
	 * no longer exists.
	 * 
	 * @return boolean
	 */
	public function isOrphan() {
		return $this->orphanType != null;
	}
	
	/**
	 * Serializes the mouf instance property into a PHP array
	 * @return array
	 */
	public function toJson() {
		$result = array();
		$value = $this->getValue();

		if ($value instanceof MoufInstanceDescriptor) {
			$serializableValue = $value->getIdentifierName();
			//$result['type'] = 'object';
		} elseif (is_array($value)) {
			// We cannot match a PHP array to a JSON array!
			// The keys in a PHP array are ordered. The key in a JSON array are not ordered!
			// Therefore, we will be sending the arrays as JSON arrays of key/values to preserve order.
			$serializableValue = self::arrayToJson($value);
			
			//$result['type'] = 'scalar';
			// Let's find the type:
			/*foreach ($value as $val) {
				if ($val instanceof MoufInstanceDescriptor) {
					//$result['type'] = 'object';
					break;
				}
			}*/
			
		} else {
			$serializableValue = $value;
			//$result['type'] = 'scalar';
		}
		
		try {
			$type = $this->propertyDescriptor->getTypes()->getCompatibleTypeForInstance($value);
		} catch (\ReflectionException $e) {
			// If an error occurs here, let's silently continue with an error message added.
			$result['warning'] = $e->getMessage();
			$type = null;
		}
		/*if ($type == null) {
			throw new MoufException("Error for property ".$this->propertyDescriptor->getName()." for instance ".$this->instanceDescriptor->getName().".");
		}*/
		if ($type == null) {
			$result['type'] = null;
		} else {
			$result['type'] = $type->toJson();
		}
		$result['value'] = $serializableValue;
		$result['isset'] = $this->isValueSet();
		$result['origin'] = $this->getOrigin();
		$result['metadata'] = $this->getMetaData();
		return $result;
	}
	
	/**
	 * We cannot match a PHP array to a JSON array!
	 * The keys in a PHP array are ordered. The key in a JSON array are not ordered!
	 * Therefore, we will be sending the arrays as JSON arrays of key/values to preserve order.
	 *
	 * @param array $phpArray
	 */
	private static function arrayToJson(array $phpArray) {
		$serializableValue = array();
		foreach ($phpArray as $key=>$val) {
			if ($val instanceof MoufInstanceDescriptor) {
				$value = $val->getIdentifierName();
			} else if (is_array($val)) {
				$value = self::arrayToJson($val);
			} else {
				$value = $val;
			}
				
			$serializableValue[] = array(
					"key" => $key,
					"value" => $value
			);
		}
		return $serializableValue;
	}
}