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
 * The interface implemented by objects representing a method.
 * 
 * @author David Negrier
 */
interface MoufReflectionMethodInterface {
    public function getName();
    
    public function isPublic();
    
    public function isPrivate();
    
    public function isProtected();

    public function isStatic();
    
    public function isFinal();
    
    public function isConstructor();
    
    public function isAbstract();
		
	/**
	 * Returns the full comment for the method
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
	 * returns the class that declares this method
	 *
	 * @return  MoufReflectionClass
	 */
	public function getDeclaringClass();
}