<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012-2015 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf;

use Mouf\Composer\ComposerService;

use Mouf\Reflection\MoufReflectionProxy;
use Mouf\Reflection\MoufReflectionClass;
use Mouf\Reflection\ReflectionClassManagerInterface;
use Interop\Container\ContainerInterface;
use Mouf\Composer\ClassNameMapper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use Mouf\Composer\ClassNameUtils;
use Mouf\Reflection\MoufReflectionClassManager;
use GeneratedClasses\Container;

/**
 * This class is managing object instanciation in the Mouf framework.
 * This is a dependency injection container (DIC).
 * Use it to retrieve instances declared using the Mouf UI, or to create/edit the instances.
 *
 */
class MoufContainer implements ContainerInterface {
	const DECLARE_ON_EXIST_EXCEPTION = 'exception';
	const DECLARE_ON_EXIST_KEEP_INCOMING_LINKS = 'keepincominglinks';
	const DECLARE_ON_EXIST_KEEP_ALL = 'keepall';

	/**
	 * The name of the file containing the configuration.
	 * 
	 * @var string
	 */
	private $configFile;
	
	/**
	 * The object in charge of fetching class descriptor instances. Only used in edition mode.
	 * 
	 * @var ReflectionClassManagerInterface
	 */
	private $reflectionClassManager;
	
	/**
	 * The array of component instances managed by mouf.
	 * The objects in this array have already been instanciated.
	 * The key is the name of the instance, the value is the object.
	 *
	 * @var array<string, object>
	 */
	private $objectInstances = array();

	/**
	 * The array of component instances that have been declared.
	 * This array contains the definition that will be used to create the instances.
	 *
	 * $declaredInstance["instanceName"] = $instanceDefinitionArray;
	 *
	 * $instanceDefinitionArray["class"] = "string"
	 * $instanceDefinitionArray["fieldProperties"] = array("propertyName" => $property);
	 * $instanceDefinitionArray["setterProperties"] = array("setterName" => $property);
	 * $instanceDefinitionArray["fieldBinds"] = array("propertyName" => "instanceName");
	 * $instanceDefinitionArray["setterBinds"] = array("setterName" => "instanceName");
	 * $instanceDefinitionArray["comment"] = "string"
	 * $instanceDefinitionArray["weak"] = true|false (if true, object can be garbage collected if not referenced)
	 * $instanceDefinitionArray["anonymous"] = true|false (if true, object name should not be displayed. Object becomes "weak")
	 * $instanceDefinitionArray["external"] = true|false
	 * $instanceDefinitionArray["code"] = "php code"|empty (if this is an instance declared via code, "code" is some PHP code to create the instance). Otherwise, the property is not set.
	 *
	 * $property["type"] = "string|config|request|session";
	 * $property["value"] = $value;
	 * $property['metadata'] = array($key=>$value)
	 *
	 * @var array<string, array>
	 */
	private $declaredInstances = array();
	
	/**
	 * A list of PHP closures used for instantiating instances.
	 * For instance:
	 *
	 * $closures["instanceName"]["constructor"][4] = function($moufManager) {...}
	 *
	 * All closures are taking the $moufManager has sole and unique parameter.
	 *
	 * @var array
	 */
	private $closures;
		
	/**
	 * If set, all dependencies lookup will be delegated to this container.
	 *
	 * @var ContainerInterface
	 */
	protected $delegateLookupContainer;

	/**
	 * The name of the main class that will be generated (by default: Mouf)
	 *
	 * @var string
	 */
	private $mainClassName;
	
	/**
	 * The path to the file of the main class
	 *
	 * @var string
	 */
	private $mainClassFile;
	
	/**
	 * The instance of the main class
	 *
	 * @var string
	 */
	private $mainClass;
	
	// TODOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO:
	/*
	 * QUESTION: le classname est OBLIGATOIRE pour la gestion des closures efficace.
	 * Du coup, doit-on le mettre dans les paramètres du constructeur?
	 * Puisque la classe est obligatoire, peut elle contenir un lien vers le fichier de conf à charger? => mais alors comment initialiser une nouvelle classe???
	 *  => initialisation avec une méthode spéciale de MoufManager? Genre MoufManager->createContainer($className, $configFile);
	 *  
	 * Question: et si la config faisait partie de la classe???
	 *  => Avantage: un seul fichier
	 *  => Inconvénient: 2 fois les conflits dans le même fichier le jour où on passe à la compilation
	 *  			+ problème si on passe le format en YAML.
	 *  
	 *  => Est-ce qu'on EXTEND de MoufContainer (Symfony-style?) ou alors on fait simplement un "closures-file"?
	 *  => Avantage de la classe: via l'autoload, on peut charger le fichier de conf.
	 *  => le constructeur serait: 
	 *  	public function __construct() {
	 *  		parent::__construct("path_to_config_file", __CLASS__, __FILE__);
	 *  	}
	 */
	
	/**
	 * Constructs a container.
	 * 
	 * @param string $mainClassName
	 * @param ReflectionClassManagerInterface $reflectionClassManager The object in charge of fetching class descriptor instances. Only used in edition mode.
	 * @param ContainerInterface $delegateLookupContainer The container that should be used to perform dependency lookups (see ContainerInterop delegate lookup feeture for more info).
	 */
	public function __construct($configFile, $mainClassName, ReflectionClassManagerInterface $reflectionClassManager = null, ContainerInterface $delegateLookupContainer = null, $mainClassFile = null) {
		$this->configFile = $configFile;
		$this->mainClassName = $mainClassName;
		$this->reflectionClassManager = $reflectionClassManager;
		if ($reflectionClassManager === null) {
			$this->reflectionClassManager = new MoufReflectionClassManager();
		} else {
			$this->reflectionClassManager = $reflectionClassManager;
		}
		if ($delegateLookupContainer) {
			$this->delegateLookupContainer = $delegateLookupContainer;
		} else {
			$this->delegateLookupContainer = $this;
		}
		$this->mainClassFile = $mainClassFile;
		
		if (file_exists($configFile)) {
			$this->declaredInstances = require $configFile;
		}
	}
	
	/**
	 * Returns the class file (and eventually computes it).
	 * 
	 * @throws MoufException
	 * @return string
	 */
	protected function getMainClassFile() {
		if ($this->mainClassFile === null) {
			$this->mainClassFile = self::getClassFileFromClassName($this->mainClass);
		}
		return $this->mainClassFile;
	}
	
	protected static function getClassFileFromClassName($className) {
		$classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__.'/../../../../../composer.json');
		$possibleFiles = $classNameMapper->getPossibleFileNames($className);
			
		if (count($possibleFiles) == 0) {
			throw new MoufException("No autoload settings detected for class '".$className."' in your composer.json file. Please add a PSR-0 or PSR-4 autoloader compatible with '".$className."' and run 'php composer.phar dumpautoload'.");
		}
		return $possibleFiles[0];
	}
	
	/**
	 * Loads the config file passed in parameter.
	 * This will unload any other config file loaded before.
	 * 
	 * @param string $fileName
	 */
	/*public function load($fileName) {
		$this->objectInstances = array();
		$this->declaredInstances = require $fileName;
		$this->configFile = $fileName;
	}*/
	
	/**
	 * Sets the reflection class manager, after instanciation.
	 * (Useful for mouf hidden mode, where instanciation is done before
	 * we know the real mode).
	 * 
	 * @param ReflectionClassManagerInterface $reflectionClassManager
	 */
	public function setReflectionClassManager(ReflectionClassManagerInterface $reflectionClassManager) {
		$this->reflectionClassManager = $reflectionClassManager;
	}
	
	/**
	 * Returns the instance of the specified object.
	 *
	 * @param string $instanceName
	 * @return mixed
	 */
	public function get($instanceName) {
		if (!isset($this->objectInstances[$instanceName]) || $this->objectInstances[$instanceName] == null) {
			$this->instantiateComponent($instanceName);
		}
		return $this->objectInstances[$instanceName];
	}
	
	/**
	 * Returns true if the instance name passed in parameter is defined in Mouf.
	 *
	 * @param string $instanceName
	 */
	public function has($instanceName) {
		return isset($this->declaredInstances[$instanceName]);
	}

	/**
	 * Returns the list of all instances of objects in Mouf.
	 * Objects are not instanciated. Instead, a list containing the name of the instance in the key
	 * and the name of the class in the value is returned.
	 *
	 * @return array<string, string>
	 */
	public function getInstancesList() {
		$arr = array();
		foreach ($this->declaredInstances as $instanceName=>$classDesc) {
			$arr[$instanceName] = isset($classDesc['class'])?$classDesc['class']:null;
		}
		return $arr;
	}

	/**
	 * Sets at once all the instances of all the components.
	 * This is used internally to load the state of Mouf very quickly.
	 * Do not use directly.
	 *
	 * @param array $definition A huge array defining all the declared instances definitions.
	 */
	public function addComponentInstances(array $definition) {
		$this->declaredInstances = array_merge($this->declaredInstances, $definition);
	}

	/**
	 * Declares a new component. Low-level function. Unless you are worried by performances, you should use the createInstance function instead.
	 *
	 * @param string $instanceName
	 * @param string $className
	 * @param boolean $external Whether the component is external or not. Defaults to false.
	 * @param int $mode Depending on the mode, the behaviour will be different if an instance with the same name already exists.
	 * @param bool $weak If the object is weak, it will be destroyed if it is no longer referenced.
	 */
	public function declareComponent($instanceName, $className, $external = false, $mode = self::DECLARE_ON_EXIST_EXCEPTION, $weak = false) {
		if (isset($this->declaredInstances[$instanceName])) {
			if ($mode == self::DECLARE_ON_EXIST_EXCEPTION) {
				throw new MoufException("Unable to create Mouf instance named '".$instanceName."'. An instance with this name already exists.");
			} elseif ($mode == self::DECLARE_ON_EXIST_KEEP_INCOMING_LINKS) {
				$this->declaredInstances[$instanceName]["fieldProperties"] = array();
				$this->declaredInstances[$instanceName]["setterProperties"] = array();
				$this->declaredInstances[$instanceName]["fieldBinds"] = array();
				$this->declaredInstances[$instanceName]["setterBinds"] = array();
				$this->declaredInstances[$instanceName]["weak"] = $weak;
				$this->declaredInstances[$instanceName]["comment"] = "";
			} elseif ($mode == self::DECLARE_ON_EXIST_KEEP_ALL) {
				// Do nothing
			}
		}
		
		if (strpos($className, '\\' === 0)) {
			$className = substr($className, 1);
		}
		
		$this->declaredInstances[$instanceName]["class"] = $className;
		$this->declaredInstances[$instanceName]["external"] = $external;
	}

	/**
	 * Removes an instance.
	 * Sets to null any property linking to that component or remove the property from any array it might belong to.
	 *
	 * @param string $instanceName
	 */
	public function removeInstance($instanceName) {
		unset($this->instanceDescriptors[$instanceName]);
		unset($this->declaredInstances[$instanceName]);
		if (isset($this->instanceDescriptors[$instanceName])) {
			unset($this->instanceDescriptors[$instanceName]);
		}
		
		foreach ($this->declaredInstances as $declaredInstanceName=>$declaredInstance) {
			if (isset($declaredInstance["constructor"])) {
				foreach ($declaredInstance["constructor"] as $index=>$propWrapper) {
					if ($propWrapper['parametertype'] == 'object') {
						$properties = $propWrapper['value'];
						if (is_array($properties)) {
							// If this is an array of properties
							$keys_matching = array_keys($properties, $instanceName);
							if (!empty($keys_matching)) {
								foreach ($keys_matching as $key) {
									unset($properties[$key]);
								}
								$this->setParameterViaConstructor($declaredInstanceName, $index, $properties, 'object');
							}
						} else {
							// If this is a simple property
							if ($properties == $instanceName) {
								$this->setParameterViaConstructor($declaredInstanceName, $index, null, 'object');
							}
						}
					}
				}
			}
		}
		
		foreach ($this->declaredInstances as $declaredInstanceName=>$declaredInstance) {
			if (isset($declaredInstance["fieldBinds"])) {
				foreach ($declaredInstance["fieldBinds"] as $paramName=>$properties) {
					if (is_array($properties)) {
						// If this is an array of properties
						$keys_matching = array_keys($properties, $instanceName);
						if (!empty($keys_matching)) {
							foreach ($keys_matching as $key) {
								unset($properties[$key]);
							}
							$this->bindComponents($declaredInstanceName, $paramName, $properties);
						}
					} else {
						// If this is a simple property
						if ($properties == $instanceName) {
							$this->bindComponent($declaredInstanceName, $paramName, null);
						}
					}
				}
			}
		}
		
		foreach ($this->declaredInstances as $declaredInstanceName=>$declaredInstance) {
			if (isset($declaredInstance["setterBinds"])) {
				foreach ($declaredInstance["setterBinds"] as $setterName=>$properties) {
					if (is_array($properties)) {
						// If this is an array of properties
						$keys_matching = array_keys($properties, $instanceName);
						if (!empty($keys_matching)) {
							foreach ($keys_matching as $key) {
								unset($properties[$key]);
							}
							$this->bindComponentsViaSetter($declaredInstanceName, $setterName, $properties);
						}
					} else {
						// If this is a simple property
						if ($properties == $instanceName) {
							$this->bindComponentViaSetter($declaredInstanceName, $setterName, null);
						}
					}
				}
			}
		}
	}

	/**
	 * Renames an instance.
	 * All properties are redirected to the new instance accordingly.
	 *
	 * @param string $instanceName Old name
	 * @param string $newInstanceName New name
	 */
	public function renameInstance($instanceName, $newInstanceName) {
		if ($instanceName == $newInstanceName) {
			return;
		}

		if (isset($this->declaredInstances[$newInstanceName])) {
			throw new MoufException("Unable to rename instance '$instanceName' to '$newInstanceName': Instance '$newInstanceName' already exists.");
		}

		if (isset($this->declaredInstances[$instanceName]['external']) && $this->declaredInstances[$instanceName]['external'] == true) {
			throw new MoufException("Unable to rename instance '$instanceName' into '$newInstanceName': Instance '$instanceName' is declared externally.");
		}

		$this->declaredInstances[$newInstanceName] = $this->declaredInstances[$instanceName];
		unset($this->declaredInstances[$instanceName]);
		
		foreach ($this->declaredInstances as $declaredInstanceName=>$declaredInstance) {
			if (isset($declaredInstance["constructor"])) {
				foreach ($declaredInstance["constructor"] as $index=>$propWrapper) {
					if ($propWrapper['parametertype'] == 'object') {
						$properties = $propWrapper['value'];
						if (is_array($properties)) {
							// If this is an array of properties
							$keys_matching = array_keys($properties, $instanceName);
							if (!empty($keys_matching)) {
								foreach ($keys_matching as $key) {
									$properties[$key] = $newInstanceName;
								}
								$this->setParameterViaConstructor($declaredInstanceName, $index, $properties, 'object');
							}
						} else {
							// If this is a simple property
							if ($properties == $instanceName) {
								$this->setParameterViaConstructor($declaredInstanceName, $index, $newInstanceName, 'object');
							}
						}
					}
				}
			}
		}
		
		
		foreach ($this->declaredInstances as $declaredInstanceName=>$declaredInstance) {
			if (isset($declaredInstance["fieldBinds"])) {
				foreach ($declaredInstance["fieldBinds"] as $paramName=>$properties) {
					if (is_array($properties)) {
						// If this is an array of properties
						$keys_matching = array_keys($properties, $instanceName);
						if (!empty($keys_matching)) {
							foreach ($keys_matching as $key) {
								$properties[$key] = $newInstanceName;
							}
							$this->bindComponents($declaredInstanceName, $paramName, $properties);
						}
					} else {
						// If this is a simple property
						if ($properties == $instanceName) {
							$this->bindComponent($declaredInstanceName, $paramName, $newInstanceName);
						}
					}
				}
			}
		}

		foreach ($this->declaredInstances as $declaredInstanceName=>$declaredInstance) {
			if (isset($declaredInstance["setterBinds"])) {
				foreach ($declaredInstance["setterBinds"] as $setterName=>$properties) {
					if (is_array($properties)) {
						// If this is an array of properties
						$keys_matching = array_keys($properties, $instanceName);
						if (!empty($keys_matching)) {
							foreach ($keys_matching as $key) {
								$properties[$key] = $newInstanceName;
							}
							$this->bindComponentsViaSetter($declaredInstanceName, $setterName, $properties);
						}
					} else {
						// If this is a simple property
						if ($properties == $instanceName) {
							$this->bindComponentViaSetter($declaredInstanceName, $setterName, $newInstanceName);
						}
					}
				}
			}
		}
		
		if (isset($this->instanceDescriptors[$instanceName])) {
			$this->instanceDescriptors[$newInstanceName] = $this->instanceDescriptors[$instanceName];
			unset($this->instanceDescriptors[$instanceName]);
		}
	}

	/**
	 * Return the type of the instance.
	 *
	 * @param string $instanceName The instance name
	 * @return string The class name of the instance
	 */
	public function getInstanceType($instanceName) {
		if (isset($this->declaredInstances[$instanceName]['class'])) {
			return $this->declaredInstances[$instanceName]['class'];
		} else {
			return null;
		}
	}

	/**
	 * Returns the list of all closures associated to instances.
	 * 
	 * @return array<string, Closure>
	 */
	protected function _getClosures() {
		return [];
	}
	
	/**
	 * Instantiate the object (and any object needed on the way)
	 *
	 */
	private function instantiateComponent($instanceName) {
		if (!isset($this->declaredInstances[$instanceName])) {
			throw new MoufInstanceNotFoundException("The object instance '".$instanceName."' is not defined.", 1, $instanceName);
		}
		try {
			$instanceDefinition = $this->declaredInstances[$instanceName];
	
			if (isset($instanceDefinition['code'])) {
				if (isset($instanceDefinition['error'])) {
					throw new MoufException("The code defining instance '$instanceName' is invalid: ".$instanceDefinition['error']);
				}
				$closures = $this->_getClosures();
				$closure = $closures[$instanceName];
				$instance = $closure($this->delegateLookupContainer);
				$this->objectInstances[$instanceName] = $instance;
				return $instance;
			}
			
			$className = $instanceDefinition["class"];
	
			if (isset($instanceDefinition['constructor'])) {
				$constructorParametersArray = $instanceDefinition['constructor'];
				
				$classDescriptor = new \ReflectionClass($className);
				
				$constructorParameters = array();
				foreach ($constructorParametersArray as $key=>$constructorParameterDefinition) {
					$value = $constructorParameterDefinition["value"];
					switch ($constructorParameterDefinition['parametertype']) {
						case "primitive":
							switch ($constructorParameterDefinition["type"]) {
								case "string":
									$constructorParameters[] = $value;
									break;
								case "request":
									$constructorParameters[] = $_REQUEST[$value];
									break;
								case "session":
									$constructorParameters[] = $_SESSION[$value];
									break;
								case "config":
									$constructorParameters[] = constant($value);
									break;
								case "php":
									$closures = $this->_getClosures();
									$closure = $closures[$instanceName]['constructor'][$key];
									if ($closure instanceof \Closure) {
										$constructorParameters[] = $closure($this->delegateLookupContainer);
									} else {
										throw new MoufException("Parse error in the callback of '$instanceName' constructor argument '$key': ".$closure);
									}
									break;
								default:
									throw new MoufException("Invalid type '".$constructorParameterDefinition["type"]."' for object instance '$instanceName'.");
							}
							break;
						case "object":
							if (is_array($value)) {
								$tmpArray = array();
								foreach ($value as $keyInstanceName=>$valueInstanceName) {
									if ($valueInstanceName !== null) {
										$tmpArray[$keyInstanceName] = $this->delegateLookupContainer->get($valueInstanceName);
									} else {
										$tmpArray[$keyInstanceName] = null;
									}
								}
								$constructorParameters[] = $tmpArray;
							} else {
								if ($value !== null) {
									$constructorParameters[] = $this->delegateLookupContainer->get($value);
								} else {
									$constructorParameters[] = null;
								}
							}
							break;
						default:	
							throw new MoufException("Unknown parameter type ".$constructorParameterDefinition['parametertype']." for parameter in constructor of instance '".$instanceName."'");
					}
				}
				$object = $classDescriptor->newInstanceArgs($constructorParameters);
			} else {
				$object = new $className();
			}
			$this->objectInstances[$instanceName] = $object;
			if (isset($instanceDefinition["fieldProperties"])) {
				foreach ($instanceDefinition["fieldProperties"] as $key=>$valueDef) {
					switch ($valueDef["type"]) {
						case "string":
							$object->$key = $valueDef["value"];
							break;
						case "request":
							$object->$key = $_REQUEST[$valueDef["value"]];
							break;
						case "session":
							$object->$key = $_SESSION[$valueDef["value"]];
							break;
						case "config":
							$object->$key = constant($valueDef["value"]);
							break;
						case "php":
							$closures = $this->_getClosures();
							$closure = $closures[$instanceName]['fieldProperties'][$key];
							if ($closure instanceof \Closure) {
								$closure = $closure->bindTo($object);
								$object->$key = $closure($this->delegateLookupContainer);
							} else {
								throw new MoufException("Parse error in the callback of '$instanceName' property '$key': ".$closure);
							}
							break;
						default:
							throw new MoufException("Invalid type '".$valueDef["type"]."' for object instance '$instanceName'.");
					}
				}
			}
	
			if (isset($instanceDefinition["setterProperties"])) {
				foreach ($instanceDefinition["setterProperties"] as $key=>$valueDef) {
					//$object->$key($valueDef["value"]);
					switch ($valueDef["type"]) {
						case "string":
							$object->$key($valueDef["value"]);
							break;
						case "request":
							$object->$key($_REQUEST[$valueDef["value"]]);
							break;
						case "session":
							$object->$key($_SESSION[$valueDef["value"]]);
							break;
						case "config":
							$object->$key(constant($valueDef["value"]));
							break;
						case "php":
							$closures = $this->_getClosures();
							$closure = $closures[$instanceName]['setterProperties'][$key];
							if ($closure instanceof \Closure) {
								$closure = $closure->bindTo($object);
							} else {
								throw new MoufException("Parse error in the callback of '$instanceName' setter '$key': ".$closure);
							}
							$object->$key($closure($this->delegateLookupContainer));
							break;
						default:
							throw new MoufException("Invalid type '".$valueDef["type"]."' for object instance '$instanceName'.");
					}
				}
			}
	
			if (isset($instanceDefinition["fieldBinds"])) {
				foreach ($instanceDefinition["fieldBinds"] as $key=>$value) {
					if (is_array($value)) {
						$tmpArray = array();
						foreach ($value as $keyInstanceName=>$valueInstanceName) {
							if ($valueInstanceName !== null) {
								$tmpArray[$keyInstanceName] = $this->get($valueInstanceName);
							} else {
								$tmpArray[$keyInstanceName] = null;
							}
						}
						$object->$key = $tmpArray;
					} else {
						$object->$key = $this->get($value);
					}
				}
			}
	
			if (isset($instanceDefinition["setterBinds"])) {
				foreach ($instanceDefinition["setterBinds"] as $key=>$value) {
					if (is_array($value)) {
						$tmpArray = array();
						foreach ($value as $keyInstanceName=>$valueInstanceName) {
							if ($valueInstanceName !== null) {
								$tmpArray[$keyInstanceName] = $this->get($valueInstanceName);
							} else {
								$tmpArray[$keyInstanceName] = null;
							}
						}
						$object->$key($tmpArray);
					} else {
						$object->$key($this->get($value));
					}
				}
			}
		} catch (MoufInstanceNotFoundException $e) {
			throw new MoufInstanceNotFoundException("The object instance '".$instanceName."' could not be created because it depends on an object in error (".$e->getMissingInstanceName().")", 2, $instanceName, $e);
		}
		return $object;
	}
	
	/**
	 * Binds a parameter to the instance.
	 * Low-level function. Unless you are worried by performances, you should use the createInstance function instead.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @param string $paramValue
	 * @param string $type Can be one of "string|config|request|session"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameter($instanceName, $paramName, $paramValue, $type = "string", array $metadata = array()) {
		if ($type != "string" && $type != "config" && $type != "request" && $type != "session" && $type != "php") {
			throw new MoufException("Invalid type. Must be one of: string|config|request|session. Value passed: '".$type."'");
		}
		
		$this->declaredInstances[$instanceName]["fieldProperties"][$paramName]["value"] = $paramValue;
		$this->declaredInstances[$instanceName]["fieldProperties"][$paramName]["type"] = $type;
		$this->declaredInstances[$instanceName]["fieldProperties"][$paramName]["metadata"] = $metadata;
	}

	/**
	 * Binds a parameter to the instance using a setter.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @param string $paramValue
	 * @param string $type Can be one of "string|config|request|session"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameterViaSetter($instanceName, $setterName, $paramValue, $type = "string", array $metadata = array()) {
		if ($type != "string" && $type != "config" && $type != "request" && $type != "session" && $type != "php") {
			throw new MoufException("Invalid type. Must be one of: string|config|request|session");
		}

		$this->declaredInstances[$instanceName]["setterProperties"][$setterName]["value"] = $paramValue;
		$this->declaredInstances[$instanceName]["setterProperties"][$setterName]["type"] = $type;
		$this->declaredInstances[$instanceName]["setterProperties"][$setterName]["metadata"] = $metadata;
	}

	/**
	 * Binds a parameter to the instance using a construcotr parameter.
	 *
	 * @param string $instanceName
	 * @param string $index
	 * @param string $paramValue
	 * @param string $parameterType Can be one of "primitive" or "object".
	 * @param string $type Can be one of "string|config|request|session"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameterViaConstructor($instanceName, $index, $paramValue, $parameterType, $type = "string", array $metadata = array()) {
		if ($type != "string" && $type != "config" && $type != "request" && $type != "session" && $type != "php") {
			throw new MoufException("Invalid type. Must be one of: string|config|request|session");
		}

		$this->declaredInstances[$instanceName]['constructor'][$index]["value"] = $paramValue;
		$this->declaredInstances[$instanceName]['constructor'][$index]["parametertype"] = $parameterType;
		$this->declaredInstances[$instanceName]['constructor'][$index]["type"] = $type;
		$this->declaredInstances[$instanceName]['constructor'][$index]["metadata"] = $metadata;
		
		// Now, let's make sure that all indexes BEFORE ours are set, and let's order everything by key.
		for ($i=0; $i<$index; $i++) {
			if (!isset($this->declaredInstances[$instanceName]['constructor'][$i])) {
				// If the parameter before does not exist, let's set it to null.
				$this->declaredInstances[$instanceName]['constructor'][$i]["value"] = null;
				$this->declaredInstances[$instanceName]['constructor'][$i]["parametertype"] = "primitive";
				$this->declaredInstances[$instanceName]['constructor'][$i]["type"] = "string";
				$this->declaredInstances[$instanceName]['constructor'][$i]["metadata"] = array();
			}
		}
		ksort($this->declaredInstances[$instanceName]['constructor']);
	}


	/**
	 * Unsets all the parameters (using a property or a setter) for the given instance.
	 *
	 * @param string $instanceName The instance to consider
	 */
	public function unsetAllParameters($instanceName) {
		unset($this->declaredInstances[$instanceName]["fieldProperties"]);
		unset($this->declaredInstances[$instanceName]["setterProperties"]);
		unset($this->declaredInstances[$instanceName]["fieldBinds"]);
		unset($this->declaredInstances[$instanceName]["setterBinds"]);
	}

	/**
	 * Returns the value for the given parameter.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @return mixed
	 */
	public function getParameter($instanceName, $paramName) {
		// todo: improve this
		if (isset($this->declaredInstances[$instanceName]['fieldProperties'][$paramName]['value'])) {
			return $this->declaredInstances[$instanceName]['fieldProperties'][$paramName]['value'];
		} else {
			return null;
		}
	}
	
	/**
	 * Returns true if the value of the given parameter is set.
	 * False otherwise.
	 * 
	 * @param string $instanceName
	 * @param string $paramName
	 * @return boolean
	 */
	public function isParameterSet($instanceName, $paramName) {
		return isset($this->declaredInstances[$instanceName]['fieldProperties'][$paramName]) || isset($this->declaredInstances[$instanceName]['fieldBinds'][$paramName]);
	}
	
	/**
	 * Completely unset this parameter from the DI container.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 */
	public function unsetParameter($instanceName, $paramName) {
		unset($this->declaredInstances[$instanceName]['fieldProperties'][$paramName]);
		unset($this->declaredInstances[$instanceName]['fieldBinds'][$paramName]);
	}

	/**
	 * Returns the value for the given parameter that has been set using a setter.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @return mixed
	 */
	public function getParameterForSetter($instanceName, $setterName) {
		// todo: improve this
		if (isset($this->declaredInstances[$instanceName]['setterProperties'][$setterName]['value'])) {
			return $this->declaredInstances[$instanceName]['setterProperties'][$setterName]['value'];
		} else {
			return null;
		}
	}
	
	/**
	 * Returns true if the value of the given setter parameter is set.
	 * False otherwise.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @return boolean
	 */
	public function isParameterSetForSetter($instanceName, $setterName) {
		return isset($this->declaredInstances[$instanceName]['setterProperties'][$setterName]) || isset($this->declaredInstances[$instanceName]['setterBinds'][$setterName]);
	}
	
	/**
	 * Completely unset this setter parameter from the DI container.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 */
	public function unsetParameterForSetter($instanceName, $setterName) {
		unset($this->declaredInstances[$instanceName]['setterProperties'][$setterName]);
		unset($this->declaredInstances[$instanceName]['setterBinds'][$setterName]);
	}

	/**
	 * Returns the value for the given parameter that has been set using a constructor.
	 *
	 * @param string $instanceName
	 * @param int $index
	 * @return mixed
	 */
	public function getParameterForConstructor($instanceName, $index) {
		if (isset($this->declaredInstances[$instanceName]['constructor'][$index]['value'])) {
			return $this->declaredInstances[$instanceName]['constructor'][$index]['value'];
		} else {
			return null;
		}
	}

	/**
	 * The type of the parameter for a constructor parameter. Can be one of "primitive" or "object".
	 * @param string $instanceName
	 * @param int $index
	 * @return string
	 */
	public function isConstructorParameterObjectOrPrimitive($instanceName, $index) {
		if (isset($this->declaredInstances[$instanceName]['constructor'][$index]['parametertype'])) {
			return $this->declaredInstances[$instanceName]['constructor'][$index]['parametertype'];
		} else {
			return null;
		}
	}

	/**
	 * Returns true if the value of the given constructor parameter is set.
	 * False otherwise.
	 *
	 * @param string $instanceName
	 * @param int $index
	 * @return boolean
	 */
	public function isParameterSetForConstructor($instanceName, $index) {
		return isset($this->declaredInstances[$instanceName]['constructor'][$index]);
	}
	
	/**
	 * Completely unset this constructor parameter from the DI container.
	 *
	 * @param string $instanceName
	 * @param int $index
	 */
	public function unsetParameterForConstructor($instanceName, $index) {
		if (isset($this->declaredInstances[$instanceName]['constructor'])) {
			$max = count($this->declaredInstances[$instanceName]['constructor']);
			if($index != $max - 1) {
				// It is forbidden to unset a parameter that is not the last.
				// Let set null
				$this->setParameterViaConstructor($instanceName, $index, null, 'primitive');
			}
			else {
				unset($this->declaredInstances[$instanceName]['constructor'][$index]);
			}
		}
	}
	

	/**
	 * Returns the type for the given parameter (can be one of "string", "config", "session" or "request")
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @return string
	 */
	public function getParameterType($instanceName, $paramName) {
		if (isset($this->declaredInstances[$instanceName]['fieldProperties'][$paramName]['type'])) {
			return $this->declaredInstances[$instanceName]['fieldProperties'][$paramName]['type'];
		} else {
			return null;
		}
	}

	/**
	 * Returns the type for the given parameter that has been set using a setter (can be one of "string", "config", "session" or "request")
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @return string
	 */
	public function getParameterTypeForSetter($instanceName, $setterName) {
		if (isset($this->declaredInstances[$instanceName]['setterProperties'][$setterName]['type'])) {
			return $this->declaredInstances[$instanceName]['setterProperties'][$setterName]['type'];
		} else {
			return null;
		}
	}

	/**
	 * Returns the type for the given parameter that has been set using a setter (can be one of "string", "config", "session" or "request")
	 *
	 * @param string $instanceName
	 * @param int $index
	 * @return string
	 */
	public function getParameterTypeForConstructor($instanceName, $index) {
		if (isset($this->declaredInstances[$instanceName]['constructor'][$index]['type'])) {
			return $this->declaredInstances[$instanceName]['constructor'][$index]['type'];
		} else {
			return null;
		}
	}

	/**
	 * Sets the type for the given parameter (can be one of "string", "config", "session" or "request")
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @param string $type
	 */
	public function setParameterType($instanceName, $paramName, $type) {
		$this->declaredInstances[$instanceName]['fieldProperties'][$paramName]['type'] = $type;
	}

	/**
	 * Sets the type for the given parameter that has been set using a setter (can be one of "string", "config", "session" or "request")
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @param string $type
	 */
	public function setParameterTypeForSetter($instanceName, $setterName, $type) {
		$this->declaredInstances[$instanceName]['setterProperties'][$setterName]['type'] = $type;
	}

	/**
	 * Sets the type for the given parameter that has been set using a constructor parameter (can be one of "string", "config", "session" or "request")
	 *
	 * @param string $instanceName
	 * @param int $index
	 * @param string $type
	 */
	public function setParameterTypeForConstructor($instanceName, $index, $type) {
		$this->declaredInstances[$instanceName]['constructor'][$index]['type'] = $type;
	}

	/**
	 * Returns the metadata for the given parameter.
	 * Metadata is an array of key=>value, containing additional info.
	 * For instance, it could contain information on the way to represent a field in the UI, etc...
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @return string
	 */
	public function getParameterMetadata($instanceName, $paramName) {
		if (isset($this->declaredInstances[$instanceName]['fieldProperties'][$paramName]['metadata'])) {
			return $this->declaredInstances[$instanceName]['fieldProperties'][$paramName]['metadata'];
		} else {
			return array();
		}
	}

	/**
	 * Returns the metadata for the given parameter that has been set using a setter.
	 * Metadata is an array of key=>value, containing additional info.
	 * For instance, it could contain information on the way to represent a field in the UI, etc...
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @return string
	 */
	public function getParameterMetadataForSetter($instanceName, $setterName) {
		if (isset($this->declaredInstances[$instanceName]['setterProperties'][$setterName]['metadata'])) {
			return $this->declaredInstances[$instanceName]['setterProperties'][$setterName]['metadata'];
		} else {
			return array();
		}
	}

	/**
	 * Returns the metadata for the given parameter that has been set using a constructor parameter.
	 * Metadata is an array of key=>value, containing additional info.
	 * For instance, it could contain information on the way to represent a field in the UI, etc...
	 *
	 * @param string $instanceName
	 * @param int $index
	 * @return string
	 */
	public function getParameterMetadataForConstructor($instanceName, $index) {
		if (isset($this->declaredInstances[$instanceName]['constructor'][$index]['metadata'])) {
			return $this->declaredInstances[$instanceName]['constructor'][$index]['metadata'];
		} else {
			return array();
		}
	}






	/**
	 * Returns true if the param is set for the given instance.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @return boolean
	 */
	public function hasParameter($instanceName, $paramName) {
		// todo: improve this
		return isset($this->declaredInstances[$instanceName]['fieldProperties'][$paramName]);
	}

	/**
	 * Returns true if the param is set for the given instance using a setter.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @return boolean
	 */
	public function hasParameterForSetter($instanceName, $setterName) {
		// todo: improve this
		return isset($this->declaredInstances[$instanceName]['setterProperties'][$setterName]);
	}

	/**
	 * Binds another instance to the instance.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @param string $paramValue the name of the instance to bind to.
	 */
	public function bindComponent($instanceName, $paramName, $paramValue) {
		if ($paramValue == null) {
			unset($this->declaredInstances[$instanceName]["fieldBinds"][$paramName]);
		} else {
			$this->declaredInstances[$instanceName]["fieldBinds"][$paramName] = $paramValue;
		}
	}

	/**
	 * Binds another instance to the instance via a setter.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @param string $paramValue the name of the instance to bind to.
	 */
	public function bindComponentViaSetter($instanceName, $setterName, $paramValue) {
		if ($paramValue == null) {
			unset($this->declaredInstances[$instanceName]["setterBinds"][$setterName]);
		} else {
			$this->declaredInstances[$instanceName]["setterBinds"][$setterName] = $paramValue;
		}
	}

	/**
	 * Binds an array of instance to the instance.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @param array $paramValue an array of names of instance to bind to.
	 */
	public function bindComponents($instanceName, $paramName, $paramValue) {
		if ($paramValue == null) {
			unset($this->declaredInstances[$instanceName]["fieldBinds"][$paramName]);
		} else {
			$this->declaredInstances[$instanceName]["fieldBinds"][$paramName] = $paramValue;
		}
	}

	/**
	 * Binds an array of instance to the instance via a setter.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @param array $paramValue an array of names of instance to bind to.
	 */
	public function bindComponentsViaSetter($instanceName, $setterName, $paramValue) {
		if ($paramValue == null) {
			unset($this->declaredInstances[$instanceName]["setterBinds"][$setterName]);
		} else {
			$this->declaredInstances[$instanceName]["setterBinds"][$setterName] = $paramValue;
		}
	}
	
	/**
	 * Creates a NEW container (will reset any container with same settings).
	 * A container is made of 2 parts: a config file, and a class (representing the container with all instances loaded).
	 * 
	 * @param string $configFile
	 * @param string $className
	 * @param string $classFile (optional) The path to the generated class. If not passed, the path will be infered from composer autoloading settings.
	 */
	public static function createContainer($configFile, $className, $classFile = null) {
		$container = new MoufContainer($configFile, $className, new MoufReflectionClassManager(), null, $classFile);
		$container->write($configFile, $className, $classFile);		
	}
	
	/**
	 * Tests if a file can be created.
	 * Will create the file's directory if needed with 775 rights.
	 * Will throw an exception if file cannot be created.
	 * 
	 * @param string $file
	 */
	private static function setupFile($filename) {
		$fs = new Filesystem();
		
		$dirname = dirname($filename);
		
		if (!is_dir($dirname)) {
			try {
				$fs->mkdir($dirname, 0775);
			} catch (IOExceptionInterface $e) {
				throw new MoufException("An error occurred while creating your directory at '".$e->getPath()."'", 1, $e);
			}
		}
		
		if ($fs->exists($filename) && !is_writable($filename)) {
			throw new MoufException("Error, unable to write file '".$filename."'");
		}
		
		if (!$fs->exists($filename) && !is_writable($dirname)) {
			throw new MoufException("Error, unable to write a file in directory '".$dirname."'");
		}
	}

	/**
	 * This function saves the container configuration and generates a static class file to access instances (TODO: remove/change/optimize this).
	 * The configuration is save in the file it was loaded from, unless another file name is passed in 
	 * parameter.
	 * 
	 * @param string $fileName (optionnal): the file name of the configuration.
	 * @param string $className (optionnal): the name of the class generated.
	 * @param string $classFile (optionnal): the path to the class file, relative to the directory of this file (if the class has a namespace, this directory should not contain the namespace part). It should not end with a /.
	 * @throws MoufException
	 */
	public function write($filename = null, $className = null, $classFile = null) {
		$fs = new Filesystem();
		
		if ($filename === null) {
			$filename = $this->configFile;
		}
		if ($classFile === null) {
			if ($className === null) {
				$classFile = $this->getMainClassFile();
			} else {
				$classFile = self::getClassFileFromClassName($className);
			}
		}
		if ($className === null) {
			$className = $this->mainClassName;
		}
		
		self::setupFile($filename);
		self::setupFile($classFile);
		
		// Let's start by garbage collecting weak instances.
		$this->purgeUnreachableWeakInstances();

		//////////////////////// Lets generate the Config file ///////////////////////
		// Declare all components in one instruction
		$internalDeclaredInstances = array();
		foreach ($this->declaredInstances as $name=>$declaredInstance) {
			if (!isset($declaredInstance["external"]) || !$declaredInstance["external"]) {
				$internalDeclaredInstances[$name] = $declaredInstance;
			}
		}
		
		// Sort all instances by key. This way, new instances are not added at the end of the array,
		// and this reduces the number of conflicts when working in team with a version control system.
		ksort($internalDeclaredInstances);
		
		$code = "<?php
/**
 * This is a file automatically generated by the Mouf framework and contains
 * the list of instances of the container.
 *
 * Unless you know what you are doing, do not modify it, as it could be overwritten.
 */
		
return ".var_export($internalDeclaredInstances, true).";\n";
		
		$fs->dumpFile($filename, $code);
		
		//////////////////////// Lets generate the class file ///////////////////////
		$namespace = ClassNameUtils::getNamespace($className);
		$shortClassName = ClassNameUtils::getClassName($className);
		
		$classCode = "<?php
/**
 * This is a file automatically generated by the Mouf framework and contains
 * the class representing the container.
 *
 * Do not modify it, as it could be overwritten.
 */
";
		if ($namespace) {
			$classCode .= "namespace $namespace;\n\n";
		}


		$classCode .= "
use Interop\Container\ContainerInterface;
use Mouf\MoufContainer;
use Mouf\Reflection\ReflectionClassManagerInterface;

class $shortClassName extends MoufContainer {

    public function __construct(ContainerInterface \$delegateLookupContainer = null, ReflectionClassManagerInterface \$reflectionClassManager = null) {
        parent::__construct(__DIR__.".var_export("/".$fs->makePathRelative(dirname($filename), dirname($classFile)).basename($filename), true).", __CLASS__, \$reflectionClassManager, \$delegateLookupContainer);
    }

";
		// Now, let's export the closures!
		/***** closures Start ******/
		$classCode .= "    protected function _getClosures() {
		return [\n";
		
		$targetArray = [];
		foreach ($internalDeclaredInstances as $instanceName=>$instanceDesc) {
			if (isset($instanceDesc['constructor'])) {
				foreach ($instanceDesc['constructor'] as $key=>$param) {
					if ($param['type'] == 'php') {
						try {
							CodeValidatorService::validateCode($param['value']);
							$targetArray[$instanceName]['constructor'][$key] = $param['value'];
						} catch (\PhpParser\Error $ex) {
							error_log("Error in callback declared code for instance '$instanceName', constructor argument '$key': ".$ex->getMessage());
							$targetArray[$instanceName]['constructor'][$key] = $ex;
						}
					}
				}
			}
			if (isset($instanceDesc['fieldProperties'])) {
				foreach ($instanceDesc['fieldProperties'] as $key=>$param) {
					if ($param['type'] == 'php') {
						try {
							CodeValidatorService::validateCode($param['value']);
							$targetArray[$instanceName]['fieldProperties'][$key] = $param['value'];
						} catch (\PhpParser\Error $ex) {
							error_log("Error in callback declared code for instance '$instanceName', public property '$key': ".$ex->getMessage());
							$targetArray[$instanceName]['fieldProperties'][$key] = $ex;
						}
					}
				}
			}
			if (isset($instanceDesc['setterProperties'])) {
				foreach ($instanceDesc['setterProperties'] as $key=>$param) {
					if ($param['type'] == 'php') {
						try {
							CodeValidatorService::validateCode($param['value']);
							$targetArray[$instanceName]['setterProperties'][$key] = $param['value'];
						} catch (\PhpParser\Error $ex) {
							error_log("Error in callback declared code for instance '$instanceName', setter '$key': ".$ex->getMessage());
							$targetArray[$instanceName]['fieldProperties'][$key] = $ex;
						}
					}
				}
			}
			if (isset($instanceDesc['code']) && !isset($instanceDesc['error'])) {
				$targetArray[$instanceName] = $instanceDesc['code'];
			}
		}
		foreach ($targetArray as $instanceName=>$instanceDesc) {
			// If the whole instance is a PHP declaration
			if (is_string($instanceDesc)) {
				$classCode .=  "            ".var_export($instanceName, true)." => function(ContainerInterface \$container) {\n				";
				$classCode .= $instanceDesc;
				$classCode .= "\n            },\n";
			} else {
				// If properties are a PHP declaration
				$classCode .= "            ".var_export($instanceName, true)." => [\n";
				if (isset($instanceDesc['constructor'])) {
					$classCode .= "                'constructor' => [\n";
					foreach ($instanceDesc['constructor'] as $key=>$code) {
						$classCode .= "                    ".var_export($key, true)." => ";
						if (!$code instanceof \PhpParser\Error) {
							$classCode .= "function(ContainerInterface \$container) {\n                        ";
							$classCode .= $code;
							$classCode .= "\n                },\n";
						} else {
							// If the code is an exception we put the error message instead of a callback in the closures array
							$classCode .= var_export($code->getMessage(), true).",\n";
						}
					}
					$classCode .= "                ],\n";
				}
				if (isset($instanceDesc['fieldProperties'])) {
					$classCode .= "                'fieldProperties' => [\n";
					foreach ($instanceDesc['fieldProperties'] as $key=>$code) {
						$classCode .= "                    ".var_export($key, true)." => ";
						if (!$code instanceof \PhpParser\Error) {
							$classCode .= "function(ContainerInterface \$container) {\n                        ";
							$classCode .= $code;
							$classCode .= "\n                    },\n";
						} else {
							// If the code is an exception we put the error message instead of a callback in the closures array
							$classCode .= var_export($code->getMessage(), true).",\n";
						}
					}
					$classCode .= "                ],\n";
				}
				if (isset($instanceDesc['setterProperties'])) {
					$classCode .= "                'setterProperties' => [\n";
					foreach ($instanceDesc['setterProperties'] as $key=>$code) {
						$classCode .= "                    ".var_export($key, true)." => ";
						if (!$code instanceof \PhpParser\Error) {
							$classCode .= "function(ContainerInterface \$container) {\n                        ";
							$classCode .= $code;
							$classCode .= "\n                    },\n";
						} else {
							// If the code is an exception we put the error message instead of a callback in the closures array
							$classCode .= var_export($code->getMessage(), true).",\n";
						}
					}
					$classCode .= "                ],\n";
				}
				$classCode .= "            ],\n";
			}
		}
		
		$classCode .= "		];
	}\n";
		/***** closures end ******/
		
		
		$getters = array();
		foreach ($this->declaredInstances as $name=>$classDesc) {
			if (!isset($classDesc['class'])) {
				if (isset($classDesc['code'])) {
					continue;
				}
				throw new MoufException("No class for instance '$name'");
			}
			if (isset($classDesc['anonymous']) && $classDesc['anonymous']) {
				continue;
			}
			$className = $classDesc['class'];
			$getter = self::generateGetterString($name);
			if (isset($getters[strtolower($getter)])){
				$i = 0;
				while (isset($getters[strtolower($getter."_$i")])) {
					$i++;
				}
				$getter = $getter."_$i";
			}
			$getters[strtolower($getter)] = true;
			$classCode .= "    /**\n";
			$classCode .= "     * @return $className\n";
			$classCode .= "     */\n";
			$classCode .= "    public function ".$getter."() {\n";
			$classCode .= "        return \$this->get(".var_export($name,true).");\n";
			$classCode .= "    }\n\n";
		}
		$classCode .= "}\n";
			
		$fs->dumpFile($classFile, $classCode);
	}

	/**
	 * Generate the string for the getter by uppercasing the first character and prepending "get".
	 *
	 * @param string $instanceName
	 * @return string
	 */
	private function generateGetterString($instanceName) {
		$modInstance = str_replace(" ", "", $instanceName);
		$modInstance = str_replace("\n", "", $modInstance);
		$modInstance = str_replace("-", "", $modInstance);
		$modInstance = str_replace(".", "_", $modInstance);
		// Let's remove anything that is not an authorized character:
		$modInstance = preg_replace("/[^A-Za-z0-9_]/", "", $modInstance);


		return "get".strtoupper(substr($modInstance,0,1)).substr($modInstance,1);
	}

	/**
	 * Return all instances names whose instance type is (or extends or inherits) the provided $instanceType.
	 * Note: this will silently ignore any instance whose class cannot be found.
	 * Note: can only be used if an autoloader for the classes is available (so if we are in the
	 * scope of the application).
	 *
	 * @param string $instanceType
	 * @return array<string>
	 */
	public function findInstances($instanceType) {
		
		$instancesArray = array();

		$reflectionInstanceType = new \ReflectionClass($instanceType);
		$isInterface = $reflectionInstanceType->isInterface();

		foreach ($this->declaredInstances as $instanceName=>$classDesc) {
			if (!isset($classDesc['class'])) {
				continue;			
			}
			$className = $classDesc['class'];
			
			// Silently ignore any non existing class.
			if (!class_exists($className)) {
				continue;
			}
			
			// TODO: we can optimize this by storing in an array the result of a class name.
			$reflectionClass = new \ReflectionClass($className);
			if ($isInterface) {
				if ($reflectionClass->implementsInterface($instanceType)) {
					$instancesArray[] = $instanceName;
				}
			} else {
				if ($reflectionClass->isSubclassOf($instanceType) || $reflectionClass->getName() == $instanceType) {
					$instancesArray[] = $instanceName;
				}
			}
		}
		return $instancesArray;
	}

	/**
	 * Returns the name(s) of the component bound to instance $instanceName on property $propertyName.
	 *
	 * @param string $instanceName
	 * @param string $propertyName
	 * @return string or array<string> if there are many components.
	 */
	public function getBoundComponentsOnProperty($instanceName, $propertyName) {
		if (isset($this->declaredInstances[$instanceName]) && isset($this->declaredInstances[$instanceName]['fieldBinds']) && isset($this->declaredInstances[$instanceName]['fieldBinds'][$propertyName])) {
			return $this->declaredInstances[$instanceName]['fieldBinds'][$propertyName];
		}
		else
			return null;
	}

	/**
	 * Returns the name(s) of the component bound to instance $instanceName on setter $setterName.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @return string or array<string> if there are many components.
	 */
	public function getBoundComponentsOnSetter($instanceName, $setterName) {
		if (isset($this->declaredInstances[$instanceName]) && isset($this->declaredInstances[$instanceName]['setterBinds']) && isset($this->declaredInstances[$instanceName]['setterBinds'][$setterName]))
			return $this->declaredInstances[$instanceName]['setterBinds'][$setterName];
		else
			return null;
	}

	/**
	 * Returns the list of all components bound to that component.
	 *
	 * @param string $instanceName
	 * @return array<string, comp(s)> where comp(s) is a string or an array<string> if there are many components for that property. The key of the array is the name of the property.
	 */
	/*public function getBoundComponents($instanceName) {
		// FIXME: not accounting for components bound in constructor
		// it is likely this method is not used anymore
		// TODO: check usage and remove.
		$binds = array();
		if (isset($this->declaredInstances[$instanceName]) && isset($this->declaredInstances[$instanceName]['fieldBinds'])) {
			$binds = $this->declaredInstances[$instanceName]['fieldBinds'];
		}
		if (isset($this->declaredInstances[$instanceName]) && isset($this->declaredInstances[$instanceName]['setterBinds'])) {
			foreach ($this->declaredInstances[$instanceName]['setterBinds'] as $setter=>$bind) {
				$binds[MoufPropertyDescriptor::getPropertyNameFromSetterName($setter)] = $bind;
			}
		}
		return $binds;
	}*/

	/**
	 * Returns the list of instances that are pointing to this instance through one of their properties.
	 *
	 * @param string $instanceName
	 * @return array<string, string> The instances pointing to the passed instance are returned in key and in the value
	 */
	public function getOwnerComponents($instanceName) {
		$instancesList = array();

		foreach ($this->declaredInstances as $scannedInstance=>$instanceDesc) {
			if (isset($instanceDesc['fieldBinds'])) {
				foreach ($instanceDesc['fieldBinds'] as $declaredBindProperty) {
					if (is_array($declaredBindProperty)) {
						if (array_search($instanceName, $declaredBindProperty) !== false) {
							$instancesList[$scannedInstance] = $scannedInstance;
							break;
						}
					} elseif ($declaredBindProperty == $instanceName) {
						$instancesList[$scannedInstance] = $scannedInstance;
					}
				}
			}
		}

		foreach ($this->declaredInstances as $scannedInstance=>$instanceDesc) {
			if (isset($instanceDesc['setterBinds'])) {
				foreach ($instanceDesc['setterBinds'] as $declaredBindProperty) {
					if (is_array($declaredBindProperty)) {
						if (array_search($instanceName, $declaredBindProperty) !== false) {
							$instancesList[$scannedInstance] = $scannedInstance;
							break;
						}
					} elseif ($declaredBindProperty == $instanceName) {
						$instancesList[$scannedInstance] = $scannedInstance;
					}
				}
			}
		}
		
		foreach ($this->declaredInstances as $scannedInstance=>$instanceDesc) {
			if (isset($instanceDesc['constructor'])) {
				foreach ($instanceDesc['constructor'] as $declaredConstructorProperty) {
					if ($declaredConstructorProperty['parametertype']=='object') {
						$value = $declaredConstructorProperty['value'];
						if (is_array($value)) {
							if (array_search($instanceName, $value) !== false) {
								$instancesList[$scannedInstance] = $scannedInstance;
								break;
							}
						} elseif ($value == $instanceName) {
							$instancesList[$scannedInstance] = $scannedInstance;
						}
					}
				}
			}
		}

		return $instancesList;
	}

	/**
	 * Returns the name of a Mouf instance from the object.
	 * Note: this can be pretty slow as all instances are searched.
	 * FALSE is returned if nothing is found.
	 *
	 * @param object $instance
	 * @return string|false The name of the instance.
	 */
	public function findInstanceName($instance) {
		return array_search($instance, $this->objectInstances);
	}

	/**
	 * Duplicates an instance.
	 *
	 * @param string $srcInstanceName The name of the source instance.
	 * @param string $destInstanceName The name of the new instance.
	 */
	public function duplicateInstance($srcInstanceName, $destInstanceName) {
		if (!isset($this->declaredInstances[$srcInstanceName])) {
			throw new MoufException("Error while duplicating instance: unable to find source instance ".$srcInstanceName);
		}
		if ($destInstanceName == null) {
			if (!$this->isInstanceAnonymous($srcInstanceName)) {
				throw new MoufException("Error while duplicating instance: you need to give a destination name.");
			}
			$destInstanceName = $this->getFreeAnonymousName();
		}
		
		if (isset($this->declaredInstances[$destInstanceName])) {
			throw new MoufException("Error while duplicating instance: the dest instance already exists: ".$destInstanceName);
		}
		$this->declaredInstances[$destInstanceName] = $this->declaredInstances[$srcInstanceName];
		
		// TODO: special case: if an instance is pointing to itself, it might be a good idea to keep the copy
		// pointing to the copy instead of the original.
		
		// We should also recursively duplicate anonymous instances:
		if (isset($this->declaredInstances[$destInstanceName]["fieldBinds"])) {
			foreach ($this->declaredInstances[$destInstanceName]["fieldBinds"] as $key=>$boundInstance) {
				if (is_array($boundInstance)) {
					foreach ($boundInstance as $key2=>$item) {
						if ($this->isInstanceAnonymous($item)) {
							$this->declaredInstances[$destInstanceName]["fieldBinds"][$key][$key2] = $this->duplicateInstance($item);
						}
					}
				} else {
					if ($this->isInstanceAnonymous($boundInstance)) {
						$this->declaredInstances[$destInstanceName]["fieldBinds"][$key] = $this->duplicateInstance($boundInstance);
					}
				}
			}
		}

		if (isset($this->declaredInstances[$destInstanceName]["setterBinds"])) {
			foreach ($this->declaredInstances[$destInstanceName]["setterBinds"] as $key=>$boundInstance) {
				if (is_array($boundInstance)) {
					foreach ($boundInstance as $key2=>$item) {
						if ($this->isInstanceAnonymous($item)) {
							$this->declaredInstances[$destInstanceName]["setterBinds"][$key][$key2] = $this->duplicateInstance($item);
						}
					}
				} else {
					if ($this->isInstanceAnonymous($boundInstance)) {
						$this->declaredInstances[$destInstanceName]["setterBinds"][$key] = $this->duplicateInstance($boundInstance);
					}
				}
			}
		}
		
		if (isset($this->declaredInstances[$destInstanceName]["constructor"])) {
			foreach ($this->declaredInstances[$destInstanceName]["constructor"] as $index=>$parameter) {
				if ($parameter['parametertype'] == 'object' && $parameter['type'] == 'string') {
					$boundInstance = $parameter['value'];
					if (is_array($boundInstance)) {
						foreach ($boundInstance as $key2=>$item) {
							if ($this->isInstanceAnonymous($item)) {
								$this->declaredInstances[$destInstanceName]["constructor"][$index][$key2] = $this->duplicateInstance($item);
							}
						}
					} else {
						if ($this->isInstanceAnonymous($boundInstance)) {
							$this->declaredInstances[$destInstanceName]["constructor"][$index] = $this->duplicateInstance($boundInstance);
						}
					}
				}
			}
		}
				
		return $destInstanceName;
	}

	/**
	 * This function will delete any weak instance that would not be referred anymore.
	 * This is used to garbage-collect any unused weak instances.
	 * 
	 * This is public only for test purposes
	 */
	public function purgeUnreachableWeakInstances() {
		foreach ($this->declaredInstances as $key=>$instance) {
			if (!isset($instance['weak']) || $instance['weak'] == false) {
				$this->walkForGarbageCollection($key);
			}
		}

		// At this point any instance with the "noGarbageCollect" attribute should be kept. Others should be eliminated.
		$keptInstances = array();
		foreach ($this->declaredInstances as $key=>$instance) {
			if (isset($instance['noGarbageCollect']) && $instance['noGarbageCollect'] == true) {
				// Let's clear the flag
				unset($this->declaredInstances[$key]['noGarbageCollect']);
			} else {
				// Let's delete the weak instance
				unset($this->declaredInstances[$key]);
			}
		}
	}

	/**
	 * Recursive function that mark this instance as NOT garbage collectable and go through referred nodes.
	 *
	 * @param string $instanceName
	 */
	public function walkForGarbageCollection($instanceName) {
		// In case the instance does not exist (this could happen after a failed merge or a manual edit of MoufComponents.php...)
        if (!isset($this->declaredInstances[$instanceName])) {
            return;
        }
		$instance = &$this->declaredInstances[$instanceName];
		if (isset($instance['noGarbageCollect']) && $instance['noGarbageCollect'] == true) {
			// No need to go through already visited nodes.
			return;
		}

		$instance['noGarbageCollect'] = true;

		$declaredInstances = &$this->declaredInstances;
		$moufManager = $this;
		if (isset($instance['constructor'])) {
			foreach ($instance['constructor'] as $argument) {
				if ($argument["parametertype"] == "object") {
					$value = $argument["value"];
					if(is_array($value)) {
						array_walk_recursive($value, function($singleValue) use (&$declaredInstances, $moufManager) {
							if ($singleValue != null) {
								$moufManager->walkForGarbageCollection($singleValue);
							}
						});
						/*foreach ($value as $singleValue) {
							if ($singleValue != null) {
								$this->walkForGarbageCollection($this->declaredInstances[$singleValue]);
							}
						}*/
					}
					else {
						if ($value != null) {
							$this->walkForGarbageCollection($value);
						}
					}
				}

			}
		}
		if (isset($instance['fieldBinds'])) {
			foreach ($instance['fieldBinds'] as $prop) {
				if(is_array($prop)) {
					array_walk_recursive($prop, function($singleProp) use (&$declaredInstances, $moufManager) {
						if ($singleProp != null) {
							$moufManager->walkForGarbageCollection($singleProp);
						}
					});
							
					/*foreach ($prop as $singleProp) {
						if ($singleProp != null) {
							$this->walkForGarbageCollection($this->declaredInstances[$singleProp]);
						}
					}*/
				}
				else {
					$this->walkForGarbageCollection($prop);
				}
			}
		}
		if (isset($instance['setterBinds'])) {
			foreach ($instance['setterBinds'] as $prop) {
				if(is_array($prop)) {
					array_walk_recursive($prop, function($singleProp) use (&$declaredInstances, $moufManager) {
						if ($singleProp != null) {
							$moufManager->walkForGarbageCollection($singleProp);
						}
					});
					/*foreach ($prop as $singleProp) {
						if ($singleProp != null) {
							$this->walkForGarbageCollection($this->declaredInstances[$singleProp]);
						}
					}*/
					
				}
				else {
					$this->walkForGarbageCollection($prop);
				}
			}
		}
	}

	/**
	 * Returns true if the instance is week
	 *
	 * @param string $instanceName
	 * @return bool
	 */
	public function isInstanceWeak($instanceName) {
		if (isset($this->declaredInstances[$instanceName]['weak'])) {
			return $this->declaredInstances[$instanceName]['weak'];
		} else {
			return false;
		}
	}

	/**
	 * Decides whether an instance should be weak or not.
	 * @param string $instanceName
	 * @param bool $weak
	 */
	public function setInstanceWeakness($instanceName, $weak) {
		$this->declaredInstances[$instanceName]['weak'] = $weak;
	}


	/**
	 * Returns true if the instance is anonymous
	 *
	 * @param string $instanceName
	 * @return bool
	 */
	public function isInstanceAnonymous($instanceName) {
		if (isset($this->declaredInstances[$instanceName]['anonymous'])) {
			return $this->declaredInstances[$instanceName]['anonymous'];
		} else {
			return false;
		}
	}

	/**
	 * Decides whether an instance is anonymous or not.
	 * @param string $instanceName
	 * @param bool $anonymous
	 */
	public function setInstanceAnonymousness($instanceName, $anonymous) {
		if ($anonymous) {
			$this->declaredInstances[$instanceName]['anonymous'] = true;
			// An anonymous object must be weak.
			$this->declaredInstances[$instanceName]['weak'] = true;
		} else {
			unset($this->declaredInstances[$instanceName]['anonymous']);
		}
	}

	/**
	 * Returns an "anonymous" name for an instance.
	 * "anonymous" names start with "__anonymous__" and is followed by a number.
	 * This function will return a name that is not already used.
	 *
	 * @return string
	 */
	public function getFreeAnonymousName() {
		$i=rand();
		do {
			$anonName = "__anonymous__".UniqueIdService::getUniqueId()."_".$i;
			if (!isset($this->declaredInstances[$anonName])) {
				break;
			}
			$i++;
		} while (true);
		
		return $anonName;
	}

	/**
	 * An array of instanciated MoufInstanceDescriptor objects.
	 * These descriptors are created by getInstanceDescriptor or createInstance function.
	 *
	 * @var array<string, MoufInstanceDescriptor>
	 */
	private $instanceDescriptors;

	/**
	 * Returns an object describing the instance whose name is $name.
	 *
	 * @param string $name
	 * @return MoufInstanceDescriptor
	 */
	public function getInstanceDescriptor($name) {
		if (isset($this->instanceDescriptors[$name])) {
			return $this->instanceDescriptors[$name];
		} elseif (isset($this->declaredInstances[$name])) {
			$this->instanceDescriptors[$name] = new MoufInstanceDescriptor($this, $name);
			return $this->instanceDescriptors[$name];
		} else {
			throw new MoufException("Instance '".$name."' does not exist.");
		}
	}

	/**
	 * Creates a new instance and returns the instance descriptor.
	 * @param string $className The name of the class of the instance.
	 * @param int $mode Depending on the mode, the behaviour will be different if an instance with the same name already exists.
	 * @return MoufInstanceDescriptor
	 */
	public function createInstance($className, $mode = self::DECLARE_ON_EXIST_EXCEPTION) {
		// FIXME: mode is useless here! We are creating an anonymous instance!
		$className = ltrim($className, "\\");
		$name = $this->getFreeAnonymousName();
		$this->declareComponent($name, $className, false, $mode);
		$this->setInstanceAnonymousness($name, true);
		return $this->getInstanceDescriptor($name);
	}

	/**
	 * Returns the class in charge of managing the list of class descriptors.
	 * 
	 * @return \Mouf\Reflection\ReflectionClassManagerInterface
	 */
	public function getReflectionClassManager() {
		return $this->reflectionClassManager;
	}
	
	/**
	 * Creates a new instance declared by PHP code.
	 *
	 * @return MoufInstanceDescriptor
	 */
	public function createInstanceByCode() {
		$name = $this->getFreeAnonymousName();
	
		$this->declaredInstances[$name]["weak"] = false;
		$this->declaredInstances[$name]["comment"] = "";
		$this->declaredInstances[$name]["class"] = null;
		$this->declaredInstances[$name]["external"] = false;
		$this->declaredInstances[$name]["code"] = "";
		$this->setInstanceAnonymousness($name, true);
	
		return $this->getInstanceDescriptor($name);
	}
	
	/**
	 * For instance created via callback (created using `createInstanceByCode`),
	 * sets the PHP code to be executed to create the instances.
	 *
	 * @param string $instanceName
	 * @param string $code
	 */
	public function setCode($instanceName, $code) {
		if (!isset($this->declaredInstances[$instanceName])) {
			throw new MoufException("Instance '$instanceName' does not exist.");
		}
		if (!isset($this->declaredInstances[$instanceName]["code"])) {
			throw new MoufException("Instance '$instanceName' has not been created using `createInstanceByCode`. It cannot have a PHP code attached to it.");
		}
		$this->declaredInstances[$instanceName]["code"] = $code;
		$this->findInstanceByCallbackType($instanceName);
	}
	
	/**
	 * Returns a string containing the PHP code that will be executed to instantiate this instance (if
	 * PHP code was passed using setCode), or "null" if this is a "normal" instance.
	 *
	 * @param string $instanceName
	 * @return string|NULL
	 */
	public function getCode($instanceName) {
		if (isset($this->declaredInstances[$instanceName]["code"])) {
			return $this->declaredInstances[$instanceName]["code"];
		}
		return null;
	}
	
	/**
	 * Returns any error that would have been triggered by executing the php code that creates the instance (if
	 * PHP code was passed using setCode), or "null" if this is a "normal" instance or there is no error.
	 *
	 * @param string $instanceName
	 * @return string
	 */
	public function getErrorOnInstanceCode($instanceName) {
		if (isset($this->declaredInstances[$instanceName]["error"])) {
			return $this->declaredInstances[$instanceName]["error"];
		}
		return null;
	}
	
	/**
	 * Returns the type of an instance defined by callback.
	 * For this, the instanciation code will be executed and the result will be returned.
	 *
	 * @param string $instanceName The name of the instance to analyze.
	 * @return string
	 */
	private function findInstanceByCallbackType($instanceName) {
		// Note: we execute the code in another thread. Always.
		// This prevent crashing the main thread.
		try {
			$fullyQualifiedClassName = MoufReflectionProxy::getReturnTypeFromCode($this->declaredInstances[$instanceName]["code"], $this->getScope() == self::SCOPE_ADMIN);
			unset($this->declaredInstances[$instanceName]["error"]);
			unset($this->declaredInstances[$instanceName]["constructor"]);
			unset($this->declaredInstances[$instanceName]["fieldProperties"]);
			unset($this->declaredInstances[$instanceName]["setterProperties"]);
			unset($this->declaredInstances[$instanceName]["fieldBinds"]);
			unset($this->declaredInstances[$instanceName]["setterBinds"]);
			$this->declaredInstances[$instanceName]["class"] = $fullyQualifiedClassName;
		} catch (\Exception $e) {
			$this->declaredInstances[$instanceName]["error"] = $e->getMessage();
			unset($this->declaredInstances[$instanceName]["class"]);
			$fullyQualifiedClassName = null;
		}
		return $fullyQualifiedClassName;
	}
	
	/**
	 * Returns the list of public properties' names configured for this instance.
	 *
	 * @param string $instanceName
	 * @return string[]
	 */
	public function getParameterNames($instanceName) {
		return array_merge(
				isset($this->declaredInstances[$instanceName]["fieldProperties"])?array_keys($this->declaredInstances[$instanceName]["fieldProperties"]):array(),
				isset($this->declaredInstances[$instanceName]["fieldBinds"])?array_keys($this->declaredInstances[$instanceName]["fieldBinds"]):array()
		);
	}
	
	/**
	 * Returns the list of setters' names configured for this instance.
	 *
	 * @param string $instanceName
	 * @return string[]
	 */
	public function getParameterNamesForSetter($instanceName) {
		return array_merge(
				isset($this->declaredInstances[$instanceName]["setterProperties"])?array_keys($this->declaredInstances[$instanceName]["setterProperties"]):array(),
				isset($this->declaredInstances[$instanceName]["setterBinds"])?array_keys($this->declaredInstances[$instanceName]["setterBinds"]):array()
		);
	}
	
	/**
	 * Returns the list of constructor parameters (index position of the parameter) configured for this instance.
	 *
	 * @param string $instanceName
	 * @return int[]
	 */
	public function getParameterNamesForConstructor($instanceName) {
		if (isset($this->declaredInstances[$instanceName]["constructor"])) {
			return array_keys($this->declaredInstances[$instanceName]["constructor"]);
		} else {
			return array();
		}
	}
}
?>