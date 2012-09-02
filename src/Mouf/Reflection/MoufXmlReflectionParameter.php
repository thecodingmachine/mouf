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
 * This class behaves like MoufReflectionParameter, except it is completely based on a Xml message.
 * It does not try to access the real class.
 * Therefore, you can use this class to perform reflection in a class that is not loaded, which can
 * be useful.
 * 
 */
class MoufXmlReflectionParameter extends \ReflectionParameter implements MoufReflectionParameterInterface
{
	/**
	 * The XML message we will analyse
	 *
	 * @var SimpleXmlElement
	 */
	private $xmlElem;
	
    /**
     * name of reflected routine
     *
     * @var  string
     */
    protected $routineName;
    /**
     * reflection instance of routine containing this parameter
     *
     * @var  MoufXmlReflectionRoutine
     */
    protected $refRoutine;
    /**
     * name of reflected parameter
     *
     * @var  string
     */
    protected $paramName;

    /**
     * constructor
     *
     * @param  string|array|MoufReflectionMethod  $routine    name or reflection instance of routine
     * @param  string                              $paramName  name of parameter to reflect
     */
    public function __construct($routine, $xmlElem)
    {
    	if ($routine instanceof MoufXmlReflectionMethod) {
            $this->refRoutine  = $routine;
            //$this->routineName = array($routine->getDeclaringClass()->getName(), $routine->getName());
    	} else {
    		$this->routineName = $routine;
    	}
        /*if ($routine instanceof MoufXmlReflectionMethod) {
            $this->refRoutine  = $routine;
            //$this->routineName = array($routine->getDeclaringClass()->getName(), $routine->getName());
        } *//*elseif ($routine instanceof MoufReflectionFunction) {
            $this->refRoutine  = $routine;
            $this->routineName = $routine->getName();
        }*/ /*else {
            $this->routineName = $routine;
        }*/
        
        $this->xmlElem = $xmlElem;
        $this->paramName = (string)($xmlElem['name']);
    }
    
    /**
     * helper method to return the reflection routine defining this parameter
     *
     * @return  stubReflectionRoutine
     * @todo    replace by getDeclaringFunction() as soon as Stubbles requires at least PHP 5.2.3
     */
    protected function getRefRoutine()
    {
        if (null === $this->refRoutine) {
            if (is_array($this->routineName) === true) {
                $this->refRoutine = new MoufReflectionMethod($this->routineName[0], $this->routineName[1]);
            } /*else {
                $this->refRoutine = new stubReflectionFunction($this->routineName);
            }*/
        }
        
        return $this->refRoutine;
    }

    /**
     * checks whether a value is equal to the class
     *
     * @param   mixed  $compare
     * @return  bool
     */
    /*public function equals($compare)
    {
        if (($compare instanceof self) == false) {
            return false;
        }
        
        $class        = $this->getDeclaringClass();
        $compareClass = $compare->getDeclaringClass();
        if ((null == $class && null != $compareClass) || null != $class && null == $compareClass) {
            return false;
        }
        
        if (null == $class) {
            return ($compare->routineName == $this->routineName && $compare->paramName == $this->paramName);
        }
        
        return ($compareClass->getName() == $class->getName() && $compare->routineName == $this->routineName && $compare->paramName == $this->paramName);
    }*/

    /**
     * returns the class that declares this parameter
     *
     * @return  stubReflectionClass
     */
    /*public function getDeclaringClass()
    {
        if (is_array($this->routineName) === false) {
            return null;
        }
        
        $refClass     = parent::getDeclaringClass();
        $moufRefClass = new MoufXmlReflectionClass($refClass->getName());
        return $moufRefClass;
    }*/

    /**
     * returns the type (class) hint for this parameter
     *
     * @return  stubReflectionClass
     */
    /*public function getClass()
    {
        $refClass = parent::getClass();
        if (null === $refClass) {
            return null;
        }
        
        $moufRefClass = new MoufReflectionClass($refClass->getName());
        return $moufRefClass;
    }*/
    
    public function getName() {
    	return $this->paramName;
    }
    
	public function isDefaultValueAvailable() {
    	return (string)($this->xmlElem['hasDefault']) == "true";
    }
    
	/**
	 * Returns the default value
	 *
	 * @return mixed
	 */
	public function getDefault() {
		return unserialize((string)($this->xmlElem['default']));
	}
	
	public function isArray() {
    	return (string)($this->xmlElem['isArray']) == "true";
    }
    
    /**
     * Returns the class of the parameter (if any)
     * 
     * @return string
     */
	public function getType() {
    	return (string)$this->xmlElem['class'];
    }
}
?>