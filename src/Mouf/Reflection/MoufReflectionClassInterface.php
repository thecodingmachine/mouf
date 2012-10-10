<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Reflection;

/**
 * The interface implemented by objects representing a class (with or without a @Component annotation).
 * 
 * @author David Negrier
 */
interface MoufReflectionClassInterface {
	/**
	 * Returns the class name
	 *
	 * @return string
	 */
	public function getName();
	
	/**
	 * Returns the comment for the class.
	 *
	 * @return string
	 */
	public function getDocComment();
	
	/**
	* Returns the number of declared annotations of type $annotationName in the class comment.
	*
	* @param string $annotationName
	* @return int
	*/
	public function hasAnnotation($annotationName);
	
	/**
	 * Returns the annotation objects associated to $annotationName in an array.
	 * For instance, if there is one annotation "@Filter toto", there will be an array of one element.
	 * The element will contain an object of type FilterAnnotation. If the class FilterAnnotation is not defined,
	 * a string is returned instead of an object.
	 *
	 * @param string $annotationName
	 * @return array<$objects>
	 */
	public function getAnnotations($annotationName);
	
	/**
	 * Returns a map associating the annotation title to an array of objects representing the annotation.
	 *
	 * @var array("annotationClass"=>array($annotationObjects))
	 */
	public function getAllAnnotations();
	
	/**
	 * returns the specified method or null if it does not exist
	 *
	 * @param   string                $name  name of method to return
	 * @return  MoufXmlReflectionMethod
	 */
	public function getMethod($name);
	
	/**
	 * returns a list of all methods
	 *
	 * @return  array<MoufXmlReflectionMethod>
	 */
	public function getMethods();
	
	/**
	 * returns the specified property or null if it does not exist
	 *
	 * @param   string                  $name  name of property to return
	 * @return  MoufReflectionProperty
	 */
	public function getProperty($name);
	
	/**
	 * returns a list of all properties
	 *
	 * @return  array<MoufXmlReflectionProperty>
	 */
	public function getProperties();
	
	/**
	 * For the current class, returns a list of "use" statement used in the file for that class.
	 * The key is the "alias" of the path, and the value the path.
	 *
	 * So if you have:
	 * 	use Mouf\Mvc\Splash\Controller as SplashController
	 *
	 * the key will be "SplashController" and the value "Mouf\Mvc\Splash\Controller"
	 *
	 * Similarly, if you have only
	 * 	use Mouf\Mvc\Splash\Controller
	 *
	 * the key will be "Controllers" and the value "Mouf\Mvc\Splash\Controller"
	 *
	 * @return array<string, string>
	 */
	public function getUseNamespaces();
	
}