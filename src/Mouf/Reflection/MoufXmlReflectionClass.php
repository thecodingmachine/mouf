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
 * This class behaves like MoufReflectionClass, except it is completely based on a Xml message.
 * It does not try to access the real class.
 * Therefore, you can use this class to perform reflection in a class that is not loaded, which can
 * be useful.
 * 
 */
class MoufXmlReflectionClass implements MoufReflectionClassInterface {
	
	/**
	 * The XML message we will analyse
	 *
	 * @var \SimpleXmlElement
	 */
	private $xmlRoot;
	
	/**
	 * The phpDocComment we will use to access annotations.
	 *
	 * @var MoufPhpDocComment
	 */
	private $docComment;
	
	/**
	 * Default constructor
	 *
	 * @param string $className The name of the class to analyse.
	 */
	public function __construct($xmlStr) {
		$this->xmlRoot = simplexml_load_string($xmlStr);
		
		if ($this->xmlRoot == null) {
			throw new \Exception("An error occured while retrieving message: ".$xmlStr);
		}
	}
	
	/**
	 * Returns the class name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->xmlRoot['name'];
	}
	
	/**
	 * Returns the comment for the class.
	 *
	 * @return string
	 */
	public function getDocComment() {
		return (string)($this->xmlRoot->comment);
	}
	
	/*public function isPublic() {
		return $this->xmlRoot['modifier']=="public";
	}

	public function isProtected() {
		return $this->xmlRoot['modifier']=="protected";
	}
	
	public function isPrivate() {
		return $this->xmlRoot['modifier']=="private";
	}
	
	public function isStatic() {
		return $this->xmlRoot['static']=="true";
	}
	
	public function isAbstract() {
		return $this->xmlRoot['abstract']=="true";
	}
	
	public function isFinal() {
		return $this->xmlRoot['final']=="true";
	}
	
	public function isConstructor() {
		return $this->xmlRoot['constructor']=="true";
	}*/
	
	/**
	 * Analyzes and parses the comment (if it was not previously done).
	 *
	 */
	private function analyzeComment() {
		if ($this->docComment == null) {
			$this->docComment = new MoufPhpDocComment($this->getDocComment());
		}
	}
	
	/**
	 * Returns the comment text, without the annotations.
	 *
	 * @return string
	 */
	public function getDocCommentWithoutAnnotations() {
		$this->analyzeComment();
		
		return $this->docComment->getComment();
	}

	/**
	 * Returns the full MoufPhpDocComment
	 *
	 * @return MoufPhpDocComment
	 */
	public function getMoufDocComment() {
		$this->analyzeComment();
		return $this->docComment;
	}
	
	
	/**
	 * Returns the number of declared annotations of type $annotationName in the class comment.
	 *
	 * @param string $annotationName
	 * @return int
	 */
	public function hasAnnotation($annotationName) {
		$this->analyzeComment();
		
		return $this->docComment->getAnnotationsCount($annotationName);
	}
	
	/**
	 * Returns the annotation objects associated to $annotationName in an array.
	 * For instance, if there is one annotation "@Filter toto", there will be an array of one element.
	 * The element will contain an object of type FilterAnnotation. If the class FilterAnnotation is not defined,
	 * a string is returned instead of an object.  
	 *
	 * @param string $annotationName
	 * @return array<$objects>
	 */
	public function getAnnotations($annotationName) {
		$this->analyzeComment();
		
		return $this->docComment->getAnnotations($annotationName);
	}
	
	/**
	 * Returns a map associating the annotation title to an array of objects representing the annotation.
	 * 
	 * @var array("annotationClass"=>array($annotationObjects))
	 */
	public function getAllAnnotations() {
		$this->analyzeComment();
		
		return $this->docComment->getAllAnnotations();
	}
	
	/**
     * returns the specified method or null if it does not exist
     *
     * @param   string                $name  name of method to return
     * @return  MoufXmlReflectionMethod
     */
    public function getMethod($name)
    {
        foreach ($this->xmlRoot->method as $method) {
    		if ($method['name'] == $name) {
		        $moufRefMethod = new MoufXmlReflectionMethod($this, $method);
		        return $moufRefMethod;
    		}
    	}
    	return null;
    }
    
	/**
     * returns methods mathcing the given pattern
     *
     * @param   string $regex the regular expression to match (without trailing slashes)
     * @return  array<MoufXmlReflectionMethod>
     */
    public function getMethodsByPattern($regex)
    {
    	$methods = array();
        foreach ($this->xmlRoot->method as $method) {
    		if (preg_match("/$regex/", $method['name'])) {
		        $moufRefMethod = new MoufXmlReflectionMethod($this, $method);
		        $methods[] = $moufRefMethod;
    		}
    	}
    	return $methods;
    }

    /**
     * returns a list of all methods
     *
     * @return  array<MoufXmlReflectionMethod>
     */
    public function getMethods()
    {
        $moufMethods = array();
        foreach ($this->xmlRoot->method as $method) {
            $moufMethods[] = new MoufXmlReflectionMethod($this, $method);
        }
        
        return $moufMethods;
    }
    
    public function getConstructor() {
    	foreach ($this->xmlRoot->method as $method) {
    		if (((string)$method['constructor']) == "true") {
    			return new MoufXmlReflectionMethod($this, $method);
    		}
    	}
    	return null;
    }

    /**
     * returns the specified property or null if it does not exist
     *
     * @param   string                  $name  name of property to return
     * @return  MoufReflectionProperty
     */
    public function getProperty($name)
    {
    	foreach ($this->xmlRoot->property as $property) {
    		if ($property['name'] == $name) {
		        $moufRefProperty = new MoufXmlReflectionProperty($this, $property);
		        return $moufRefProperty;
    		}
    	}
    	return null;
        /*if (parent::hasProperty($name) == false) {
            return null;
        }
        
        $moufRefProperty = new MoufReflectionProperty($this, $name);
        return $moufRefProperty;*/
    }

    /**
     * returns a list of all properties
     *
     * @return  array<MoufXmlReflectionProperty>
     */
    public function getProperties()
    {
        //$properties     = parent::getProperties();
        
        $moufProperties = array();
        foreach ($this->xmlRoot->property as $property) {
            $moufProperties[] = new MoufXmlReflectionProperty($this, $property);
        }
        
        return $moufProperties;
    }

    /**
     * The list of Mouf properties this class contains.
     * This is initialized by a call to getMoufProperties()
     * 
     * @var array<MoufPropertyDescriptor> An array containing MoufXmlReflectionProperty objects.
     */
    private $moufProperties = null;
    
    /**
     * Returns a list of properties that have the @Property annotation (and a list of setter that have the @Property annotation) 
     * 
     * @return array<string, MoufPropertyDescriptor> An array containing MoufXmlReflectionProperty objects.
     */
    /*public function getMoufProperties() {
    	require_once dirname(__FILE__)."/MoufReflectionHelper.php";
    	 
    	if ($this->moufProperties === null) {
    		$this->moufProperties = MoufReflectionHelper::getMoufProperties($this);
    	}
    	
    	return $this->moufProperties;
    }*/
    
    /**
     * Returns the Mouf property whose name is $name
     * The property name is the "name" of the public property, or the "setter function name" of the setter-based property.
     * 
     * @param string $name
     * @return MoufPropertyDescriptor
     */
    public function getMoufProperty($name) {
    	$moufProperties = $this->getMoufProperties();
    	if (isset($moufProperties[$name])) {
    		return $moufProperties[$name];
    	} else {
    		return null;
    	}
    	
    }

    
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
    public function getUseNamespaces() {
    	$uses = array();
    	foreach ($this->xmlRoot->use as $use) {
    		/* @var $use \SimpleXmlElement */
    		$uses[(string) $use['as']] = (string) $use['path'];
    	}
    	return $uses;
    }
    
    
    /**
     * returns a list of all properties which satify the given matcher
     *
     * @param   MoufPropertyMatcher            $propertyMatcher
     * @return  array<MoufReflectionProperty>
     */
    /*public function getPropertiesByMatcher(MoufPropertyMatcher $propertyMatcher)
    {
        $properties     = parent::getProperties();
        $moufProperties = array();
        foreach ($properties as $property) {
            if ($propertyMatcher->matchesProperty($property) === true) {
                $moufProperty = new MoufReflectionProperty($this, $property->getName());
                if ($propertyMatcher->matchesAnnotatableProperty($moufProperty) === true) {
                    $moufProperties[] = $moufProperty;
                }
            }
        }
        
        return $moufProperties;
    }*/

    /**
     * returns a list of all interfaces
     *
     * @return  array<MoufReflectionClass>
     */
    /*public function getInterfaces()
    {
        $interfaces     = parent::getInterfaces();
        $moufRefClasses = array();
        foreach ($interfaces as $interface) {
            $moufRefClasses[] = new self($interface->getName());
        }
        
        return $moufRefClasses;
    }*/

    /**
     * returns a list of all interfaces
     *
     * @return  MoufReflectionClass
     */
    /*public function getParentClass()
    {
        $parentClass  = parent::getParentClass();
        if (null === $parentClass || false === $parentClass) {
            return null;
        }
        
        $moufRefClass = new self($parentClass->getName());
        return $moufRefClass;
    }*/

    /**
     * returns the extension to where this class belongs too
     *
     * @return  MoufReflectionExtension
     */
    /*public function getExtension()
    {
        $extensionName  = $this->getExtensionName();
        if (null === $extensionName || false === $extensionName) {
            return null;
        }
        
        $moufRefExtension = new MoufReflectionExtension($extensionName);
        return $moufRefExtension;
    }*/
    
}
?>