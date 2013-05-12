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

use Mouf\MoufCache;

use Mouf\Utils\Cache\CacheInterface;
use Mouf\MoufPropertyDescriptor;

/**
 * Reflection class extending ReflectionClass in order to access annotations.
 * 
 */
class MoufReflectionClass extends \ReflectionClass implements MoufReflectionClassInterface {
	
	/**
	 * Export neither fields nor methods on a toJson() call.
	 * @var string
	 */
	const EXPORT_TINY = "tiny";
	
	/**
	 * Export constructor, public fields and setters on a to toJson() call.
	 * @var string
	 */
	const EXPORT_PROPERTIES = "properties";
	
	/**
	 * Export all fields and methods on a to toJson() call.
	 * @var string
	 */
	const EXPORT_ALL = "all";
	
	/**
	 * The phpDocComment we will use to access annotations.
	 *
	 * @var MoufPhpDocComment
	 */
	private $docComment;
	
	/**
	 * The cache service used to store data hard to analyze.
	 * 
	 * @var CacheInterface
	 */
	private $cacheService;
	
	/**
	 * Default constructor
	 *
	 * @param string $className The name of the class to analyse.
	 */
	public function __construct($className) {
		parent::__construct($className);
		$this->cacheService = new MoufCache();
	}
	
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
     * @return  MoufReflectionMethod
     */
    public function getMethod($name)
    {
        if (parent::hasMethod($name) == false) {
            return null;
        }
        
        $moufRefMethod = new MoufReflectionMethod($this, $name);
        return $moufRefMethod;
    }
    
    public function getConstructor() {
    	$constructor = parent::getConstructor();
    	if ($constructor == null) {
    		return null;
    	}
    	$moufRefMethod = new MoufReflectionMethod($this, $constructor->getName());
    	return $moufRefMethod;
    }

    /**
     * returns a list of all methods
     *
     * @return  array<MoufReflectionMethod>
     */
    public function getMethods($filter = null)
    {
        $methods    = parent::getMethods();
        $moufMethods = array();
        foreach ($methods as $method) {
            $moufMethods[] = new MoufReflectionMethod($this, $method->getName());
        }
        
        return $moufMethods;
    }
    
    /**
    * returns a list of all methods matching a given regex
    * @param string $regex the regex to macth
    * @return  array<MoufReflectionMethod>
    */
    public function getMethodsByPattern($regex)
    {
    	$methods    = parent::getMethods();
    	$moufMethods = array();
    	foreach ($methods as $method) {
    		if (preg_match("/$regex/", $method->getName())) {
    			$moufMethods[$method->getName()] = new MoufReflectionMethod($this, $method->getName());
    		}
    	}
    
    	return $moufMethods;
    }

    /**
     * returns a list of all methods which satify the given matcher
     *
     * @param   MoufMethodMatcher            $methodMatcher
     * @return  array<MoufReflectionMethod>
     */
    /*public function getMethodsByMatcher(MoufMethodMatcher $methodMatcher)
    {
        $methods     = parent::getMethods();
        $moufMethods = array();
        foreach ($methods as $method) {
            if ($methodMatcher->matchesMethod($method) === true) {
                $moufMethod = new MoufReflectionMethod($this, $method->getName());
                if ($methodMatcher->matchesAnnotatableMethod($moufMethod) === true) {
                    $moufMethods[] = $moufMethod;
                }
            }
        }
        
        return $moufMethods;
    }*/

    /**
     * returns the specified property or null if it does not exist
     *
     * @param   string                  $name  name of property to return
     * @return  MoufReflectionProperty
     */
    public function getProperty($name)
    {
        if (parent::hasProperty($name) == false) {
            return null;
        }
        
        $moufRefProperty = new MoufReflectionProperty($this, $name);
        return $moufRefProperty;
    }

    /**
     * returns a list of all properties
     *
     * @return  MoufReflectionProperty[]
     */
    public function getProperties($filter = null)
    {
        $properties     = parent::getProperties();
        $moufProperties = array();
        foreach ($properties as $property) {
        	// Workaround for PHP BUG: https://bugs.php.net/bug.php?id=62560
        	if ($this->getName() == "Exception" && strpos($property->getName(), "trace") === 0) {
        		continue;
        	}
            $moufProperties[] = new MoufReflectionProperty($this, $property->getName());
        }
        
        return $moufProperties;
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
    public function getInterfaces()
    {
        $interfaces     = parent::getInterfaces();
        $moufRefClasses = array();
        foreach ($interfaces as $interface) {
            $moufRefClasses[] = new self($interface->getName());
        }
        
        return $moufRefClasses;
    }

    /**
     * returns a list of all interfaces
     *
     * @return  MoufReflectionClass
     */
    public function getParentClass()
    {
        $parentClass  = parent::getParentClass();
        if (null === $parentClass || false === $parentClass) {
            return null;
        }
        
        $moufRefClass = new self($parentClass->getName());
        return $moufRefClass;
    }

    /**
     * returns the extension to where this class belongs too
     *
     * @return  MoufReflectionExtension
     */
    public function getExtension()
    {
        $extensionName  = $this->getExtensionName();
        if (null === $extensionName || false === $extensionName) {
            return null;
        }
        
        $moufRefExtension = new MoufReflectionExtension($extensionName);
        return $moufRefExtension;
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
    	//require_once 'MoufReflectionHelper.php';
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
    /*public function getMoufProperty($name) {
    	$moufProperties = $this->getMoufProperties();
    	if (isset($moufProperties[$name])) {
    		return $moufProperties[$name];
    	} else {
    		return null;
    	}
    	 
    }*/
    
    /**
     * @var MoufPropertyDescriptor[]
     */
    private $injectablePropertiesByConstructor;
    
    /**
     * Returns the list of MoufPropertyDescriptor that can be injected via the constructor.
     * 
     * @return MoufPropertyDescriptor[] An associative array. The key is the name of the argument.
     */
    public function getInjectablePropertiesByConstructor() {
    	if ($this->injectablePropertiesByConstructor === null) {
	    	$moufProperties = array();
	    	$constructor = $this->getConstructor();
	    	if ($constructor != null) {
	    		foreach($constructor->getParameters() as $parameter) {
	    			$propertyDescriptor = new MoufPropertyDescriptor($parameter);
	    			$moufProperties[$propertyDescriptor->getName()] = $propertyDescriptor;
	    		}
	    	}
	    	$this->injectablePropertiesByConstructor = $moufProperties;
    	}
    	return $this->injectablePropertiesByConstructor;
    }
    
    /**
     * Returns a Mouf property descriptor for the public property whose argument name is $name.
     *
     * @param string $name
     * @return MoufPropertyDescriptor
     */
    public function getInjectablePropertyByConstructor($name) {
    	$properties = $this->getInjectablePropertiesByConstructor();
    	return $properties[$name];
    }
    
    
    /**
     * @var MoufPropertyDescriptor[]
     */
    private $injectablePropertiesByPublicProperty;
    
    /**
     * Returns the list of MoufPropertyDescriptor that can be injected via a public property of the class.
     * 
     * @return MoufPropertyDescriptor[] An associative array. The key is the name of the argument.
     */
    public function getInjectablePropertiesByPublicProperty() {
    	if ($this->injectablePropertiesByPublicProperty === null) {
	    	$moufProperties = array();
	    	foreach($this->getProperties() as $attribute) {
	    		/* @var $attribute MoufXmlReflectionProperty */
	    		//if ($attribute->hasAnnotation("Property")) {
	    		if ($attribute->isPublic() && !$attribute->isStatic()) {
	    			// We might want to catch it and to display it properly
	    			$propertyDescriptor = new MoufPropertyDescriptor($attribute);
	    			$moufProperties[$attribute->getName()] = $propertyDescriptor;
	    		}
	    		//}
	    	}
	    	$this->injectablePropertiesByPublicProperty = $moufProperties;
    	}
    	return $this->injectablePropertiesByPublicProperty;
    }
    
    /**
     * Returns a Mouf property descriptor for the public property whose name is $name.
     * 
     * @param string $name
     * @return MoufPropertyDescriptor
     */
    public function getInjectablePropertyByPublicProperty($name) {
    	$properties = $this->getInjectablePropertiesByPublicProperty();
    	return $properties[$name];
    }
    
    /**
     * @var MoufPropertyDescriptor[]
     */
    private $injectablePropertiesBySetter;
    
    /**
     * Returns the list of MoufPropertyDescriptor that can be injected via a setter of the class.
     *
     * @return MoufPropertyDescriptor[] An associative array. The key is the name of the method name.
     */
    public function getInjectablePropertiesBySetter() {
    	if ($this->injectablePropertiesBySetter === null) {
    		$this->injectablePropertiesBySetter = self::staticGetInjectablePropertiesBySetter($this);
    	}
    	return $this->injectablePropertiesBySetter;
    }

    /**
     * We need this static method because we cannot use traits for PHP 5.3 that would have been useful
     * to provide those methods to both MoufReflectionClass and MoufXMLReflectionClass.
     * 
     * @param MoufReflectionClassInterface $refClass
     * @return multitype:\Mouf\MoufPropertyDescriptor
     */
    public static function staticGetInjectablePropertiesBySetter(MoufReflectionClassInterface $refClass) {
    	$moufProperties = array();
    	foreach($refClass->getMethodsByPattern('^set..*') as $method) {
    		/* @var $attribute MoufXmlReflectionProperty */
    		//if ($method->hasAnnotation("Property")) {
    		 
    		$parameters = $method->getParameters();
    		if (count($parameters) == 0) {
    			continue;
    		}
    		if (count($parameters)>1) {
    			$ko = false;
    			for ($i=1, $count=count($parameters); $i<$count; $i++) {
    				$param = $parameters[$i];
    				if (!$param->isDefaultValueAvailable()) {
    					$ko = true;
    				}
    			}
    			if ($ko) {
    				continue;
    			}
    		}
    		 
    		$propertyDescriptor = new MoufPropertyDescriptor($method);
    		$moufProperties[$method->getName()] = $propertyDescriptor;
    		//}
    	}
    	return $moufProperties;
    }
    
    
    /**
     * Returns a Mouf property descriptor for the setter whose method name is $name.
     *
     * @param string $name
     * @return MoufPropertyDescriptor
     */
    public function getInjectablePropertyBySetter($name) {
    	$properties = $this->getInjectablePropertiesBySetter();
    	return $properties[$name];
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
    
    public function toXml() {
    	$root = simplexml_load_string("<class name=\"".$this->getName()."\"></class>");
    	$commentNode = $root->addChild("comment");
    	
    	$node= dom_import_simplexml($commentNode);
   		$no = $node->ownerDocument;
   		$node->appendChild($no->createCDATASection($this->getDocComment())); 
    	

    	foreach ($this->getProperties() as $property) {
    		$property->toXml($root);
    	}

       	foreach ($this->getMethods() as $method) {
    		$method->toXml($root);
    	}
    	
    	$uses = $this->getUseNamespaces();
    	foreach ($uses as $as=>$path) {
    		$use = $root->addChild("use");
    		$use->addAttribute("as", $as);
    		$use->addAttribute("path", $path);
    	}
    	
    	$xml = $root->asXml();
    	return $xml;
    }
    
    /**
     * Returns the last modification date of this file or one of the parent classes of this file.
     * This return actually the last modification date of this file and all parents classes.
     * 
     * This is useful to discard cache records if this file or one of its parents is updated.
     * 
     * TODO: take traits into account.
     */
    protected function getLastModificationDate() {
    	$parent = $this->getParentClass();
    	if ($parent != null) {
    		return max(filemtime($this->getFileName()), $parent->getLastModificationDate());
    	} else {
    		return filemtime($this->getFileName());
    	}
    }
    
    /**
     * Returns a PHP array representing the class.
     * 
     * @param string $exportMode Decide what to export. Defaults to ALL.
     * @return string
     */
    public function toJson($exportMode = self::EXPORT_ALL) {
    	if ($this->cacheService) {
    		// We store in cache the result array, along with the filename and the last date the file has been modified.
    		// Both must match for cache to be served.
    		$resultArray = $this->cacheService->get("mouf_class_json_".$this->getName()."/".$exportMode);
    		if ($resultArray) {
    			if ($resultArray['filename'] == $this->getFileName()) {
    				if ($resultArray['modificationdate'] == $this->getLastModificationDate()) {
    					return $resultArray['json'];
    				}
    			}	
    		}
    		$jsonArray = $this->performToJson($exportMode);
    		
    		$this->cacheService->set("mouf_class_json_".$this->getName()."/".$exportMode,
    				array(
    						'filename'=>$this->getFileName(),
    						'modificationdate'=>$this->getLastModificationDate(),
    						'json'=>$jsonArray
    					)
    				);
    		
    		return $jsonArray;
    	} else {
    		// TODO: move MoufReflectionHelper::classToJson inside this class.
    		return $this->performToJson($exportMode);
    	}
    }
    
    /**
     * Returns a PHP array representing the class.
     *
     * @return array
     */
    public function performToJson($exportMode) {
    	$result = array();
    	$result['name'] = $this->getName();
    
    	$result['exportmode'] = $exportMode;
    
    	// The filename is relative to the ROOT_PATH.
    	// It is "null" if the class is not part of the ROOT_PATH.
    	$fileName = $this->getFileName();
    	if (strpos($fileName, ROOT_PATH) === 0) {
    		$result['filename'] = substr($fileName, strlen(ROOT_PATH));
    	} else {
    		$result['filename'] = null;
    	}
    	$result['startline'] = $this->getStartLine();
    	$result['isinstantiable'] = $this->isInstantiable();
    
    	$result['comment'] = $this->getMoufDocComment()->getJsonArray();
    	$result['implements'] = array();

    	$interfaces = $this->getInterfaces();
    	foreach ($interfaces as $interface) {
    		/* @var $interface MoufReflectionClass */
    		$result['implements'][] = $interface->getName();
    	}
    
    	/*$extends = array();
    		$currentClass = $this;
    	while ($currentClass->getExtension()) {
    	$currentClass = $currentClass->getExtension();
    	$extends[] = $currentClass->getName();
    	}
    	$result['extends'] = $extends;*/
    	if ($this->getParentClass()) {
    		$result['extend'] = $this->getParentClass()->getName();
    	}
    
    	if ($exportMode != MoufReflectionClass::EXPORT_TINY) {
    
    		$result['properties'] = array();
    		foreach ($this->getProperties() as $property) {
    			if ($property->isPublic() && !$property->isStatic()) {
    				$result['properties'][] = $property->toJson();
    			}
    		}
    			
    		$result['methods'] = array();
    		foreach ($this->getMethods() as $method) {
    			$doExport = false;
    			if ($exportMode == MoufReflectionClass::EXPORT_PROPERTIES) {
    				$doExport = $method->isSetter();
    			} else {
    				$doExport = true;
    			}
    			if ($doExport) {
    				$result['methods'][] = $method->toJson();
    			}
    		}
    
    	}
    		
    	return $result;
    }
    

    private $useNamespaces;
    
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
     * the key will be "Controller" and the value "Mouf\Mvc\Splash\Controller"
     * 
     * @return array<string, string>
     */
    public function getUseNamespaces() {
    	if ($this->useNamespaces === null) {
    		$this->useNamespaces = array();
    		
    		$contents = file_get_contents($this->getFileName());
    		
    		// Optim to avoid doing the token_get_all think that is costly.
    		if (strpos($contents, 'use ') === false) {
    			return array();
    		}
    		
   			$tokens   = token_get_all($contents);
    		
    		$classes = array();
    		
    		//$namespace = '';
    		for ($i = 0, $max = count($tokens); $i < $max; $i++) {
    			$token = $tokens[$i];
    		
    			if (is_string($token)) {
    				continue;
    			}
    		
    			$class = '';
    		
    			$path = '';
    			
    			if ($token[0] == T_USE) {
	    			while (($t = $tokens[++$i]) && is_array($t)) {
	    				//if (in_array($t[0], array(T_STRING, T_NS_SEPARATOR))) {
	    				$type = $t[0];
	    				if ($type == T_STRING || $type == T_NS_SEPARATOR) {
	    					$path .= $t[1];
	    					if ($type == T_STRING) {
	    						$as = $t[1];
	    					} 
	    				}
	    			}
	    				
	    			if (empty($path)) {
	    				// Path can be empty if the USE statement is not at the beginning of the file but part of a closure
	    				//		(function() use ($var))
	    				continue;
	    			}
	    				
    				$nextToken = $tokens[$i+1];
    				if ($nextToken[0] === T_AS) {
    					$as = $tokens[$i+2][1];
    				}
    				$path = ltrim($path, '\\');
	    				
	    			$this->useNamespaces[$as] = $path;
    			}
    		}
    		
    	}
    	return $this->useNamespaces;
    }
}
?>