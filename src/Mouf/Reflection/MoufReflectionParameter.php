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
 * Extended Reflection class for parameters.
 * 
 */
class MoufReflectionParameter extends \ReflectionParameter implements MoufReflectionParameterInterface
{
    /**
     * name of reflected routine
     *
     * @var  string
     */
    protected $routineName;
    /**
     * reflection instance of routine containing this parameter
     *
     * @var  stubReflectionRoutine
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
    public function __construct($routine, $paramName)
    {
        if ($routine instanceof MoufReflectionMethod) {
            $this->refRoutine  = $routine;
            $this->routineName = array($routine->getDeclaringClass()->getName(), $routine->getName());
        } /*elseif ($routine instanceof MoufReflectionFunction) {
            $this->refRoutine  = $routine;
            $this->routineName = $routine->getName();
        }*/ else {
            $this->routineName = $routine;
        }
        
        $this->paramName = $paramName;
        parent::__construct($this->routineName, $paramName);
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
    public function equals($compare)
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
    }

    /**
     * returns the class that declares this parameter
     *
     * @return  stubReflectionClass
     */
    public function getDeclaringClass()
    {
        if (is_array($this->routineName) === false) {
            return null;
        }
        
        $refClass     = parent::getDeclaringClass();
        $moufRefClass = new MoufReflectionClass($refClass->getName());
        return $moufRefClass;
    }

    /**
     * returns the type (class) hint for this parameter
     *
     * @return  stubReflectionClass
     */
    public function getClass()
    {
        $refClass = parent::getClass();
        if (null === $refClass) {
            return null;
        }
        
        $moufRefClass = new MoufReflectionClass($refClass->getName());
        return $moufRefClass;
    }
    
    /**
    * Returns the class of the parameter (if any)
    *
    * @return string
    */
    public function getType() {
    	if ($this->getClass() != null) {
    		return $this->getClass()->getName();
    	} else {
    		return null;
    	}
    }
    
   	/**
   	 * Appends this property to the XML node passed in parameter.
   	 *
   	 * @param SimpleXmlElement $root The root XML node the property will be appended to.
   	 */
    public function toXml(SimpleXmlElement $root) {
    	$propertyNode = $root->addChild("parameter");
    	$propertyNode->addAttribute("name", $this->getName());
    	$propertyNode->addAttribute("hasDefault", $this->isDefaultValueAvailable()?"true":"false");
    	if ($this->isDefaultValueAvailable()) {
			$propertyNode->addAttribute("default", serialize($this->getDefaultValue())); 
    	}
    	$propertyNode->addAttribute("isArray", $this->isArray()?"true":"false");
    	if ($this->getClass() != null) {
    		$propertyNode->addAttribute("class", $this->getClass()->getName());
    	}
    }
}
?>