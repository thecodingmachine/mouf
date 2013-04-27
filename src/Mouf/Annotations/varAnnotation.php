<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Annotations;


use Mouf\Reflection\TypesDescriptor;

use Mouf\Reflection\TypeDescriptor;

/**
 * The @var annotation.
 * This annotation contains the type as the first word.
 * 
 * The type can be any class or interface, or a primitive type (string, boolean, ...)
 * It can also be an array. In this case, you can specify the array type using: array<Type>, or even array<Type, Type>
 */
class varAnnotation 
{
	private $types;
	
    public function __construct($value)
    {
    	$this->analyzeType($value);
    }
    
    /**
     * Analyzes the type and fills the type (and keytype and subtype if necessary).
     *
     * @param string $value
     */
    protected function analyzeType($value) {
    	$this->types = TypesDescriptor::parseTypeString($value);
    }
    

    /**
     * Returns the main type.
     *
     * @return TypesDescriptor
     */
    public function getTypes() {
    	return $this->types;
    }
    
    /**
     * Returns the main type. 
     *
     * @return string
     */
    /*public function getType() {
    	return $this->type;
    }*/
    
    /**
     * Returns the type of the array (if the main type is an array)
     *
     * @return string
     */
    /*public function getSubType() {
    	return $this->subtype;
    }*/
    
    /**
     * Returns the type of the key of the array (if the main type is an array)
     *
     * @return string
     */
    /*public function getKeyType() {
    	return $this->keytype;
    }*/
    
    /**
     * Returns true if the type is an array and it has a key defined.
     *
     * @return boolean
     */
    /*public function isAssociativeArray() {
    	return $this->keytype !== null;
    }*/
    
}
