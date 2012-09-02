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


/**
 * The @var annotation.
 * This annotation contains the type as the first word.
 * 
 * The type can be any class or interface, or a primitive type (string, boolean, ...)
 * It can also be an array. In this case, you can specify the array type using: array<Type>, or even array<Type, Type>
 */
class varAnnotation 
{
	private $type;
	private $subtype;
	private $keytype;
	
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
    	preg_match("/^([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)/", $value, $values);
    	
        $this->type = $values[1];    
        if ($this->type == "array") {
        	preg_match("/^([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)[<](.*)[>]/", $value, $stvalues);
        	if (isset($stvalues[2])) {
	        	$tmpType = trim($stvalues[2]);
	        	if (strpos($tmpType,",") === false) {
	        		$this->subtype = $tmpType;
	        	} else {
	        		$types = explode(",", $tmpType);
	        		$this->keytype = trim($types[0]);
	        		$this->subtype = trim($types[1]); 
	        	}
        	}
        }
    }
    
    /**
     * Returns the main type. 
     *
     * @return string
     */
    public function getType() {
    	return $this->type;
    }
    
    /**
     * Returns the type of the array (if the main type is an array)
     *
     * @return string
     */
    public function getSubType() {
    	return $this->subtype;
    }
    
    /**
     * Returns the type of the key of the array (if the main type is an array)
     *
     * @return string
     */
    public function getKeyType() {
    	return $this->keytype;
    }
    
    /**
     * Returns true if the type is an array and it has a key defined.
     *
     * @return boolean
     */
    public function isAssociativeArray() {
    	return $this->keytype !== null;
    }
    
}

?>
