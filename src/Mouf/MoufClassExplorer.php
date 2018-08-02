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
	
	private $useCache = true;
	
	private $cacheService;

	private $analysisOffset = 0;
	
	public function __construct($selfEdit = false) {
		$this->selfEdit = $selfEdit;
		$this->cacheService = new MoufCache();
	}
	
	/**
	 * 
	 * @param bool $useCache
	 */
	public function setUseCache($useCache) {
		$this->useCache = $useCache;
	}
	
	private function analyze() {		
		if ($this->dataAvailable) {
			return;
		}
		
		$vendorCachePath = $this->selfEdit ? __DIR__.'/../../../../../mouf/no_commit/mouf_vendor_analysis.json'
			                               : __DIR__.'/../../../../../mouf/no_commit/vendor_analysis.json';

        if (defined('PROFILE_MOUF') && constant('PROFILE_MOUF') == true) {
            error_log("PROFILING: MoufClassExplorer::analyze : Start : ".date('H:i:s', time()));
        }

        if (\file_exists($vendorCachePath)) {
			$vendorCacheContent = \file_get_contents($vendorCachePath);
			if ($vendorCacheContent === false) {
				throw new MoufException('An error occured while reading file '.$vendorCachePath);
			}
			$vendorAnalysis = json_decode($vendorCacheContent, true);
			if ($vendorAnalysis === false) {
                throw new MoufException('Unable to decode content of file  '.$vendorCachePath);
			}
		} else {
            $classMap = MoufReflectionProxy::getClassMap('VENDOR', $this->selfEdit);
            $vendorAnalysis = $this->analyzeClassMap($classMap);
            $result = \file_put_contents($vendorCachePath, \json_encode($vendorAnalysis, \JSON_PRETTY_PRINT));
            if ($result === false) {
                throw new MoufException('An error occured while writing file '.$vendorCachePath);
			}
		}

        if (defined('PROFILE_MOUF') && constant('PROFILE_MOUF') == true) {
            error_log("PROFILING: MoufClassExplorer::analyze : finished vendor processing : ".date('H:i:s', time()));
        }

        $classMap = MoufReflectionProxy::getClassMap('ROOT_PACKAGE', $this->selfEdit);

		if (defined('PROFILE_MOUF') && constant('PROFILE_MOUF') == true) {
			error_log("PROFILING: MoufClassExplorer::analyze : finished MoufReflectionProxy::getClassMap: ".date('H:i:s', time()));
		}
		
		$results = $this->analyzeClassMap($classMap);

		// Let's remove from the classmap any class in error.
		$this->classMap = array_merge($results['classMap'], $vendorAnalysis['classMap']);
		$this->forbiddenClasses = array_merge($results['forbiddenClasses'], $vendorAnalysis['forbiddenClasses']);
		
		if ($this->useCache) {
			// Cache duration: 30 minutes.
			$this->cacheService->set("mouf.classMap.".__DIR__."/".json_encode($this->selfEdit), $this->classMap, 30*60);
			$this->cacheService->set("forbidden.classes.".__DIR__."/".json_encode($this->selfEdit), $this->forbiddenClasses, 30*60);
		}
		
		if (defined('PROFILE_MOUF') && constant('PROFILE_MOUF') == true) {
			error_log("PROFILING: MoufClassExplorer::analyze : finished analyze: ".date('H:i:s', time()));
		}
		
		$this->dataAvailable = true;
	}

    /**
	 * Returns an array with 2 elements:
	 *
	 * - a working class map
	 * - a list of forbidden classes that trigger errors
	 *
     * @param array $classMap
     * @return array
     * @throws MoufException
     */
	private function analyzeClassMap(array $classMap)
	{
        $forbiddenClasses = [];

        do {
            $notYetAnalysedClassMap = $classMap;
            $nbRun = 0;
            while (!empty($notYetAnalysedClassMap)) {
                $this->analysisOffset = 0;
                $this->analysisResponse = MoufReflectionProxy::analyzeIncludes2($this->selfEdit, $notYetAnalysedClassMap);
                $nbRun++;
                $startupPos = strpos($this->analysisResponse, "FDSFZEREZ_STARTUP\n");
                if ($startupPos === false) {
                    // It seems there is a problem running the script, let's throw an exception
                    throw new MoufException("Error while running classes analysis: ".$this->analysisResponse);
                }

                $this->analysisOffset = $startupPos+18;
                //echo($this->analysisResponse);exit;
                while (true) {
                    $beginMarker = $this->popLine();
                    if ($beginMarker == "SQDSG4FDSE3234JK_ENDFILE") {
                        // We are finished analysing the file! Yeah!
                        break;
                    } elseif ($beginMarker != "X4EVDX4SEVX5_BEFOREINCLUDE") {
                        //echo $beginMarker."\n".$this->analysisResponse;
                        throw new \Exception("Strange behaviour while importing classes. Begin marker: ".$beginMarker);
                    }

                    $analyzedClassName = $this->popLine();

                    // Now, let's see if the end marker is right after the begin marker...
                    $endMarkerPos = strpos($this->analysisResponse, "DSQRZREZRZER__AFTERINCLUDE\n", $this->analysisOffset);
                    if ($endMarkerPos !== $this->analysisOffset) {
                        // There is a problem...
                        if ($endMarkerPos === false) {
                            // An error occured:
                            $forbiddenClasses[$analyzedClassName] = substr($this->analysisResponse, $this->analysisOffset);
                            unset($notYetAnalysedClassMap[$analyzedClassName]);
                            break;
                        } else {
                            $forbiddenClasses[$analyzedClassName] = substr($this->analysisResponse, $this->analysisOffset, $endMarkerPos - $this->analysisOffset);
                            $this->analysisOffset = $endMarkerPos;
                        }
                    }
                    $this->popLine();

                    unset($notYetAnalysedClassMap[$analyzedClassName]);

                }
            }

            foreach ($forbiddenClasses as $badClass=>$errorMessage) {
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

        return array(
        	'forbiddenClasses' => $forbiddenClasses,
			'classMap' => $classMap
		);
	}
	
	/**
	 * The text response from the analysis script.
	 * @var string
	 */
	private $analysisResponse;
	
	/**
	 * Get the current line from $analysisResponse and returns it.
     * Moves the internal offset pointer.
	 */
	private function popLine() {
		$newLinePos = strpos($this->analysisResponse, "\n", $this->analysisOffset);
		
		if ($newLinePos === false) {
			throw new \Exception("End of file reached!");
		}
		
		$line = substr($this->analysisResponse, $this->analysisOffset, ($newLinePos - $this->analysisOffset));
        $this->analysisOffset = $newLinePos + 1;
		return $line;
	}
	
	/**
	 * Returns the classmap of all available and safe to include classes.
	 * @return array<string, string>
	 */
	public function getClassMap() {
		if ($this->classMap) {
			return $this->classMap;
		}

		if ($this->useCache) {
			// Cache duration: 30 minutes.
			$this->classMap = $this->cacheService->get("mouf.classMap.".__DIR__."/".json_encode($this->selfEdit));
			if ($this->classMap != null) {
				return $this->classMap;
			}
		}
		
		$this->analyze();
		return $this->classMap;
	}
	
	/**
	 * Returns the array of all classes that have problems to be included, along the error associated.
	 * The key is the filename, the value the error message outputed.
	 * @return array<string, string>
	 */
	public function getErrors() {
		if ($this->forbiddenClasses) {
			return $this->forbiddenClasses;
		}
		
		if ($this->useCache) {
			// Cache duration: 30 minutes.
			$this->forbiddenClasses = $this->cacheService->get("forbidden.classes.".__DIR__."/".json_encode($this->selfEdit));
			if ($this->forbiddenClasses != null) {
				return $this->forbiddenClasses;
			}
		}
		
		$this->analyze();
		return $this->forbiddenClasses;
	}
	
}
?>
