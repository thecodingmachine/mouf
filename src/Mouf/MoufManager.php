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

use Mouf\Composer\ComposerService;
use Mouf\Reflection\MoufReflectionProxy;
use Mouf\Reflection\MoufReflectionClass;
use Interop\Container\ContainerInterface;

/**
 * The class managing object instanciation in the Mouf framework.
 * Users should use the "Mouf" class instead.
 *
 */
class MoufManager implements ContainerInterface {
	const SCOPE_APP = 'app';
	const SCOPE_ADMIN = 'admin';

	const DECLARE_ON_EXIST_EXCEPTION = 'exception';
	const DECLARE_ON_EXIST_KEEP_INCOMING_LINKS = 'keepincominglinks';
	const DECLARE_ON_EXIST_KEEP_ALL = 'keepall';

	/**
	 * The default instance of the MoufManager.
	 *
	 * @var MoufManager
	 */
	private static $defaultInstance;

	/**
	 * The hidden instance of the MoufManager.
	 * The hidden instance is used when there must be more than one instance of Mouf loaded.
	 * This happens for instance in the Mouf adminsitration screens:
	 * The Mouf admin components are stored in the default instance, while the configuration of the application
	 * being designed is stored in the hiddenInstance.
	 *
	 * @var MoufManager
	 */
	private static $hiddenInstance;


	/**
	 * Returns the default instance of the MoufManager.
	 *
	 * @return MoufManager
	 */
	public static function getMoufManager() {
		return self::$defaultInstance;
	}

	/**
	 * Returns the hidden instance of the MoufManager.
	 * The hidden instance is used when there must be more than one instance of Mouf loaded.
	 * This happens for instance in the Mouf adminsitration screens:
	 * The Mouf admin components are stored in the default instance, while the configuration of the application
	 * being designed is stored in the hiddenInstance.
	 *
	 * @return MoufManager
	 */
	public static function getMoufManagerHiddenInstance() {
		return self::$hiddenInstance;
	}

	/**
	 * Returns true if there is a hidden instance (which probably means we are in the Mouf admin console).
	 *
	 * @return boolean
	 */
	public static function hasHiddenInstance() {
		return (self::$hiddenInstance != null);
	}

	/**
	 * Instantiates the default instance of the MoufManager.
	 * Does nothing if the default instance is already instanciated.
	 */
	public static function initMoufManager() {
		if (self::$defaultInstance == null) {
			self::$defaultInstance = new MoufManager();
			self::$defaultInstance->configManager = new MoufConfigManager("../../../../../config.php");
			self::$defaultInstance->componentsFileName = "../../../../../mouf/MoufComponents.php";
			//self::$defaultInstance->requireFileName = "../MoufRequire.php";
			self::$defaultInstance->adminUiFileName = "../../../../../mouf/MoufUI.php";
			self::$defaultInstance->mainClassName = "Mouf";
			// FIXME: not appscope for sure
			self::$defaultInstance->scope = MoufManager::SCOPE_APP;

		}
	}

	/**
	 * This function takes the whole configuration stored in the default instance of the Mouf framework
	 * and switches it in the hidden instance.
	 * The default instance is cleaned afterwards.
	 *
	 */
	public static function switchToHidden() {
		self::$hiddenInstance = self::$defaultInstance;
		self::$defaultInstance = new MoufManager();
		self::$defaultInstance->configManager = new MoufConfigManager("../../config.php");
		self::$defaultInstance->componentsFileName = "../../mouf/MoufComponents.php";
		//self::$defaultInstance->requireFileName = "MoufAdminRequire.php";
		self::$defaultInstance->adminUiFileName = "../../mouf/MoufUI.php";
		self::$defaultInstance->mainClassName = "MoufAdmin";
		self::$defaultInstance->scope = MoufManager::SCOPE_ADMIN;
	}

	public function __construct() {
		// Unless the setDelegateLookupContainer is set, we lookup dependencies inside our own container.
		$this->delegateLookupContainer = $this;
	}

	/**
	 * If set, all dependencies lookup will be delegated to this container.
	 *
	 * @var ContainerInterface
	 */
	protected $delegateLookupContainer;

	/**
	 * The config manager (that writes the config.php file).
	 *
	 * @var MoufConfigManager
	 */
	private $configManager;

	/**
	 * The array of component instances managed by mouf.
	 * The objects in this array have been already instanciated.
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
	 * A list of components name that are external.
	 * External components are not saved when the rewriteMouf method is called.
	 * They are useful for declaring components instances that should not be modified.
	 *
	 * @var array<string>
	 */
	private $externalComponents = array();


	/**
	 * A list of variables that are stored in Mouf. Variables can contain anything, and are used by some modules for different
	 * purposes. For instance, the list of repositories is stored as a variables, etc...
	 *
	 * @var array<string, mixed>
	 */
	private $variables = array();

	/**
	 * The name of the file that contains the components declarations
	 *
	 * @var string
	 */
	private $componentsFileName;

	/**
	 * The name of the file that contains the "requires" on the components
	 *
	 * @var string
	 */
	private $requireFileName;

	/**
	 * The scope for the MoufManager.
	 * Can be one of MoufManager::SCOPE_APP (the main application) or MoufManager::SCOPE_ADMIN (the Mouf instance for the admin)
	 *
	 * @var string
	 */
	private $scope;

	/**
	 * The name of the file that contains the "requires" on the components for the admin part of Mouf
	 *
	 * @var string
	 */
	private $adminUiFileName;

	/**
	 * The name of the main class that will be generated (by default: Mouf)
	 *
	 * @var string
	 */
	private $mainClassName;

	/**
	 * The instance of the main class
	 *
	 * @var string
	 */
	private $mainClass;

	/**
	 * Returns the config manager (the service in charge of writing the config.php file).
	 *
	 * @return MoufConfigManager
	 */
	public function getConfigManager() {
		return $this->configManager;
	}

	/**
	 * Returns the instance of the specified object.
	 *
	 * @param string $instanceName
	 * @return object
	 */
	public function get($instanceName) {
		if (!isset($this->objectInstances[$instanceName]) || $this->objectInstances[$instanceName] == null) {
			$this->instantiateComponent($instanceName);
		}
		return $this->objectInstances[$instanceName];
	}

	/**
	 * Returns the instance of the specified object.
	 * Alias of "get"
	 *
	 * @param string $instanceName
	 * @return object
	 */
	public function getInstance($instanceName) {
		return $this->get($instanceName);
	}

	/**
	 * Returns true if the instance name passed in parameter is defined in Mouf.
	 *
	 * @param string $instanceName
	 */
	public function instanceExists($instanceName) {
		return isset($this->declaredInstances[$instanceName]);
	}

	/**
	 * Returns true if the instance name passed in parameter is defined in Mouf.
	 * Alias of "instanceExists"
	 *
	 * @param string $instanceName
	 * @return bool
	 */
	public function has($instanceName) {
		return $this->instanceExists($instanceName);
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
			//if (!isset($classDesc["class"])) {var_dump($instanceName);var_dump($classDesc);}
			$arr[$instanceName] = isset($classDesc['class'])?$classDesc['class']:null;
		}
		return $arr;
	}

	/**
	 * Sets at one all the instances of all the components.
	 * This is used internally to load the state of Mouf very quickly.
	 * Do not use directly.
	 *
	 * @param array $definition A huge array defining all the declared instances definitions.
	 */
	public function addComponentInstances(array $definition) {
		$this->declaredInstances = array_merge($this->declaredInstances, $definition);
	}


	/**
	 * Declares a new component.
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

		$this->declaredInstances[$instanceName]["class"] = $className;
		$this->declaredInstances[$instanceName]["external"] = $external;
	}

	/**
	 * Removes an instance.
	 * Sets to null any property linking to that component.
	 *
	 * @param string $instanceName
	 */
	public function removeComponent($instanceName) {
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
	public function renameComponent($instanceName, $newInstanceName) {
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

	private function getClosures() {
		if ($this->mainClass === null){
			$this->mainClass = new $this->mainClassName();
		}
		if ($this->closures === null){
			$this->closures = $this->mainClass->getClosures();
		}

		return $this->closures;
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
				$closures = $this->getClosures();
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
									$closures = $this->getClosures();
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
				if (!isset($this->objectInstances[$instanceName])) {
						$object = $classDescriptor->newInstanceArgs($constructorParameters);
				} else {
						$object = $this->objectInstances[$instanceName];
				}
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
							$closures = $this->getClosures();
							$closure = $closures[$instanceName]['fieldProperties'][$key];
							if ($closure instanceof \Closure) {
								$closure = $closure->bindTo($object);
								$object->$key = $closure($this->delegateLookupContainer);
							} else {
								throw new MoufContainerException("Parse error in the callback of '$instanceName' property '$key': ".$closure);
							}
							break;
						default:
							throw new MoufContainerException("Invalid type '".$valueDef["type"]."' for object instance '$instanceName'.");
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
							$closures = $this->getClosures();
							$closure = $closures[$instanceName]['setterProperties'][$key];
							if ($closure instanceof \Closure) {
								$closure = $closure->bindTo($object);
							} else {
								throw new MoufContainerException("Parse error in the callback of '$instanceName' setter '$key': ".$closure);
							}
							$object->$key($closure($this->delegateLookupContainer));
							break;
						default:
							throw new MoufContainerException("Invalid type '".$valueDef["type"]."' for object instance '$instanceName'.");
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
			throw new MissingDependencyException("The object instance '".$instanceName."' could not be created because it depends on an object in error (".$e->getMissingInstanceName().")", 2, $instanceName, $e);
		}
		return $object;
	}

	/**
	 * Binds a parameter to the instance.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 * @param string $paramValue
	 * @param string $type Can be one of "string|config|request|session|php"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameter($instanceName, $paramName, $paramValue, $type = "string", array $metadata = array()) {
		if ($type != "string" && $type != "config" && $type != "request" && $type != "session" && $type != "php") {
			throw new MoufContainerException("Invalid type. Must be one of: string|config|request|session. Value passed: '".$type."'");
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
	 * @param string $type Can be one of "string|config|request|session|php"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameterViaSetter($instanceName, $setterName, $paramValue, $type = "string", array $metadata = array()) {
		if ($type != "string" && $type != "config" && $type != "request" && $type != "session" && $type != "php") {
			throw new MoufContainerException("Invalid type. Must be one of: string|config|request|session");
		}

		$this->declaredInstances[$instanceName]["setterProperties"][$setterName]["value"] = $paramValue;
		$this->declaredInstances[$instanceName]["setterProperties"][$setterName]["type"] = $type;
		$this->declaredInstances[$instanceName]["setterProperties"][$setterName]["metadata"] = $metadata;
	}

    /**
     * Binds a parameter to the instance using a constructor parameter.
     *
     * @param string $instanceName
     * @param string $index
     * @param string $paramValue
     * @param string $parameterType Can be one of "primitive" or "object".
     * @param string $type Can be one of "string|config|request|session|php"
     * @param array $metadata An array containing metadata
     * @throws MoufContainerException
     */
    public function setParameterViaConstructor($instanceName, $index, $paramValue, $parameterType, $type = "string", array $metadata = array()) {
        if ($type != "string" && $type != "config" && $type != "request" && $type != "session" && $type != "php") {
            throw new MoufContainerException("Invalid type. Must be one of: string|config|request|session");
        }

        $this->declaredInstances[$instanceName]['constructor'][$index] =
            array(
                "value" => $paramValue,
                "parametertype" => $parameterType,
                "type" => $type,
                "metadata" => $metadata
            );

        // Now, let's make sure that all indexes BEFORE ours are set, and let's order everything by key.
        for ($i=0; $i<$index; $i++) {
            if (!isset($this->declaredInstances[$instanceName]['constructor'][$i])) {
                // If the parameter before does not exist, let's set it to null.
                $this->declaredInstances[$instanceName]['constructor'][$i] =
                    array(
                        "value" => null,
                        "parametertype" => "primitive",
                        "type" => "string",
                        "metadata" => array()
                    );
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
	 * Returns the type for the given parameter that has been set using a setter (can be one of "string", "config", "session", "request" or "php")
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
	 * This function will rewrite the MoufComponents.php file and the MoufRequire.php file (or their "admin" counterpart)
	 * according to parameters stored in the MoufManager
	 */
	public function rewriteMouf() {
		//require_once('MoufPackageManager.php');

		if ((file_exists(dirname(__FILE__)."/".$this->componentsFileName) && !is_writable(dirname(__FILE__)."/".$this->componentsFileName)) || (!file_exists(dirname(__FILE__)."/".$this->componentsFileName) && !is_writable(dirname(dirname(__FILE__)."/".$this->componentsFileName)))) {
			$dirname = realpath(dirname(dirname(__FILE__)."/".$this->componentsFileName));
			$filename = basename(dirname(__FILE__)."/".$this->componentsFileName);
			throw new MoufContainerException("Error, unable to write file ".$dirname."/".$filename);
		}

		// Let's start by garbage collecting weak instances.
		$this->purgeUnreachableWeakInstances();

		$fp = fopen(dirname(__FILE__)."/".$this->componentsFileName, "w");
		fwrite($fp, "<?php\n");
		fwrite($fp, "/**\n");
		fwrite($fp, " * This is a file automatically generated by the Mouf framework. Do not modify it, as it could be overwritten.\n");
		fwrite($fp, " */\n");
		fwrite($fp, "use Mouf\MoufManager;\n");
		fwrite($fp, "use Interop\Container\ContainerInterface;\n");
		fwrite($fp, "MoufManager::initMoufManager();\n");
		fwrite($fp, "\$moufManager = MoufManager::getMoufManager();\n");
		fwrite($fp, "\n");
		fwrite($fp, "\$moufManager->getConfigManager()->setConstantsDefinitionArray(".var_export($this->getConfigManager()->getConstantsDefinitionArray(), true).");\n");
		fwrite($fp, "\n");

		// Import all variables
		fwrite($fp, "\$moufManager->setAllVariables(".var_export($this->variables, true).");\n");
		fwrite($fp, "\n");

		// Sort all instances by key. This way, new instances are not added at the end of the array,
		// and this reduces the number of conflicts when working in team with a version control system.
		ksort($this->declaredInstances);

		// Declare all components in one instruction
		$internalDeclaredInstances = array();
		foreach ($this->declaredInstances as $name=>$declaredInstance) {
			if (!isset($declaredInstance["external"]) || !$declaredInstance["external"]) {
				$internalDeclaredInstances[$name] = $declaredInstance;
			}
		}

		fwrite($fp, "\$moufManager->addComponentInstances(".var_export($internalDeclaredInstances, true).");\n");
		fwrite($fp, "\n");

		fwrite($fp, "\n");

		fwrite($fp, "unset(\$moufManager);\n");
		fwrite($fp, "\n");

		fwrite($fp, "/**\n");
		fwrite($fp, " * This is the base class of the Manage Object User Friendly or Modular object user framework (MOUF) framework.\n");
		fwrite($fp, " * This object can be used to get the objects manage by MOUF.\n");
		fwrite($fp, " *\n");
		fwrite($fp, " */\n");
		fwrite($fp, "class $this->mainClassName {\n");
		fwrite($fp, "	");

		// Now, let's export the closures!
		/***** closures Start ******/
		fwrite($fp, "public function getClosures() {\n");
		fwrite($fp, "		return [\n");

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
				fwrite($fp, "			".var_export($instanceName, true)." => function(ContainerInterface \$container) {\n				");
				fwrite($fp, $instanceDesc);
				fwrite($fp, "\n			},\n");
			} else {
				// If properties are a PHP declaration
				fwrite($fp, "			".var_export($instanceName, true)." => [\n");
				if (isset($instanceDesc['constructor'])) {
					fwrite($fp, "				'constructor' => [\n");
					foreach ($instanceDesc['constructor'] as $key=>$code) {
						fwrite($fp, "					".var_export($key, true)." => ");
						if (!$code instanceof \PhpParser\Error) {
							fwrite($fp, "function(ContainerInterface \$container) {\n						");
							fwrite($fp, $code);
							fwrite($fp, "\n					},\n");
						} else {
							// If the code is an exception we put the error message instead of a callback in the closures array
							fwrite($fp, var_export($code->getMessage(), true).",\n");
						}
					}
					fwrite($fp, "				],\n");
				}
				if (isset($instanceDesc['fieldProperties'])) {
					fwrite($fp, "				'fieldProperties' => [\n");
					foreach ($instanceDesc['fieldProperties'] as $key=>$code) {
						fwrite($fp, "					".var_export($key, true)." => ");
						if (!$code instanceof \PhpParser\Error) {
							fwrite($fp, "function(ContainerInterface \$container) {\n						");
							fwrite($fp, $code);
							fwrite($fp, "\n					},\n");
						} else {
							// If the code is an exception we put the error message instead of a callback in the closures array
							fwrite($fp, var_export($code->getMessage(), true).",\n");
						}
					}
					fwrite($fp, "				],\n");
				}
				if (isset($instanceDesc['setterProperties'])) {
					fwrite($fp, "				'setterProperties' => [\n");
					foreach ($instanceDesc['setterProperties'] as $key=>$code) {
						fwrite($fp, "					".var_export($key, true)." => ");
						if (!$code instanceof \PhpParser\Error) {
							fwrite($fp, "function(ContainerInterface \$container) {\n						");
							fwrite($fp, $code);
							fwrite($fp, "\n					},\n");
						} else {
							// If the code is an exception we put the error message instead of a callback in the closures array
							fwrite($fp, var_export($code->getMessage(), true).",\n");
						}
					}
					fwrite($fp, "				],\n");
				}
				fwrite($fp, "			],\n");
			}
		}

		fwrite($fp, "		];\n");
		fwrite($fp, "	}\n");
		/***** closures end ******/


		$getters = array();
		foreach ($this->declaredInstances as $name=>$classDesc) {
			if (!isset($classDesc['class'])) {
				if (isset($classDesc['code'])) {
					continue;
				}
				throw new MoufContainerException("No class for instance '$name'");
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
			fwrite($fp, "	/**\n");
			fwrite($fp, "	 * @return $className\n");
			fwrite($fp, "	 */\n");
			fwrite($fp, "	 public static function ".$getter."() {\n");
			fwrite($fp, "	 	return MoufManager::getMoufManager()->get(".var_export($name,true).");\n");
			fwrite($fp, "	 }\n\n");
		}
		fwrite($fp, "}\n");


		fclose($fp);
		if (function_exists("opcache_invalidate")) {
			opcache_invalidate(dirname(__FILE__)."/".$this->componentsFileName);
		}

		// Note: rewriting MoufUI here is useless, since it is only modified on update or install of packages.
		$selfEdit = ($this->scope == MoufManager::SCOPE_ADMIN);
		$composerService = new ComposerService($selfEdit);
		$composerService->rewriteMoufUi();

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
	public function getBoundComponents($instanceName) {
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
	}

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
	 * Note: this quite be pretty slow as all instances are searched.
	 * FALSE is returned if nothing is found.
	 *
	 * @param object $instance
	 * @return string The name of the instance.
	 */
	public function findInstanceName($instance) {
		return array_search($instance, $this->objectInstances, true);
	}

	/**
	 * Duplicates an instance.
	 *
	 * @param string $srcInstanceName The name of the source instance.
	 * @param string $destInstanceName The name of the new instance (can be null if we are duplicating an anonymous instance)
	 * @return string the destination instance name
	 */
	public function duplicateInstance($srcInstanceName, $destInstanceName = null) {
		if (!isset($this->declaredInstances[$srcInstanceName])) {
			throw new MoufContainerException("Error while duplicating instance: unable to find source instance ".$srcInstanceName);
		}
		if ($destInstanceName == null) {
			if (!$this->isInstanceAnonymous($srcInstanceName)) {
				throw new MoufContainerException("Error while duplicating instance: you need to give a destination name.");
			}
			$destInstanceName = $this->getFreeAnonymousName();
		}

		if (isset($this->declaredInstances[$destInstanceName])) {
			throw new MoufContainerException("Error while duplicating instance: the dest instance already exists: ".$destInstanceName);
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
	 * Returns the value of a variable (or null if the variable is not set).
	 * Variables can contain anything, and are used by some modules for different
	 * purposes. For instance, the list of repositories is stored as a variables, etc...
	 *
	 * @param string $name
	 */
	public function getVariable($name) {
		if (isset($this->variables[$name])) {
			return $this->variables[$name];
		} else {
			return null;
		}
	}

	/**
	 * Returns whether the variable is set or not.
	 *
	 * @param string $name
	 */
	public function issetVariable($name) {
		return isset($this->variables[$name]);
	}


	/**
	 * Sets the value of a variable.
	 * Variables can contain anything, and are used by some modules for different
	 * purposes. For instance, the list of repositories is stored as a variables, etc...
	 *
	 * @param string $name
	 */
	public function setVariable($name, $value) {
		$this->variables[$name] = $value;
	}

	/**
	 * Sets all the variables, at once.
	 * Used at load time to initialize all variables.
	 *
	 * @param array $variables
	 */
	public function setAllVariables(array $variables) {
		$this->variables = $variables;
	}

	/**
	 * Returns the scope for this MoufManager.
	 * The scope can be one of MoufManager::SCOPE_APP (the main application) or MoufManager::SCOPE_ADMIN (the Mouf instance for the admin)
	 *
	 * @return string
	 */
	public function getScope() {
		return $this->scope;
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
	 * The number suffixing "__anonymous__" is returned in a random fashion. This way,
	 * VCS merges are easier to handle.
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
			throw new MoufContainerException("Instance '".$name."' does not exist.");
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
     * Alters the class of instance $instanceName.
     * Useful for migration purposes.
     *
     * @param string $instanceName
     * @param string $className
     * @throws MoufInstanceNotFoundException
     */
	public function alterClass($instanceName, $className) {
        if (!isset($this->declaredInstances[$instanceName])) {
            throw new MoufInstanceNotFoundException("Unable to find instance '".$instanceName."'.");
        }
        $this->declaredInstances[$instanceName]["class"] = $className;
    }

	/**
	 * A list of descriptors.
	 *
	 * @var array<string, MoufXmlReflectionClass>
	 */
	private $classDescriptors = array();

	/**
	 * Returns an object describing the class passed in parameter.
	 * This method should only be called in the context of the Mouf administration UI.
	 *
	 * @param string $className The name of the class to import
	 * @return MoufXmlReflectionClass
	 */
	public function getClassDescriptor($className) {
		if (!isset($this->classDescriptors[$className])) {
			if (MoufManager::getMoufManager() == null || (MoufManager::getMoufManager()->getScope() == self::SCOPE_APP && $this->getScope() == self::SCOPE_APP)
					|| (MoufManager::getMoufManager()->getScope() == self::SCOPE_ADMIN && $this->getScope() == self::SCOPE_ADMIN)) {
				// We are fully in the scope of the application:
				$this->classDescriptors[$className] = new MoufReflectionClass($className);
			} else {
				$this->classDescriptors[$className] = MoufReflectionProxy::getClass($className, $this->getScope() == self::SCOPE_ADMIN);
			}
		}
		return $this->classDescriptors[$className];
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
			throw new MoufContainerException("Instance '$instanceName' does not exist.");
		}
		if (!isset($this->declaredInstances[$instanceName]["code"])) {
			throw new MoufContainerException("Instance '$instanceName' has not been created using `createInstanceByCode`. It cannot have a PHP code attached to it.");
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
	 * If set, all dependencies lookup will be delegated to this container.
	 *
	 * @param ContainerInterface $delegateLookupContainer
	 */
	public function setDelegateLookupContainer(ContainerInterface $delegateLookupContainer) {
		$this->delegateLookupContainer = $delegateLookupContainer;
		return $this;
	}

	/**
	 * Check if there is no loop in constructor arguments
	 * @throws MoufContainerException
	 */
	public function checkConstructorLoop() {
	    foreach ($this->declaredInstances as $instanceName => $descriptor) {
	        $this->walkConstructorLoop($instanceName, []);
	    }
	}

	/**
	 * This take a instance name and the path to access.
	 * It throw an exception if a loop was detected
	 *
	 * @param string $instanceName
	 * @param array $path
	 * @throws MoufContainerException
	 */
	private function walkConstructorLoop($instanceName, array $path) {
	    if(isset($path[$instanceName])) {
	        $instances = array_keys($path);
	        $instances = array_slice($instances, array_search($instanceName, $instances));
	        throw new MoufContainerException('A loop was detected on constructor arguments '.implode(' -> ', $instances).' -> '.$instanceName);
	    }
	    $path[$instanceName] = true;
	    $descriptor = $this->declaredInstances[$instanceName];
	    if(isset($descriptor['constructor'])) {
    	    foreach ($descriptor['constructor'] as $constructorArg) {
    	        if($constructorArg['parametertype'] == 'object') {
    	            if(is_array($constructorArg['value'])) {
    	                foreach ($constructorArg['value'] as $subInstanceName) {
    	                    if($subInstanceName !== null) {
    	                        $this->walkConstructorLoop($subInstanceName, $path);
    	                    }
    	                }
    	            }
    	            else {
    	            	if($constructorArg['value'] !== null) {
    	                    $this->walkConstructorLoop($constructorArg['value'], $path);
    	            	}
    	            }
    	        }
    	    }
	    }
	}
}
