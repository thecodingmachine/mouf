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
 * This class behaves like MoufReflectionMethod, except it is completely based on a Xml message.
 * It does not try to access the real class.
 * Therefore, you can use this class to perform reflection in a class that is not loaded, which can
 * be useful.
 *
 */
class MoufXmlReflectionMethod implements MoufReflectionMethodInterface
{
	/**
	 * The XML message we will analyse
	 *
	 * @var SimpleXmlElement
	 */
	private $xmlElem;


	/**
     * name of the reflected class
     *
     * @var  string
     */
    protected $className;
    /**
     * declaring class
     *
     * @var  MoufReflectionClass
     */
    protected $refClass;
    /**
     * name of the reflected method
     *
     * @var  string
     */
    protected $methodName;

    /**
     * Modifier
     *
     * @var string
     */
    protected $modifier;

    /**
	 * The phpDocComment we will use to access annotations.
	 *
	 * @var MoufPhpDocComment
	 */
	private $docComment;

    /**
     * constructor
     *
     * @param  string|MoufReflectionClass  $class       name of class to reflect
     * @param  SimpleXmlElement                          $methodName  name of method to reflect
     */
    public function __construct($class, $xmlElem)
    {
        if ($class instanceof MoufXmlReflectionClass) {
            $this->refClass   = $class;
            $this->className  = $this->refClass->getName();
        } else {
            $this->className  = $class;
        }

        $this->xmlElem = $xmlElem;
        $this->methodName = (string)$xmlElem['name'];
        $this->modifier = (string)$xmlElem['modifier'];
        //$this->methodName = $methodName;
        //parent::__construct($this->className, $methodName);
    }

    public function getName() {
    	return $this->methodName;
    }

    public function isPublic() {
    	return $this->modifier == "public";
    }

    public function isPrivate() {
    	return $this->modifier == "private";
    }

    public function isProtected() {
    	return $this->modifier == "protected";
    }

    public function isStatic() {
    	return ((string)$this->xmlElem['static']) == "true";
    }

    public function isFinal() {
    	return ((string)$this->xmlElem['final']) == "true";
    }

    public function isConstructor() {
    	return ((string)$this->xmlElem['constructor']) == "true";
    }

    public function isAbstract() {
    	return ((string)$this->xmlElem['abstract']) == "true";
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
	 * Returns the full comment for the method
	 *
	 * @return string
	 */
	public function getDocComment() {
		return (string)($this->xmlElem->comment);
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
            return ($compare->className === $this->className && $compare->methodName === $this->methodName);
        }

        return false;
    }


    /**
     * returns the class that declares this method
     *
     * @return  MoufReflectionClass
     */
    public function getDeclaringClass()
    {
        return $this->refClass;
    }


	/**
     * returns the specified parameter or null if it does not exist
     *
     * @param   string                $name  name of parameter to return
     * @return  MoufXmlParameterMethod
     */
    public function getParameter($name)
    {
        foreach ($this->xmlRoot->parameter as $parameter) {
    		if ($parameter['name'] == $name) {
		        $moufRefParameter = new MoufXmlReflectionParameter($this, $parameter);
		        return $moufRefParameter;
    		}
    	}
    	return null;
    }

    /**
     * returns a list of all parameters
     *
     * @return  array<MoufXmlReflectionParameter>
     */
    public function getParameters()
    {
        $moufParameters = array();
        foreach ($this->xmlElem->parameter as $parameter) {
            $moufParameters[] = new MoufXmlReflectionParameter($this, $parameter);
        }

        return $moufParameters;
    }

}
?>
