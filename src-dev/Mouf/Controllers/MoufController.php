<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Mouf\Controllers;

use Mouf\MoufCache;

use Mouf\MoufClassExplorer;

use Mouf\Composer\ComposerService;

use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Reflection\MoufReflectionProxy;
use Mouf\MoufManager;

use Mouf\MoufSearchable;

use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller allowing access to the Mouf framework.
 *
 * @Component
 */
class MoufController extends Controller implements MoufSearchable {

	public $instanceName;
	public $className;
	public $properties;
	//public $instance;
	public $reflectionClass;
	public $selfedit;
	
	/**
	 * The active MoufManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $moufManager;
	
	/**
	 * The template used by the main page for mouf.
	 *
	 * @Property
	 * @Compulsory
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @Property
	 * @Compulsory
	 * @var HtmlBlock
	 */
	public $contentBlock;
		
	/**
	 * Array of all instances sorted by package and by class.
	 *
	 * @var array<string, array<string, string>>
	 */
	public $instancesByPackage;
	
	/**
	 * Array of all instances that are bound to classes that no longer exist.
	 * 
	 * @var array<string, string> key: instance name, value: class name.
	 */
	public $inErrorInstances;
	
	/**
	 * The search query, or null if all instances must be displayed.
	 * 
	 * @var string
	 */
	protected $query;
	
	/**
	 * An anonymous array associating instance name with display instance name.
	 * @var array<string, string>
	 */
	protected $anonymousNames = array();
	
	/**
	 * Redirects the user to the right controller.
	 * This can be the detail controller, or any other controller, depending on the @ExtendedAction annotation.
	 * 
	 * @Action
	 * @Logged
	 * @param string $name the name of the component to display
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function displayComponent($name, $selfedit = "false") {
		$this->instanceName = $name;
		$this->selfedit = $selfedit;
		/*$this->instance = MoufManager::getMoufManager()->getInstance($name);
		$this->className = MoufManager::getMoufManager()->getInstanceType($this->instanceName);*/
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->className = $this->moufManager->getInstanceType($this->instanceName);		
		$this->reflectionClass = MoufReflectionProxy::getClass($this->className, $selfedit=="true");
		$extendedActions = $this->reflectionClass->getAnnotations("ExtendedAction");
		$destinationUrl = ROOT_URL."mouf/instance/";
		if (!empty($extendedActions)) {
			foreach ($extendedActions as $extendedAction) {
				if ($extendedAction->isDefault()) {
					$destinationUrl = ROOT_URL.$extendedAction->getUrl();
					break;
				}
			}
		}
		
		// Note: performing a redirect is not optimal as it requires a reload of the page!
		header("Location: ".$destinationUrl."?name=".urlencode($name)."&selfedit=".urlencode($selfedit));
		exit;		
	}
	
	/**
	 * Whether the call was performed using ajax or not.
	 * 
	 * @var boolean
	 */
	protected $ajax = false;
	
	protected $showAnonymous;
	
	/**
	 * Lists all the instances available, sorted by "package" (directory of the class).
	 * 
	 * @Action
	 * @Logged
	 */
	public function defaultAction($selfedit = "false", $query = null, $show_anonymous = "false") {
		//$test = new ComposerService();
		//$test->getClassMap();
		$showAnonymous = $show_anonymous == "true"; 
		$this->showAnonymous = $showAnonymous;
		
		$classExplorer = new MoufClassExplorer($selfedit == "true");
		$classMap = $classExplorer->getClassMap();
		
		$this->selfedit = $selfedit;
		$this->query = $query;
		

		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		foreach ($classMap as $className=>$file) {
			$componentsByFile[$file][] = $className;
		}
		
		
		// Now, let's remove the part of the file that is common to every file.
		// The aim is to remove the C:/Program Files/.... or /var/www/...
		$first = true;
		$common = "";
		foreach ($componentsByFile as $key=>$name) {
			if ($first) {
				$common = $key;
				$first = false;
				continue;
			} else {
				if (strpos($key, $common) === 0) {
					continue;
				} else {
					// Let's find what part of the $common string is common to the key.
					while (strlen($common)!=0) {
						$common = substr($common, 0, -1);
						if (strpos($key, $common) === 0) {
							break;
						}
					}
				}
			}
		}
		// Now that we have the common part, let's remove it, and let's get only the directory name (remove the file part)
		$componentsByShortFile = array();
		foreach ($componentsByFile as $key=>$names) {
			$packageName = dirname(substr($key, strlen($common)));
			if (!isset($componentsByShortFile[$packageName])) {
				$componentsByShortFile[$packageName] = $names;
			} else {
				$componentsByShortFile[$packageName] = array_merge($componentsByShortFile[$packageName], $names);
			}
		}
		
		// Now, let's sort all this by key.
		ksort($componentsByShortFile);
		
		// We have a sorted components list.
		// Now, for each of these, let's find the instance that match...
		$instanceList = $this->moufManager->getInstancesList();
		$nonAnonymousinstanceList = array();
		// Let's revert the instance list.
		$instanceListByClass = array();
		foreach ($instanceList as $instanceName=>$className) {
			// Let's remove anonymous classes:
			if ($this->moufManager->getInstanceDescriptor($instanceName)->isAnonymous()) {
				if (!$showAnonymous) {
					continue;
				} else {
					// Let's find a name for this anonymous instance
					$anonInstaceName = $instanceName;
					// Let's find where the anonymous instance is used.
					do {
						$parents = $this->moufManager->getOwnerComponents($anonInstaceName);
						// There should be only one parent, since this is an anonymous instance
						$anonInstaceName = current($parents);
					} while ($this->moufManager->getInstanceDescriptor($anonInstaceName)->isAnonymous());

					$this->anonymousNames[$instanceName] = "Anonymous (parent: ".$anonInstaceName.")";
				}
			}
			$nonAnonymousinstanceList[$instanceName] = $className; 
			
			$instanceListByClass[$className][] = $instanceName;
		}
		
		// The instances are bound to classes that no longer exist:
		$this->inErrorInstances = $nonAnonymousinstanceList;
		
		// type: array<package, array<class, instance>>
		$instancesByPackage = array();
		foreach ($componentsByShortFile as $package=>$classes) {
			foreach ($classes as $class) {
				if (isset($instanceListByClass[$class])) {
					$instancesByPackage[$package][$class] = $instanceListByClass[$class];
					foreach ($instanceListByClass[$class] as $instance) {
						unset($this->inErrorInstances[$instance]);
					}
				}
				if (isset($instanceListByClass["\\".$class])) {
					$instancesByPackage[$package][$class] = $instanceListByClass["\\".$class];
					foreach ($instanceListByClass["\\".$class] as $instance) {
						unset($this->inErrorInstances[$instance]);
					}
				}
			}
		}
		
		$this->instancesByPackage = $instancesByPackage;
		
		// Let's apply the filter, if any:
		if ($query) {
			foreach ($this->instancesByPackage as $package=>$instanceByClass) {
				$keepPackage = false;
				if (stripos($package, $query) !== false) {
					$keepPackage = true;					
				}
				foreach ($instanceByClass as $class=>$myInstanceList) {
					$keepClass = false;
					if (stripos($class, $query) !== false) {
						$keepClass = true;					
					}
					foreach ($myInstanceList as $key=>$instanceName) {
						$keepInstance = false;
						if (stripos($instanceName, $query) !== false) {
							$keepInstance = true;					
						}
						if (stripos($instanceList[$instanceName], $query) !== false) {
							$keepInstance = true;					
						}
						// Search also in instance parameters
						$parameterNames = $this->moufManager->getParameterNames($instanceName);
						foreach ($parameterNames as $paramName) {
							$value = $this->moufManager->getParameter($instanceName, $paramName);
							if (is_array($value)) {
								array_walk_recursive($value, function($singleValue) use (&$keepInstance, $query) {
									if (stripos($singleValue, $query) !== false) {
										$keepInstance = true;
									}
								});
							} else {
								if (stripos($value, $query) !== false) {
									$keepInstance = true;
								}
							}
						}
						$parameterNames = $this->moufManager->getParameterNamesForSetter($instanceName);
						foreach ($parameterNames as $paramName) {
							$value = $this->moufManager->getParameterForSetter($instanceName, $paramName);
							if (is_array($value)) {
								array_walk_recursive($value, function($singleValue) use (&$keepInstance, $query) {
									if (stripos($singleValue, $query) !== false) {
										$keepInstance = true;
									}
								});
							} else {
								if (stripos($value, $query) !== false) {
									$keepInstance = true;
								}
							}
						}
						$parameterNames = $this->moufManager->getParameterNamesForConstructor($instanceName);
						foreach ($parameterNames as $paramName) {
							$value = $this->moufManager->getParameterForConstructor($instanceName, $paramName);
							if (is_array($value)) {
								array_walk_recursive($value, function($singleValue) use (&$keepInstance, $query) {
									if (stripos($singleValue, $query) !== false) {
										$keepInstance = true;
									}
								});
							} else {
								if (stripos($value, $query) !== false) {
									$keepInstance = true;
								}
							}
						}
						if (!$keepPackage && !$keepClass && !$keepInstance) {
							unset($this->instancesByPackage[$package][$class][$key]);
						}
					}
					if (empty($this->instancesByPackage[$package][$class])) {
						unset($this->instancesByPackage[$package][$class]);
					}
				}
				if (empty($this->instancesByPackage[$package])) {
					unset($this->instancesByPackage[$package]);
				}
			}
		}
		
		if ($this->ajax) {
			$this->loadFile(dirname(__FILE__)."/../../views/listComponentsByDirectory.php");
		} else {
			$this->contentBlock->addFile(dirname(__FILE__)."/../../views/listComponentsByDirectory.php", $this);
			//$this->contentBlock->addFile(dirname(__FILE__)."/../views/listComponentsByDirectory.php", $this);
			$this->template->toHtml();
		}
	}
	
	/**
	 * Lists all the components available, ordered by creation date, in order to edit them.
	 * 
	 * @Action
	 * @Logged
	 */
	public function instancesByDate($selfedit = "false") {
		$this->selfedit = $selfedit;

		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->contentBlock->addFile(dirname(__FILE__)."/../views/listComponents.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Displays the screen allowing to create new instances.
	 *
	 * @Action
	 * @Logged
	 */
	public function newInstance($selfedit = "false", $instanceName=null, $instanceClass=null) {
		//$componentsList = Moufspector::getComponentsList();
		$this->selfedit = $selfedit;
		$componentsList = MoufReflectionProxy::getComponentsList($selfedit=="true");
		sort($componentsList);
		
		$template = $this->template;
		$template->addContentFunction(array($this, "displayNewInstanceScreen"), $componentsList, $selfedit, $instanceName, $instanceClass);
		//$template->addContentFile(dirname(__FILE__)."/../views/displayNewInstance.php", $this);
		$template->toHtml();	
	}
	
	/**
	 * Displays the screen allowing to create new instances.
	 *
	 * @Action
	 * @Logged
	 */
	public function newInstance2($selfedit = "false", $instanceName=null, $instanceClass=null) {
		$this->instanceName = $instanceName;
		$this->instanceClass = $instanceClass;
		$this->selfedit = $selfedit;
		
		$this->contentBlock->addFile(dirname(__FILE__)."/../../views/instances/newInstance.php", $this);
		$this->template->toHtml();	
	}
	
	/**
	 * Purge the cache and redisplays the screen allowing to create new instances.
	 *
	 * @Action
	 * @Logged
	 */
	public function refreshNewInstance($selfedit = "false", $instanceName=null, $instanceClass=null) {
		$moufCache = new MoufCache();
		$moufCache->purgeAll();
		
		header("Location: newInstance2?selfedit=".$selfedit."&instanceName=".urlencode($instanceName)."&instanceClass=".urlencode($instanceClass));
	}
	
	/**
	 * Displays the new component view
	 *
	 */
	public function displayNewInstanceScreen($componentsList, $selfedit, $instanceName, $instanceClass) {
		include(dirname(__FILE__)."/../views/displayNewInstance.php");
	}
	
	/**
	 * The action that creates a new component instance.
	 *
	 * @Action
	 * @Logged
	 * @param string $instanceName The name of the instance to create
	 * @param string $instanceClass The class of the component to create
	 */
	public function createComponent($instanceName, $instanceClass, $selfedit) {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		
		$this->moufManager->declareComponent($instanceName, $instanceClass);
		$this->moufManager->rewriteMouf();
		
		// Redirect to the display component page:
		$this->displayComponent($instanceName, $selfedit);
	}
	
	/**
	 * Removes the instance passed in parameter.
	 *
	 * @Action
	 * @Logged
	 */
	public function deleteInstance($selfedit = "false", $instanceName=null, $returnurl = null) {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->moufManager->removeComponent($instanceName);
		$this->moufManager->rewriteMouf();
		
		if ($returnurl) {
			header("Location:".$returnurl);
		} else {
			header("Location: .?selfedit=".$selfedit);
		}
	}
	
	/**
	 * Outputs HTML that will be displayed in the search result screen.
	 * If there are no results, this should not return anything.
	 * 
	 * @Action
	 * @param string $query The full-text search query performed.
	 * @param string $selfedit Whether we are in self-edit mode or not.
	 */
	public function search($query, $selfedit = "false") {
		$this->ajax = true;
		$this->defaultAction($selfedit, $query);
	}
	
	/**
	 * Returns the name of the search module.
	 * This name in displayed when the search is pending.
	 * 
	 * @return string
	 */
	public function getSearchModuleName() {
		return "instances list";
	}
}
?>