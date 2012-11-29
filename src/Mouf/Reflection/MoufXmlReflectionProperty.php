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
 * This class behaves like MoufReflectionProperty, except it is completely based on a Xml message.
 * It does not try to access the real class.
 * Therefore, you can use this class to perform reflection in a class that is not loaded, which can
 * be useful.
 *  
 */
class MoufXmlReflectionProperty implements MoufReflectionPropertyInterface
{
	/**
	 * The XML message we will analyse
	 *
	 * @var SimpleXmlElement
	 */
	private $xmlElem;
	
	
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
	 * Whether the property is public, private or protected
	 * @var string
	 */
	private $modifier;
	
	/**
	 * Tells if the property is static
	 * @var unknown
	 */
	private $isStatic;
    /**
     * constructor
     *
     * @param  string|MoufReflectionClass  $class         name of class to reflect
     * @param  SimpleXmlElement                          $xmlElem  The Xml Element representing the property
     */
    public function __construct($class, $xmlElem)
    {
        if ($class instanceof MoufXmlReflectionClass) {
            $this->refClass  = $class;
            $this->className = $class->getName();
        } else {
            $this->className = $class;
        }
        
        $this->modifier = (string)($xmlElem['modifier']);
        $this->isStatic = ((string)($xmlElem['is_static'])) == "true";
        
        $this->xmlElem = $xmlElem;
        $this->propertyName = (string)($xmlElem['name']);
        
        //$this->propertyName = $propertyName;
        //parent::__construct($this->className, $propertyName);
    }
    
   	/**
	 * Returns the property name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->propertyName;
	}

   	/**
	 * Returns the default value
	 *
	 * @return mixed
	 */
	public function getDefault() {
		return unserialize((string)($this->xmlElem->default));
	}
	
    
   	/**
	 * Returns the full comment for the method
	 *
	 * @return string
	 */
	public function getDocComment() {
		return (string)($this->xmlElem->comment);
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
     * @return MoufReflectionClassInterface
     */
    public function getDeclaringClass()
    {
        return $this->refClass;
    }
    
    /**
     * Tells if the property is a public one
     */
    public function isPublic(){
    	return $this->modifier == 'public';
    }
    
    /**
     * Tells if the property is a private one
     */
    public function isPrivate(){
    	return $this->modifier == 'private';
    }
    
    /**
     * Tells if the protected is a public one
     */
    public function isProtected(){
    	return $this->modifier == 'protected';
    }
    
    /**
     * Tells if the property is static
     */
    public function isStatic(){
    	return $this->isStatic;
    }
    
}
?>
