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

use Mouf\Reflection\MoufXmlReflectionClass;

use Mouf\Composer\ComposerService;

use Mouf\Reflection\MoufReflectionClass;

/**
 * This class is used internally by Mouf and is specialized in analysing classes to find properties, etc... depending on the annotations. 
 *
 */
class Moufspector {
	
	/**
	 * Returns a list of all the classes that have the @Component attribute, or only classes extending the @Component attribute AND inheriting the passed
	 * class or interface.  Abstract classes are excluded from the list.
	 *
	 * @type string the class or interface the @component must inherit to be part of the list. If not passed, all @component classes are returned.
	 * @return array<string>
	 */
	public static function getComponentsList($type = null, $selfEdit = false) {
		$composerService = new ComposerService($selfEdit);
		
		$classesList = array_keys($composerService->getClassMap());
		//$classesList = get_declared_classes();
		$componentsList = array();
		
		foreach ($classesList as $className) {
			$refClass = new MoufReflectionClass($className);
			if (!$refClass->isAbstract() && $refClass->hasAnnotation("Component")) {
				if ($type == null) {
					$componentsList[] = $className;
				} else {
					try {
						if ($refClass->implementsInterface($type)) {
							$componentsList[] = $className;
							continue;
						}
					} catch (\ReflectionException $e) {
						// The interface might not exist, that's not a problem
					}
					try {
						if ($refClass->isSubclassOf($type)) {
							$componentsList[] = $className;
							continue;
						}
					} catch (\ReflectionException $e) {
						// The class might not exist, that's not a problem
					}
					if ($refClass->getName() == $type) {
						$componentsList[] = $className;
					}
				}
			}
		}
		return $componentsList;
	}

	/**
	 * Returns a list of all the classes that have the @Component attribute, or only classes extending the @Component attribute AND inheriting the passed
	 * class or interface. Abstract classes are excluded from the list.
	 * The list is passed as an associative arrray:
	 * array<className, array<string, string>>
	 * The inner array has those properties:
	 * 	"filename" => the path to the PHP file declaring the class.
	 *
	 * @type string the class or interface the @component must inherit to be part of the list. If not passed, all @component classes are returned.
	 * @return array<string, array<string, string>>
	 */
	public static function getEnhancedComponentsList($type = null, $selfEdit = false) {
		$composerService = new ComposerService($selfEdit);
		//$composerService->forceAutoLoad();
		
		$classesList = array_keys($composerService->getClassMap());
		//$classesList = get_declared_classes();
		$componentsList = array();
		
		foreach ($classesList as $className) {
			$refClass = new MoufReflectionClass($className);
			if (!$refClass->isAbstract() && $refClass->hasAnnotation("Component")) {
				$found = false;
				if ($type == null) {
					$found = true;
				} else {
					try {
						if ($refClass->implementsInterface($type)) {
							$found = true;
						}
					} catch (ReflectionException $e) {
						// The interface might not exist, that's not a problem
						try {
							if ($refClass->isSubclassOf($type)) {
								$found = true;
							}
						} catch (ReflectionException $e) {
							// The interface might not exist, that's not a problem
							if ($refClass->getName() == $type) {
								$found = true;
							}
						}
					}
				}
				if ($found) {
					$arr = array();
					$arr["filename"] = $refClass->getFileName();
					if ($refClass->hasAnnotation("Logo")) {
						$logos = $refClass->getAnnotations("Logo");
						if (count($logos)>1) {
							throw new MoufException("Error. In class ".$className.", only one @Logo annotation is allowed.");
						}
						$logo = $logos[0];
						// Since we did not import a LogoAnnotation class, the annotation is returned as a string.
						// We expect the @Logo parameters to be passed as a JSON object (this can be a simple string)
						$arr["logo"] = json_decode($logo);
					}
					$componentsList[$className] = $arr;
					continue;
				}
			}
		}
		return $componentsList;
	}
	
	
	/**
	 * Returns the list of properties the class $className does contain. 
	 *
	 * @param MoufXmlReflectionClass $class
	 * @return array<MoufPropertyDescriptor> An array containing MoufXmlReflectionProperty objects.
	 */
	public static function getPropertiesForClass(MoufXmlReflectionClass $refClass) {
		return $refClass->getMoufProperties();
	}
	
	/**
	 * Returns the property type.
	 * This can be one of: "string", "number", "oneof", "pointer".
	 *
	 * @param string $className
	 * @param string $property
	 */
	public static function getPropertyType($className, $property) {
		$refClass = new MoufReflectionClass($className);
		$refProperty = $refClass->getProperty($property);
		
		if ($refProperty->hasAnnotation('OneOf')) {
			return "oneof";
		}
		//if ($parameter->hasAnnotation('Var') != false) {
		
		return "pas mouf";
	}
	
	private static function analyzeClass($className) {
		$refClass = new ReflectionClass($className);
		
		$docComments = $refClass->getDocComment();
		
		$phpDocComment = new MoufPhpDocComment($docComments);
	}
	
	public static function testComment() {
		/*$refClass = new stubReflectionClass("PaypalConfig");
		$refProperty = $refClass->getProperty("paypalUrl");
		echo implode("\n", self::getDocLinesFromComment($refProperty->getDocComment()));*/
		//self::analyzeClass("PaypalConfig");
		$refClass = new MoufReflectionClass("PaypalConfig");
		$refProperty = $refClass->getProperty("paypalUrl");
		
		var_dump($refProperty->getAllAnnotations());
	}
	
	
}
?>