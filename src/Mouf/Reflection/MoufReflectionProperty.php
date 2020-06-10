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

use Mouf\MoufPropertyDescriptor;

/**
 * Extended Reflection class for class properties that allows usage of annotations.
 * 
 */
class MoufReflectionProperty extends \ReflectionProperty implements MoufReflectionPropertyInterface
{
    /**
     * Name of the class
     *
     * @var  string
     */
    protected $className;
    /**
     * The class the object was built from
     *
     * @var  MoufReflectionClass
     */
    protected $refClass;
    
    /**
     * The declaring class of the method
     *
     * @var  MoufReflectionClass
     */
    protected $declaringClass;
    /**
     * Name of the property
     *
     * @var  string
     */
    protected $propertyName;
    /**
	 * The phpDocComment we will use to access annotations.
	 *
	 * @var MoufPhpDocComment
	 */
	private $docComment;
    /**
     * constructor
     *
     * @param  string|MoufReflectionClass  $class         name of class to reflect
     * @param  string                          $propertyName  name of property to reflect
     */
    public function __construct($class, $propertyName)
    {
        if ($class instanceof MoufReflectionClass) {
            $this->refClass  = $class;
            $this->className = $class->getName();
        } else {
            $this->className = $class;
        }
        
        $this->propertyName = $propertyName;
        parent::__construct($this->className, $propertyName);
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
	 * Returns the MoufPhpDocComment instance
	 *
	 * @return MoufPhpDocComment
	 */
	public function getMoufPhpDocComment() {
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
     * checks whether a value is equal to the class
     *
     * @param   mixed  $compare
     * @return  bool
     */
    public function equals($compare)
    {
        if ($compare instanceof self) {
            return ($compare->className == $this->className && $compare->propertyName == $this->propertyName);
        }
        
        return false;
    }

    /**
     * getDeclaringClass fixing the problem with traits
     * (non-PHPdoc)
     * @see ReflectionProperty::getDeclaringClass()
     */
    function getDeclaringClass() {
    	if ($this->declaringClass) {
    		return $this->declaringClass;
    	}
    	
    	// Let's scan all traits
    	$trait = $this->deepScanTraitsForProperty($this->refClass->getTraits());
    	if ($trait != null) {
    		$this->declaringClass = $trait; 
    		return $trait;
    	}
    	
    	if ($this->refClass->getParentClass()) {
    		$declaringClass = null;
    		if ($this->refClass->getParentClass()->hasProperty($this->getName())) {
    			$declaringClass = $this->refClass->getParentClass()->getProperty($this->getName())->getDeclaringClass();
    		}
    		if ($declaringClass != null) {
    			return $declaringClass;
    		}
    	}
    	if ($this->refClass->hasProperty($this->getName())) {
    		return $this->refClass;
    	}
    	return null;
    	
    	// The property is not part of the traits, let's find in which parent it is part of.
    	/*$this->declaringClass = $this->getDeclaringClassWithoutTraits(); 
    	return $this->declaringClass;*/
    }
    
    /**
     * Recursive method called to detect a method into a nested array of traits.
     *
     * @param $traits ReflectionClass[]
     * @return ReflectionClass|null
     */
    private function deepScanTraitsForProperty(array $traits) {
    	foreach ($traits as $trait) {
    		// If the trait has a property, it's a win!
    		$result = $this->deepScanTraitsForProperty($trait->getTraits(), $this->getName());
    		if ($result != null) {
    			return $result;
    		} else {
    			if ($trait->hasProperty($this->getName())) {
    				return $trait;
    			}
    		}
    	}
    	return null;
    }
    
    /**
     * Returns the class that declares this parameter
     *
     * @return  MoufReflectionClass
     */
    public function getDeclaringClassWithoutTraits()
    {
        $refClass = parent::getDeclaringClass();
        if ($refClass->getName() === $this->className) {
            if (null === $this->refClass) {
                $this->refClass = new MoufReflectionClass($this->className);
            }
            
            return $this->refClass;
        }
        
        $moufRefClass = new MoufReflectionClass($refClass->getName());
        return $moufRefClass;
    }
    
    /**
     * Returns the default value
     *
     * @return mixed
     */
    public function getDefault() {
    	if ($this->isPublic() && !$this->isStatic() && $this->refClass->isAbstract() == false) {	    		 
	  		$className = $this->refClass->getName();
	  		// TODO: find a way to get default value for abstract properties.
	  		// TODO: optimize this: we should not have to create one new instance for every property....
	  		/*$instance = new $className();
			$property = $this->getName();
	    	return $instance->$property;*/
	  		
	  		// In some cases, the call to getDefaultProperties can log NOTICES
	  		// in particular if an undefined constant is used as default value.
	  		ob_start();
	  		$defaultProperties = $this->refClass->getDefaultProperties();
	  		$possibleError = ob_get_clean();
			if ($possibleError) {
				throw new \Exception($possibleError);	
			}
	  		return $defaultProperties[$this->getName()];
    	} else {
    		return null;
    	}
    }
    
    
   	/**
   	 * Appends this property to the XML node passed in parameter.
   	 *
   	 * @param SimpleXmlElement $root The root XML node the property will be appended to.
   	 */
    public function toXml(\SimpleXMLElement $root) {
    	$propertyNode = $root->addChild("property");
    	$propertyNode->addAttribute("name", $this->getName());
    	$commentNode = $propertyNode->addChild("comment");
    	
    	$node= dom_import_simplexml($commentNode);
   		$no = $node->ownerDocument;
   		$node->appendChild($no->createCDATASection($this->getDocComment())); 
    	
    	
		$propertyNode->addAttribute("modifier", $this->isPrivate() ? 'private' : ($this->isProtected() ? "protected" : "public"));
		$propertyNode->addAttribute("is_static", $this->isStatic() ? "true" : "false");
		
    	
    	if ($this->isPublic() && !$this->isStatic()) {
	    	// Default value
	    	$className = $this->refClass->getName();
	    	/*$instance = new $className();
			$property = $this->getName();;
	
			$propertyNode->addChild("default", serialize($instance->$property));*/
			$propertyNode->addChild("default", serialize($this->getDefault()));
    	}    	
    }
    

    /**
     * Returns a PHP array representing the property.
     *
     * @return array
     */
    public function toJson() {
    	$result = array();
    	$result['name'] = $this->getName();
    	$result['comment'] = $this->getMoufPhpDocComment()->getJsonArray();
    	
    	/*$properties = $this->getAnnotations("Property");
    		if (!empty($properties)) {
    	$result['moufProperty'] = true;*/
    	
    	try {
    		$result['default'] = $this->getDefault();
    		
	    	// TODO: is there a need to instanciate a  MoufPropertyDescriptor?
	    	$moufPropertyDescriptor = new MoufPropertyDescriptor($this);
	    	$types = $moufPropertyDescriptor->getTypes();
	    	$result['types'] = $types->toJson();
	    	 
	    	if ($types->getWarningMessage()) {
	    		$result['classinerror'] = $types->getWarningMessage();
	    	}
	    		    	
    	} catch (\Exception $e) {
    		$result['classinerror'] = $e->getMessage();
    	}
    	/*if ($moufPropertyDescriptor->isAssociativeArray()) {
    		$result['keytype'] = $moufPropertyDescriptor->getKeyType();
    	}
    	if ($moufPropertyDescriptor->isArray()) {
    		$result['subtype'] = $moufPropertyDescriptor->getSubType();
    	}*/
    	//}
    
    	return $result;
    }
    
}
?>
