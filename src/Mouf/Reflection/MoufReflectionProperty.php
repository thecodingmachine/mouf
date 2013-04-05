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
     * reflection instance for class declaring this property
     *
     * @var  MoufReflectionClass
     */
    protected $refClass;
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
     * Returns the class that declares this parameter
     *
     * @return  MoufReflectionClass
     */
    public function getDeclaringClass()
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
	  		$defaultProperties = $this->refClass->getDefaultProperties();
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
    	
    	$node= dom_import_simplexml($propertyNode);
   		$no = $node->ownerDocument;
   		$node->appendChild($no->createCDATASection($this->getDocComment())); 
    	
    	
		$propertyNode->addAttribute("modifier", $this->isPrivate() ? 'private' : $this->isProtected() ? "protected" : "public");
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
    
}
?>
