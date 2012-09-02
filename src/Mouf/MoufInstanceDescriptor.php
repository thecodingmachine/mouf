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
 * This object represent an instance declared in the Mouf framework.
 * 
 * @author David Negrier
 */
class MoufInstanceDescriptor {
	
	/**
	 * The MoufManager instance owning the component.
	 * @var MoufManager
	 */
	private $moufManager;
	
	/**
	 * The name of the instance.
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * A list of properties (not the list of all properties).
	 * Used for caching.
	 * 
	 * @var array<MoufInstancePropertyDescriptor>
	 */
	private $properties = array();
	
	/**
	 * The constructor should exclusively be used by MoufManager.
	 * Use MoufManager::getInstanceDescriptor and MoufManager::createInstance to get instances of this class.
	 * 
	 * @param MoufManager $moufManager
	 * @param unknown_type $name
	 */
	public function __construct(MoufManager $moufManager, $name) {
		$this->moufManager = $moufManager;
		$this->name = $name;
	}
	
	/**
	 * Sets the name of this instance (or rename the instance).
	 * If $name is empty, the instance will be considered anonymous.
	 * 
	 * Note: If the instance was anonymous and it is given a name, it will be automatically declared "non-weak" (but you can set the weakness of the instance
	 * back using "setWeakness").
	 * If the instance becomes anonymous, it becomes "weak".
	 * 
	 * @param string $name
	 * @return MoufInstanceDescriptor The instance is returned, for chaining purpose.
	 */
	public function setName($name) {
		if (empty($name)) {
			$name = $this->moufManager->getFreeAnonymousName();
		}
		$unsetWeakness = false;
		if ($this->moufManager->isInstanceAnonymous($this->name) && !empty($name)) {
			$unsetWeakness = true;
		}
		$this->moufManager->renameComponent($this->name, $name);
		if ($unsetWeakness) {
			$this->moufManager->setInstanceWeakness($name, false);
			$this->moufManager->setInstanceAnonymousness($name, false);
		}
		$this->name = $name;
		return $this;
	}
	
	/**
	 * Returns the name of the instance, or NULL if the instance is anonymous.
	 * @return string
	 */
	public function getName() {
		if ($this->moufManager->isInstanceAnonymous($this->name)) {
			return null;
		} else {
			return $this->name;
		}
	}
	
	/**
	 * Returns the name of the instance, or its internal name if this is an anonymous instance.
	 * 
	 * @return string
	 */
	public function getIdentifierName() {
		return $this->name;
	}
	
	/**
	 * Returns the classname of the instance.
	 * @return string
	 */
	public function getClassName() {
		return $this->moufManager->getInstanceType($this->name);
	}
	
	/**
	 * Returns true if the class is anonymous
	 * @return bool
	 */
	public function isAnonymous() {
		return $this->moufManager->isInstanceAnonymous($this->name);
	}
	
	/**
	 * Sets whether the instance should be anonymous or not.
	 * 
	 * @param bool $anonymous
	 */
	public function setInstanceAnonymousness($anonymous) {
		$this->moufManager->setInstanceAnonymousness($this->name, $anonymous);
	}
	
	/**
	 * Returns the class descriptor for this class
	 * @return MoufXmlReflectionClass
	 */
	public function getClassDescriptor() {
		return $this->moufManager->getClassDescriptor($this->getClassName());
	}
	
	/**
	 * Returns an object describing a property of a field.
	 * 
	 * @param string $name
	 * @return MoufInstancePropertyDescriptor
	 */
	public function getProperty($name) {
		if (!isset($this->properties[$name])) {
			$this->properties[$name] = new MoufInstancePropertyDescriptor($this->moufManager, $this, $name);
		}
		return $this->properties[$name]; 
	}
	
	/**
	 * Serializes the instance into a PHP array that can be easily transformed into JSON.
	 * @return array
	 */
	public function toJson() {
		$classDescriptor = $this->getClassDescriptor();
		$instanceArray['name'] = $this->name;
		$instanceArray['class'] = $this->getClassName();
		$instanceArray['anonymous'] = $this->isAnonymous();
		$instanceArray['properties'] = array();
		$moufProperties = $classDescriptor->getMoufProperties();
		foreach ($moufProperties as $propertyName=>$moufProperty) {
			/* @var $moufProperty MoufPropertyDescriptor */
			$instanceArray['properties'][$propertyName] = array();
			//$instanceArray['properties'][$propertyName]['source'] = $moufProperty->getSource();
			$property = $this->getProperty($propertyName);
			$value = $property->getValue();
			if ($value instanceof MoufInstanceDescriptor) {
				$serializableValue = $value->getIdentifierName();
			} elseif (is_array($value)) {
				// We cannot match a PHP array to a JSON array!
				// The keys in a PHP array are ordered. The key in a JSON array are not ordered!
				// Therefore, we will be sending the arrays as JSON arrays of key/values to preserve order.
				$serializableValue = self::arrayToJson($value);
				
// 				$serializableValue = array_map(function($singleValue) {
// 					if ($singleValue instanceof MoufInstanceDescriptor) {
// 						return $singleValue->getName();
// 					} else {
// 						return $singleValue;
// 					}
// 				}, $value);
			} else {
				$serializableValue = $value;
			}
			$instanceArray['properties'][$propertyName]['value'] = $serializableValue;
			$instanceArray['properties'][$propertyName]['origin'] = $property->getOrigin();
			$instanceArray['properties'][$propertyName]['metadata'] = $property->getMetaData();
			
		}
		return $instanceArray;
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
				$value = $val->getName();
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