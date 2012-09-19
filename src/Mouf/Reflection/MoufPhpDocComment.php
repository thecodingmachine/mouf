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

use \Mouf\MoufManager;

/**
 * Parses a document comment, and provides a set of getters on the different part of the comment.
 *
 */
class MoufPhpDocComment {
	
	/**
	 * The pure comment that is part of the phpDocComment (without the annotation part).
	 *
	 * @var string
	 */
	private $comment;
	
	/**
	 * A map associating the annotation title to an array of params (as strings)
	 * This class is instanciated as soon as the comments data is parsed.
	 * 
	 * @var array("annotationClass"=>array("params"))
	 */
	private $annotationsArrayAsString;
	
	/**
	 * A map associating the annotation title to an array of objects representing the annotation
	 * The annotation classes are instanciated on demand (with getAnnotations)
	 * 
	 * @var array("annotationClass"=>array($annotationObjects))
	 */
	private $annotationsArrayAsObject;
	
	/**
	 * Standard constructor. Takes the comment to parse in argument.
	 *
	 * @param string $docComment
	 */
	public function __construct($docComment) {
		$annotationsArrayAsString = array();
		$annotationsArrayAsObject = array();
		$this->parse($docComment);
	}
	
	/**
	 * Parses the doc comment and initilizes all the values of interest.
	 *
	 * @param string $docComment
	 */
	private function parse($docComment) {
		$lines = self::getDocLinesFromComment($docComment);
		
		// First, let's go to the first Annotation.
		// Anything before is the pure comment.
		$annotationLines = array();
		
		$oneAnnotationFound = false;
		// Is the line an annotation? Let's test this with a regexp.
		foreach ($lines as $line) {
			if (preg_match("/^[@][a-zA-Z]/", $line) === 0) {
				if ($oneAnnotationFound == false) {
					$this->comment .= $line."\n";
				}
			} else {
				$this->parseAnnotation($line);
				$oneAnnotationFound = true;
			}
		}
	}
	
	/**
	 * Parses an annotation line and stores the result in the MoufPhpDocComment.
	 *
	 * @param string $line
	 */
	private function parseAnnotation($line) {
		// Let's get the annotation text
		preg_match("/^[@]([a-zA-Z][a-zA-Z0-9]*)(.*)/", $line, $values);
		
		$annotationClass = isset($values[1])?$values[1]:null;
		$annotationParams = trim(isset($values[2])?$values[2]:null);
		
		$this->annotationsArrayAsString[$annotationClass][] = $annotationParams;		
	}
	
	/**
	 * Returns an array of lines of the comments.
	 *
	 * @param string $docComment
	 * @return array
	 */
	private static function getDocLinesFromComment($docComment) {
		if (strpos($docComment, "/**") === 0) {
			$docComment = substr($docComment, 3);
		}
		
		// Let's remove all the \r...
		$docComment = str_replace("\r", "", $docComment);
			
		$commentLines = explode("\n", $docComment);
		$commentLinesWithoutStars = array();
		
		// For each line, let's remove the first spaces, and the first *
		foreach ($commentLines as $commentLine) {
			$length = strlen($commentLine);
			for ($i=0; $i<$length; $i++) {
				if ($commentLine{$i} != ' ' && $commentLine{$i} != '*' && $commentLine{$i} != "\t") {
					break;
				}
			}
			$commentLinesWithoutStars[] = substr($commentLine, $i);
		}
		
		// Let's remove the trailing /:
		$lastComment = $commentLinesWithoutStars[count($commentLinesWithoutStars)-1];
		$commentLinesWithoutStars[count($commentLinesWithoutStars)-1] = substr($lastComment, 0, strlen($lastComment)-1);
		
		if ($commentLinesWithoutStars[count($commentLinesWithoutStars)-1] == "")
			array_pop($commentLinesWithoutStars);
			
		if (isset($commentLinesWithoutStars[0]) && $commentLinesWithoutStars[0] == "") {
			$commentLinesWithoutStars = array_slice($commentLinesWithoutStars, 1);
		}
		
		return $commentLinesWithoutStars;
	}
	
	/**
	 * Returns the annotation objects associated to $annotationName in an array.
	 * For instance, if there is one annotation "@Filter toto", there will be an array of one element.
	 * The element will contain an object of type FilterAnnotation. If the class FilterAnnotation is not defined,
	 * a string is returned instead of an object.  
	 *
	 * @param string $annotationName
	 * @return array($objects)
	 */
	public function getAnnotations($annotationName) {
		// Let's return the value if it is already computed.
		if (isset($this->annotationsArrayAsObject[$annotationName])) {
			return $this->annotationsArrayAsObject[$annotationName];
		}
		
		if (!isset($this->annotationsArrayAsString[$annotationName])) {
			return null;
		}
		
		// Ok, let's instanciate the annotation.
		// Is there an instance named like the annotation?
		$moufManager = MoufManager::getMoufManager();
		if ($moufManager->instanceExists($annotationName)) {
			
			$instance = $moufManager->getInstance($annotationName);
			if ($instance instanceof MoufAnnotationInterface) {
				foreach ($this->annotationsArrayAsString[$annotationName] as $value) {
					$clonedInstance = clone $instance;
					$clonedInstance->setValue($value);
					/* @var $clonedInstance MoufAnnotationInterface */
					$this->annotationsArrayAsObject[$annotationName][] = $clonedInstance;
				}
			}
			return $this->annotationsArrayAsObject[$annotationName];
		}
	
		
		$annotationClassName = $annotationName."Annotation";
		if (class_exists("\\Mouf\\Annotations\\".$annotationClassName)) {
			foreach ($this->annotationsArrayAsString[$annotationName] as $value) {
				$finalClassName = "\\Mouf\\Annotations\\".$annotationClassName;
				$this->annotationsArrayAsObject[$annotationName][] = new $finalClassName($value);
			}
		} elseif (class_exists($annotationClassName)) {
			foreach ($this->annotationsArrayAsString[$annotationName] as $value) {
				$this->annotationsArrayAsObject[$annotationName][] = new $annotationClassName($value);
			}
		} elseif (class_exists($annotationName)) {
			foreach ($this->annotationsArrayAsString[$annotationName] as $value) {
				$this->annotationsArrayAsObject[$annotationName][] = new $annotationName($value);
			}
		} else {
			foreach ($this->annotationsArrayAsString[$annotationName] as $value) {
				$this->annotationsArrayAsObject[$annotationName][] = $value;
			}
		}
		return $this->annotationsArrayAsObject[$annotationName];
	}
	
	/**
	 * Returns the comment text, without the annotations.
	 *
	 * @return string
	 */
	public function getComment() {
		return $this->comment;
	}
	
	/**
	 * Returns the number of declared annotations of type $annotationName in the class comment.
	 *
	 * @param string $annotationName
	 * @return int
	 */
	public function getAnnotationsCount($annotationName) {
		if (isset($this->annotationsArrayAsString[$annotationName]))
			return count($this->annotationsArrayAsString[$annotationName]);
		else
			return 0;
	}
	
	/**
	 * Returns a map associating the annotation title to an array of objects representing the annotation.
	 * 
	 * @var array("annotationClass"=>array($annotationObjects))
	 */
	public function getAllAnnotations() {
		$retArray = array();
		if (!$this->annotationsArrayAsString) {
			return array();
		}
		foreach ($this->annotationsArrayAsString as $key=>$value) {
			$retArray[$key] = $this->getAnnotations($key);
		}
		return $retArray;
	}
	
	/**
	 * Returns an array representing the PhpDocComment:
	 * {
	 * 	comment: comment,
	 *  annotations: {
	 *  	name: [param1, param2, param3],
	 *  	name2: [param]
	 *  }
	 * }
	 * 
	 * @return array
	 */
	public function getJsonArray() {
		return array("comment"=>$this->comment,
					 "annotations"=>$this->annotationsArrayAsString);
	}
}
?>