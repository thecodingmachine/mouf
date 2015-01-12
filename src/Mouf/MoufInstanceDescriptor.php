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

use Mouf\Reflection\MoufReflectionParameter;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\Reflection\MoufXmlReflectionClass;
use PhpParser\Error;

/**
 * This object represent an instance declared in the Mouf framework.
 * 
 * @author David Negrier
 */
class MoufInstanceDescriptor {
	
	// Instance declared via PHP code
	const TYPE_PHP = 'php';
	// Instance declared via property injection in constructor/setters/public properties
	const TYPE_DECLARATIVE = 'declarative';
	
	/**
	 * The MoufContainer instance owning the component.
	 * @var MoufContainer
	 */
	private $moufContainer;
	
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
	 * @var MoufInstancePropertyDescriptor[]
	 */
	private $properties = array();
	
	/**
	 * A list of public properties (not sure to be complete)
	 * Used for caching.
	 * 
	 * @var MoufInstancePropertyDescriptor[]
	 */
	private $publicProperties = array();
	
	/**
	 * A list of setter properties (not sure to be complete)
	 * Used for caching.
	 *
	 * @var MoufInstancePropertyDescriptor[]
	 */
	private $setterProperties = array();
	
	/**
	 * A list of constructor properties (not sure to be complete)
	 * Used for caching.
	 *
	 * @var MoufInstancePropertyDescriptor[]
	 */
	private $constructorProperties = array();
	
	/**
	 * The constructor should exclusively be used by MoufContainer.
	 * Use MoufContainer::getInstanceDescriptor and MoufContainer::createInstance to get instances of this class.
	 * 
	 * @param MoufManager|MoufContainer $moufContainer
	 * @param unknown_type $name
	 */
	public function __construct($moufContainer, $name) {
		// changes applied to accept both MoufManager and MoufContainer (for compatibility purpose).
		if ($moufContainer instanceof MoufManager) {
			$moufContainer = $moufContainer->getContainer();
		}
		
		
		$this->moufContainer = $moufContainer;
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
			$name = $this->moufContainer->getFreeAnonymousName();
		}
		$unsetWeakness = false;
		if ($this->moufContainer->isInstanceAnonymous($this->name) && !empty($name)) {
			$unsetWeakness = true;
		}
		$this->moufContainer->renameInstance($this->name, $name);
		if ($unsetWeakness) {
			$this->moufContainer->setInstanceWeakness($name, false);
			$this->moufContainer->setInstanceAnonymousness($name, false);
		}
		$this->name = $name;
		return $this;
	}
	
	/**
	 * Returns the name of the instance, or NULL if the instance is anonymous.
	 * @return string
	 */
	public function getName() {
		if ($this->moufContainer->isInstanceAnonymous($this->name)) {
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
		return $this->moufContainer->getInstanceType($this->name);
	}
	
	/**
	 * Returns true if the class is anonymous
	 * @return bool
	 */
	public function isAnonymous() {
		return $this->moufContainer->isInstanceAnonymous($this->name);
	}
	
	/**
	 * Sets whether the instance should be anonymous or not.
	 * 
	 * @param bool $anonymous
	 */
	public function setInstanceAnonymousness($anonymous) {
		$this->moufContainer->setInstanceAnonymousness($this->name, $anonymous);
	}
	
	/**
	 * Returns the class descriptor for this class
	 * @return MoufReflectionClass
	 */
	public function getClassDescriptor() {
		return $this->moufContainer->getReflectionClassManager()->getReflectionClass($this->getClassName());
	}
	
	/**
	 * Returns an object describing a property of a field.
	 * This will first search in the constructor parameters, then in the setters, and finally in the public fields.
	 * For a setter, you can use the name of the method (for instance "setField"), or directly the name
	 * of the underlying variable ("field").
	 * 
	 * @param string $name
	 * @return MoufInstancePropertyDescriptor
	 */
	public function getProperty($name) {
		$classDescriptor = $this->getClassDescriptor();
		$constructorProperties = $classDescriptor->getInjectablePropertiesByConstructor();
		if (isset($constructorProperties[$name])) {
			return $this->getConstructorArgumentProperty($name);
		}
		
		$methodProperties = $classDescriptor->getInjectablePropertiesBySetter();
		if (isset($methodProperties[$name])) {
			return $this->getSetterProperty($name);
		} else {
			foreach ($methodProperties as $methodProperty) {
				if ($methodProperty->getName() == $name) {
					return $this->getSetterProperty($methodProperty->getMethodName());
				}
			}
		}
		
		$publicProperties = $classDescriptor->getInjectablePropertiesByPublicProperty();
		if (isset($publicProperties[$name])) {
			return $this->getPublicFieldProperty($name);
		}
		
		throw new MoufException("Unable to find a property named ".$name." for instance ".$this->getName()." from class ".$this->getClassName());
	}

	/**
	 * Returns an object describing the public field $name for this instance.
	 * 
	 * @param string $name
	 * @return MoufInstancePropertyDescriptor
	 */
	public function getPublicFieldProperty($name) {
		if (!isset($this->publicProperties[$name])) {
			$propertyDescriptor = $this->getClassDescriptor()->getInjectablePropertyByPublicProperty($name);
			if ($propertyDescriptor) {
				$this->publicProperties[$name] = new MoufInstancePropertyDescriptor($this->moufContainer, $this, $propertyDescriptor);
			} else {
				$this->publicProperties[$name] = new MoufInstancePropertyDescriptor($this->moufContainer, $this, null, "publicproperty", $name);
			}
		}
		return $this->publicProperties[$name];
	}
	
	/**
	 * Returns an object describing the property via setter whose name is $name for this instance.
	 * 
	 * @param string $name
	 * @return MoufInstancePropertyDescriptor
	 */
	public function getSetterProperty($name) {
		if (!isset($this->setterProperties[$name])) {
			
			$classDescriptor = $this->getClassDescriptor();
			$methodProperties = $classDescriptor->getInjectablePropertiesBySetter();
			if (isset($methodProperties[$name])) {
				$this->setterProperties[$name] = new MoufInstancePropertyDescriptor($this->moufContainer, $this, $this->getClassDescriptor()->getInjectablePropertyBySetter($name));
			} else {
				$found = false;
				foreach ($methodProperties as $methodProperty) {
					if ($methodProperty->getName() == $name) {
						$this->setterProperties[$name] = new MoufInstancePropertyDescriptor($this->moufContainer, $this, $this->getClassDescriptor()->getInjectablePropertyBySetter($methodProperty->getMethodName()));
						$found = true;
						break;
					}
				}
				if (!$found) {
					// This must be an orphan
					$this->setterProperties[$name] = new MoufInstancePropertyDescriptor($this->moufContainer, $this, null, "setter", $name);
				}
			}
		}
		return $this->setterProperties[$name];
	}
	
	/**
	 * Returns an object describing the property set via the constructor.
	 * The name of the argument is $name.
	 *
	 * @param string $name
	 * @return MoufInstancePropertyDescriptor
	 */
	public function getConstructorArgumentProperty($name) {
		if (!isset($this->constructorProperties[$name])) {
			$propertyDescriptor = $this->getClassDescriptor()->getInjectablePropertyByConstructor($name);
			if ($propertyDescriptor) {
				$this->constructorProperties[$name] = new MoufInstancePropertyDescriptor($this->moufContainer, $this, $propertyDescriptor);
			} else {
				$this->constructorProperties[$name] = new MoufInstancePropertyDescriptor($this->moufContainer, $this, null, "constructorargument", $name);
			}
		}
		return $this->constructorProperties[$name];
	}
	
	/**
	 * Serializes the instance into a PHP array that can be easily transformed into JSON.
	 * @return array
	 */
	public function toJson() {
		$instanceArray['name'] = $this->name;
		$instanceArray['class'] = $this->getClassName();
		$instanceArray['anonymous'] = $this->isAnonymous();
		$instanceArray['type'] = $this->getType();
		
		if ($instanceArray['type'] == self::TYPE_DECLARATIVE) {
			$classDescriptor = $this->getClassDescriptor();
			
			$instanceArray['constructorArguments'] = array();
			foreach ($classDescriptor->getInjectablePropertiesByConstructor() as $propertyName=>$moufProperty) {
				$instanceArray['constructorArguments'][$propertyName] = $this->getConstructorArgumentProperty($propertyName)->toJson();
			}
	
			$instanceArray['properties'] = array();
			foreach ($classDescriptor->getInjectablePropertiesByPublicProperty() as $propertyName=>$moufProperty) {
				$instanceArray['properties'][$propertyName] = $this->getPublicFieldProperty($propertyName)->toJson();
			}
			
			$instanceArray['setters'] = array();
			foreach ($classDescriptor->getInjectablePropertiesBySetter() as $propertyName=>$moufProperty) {
				$instanceArray['setters'][$propertyName] = $this->getSetterProperty($propertyName)->toJson();
			}
			
			// Now, let's search for properties that would be declared in the configuration but that
			// would not exist in the code (this can happen if the property has been renamed in the code)
			$definedPublicProperties = $this->moufContainer->getParameterNames($this->name);
			if ($definedPublicProperties) {
				$missingPublicProperties = array_diff($definedPublicProperties, array_keys($instanceArray['properties']));
				foreach ($missingPublicProperties as $missingPublicPropertyName) {
					$instanceArray['properties'][$missingPublicPropertyName] = [
						'warning' => "The public property '".$missingPublicPropertyName."' is declared but does not exist. It is likely it once existed but was renamed or removed.",
						'orphan' => true,
						'type' => null,
						'value' => null,
						'isset' => true,
						'origin' => null,
						'metadata' => null
					];
				}
			}
			
			$definedSetters = $this->moufContainer->getParameterNamesForSetter($this->name);
			if ($definedSetters) {
				$missingSetters = array_diff($definedSetters, array_keys($instanceArray['setters']));
				foreach ($missingSetters as $missingSetterName) {
					$instanceArray['setters'][$missingSetterName] = [
					'warning' => "The setter '".$missingSetterName."' is declared but does not exist. It is likely it once existed but was renamed or removed.",
					'orphan' => true,
					'type' => null,
					'value' => null,
					'isset' => true,
					'origin' => null,
					'metadata' => null
					];
				}
			}
				
			$definedConstructorArguments = $this->moufContainer->getParameterNamesForConstructor($this->name);
			if ($definedConstructorArguments) {
				$constructor = $classDescriptor->getConstructor();
				if ($constructor) {
					$nbParameters = $classDescriptor->getConstructor()->getNumberOfParameters();
				} else {
					$nbParameters = 0;
				}
				$missingConstructorArguments = array_filter($definedConstructorArguments, function($nb) use ($nbParameters) { return $nbParameters <= $nb; });
				foreach ($missingConstructorArguments as $missingConstructorArgument) {
					$instanceArray['constructorArguments'][$missingConstructorArgument] = [
					'warning' => "The constructor argument n°'".$missingConstructorArgument."' is declared but does not exist. It is likely it once existed but was removed.",
					'orphan' => true,
					'type' => null,
					'value' => null,
					'isset' => true,
					'origin' => null,
					'metadata' => null
					];
				}
			}
			
		} else {
			$instanceArray['code'] = $this->getCode();
		}
		
		return $instanceArray;
	}
	
	/**
	 * You can set PHP code that returns the instance.
	 * This completely replaces all other properties.
	 * The PHP code must be passed as a string and must return a value using the "return" keyword.
	 * It must NOT start with <?php 
	 * 
	 * @param string $code
	 */
	public function setCode($code) {
		$this->moufContainer->setCode($this->getIdentifierName(), $code);
	}
	
	/**
	 * Returns MoufInstanceDescriptor::TYPE_DECLARATIVE if the instance has been declared via "setCode" or MoufInstanceDescriptor::TYPE_PHP otherwise.
	 * 
	 * @return string
	 */
	public function getType() {
		if ($this->moufContainer->getCode($this->getIdentifierName())===null) {
			return self::TYPE_DECLARATIVE;
		} else {
			return self::TYPE_PHP;
		}
	}
	
	/**
	 * Returns the string containing the PHP code to be executed to instantiate the instance, if "setCode" is used.
	 */
	public function getCode() {
		return $this->moufContainer->getCode($this->getIdentifierName());
	}

	/**
	 * Analyses the instance. Returns array() if everything is alright, or an array of error messages.
	 * Analysis performed:
	 * - The compulsory fields in the constructor are indeed filled.
	 * 
	 * @return string[]
	 */
	public function validate() {
		$errors = array();
		if ($this->getType() == self::TYPE_DECLARATIVE) {
			$classDescriptor = $this->getClassDescriptor();
			$constructor = $classDescriptor->getConstructor();
			
			if ($constructor) {
				$params = $constructor->getParameters();
				
				$i=0;
				
				foreach ($params as $param) {
					/* @var $param MoufReflectionParameter */
					if (!$param->isOptional()) {
						if (!$this->moufContainer->isParameterSetForConstructor($this->getIdentifierName(), $i)) {
							$name = $this->getIdentifierName();
							if ($this->isAnonymous()) {
								$name = "anonymous instance of class ".$this->getClassName();
							}
							$errors[] = "In instance <em>".$name."</em>, the constructor
									parameter '".$param->getName()."' is compulsory.
									<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
						} elseif (!$param->allowsNull()) {
							$name = $this->getIdentifierName();
							if ($this->isAnonymous()) {
								$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
							}
							if ($this->moufContainer->getParameterForConstructor($this->getIdentifierName(), $i) === null) {
								$errors[] = "In instance <em>".$name."</em>, the constructor
									parameter '".$param->getName()."' is null, but the constructor signature does not allow it to be null.
									<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
							}
						}
					}
					if ($this->moufContainer->getParameterTypeForConstructor($this->getIdentifierName(), $i) == 'php') {
						$name = $this->getIdentifierName();
						// Ok, we need to check the code.
						$code = $this->moufContainer->getParameterForConstructor($name, $i);
						try {
							CodeValidatorService::validateCode($code);
						} catch (Error $e) {
							if ($this->isAnonymous()) {
								$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
							}
							$errors[] = "In instance <em>".$name."</em>, the constructor
									parameter '".$param->getName()."' is using PHP code that contains a parse error: <em>{$e->getMessage()}</em>.
									<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
						}
					}		
					$i++;
				}
			}
			
			foreach ($classDescriptor->getInjectablePropertiesByPublicProperty() as $propertyName=>$moufProperty) {
				$property = $this->getPublicFieldProperty($propertyName);
				if ($property->getOrigin() == 'php') {
					$code = $property->getValue();
					try {
						CodeValidatorService::validateCode($code);
					} catch (Error $e) {
						$name = $this->getIdentifierName();
						if ($this->isAnonymous()) {
							$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
						}
						$errors[] = "In instance <em>".$name."</em>, the public property
									'".$param->getName()."' is using PHP code that contains a parse error: <em>{$e->getMessage()}</em>.
													<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
					}
				}
			}
			
			foreach ($classDescriptor->getInjectablePropertiesBySetter() as $propertyName=>$moufProperty) {
				$property = $this->getSetterProperty($propertyName);
				if ($property->getOrigin() == 'php') {
					$code = $property->getValue();
					try {
						CodeValidatorService::validateCode($code);
					} catch (Error $e) {
						$name = $this->getIdentifierName();
						if ($this->isAnonymous()) {
							$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
						}
						$errors[] = "In instance <em>".$name."</em>, the setter
									'".$param->getName()."' is using PHP code that contains a parse error: <em>{$e->getMessage()}</em>.
											<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
					}
				}
			}
			
			// Now, let's check that the MoufManager instance definition is not using properties that where once declared
			// but that no longer exist in the class:
			
			$definedPublicProperties = $this->moufContainer->getParameterNames($this->name);
			if ($definedPublicProperties) {
				$missingPublicProperties = array_diff($definedPublicProperties, array_keys($classDescriptor->getInjectablePropertiesByPublicProperty()));
				foreach ($missingPublicProperties as $missingPublicPropertyName) {
					$name = $this->getIdentifierName();
					if ($this->isAnonymous()) {
						$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
					}
					
					$errors[] = "In instance <em>".$name."</em>, the public property
								'".$missingPublicPropertyName."' is declared but does not exist. It is likely it once existed but was renamed or removed.
										<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
				}
			}
			
			$definedSetters = $this->moufContainer->getParameterNamesForSetter($this->name);
			if ($definedSetters) {
				$missingSetters = array_diff($definedSetters, array_keys($classDescriptor->getInjectablePropertiesBySetter()));
				foreach ($missingSetters as $missingSetterName) {
					$name = $this->getIdentifierName();
					if ($this->isAnonymous()) {
						$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
					}
				
					$errors[] = "In instance <em>".$name."</em>, the setter
								'".$missingSetterName."()' is declared but does not exist. It is likely it once existed but was renamed or removed.
										<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
				}
			}
			
			$definedConstructorArguments = $this->moufContainer->getParameterNamesForConstructor($this->name);
			if ($definedConstructorArguments) {
				$constructor = $classDescriptor->getConstructor();
				if ($constructor) {
					$nbParameters = $classDescriptor->getConstructor()->getNumberOfParameters();
					$missingConstructorArguments = array_filter($definedConstructorArguments, function($nb) use ($nbParameters) { return $nbParameters <= $nb; });
					foreach ($missingConstructorArguments as $missingArgument) {
						$name = $this->getIdentifierName();
						if ($this->isAnonymous()) {
							$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
						}
					
						$errors[] = "In instance <em>".$name."</em>, the constructor argument
								n°".$missingArgument." is used but does not exist. It is likely it once existed but was removed.
										<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
					}
				} else {
					$name = $this->getIdentifierName();
					$errors[] = "In instance <em>".$name."</em>, parameters are injected in the constructor but the constructor does not exist. It is likely it once existed but was removed.
										<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
				}
				
				
			}
				
		} else {
			$error = $this->moufContainer->getErrorOnInstanceCode($this->getIdentifierName());
			if ($error) {
				$name = $this->getIdentifierName();
				if ($this->isAnonymous()) {
					$name = "anonymous instance from class <strong>".$this->getClassName()."</strong>";
				}
				$errors[] = "The instance <em>".$name."</em> is declared by PHP code and is throwing an error message:
								$error
										<a href='".MOUF_URL."ajaxinstance/?name=".urlencode($this->getIdentifierName())."' class='btn btn-success'><i class='icon-pencil icon-white'></i> Edit</a>";
			}
		}
		return $errors;
	}
}