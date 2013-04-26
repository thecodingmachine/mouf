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

use Mouf\Annotations\paramAnnotation;

use Mouf\Reflection\MoufReflectionPropertyInterface;
use Mouf\Reflection\MoufReflectionMethodInterface;
use Mouf\Reflection\MoufReflectionParameterInterface;

/**
 * This class represents a property (as defined by the @Property annotation or as a constructor parameter).
 * Since properties can be either public fields or setter methods, this class is used
 * as an abstraction level over the property. 
 *
 */
class MoufPropertyDescriptor {
	public static $PUBLIC_FIELD = "field";
	public static $SETTER = "setter";
	public static $CONSTRUCTOR = "constructor";

	private $name;

	/**
	 * The index of the property if this is a constructor property.
	 * @var int
	 */
	private $index;
	
	private $methodName;
	
	/**
	 * A MoufReflectionPropertyInterface or a MoufReflectionMethodInterface or a MoufReflectionParameterInterface (depending on the kind of property: field or setter or constructor parameter)
	 *
	 * @var MoufReflectionPropertyInterface|MoufReflectionMethodInterface|MoufReflectionParameterInterface
	 */
	private $object;
	
	/**
	 * The parameter annotation, only filled if this is a MoufReflectionParameter
	 * 
	 * @var paramAnnotation 
	 */
	private $paramAnnotation;
	
	private $type;
	private $keyType;
	private $subType;
	
	/**
	 * Constructs the MoufPropertyDescriptor from a MoufReflectionPropertyInterface or a MoufReflectionMethodInterface or a MoufReflectionParameterInterface (depending on the kind of property: field or setter or constructor parameter)
	 *
	 * @param MoufReflectionPropertyInterface|MoufReflectionMethodInterface|MoufReflectionParameterInterface $object
	 * @throws MoufException
	 */
	public function __construct($object) {
		$this->object = $object;
		
		if (!$object instanceof MoufReflectionPropertyInterface && !$object instanceof MoufReflectionMethodInterface && !$object instanceof MoufReflectionParameterInterface) {
			throw new MoufException("Error while creating MoufPropertyDescriptor. Invalid object passed in parameter.");
		}
		
		if ($object instanceof MoufReflectionMethodInterface) {
			// Let's perform basic checks to see if this can be a real getter.
			// First, does it start with "set"?
			$methodName = $object->getName();
			
			$this->name = self::getPropertyNameFromSetterName($methodName);
			$this->methodName = $methodName;
			
			// Now, let's check the method signature.
			$parameters = $object->getParameters();
			if (count($parameters) == 0) {
				throw new MoufException("Error while creating MoufPropertyDescriptor. The setter '".$this->methodName."' does not accept any parameters.");
			}
			if (count($parameters)>1) {
				for ($i=1, $count=count($parameters); $i<$count; $i++) {
					$param = $parameters[$i]; 
					if (!$param->isDefaultValueAvailable()) {
						throw new MoufException("Error while creating MoufPropertyDescriptor. The setter '".$this->methodName."' accepts more than one parameter. A setter should have only one parameter, or the parameters after the first one should have default values.");
					}
				}
			}
		} elseif ($object instanceof MoufReflectionParameterInterface) {
			$this->name = $object->getName();
			$this->index = $object->getPosition();
		} else {
			$this->name = $object->getName();
		}
		$this->analyzeType();
	}
	
	private function analyzeType() {
		if ($this->object instanceof MoufReflectionPropertyInterface) {
			$property = $this->object;
			if ($property->hasAnnotation("var")) {
				$varTypes = $property->getAnnotations("var");
				if (count($varTypes)>1) {
					throw new MoufException("Error for property ".$property->getName().". More than one @var annotation was found.");
				}
				$varTypeAnnot = $varTypes[0];
				/* @var $varTypeAnnot varAnnotation */
				$this->type = $varTypeAnnot->getType();
				$this->subType = $varTypeAnnot->getSubType();
				$this->keyType = $varTypeAnnot->getKeyType();
				
				$declaringClass = $property->getDeclaringClass();
				$useNamespaces = $declaringClass->getUseNamespaces();
				//var_dump($declaringClass->getName());
				// Let's resolve the class name...
				$className = (string)$declaringClass->getName();
					
				$pos = strrpos($className, "\\");
				
				$namespace = null;
				// There is no namespace, let's do nothing!
				if ($pos !== false) {
					// The namespace without the final \
					$namespace = substr($className, 0, $pos);
				}
				
				$this->type = self::resolveType($this->type, $useNamespaces, $namespace);
				$this->subType = self::resolveType($this->subType, $useNamespaces, $namespace);
			}
		} elseif ($this->object instanceof MoufReflectionParameterInterface) {
			$parameter = $this->object;
			/* @var $parameter MoufReflectionParameterInterface */
			$method = $parameter->getDeclaringFunction();
			if ($method->hasAnnotation("param")) {
				$paramTypes = $method->getAnnotations("param");
				$paramsAnnotations = array();
				$paramName = $parameter->getName();
				
				if (is_array($paramTypes)) {
					foreach ($paramTypes as $param) {
						if ($param->getParameterName() == '$'.$paramName) {
							$paramsAnnotations[] = $param;
						}
					}
				}
				if (count($paramsAnnotations)>1) {
					throw new MoufException("Error in docblock of method ".$method->getName()." of class ".$method->getDeclaringClass()->getName().". More than one @param annotation was found for variable ".$paramName.".");
				}

				
				if (count($paramsAnnotations)==1) {
					// If there is one @param annotation:
					$paramAnnotation = $paramsAnnotations[0];
					$this->paramAnnotation = $paramAnnotation;
					
					
					
					$declaringClass = $method->getDeclaringClass();
					$useNamespaces = $declaringClass->getUseNamespaces();
					//var_dump($declaringClass->getName());
					// Let's resolve the class name...
					$className = (string)$declaringClass->getName();
						
					$pos = strrpos($className, "\\");
					
					$namespace = null;
					// There is no namespace, let's do nothing!
					if ($pos !== false) {
						// The namespace without the final \
						$namespace = substr($className, 0, $pos);
					}
					
					
					$this->type = self::resolveType($paramAnnotation->getType(), $useNamespaces, $namespace);
					$this->subType = self::resolveType($paramAnnotation->getSubType(), $useNamespaces, $namespace);
					$this->keyType = $paramAnnotation->getKeyType();
					/*$this->type = $paramAnnotation->getType();
					$this->subType = $paramAnnotation->getSubType();
					$this->keyType = $paramAnnotation->getKeyType();*/
				} else {
					// There are @param annotation but not for the right variable... Let's use the type instead (if any).
					if ($parameter->isArray()) {
						$this->type = "array";
					} elseif ($parameter->getType() != null) {
						$this->type = "\\".$parameter->getType();
					}
				}
			}
			
		} else {
			// For setters:
			$method = $this->object;
			if ($method->hasAnnotation("param")) {
				$paramTypes = $method->getAnnotations("param");
				$paramsAnnotations = array();
				$parameters = $method->getParameters();
				$paramName = $parameters[0]->getName();
				
				if (is_array($paramTypes)) {
					foreach ($paramTypes as $param) {
						if ($param->getParameterName() == '$'.$paramName) {
							$paramsAnnotations[] = $param;
						}
					}
				}
				if (count($paramsAnnotations)>1) {
					throw new MoufException("Error for setter ".$method->getName().". More than one @param annotation was found for variable ".$paramsAnnotations[0]->getParameterName().".");
				}
				if (count($paramsAnnotations)==1) {
					// If there is one @param annotation:
					$paramAnnotation = $paramsAnnotations[0];
				
					$declaringClass = $method->getDeclaringClass();
					$useNamespaces = $declaringClass->getUseNamespaces();
					//var_dump($declaringClass->getName());
					// Let's resolve the class name...
					$className = (string)$declaringClass->getName();
					
					$pos = strrpos($className, "\\");
						
					$namespace = null;
					// There is no namespace, let's do nothing!
					if ($pos !== false) {
						// The namespace without the final \
						$namespace = substr($className, 0, $pos);
					}
						
					$this->type = self::resolveType($paramAnnotation->getType(), $useNamespaces, $namespace);
					$this->subType = self::resolveType($paramAnnotation->getSubType(), $useNamespaces, $namespace);
					$this->keyType = $paramAnnotation->getKeyType();
				} else {
					// There are @param annotation but not for the right variable... Let's use the type instead (if any).
					$parameters = $method->getParameters();
					if ($parameters[0]->isArray()) {
						$this->type = "array";
					} elseif ($parameters[0]->getType() != null) {
						$this->type = '\\'.$parameters[0]->getType();
					}
				}
			} else {
				$parameters = $method->getParameters();
				if ($parameters[0]->isArray()) {
					$this->type = "array";
				} elseif ($parameters[0]->getType() != null) {
					$this->type = '\\'.$parameters[0]->getType();
				}
			}
		}
		// Apply a namespace to type and subtype if necessary
		//$this->applyNamespace();
	}
	
	/**
	 * Transforms the setter name in a property name.
	 * For instance, getPhone => phone or getName => name
	 *
	 * @param string $methodName
	 * @return string
	 */
	public static function getPropertyNameFromSetterName($methodName) {
		if (strpos($methodName, "set") !== 0) {
			throw new MoufException("Error while creating MoufPropertyDescriptor. A @Property annotation must be set to methods that start with 'set'. For instance: setName, and setPhone are valid @Property setters. $methodName is not a valid setter name.");
		}
		$propName1 = substr($methodName, 3);
		if (empty($propName1)) {
			 throw new MoufException("Error while creating MoufPropertyDescriptor. A @Property annotation cannot be put on a method named 'set'. It must be put on a method whose name starts with 'set'. For instance: setName, and setPhone are valid @Property setters.");
		}
		$propName2 = strtolower(substr($propName1,0,1)).substr($propName1,1);
		return $propName2;
	}
	
	/**
	 * Transforms the property name in a setter name.
	 * For instance, phone => getPhone or name => getName
	 *
	 * @param string $methodName
	 * @return string
	 */
	public static function getSetterNameForPropertyName($propertyName) {
		$propName2 = strtoupper(substr($propertyName,0,1)).substr($propertyName,1);
		return "set".$propName2;
	}
	
	/**
	 * Returns the name of the property
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns the source of the property (either MoufPropertyDescriptor::$PUBLIC_FIELD or MoufPropertyDescriptor::$SETTER).
	 *
	 * @return string
	 */
	public function getSource() {
		if ($this->object instanceof MoufReflectionPropertyInterface) {
			return MoufPropertyDescriptor::$PUBLIC_FIELD;
		} elseif ($this->object instanceof MoufReflectionParameterInterface) {
			return MoufPropertyDescriptor::$CONSTRUCTOR;
		} else {
			return MoufPropertyDescriptor::$SETTER;
		}
	}
	
	public function getDocCommentWithoutAnnotations() {
		if ($this->object instanceof MoufReflectionParameterInterface) {
			if ($this->paramAnnotation == null) {
				return "";
			}
			return $this->paramAnnotation->getComments();
		} else {
			return $this->object->getDocCommentWithoutAnnotations();
		}
	}
	
	public function hasAnnotation($name) {
		return $this->object->hasAnnotation($name);
	}
	
	public function getAnnotations($name) {
		return $this->object->getAnnotations($name);
	}
	
	public function hasType() {
		return $this->type != null;
	}
	
	/**
	 * Returns the type for the property.
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * Returns the subType for the property.
	 *
	 * @return string
	 */
	public function getSubType() {
		return $this->subType;
	}
	
	/**
	 * Returns the keyType for the property (if this is an associative array)
	 *
	 * @return string
	 */
	public function getKeyType() {
		return $this->keyType;
	}
	
	/**
	 * Returns true if the type is an associative array (with a key/value pair)
	 *
	 * @return string
	 */
	public function isAssociativeArray() {
		return $this->keyType != null;
	}
	
	/**
	 * Returns true if the property comes from a public field 
	 *
	 * @return bool
	 */
	public function isPublicFieldProperty() {
		return $this->object instanceof MoufReflectionPropertyInterface;
	}

	/**
	 * Returns true if the property comes from a setter
	 *
	 * @return bool
	 */
	public function isSetterProperty() {
		return $this->object instanceof MoufReflectionMethodInterface;
	}
	
	/**
	 * Returns true if the property comes from the constructor
	 *
	 * @return bool
	 */
	public function isConstructor() {
		return $this->object instanceof MoufReflectionParameterInterface;
	}
	
	/**
	 * Returns the setter name (for setter properties only)
	 *
	 * @return string
	 */
	public function getMethodName() {
		return $this->methodName;
	}
	
	/**
	 * Returns true if the type of this property is an array.
	 * 
	 * @return string
	 */
	public function isArray() {
 		return !empty($this->subType);
	}
	
	/**
	 * Returns true if the type passed in parameter is primitive.
	 * It will return false if this is an array or an object.
	 * 
	 * Accepted primitive types: string, char, bool, boolean, int, integer, double, float, real, mixed
	 * No type (empty type) is considered primitive type.
	 * 
	 * @param string $type
	 * @return bool
	 */
	private static function isPrimitiveTypeStatic($type) {
 		$lowerVarType = strtolower($type);
		return in_array($lowerVarType, array('string', 'char', 'bool', 'boolean', 'int', 'integer', 'double', 'float', 'real', 'mixed'));
	}
	
	/**
	 * Returns true of the type of the property is a primitive type.
	 * 
	 * Accepted primitive types: string, char, bool, boolean, int, integer, double, float, real, mixed
	 * @return bool
	 */
	public function isPrimitiveType() {
		if ($this->getType() == null) {
			return true;
		}
		return self::isPrimitiveTypeStatic($this->getType());
	}
	
	/**
	 * Returns true of the type of the property is an array of primitive types.
	 *
	 * Accepted primitive types: string, char, bool, boolean, int, integer, double, float, real, mixed
	 * @return bool
	 */
	public function isArrayOfPrimitiveTypes() {
		return self::isPrimitiveTypeStatic($this->getSubType());
	}
	
	/**
	 * Checks if the parent class has a namespace.
	 * If so, we apply the namespace to the type and subtypes
	 * 
	 */
	/*private function applyNamespace() {
		if (!$this->isPrimitiveType()) {
			// Let's append the namespace if any and if the type is a class.
			$classObj = $this->object->getDeclaringClass();
			$className = (string)$classObj->getName();
			
			$pos = strrpos($className, "\\");
			
			// There is no namespace, let's do nothing!
			if ($pos === false) {
				return;
			}
				
			// The namespace without the final \
			$namespace = substr($className, 0, $pos);
			
			if (!$this->isArray()) {
				if (strpos($this->type, "\\") !== 0) {
					$this->type = $namespace."\\".$this->type;
				}
			}
	
			if ($this->isArray() && ! $this->isArrayOfPrimitiveTypes()) {
				$this->subType = $namespace."\\".$this->subType;
			}
		}
		
	}*/
	
	/**
	 * Give a fully qualified class name from the $type and declared "use" statements.
	 * Note: The returned fully qualified name always starts with a \
	 * 
	 * @param string $type
	 * @param array<string, string> $useMap
	 */
	private static function resolveType($type, $useMap, $namespace) {
		if ($type == null || $type == "array" || self::isPrimitiveTypeStatic($type)) {
			return $type;
		}
		
		$index = strpos($type, '\\');
		if ($index === false) {
			if (isset($useMap[$type])) {
				return '\\'.$useMap[$type];
			} else {
				if ($namespace) {
					return '\\'.$namespace.'\\'.$type;
				} else {
					return '\\'.$type;
				}
			}
		}
		if ($index === 0) {
			// Starting with \. Already a fully qualified name.
			return $type;
		}
		$leftPart = substr($type, 0, $index);
		$rightPart = substr($type, $index);
		
		if (isset($useMap[$leftPart])) {
			return '\\'.$useMap[$leftPart].$rightPart;
		} else {
			return '\\'.$namespace.'\\'.$type;
		}
	}
	
	/**
	 * Returns the index of the parameter if this property is a constructor.
	 * 
	 * @return int
	 */
	public function getParameterIndex() {
		return $this->index;
	}
}
?>