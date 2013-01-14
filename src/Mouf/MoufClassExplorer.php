<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf;

use Mouf\Reflection\MoufReflectionProxy;

use Mouf\Composer\ComposerService;

use Mouf\Reflection\MoufReflectionClass;

/**
 * This class is in charge of exploring all classes declared in the project and returning what classes can be
 * included, and what classes can't be included (because they contain errors or are not PSR-1 compliant.
 *  
 * @author David Negrier
 */
class MoufClassExplorer {
	
	private $selfEdit;
	
	/**
	 * The list of classes that cause errors when included, along the error associated.
	 * @var array<string, string>
	 */
	private $forbiddenClasses = array();
	
	/**
	 * The map of classes/filename that can be used safely.
	 * 
	 * @var array<string, string>
	 */
	private $classMap = array();
	
	private $dataAvailable = false;
	
	public function __construct($selfEdit = false) {
		$this->selfEdit = $selfEdit;
	}
	
	private function analyze() {
		if ($this->dataAvailable) {
			return;
		}
		
		$classMap = MoufReflectionProxy::getClassMap($this->selfEdit);
		
		do {
			$notYetAnalysedClassMap = $classMap;
			$nbRun = 0;
			while (!empty($notYetAnalysedClassMap)) {
				$this->analysisResponse = MoufReflectionProxy::analyzeIncludes2($this->selfEdit, $notYetAnalysedClassMap);
				$nbRun++;
				
				$startupPos = strpos($this->analysisResponse, "FDSFZEREZ_STARTUP\n");
				if ($startupPos === false) {
					// It seems there is a problem running the script, let's throw an exception
					throw new MoufException("Error while running classes analysis: ".$this->analysisResponse);
				}
				
				$this->analysisResponse = substr($this->analysisResponse, $startupPos+18);
				//echo($this->analysisResponse);exit;
				while (true) {
					$beginMarker = $this->trimLine();
					if ($beginMarker == "SQDSG4FDSE3234JK_ENDFILE") {
						// We are finished analysing the file! Yeah!
						break;
					} elseif ($beginMarker != "X4EVDX4SEVX5_BEFOREINCLUDE") {
						//echo $beginMarker."\n".$this->analysisResponse;
						throw new \Exception("Strange behaviour while importing classes. Begin marker: ".$beginMarker);
					}
	
					$analyzedClassName = $this->trimLine();
					
					// Now, let's see if the end marker is right after the begin marker...
					$endMarkerPos = strpos($this->analysisResponse, "DSQRZREZRZER__AFTERINCLUDE\n");
					if ($endMarkerPos !== 0) {
						// There is a problem...
						if ($endMarkerPos === false) {
							// An error occured:
							$this->forbiddenClasses[$analyzedClassName] = $this->analysisResponse;
							unset($notYetAnalysedClassMap[$analyzedClassName]);
							break;
						} else {
							$this->forbiddenClasses[$analyzedClassName] = substr($this->analysisResponse, 0, $endMarkerPos);
							$this->analysisResponse = substr($this->analysisResponse, $endMarkerPos);
						}
					}
					$this->trimLine();
					
					unset($notYetAnalysedClassMap[$analyzedClassName]);
					
				}
			}
			
			foreach ($this->forbiddenClasses as $badClass=>$errorMessage) {
				unset($classMap[$badClass]);
			}
			
			if ($nbRun <= 1) {
				break;
			}
			
			// If we arrive here, we managed to detect a number of files to exclude.
			// BUT, the complete list of file has never been tested together.
			// and sometimes, a class included can trigger errors if another class is included at the same time
			// (most of the time, when a require is performed on a file already loaded, triggering a "class already defined" error.
			
						
		} while (true);
		
		// Let's remove from the classmap any class in error.
		$this->classMap = $classMap;
		
		$this->dataAvailable = true;
	}
	
	/**
	 * The text response from the analysis script.
	 * @var string
	 */
	private $analysisResponse;
	
	/**
	 * Trim the first line from $analysisResponse and returns it.
	 */
	private function trimLine() {
		$newLinePos = strpos($this->analysisResponse, "\n");
		
		if ($newLinePos === false) {
			throw new \Exception("End of file reached!");
		}
		
		$line = substr($this->analysisResponse, 0, $newLinePos);
		$this->analysisResponse = substr($this->analysisResponse, $newLinePos + 1);
		return $line;
	}
	
	/**
	 * Returns the classmap of all available and safe to include classes.
	 * @return array<string, string>
	 */
	public function getClassMap() {
		$this->analyze();
		return $this->classMap;
	}
	
	/**
	 * Returns the array of all classes that have problems to be included, along the error associated.
	 * The key is the filename, the value the error message outputed.
	 * @return array<string, string>
	 */
	public function getErrors() {
		$this->analyze();
		return $this->forbiddenClasses;
	}
	
}
?>