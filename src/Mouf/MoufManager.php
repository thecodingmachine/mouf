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
use Mouf\Reflection\MoufReflectionClassManager;
use Mouf\Reflection\MoufXmlReflectionClassManager;
use Interop\Container\ContainerInterface;
use Mouf\Composer\ClassNameMapper;

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
	 * 
	 * @param string $configFile
	 * @param string $className
	 * @param string $classFile Path to file containing the class, relative to ROOT_PATH.
	 * @throws MoufException
	 */
	public static function initMoufManager($configFile = null, $className = null, $classFile = null) {
		if (self::$defaultInstance == null) {
			if ($configFile == null) {
				// If we are here, we come from a Mouf 2.0 MoufComponents file.
				// We need to migrate to a new class name and all!
				
				// First, we need find a valid class name for the container (one that is autoloadable).
				$classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__.'/../../../../../composer.json');
				$possibleNamespaces = $classNameMapper->getManagedNamespaces();
				if (count($possibleNamespaces) == 0) {
					throw new MoufException("You are migrating from Mouf 2.0. Mouf needs to generate a new class that is 'autoloadable', but could not find a PSR-0 or PSR-4 directive in your composer.json file.");
				}
				$namespace = $possibleNamespaces[0];
				$className = $namespace."Container";
				
				$possibleFileNames = $classNameMapper->getPossibleFileNames($className);
				$classFile = $possibleFileNames[0];
				
				$configFile = "mouf/instances.php";
			}
			self::$defaultInstance = new MoufManager();
			self::$defaultInstance->configManager = new MoufConfigManager("../../../../../config.php");
			self::$defaultInstance->componentsFileName = "../../../../../mouf/MoufComponents.php";
			self::$defaultInstance->adminUiFileName = "../../../../../mouf/MoufUI.php";

			self::$defaultInstance->configFile = $configFile;
			self::$defaultInstance->className = $className;
			self::$defaultInstance->classFile = $classFile;
			
			self::$defaultInstance->container = new MoufContainer(__DIR__."/../../../../../".$configFile, $className, new MoufReflectionClassManager(), null, __DIR__.'/../../../../../'.$classFile);
			/*if (file_exists(__DIR__."/../../../../../mouf/instances.php")) {
				self::$defaultInstance->container->load(__DIR__."/../../../../../mouf/instances.php");
			}*/
			self::$defaultInstance->oldv20className = 'Mouf';

			// FIXME: not appscope for sure
			self::$defaultInstance->scope = MoufManager::SCOPE_APP;
			// Unless the setDelegateLookupContainer is set, we lookup dependencies inside our own container.
			self::$defaultInstance->delegateLookupContainer = self::$defaultInstance;
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
		self::$hiddenInstance->container->setReflectionClassManager(new MoufXmlReflectionClassManager());
		self::$defaultInstance = new MoufManager();
		self::$defaultInstance->configManager = new MoufConfigManager("../../config.php");
		self::$defaultInstance->componentsFileName = "../../mouf/MoufComponents.php";
		self::$defaultInstance->adminUiFileName = "../../mouf/MoufUI.php";
		self::$defaultInstance->scope = MoufManager::SCOPE_ADMIN;
		
		self::$defaultInstance->configFile = __DIR__."/../../mouf/instances.php";
		self::$defaultInstance->className = "Mouf\\AdminContainer";
		self::$defaultInstance->classFile = "src-dev/Mouf/AdminContainer.php";

		self::$defaultInstance->container = new MoufContainer(self::$defaultInstance->configFile, self::$defaultInstance->className, new MoufReflectionClassManager(), null, __DIR__."/../../".self::$defaultInstance->classFile);
		/*if (file_exists(__DIR__."/../../mouf/instances.php")) {
			self::$defaultInstance->container->load(__DIR__."/../../mouf/instances.php");
		}*/
		self::$defaultInstance->oldv20className = 'MoufAdmin';
		
		// Unless the setDelegateLookupContainer is set, we lookup dependencies inside our own container.
		self::$defaultInstance->delegateLookupContainer = self::$defaultInstance;
	}
	
	private $configFile;
	private $className;
	private $classFile;
	
	private $oldv20className;
	
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
	 * The DI container.
	 * 
	 * @var MoufContainer
	 */
	private $container;
	
	
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
	 * Returns the config manager (the service in charge of writing the config.php file).
	 *
	 * @return MoufConfigManager
	 */
	public function getConfigManager() {
		return $this->configManager;
	}
	
	/**
	 * Returns the default Mouf DI container
	 * @return \Mouf\MoufContainer
	 */
	public function getContainer() {
		return $this->container;
	}

	/**
	 * Returns the instance of the specified object.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @return object
	 */
	public function get($instanceName) {
		return $this->container->get($instanceName);
	}

	/**
	 * Returns the instance of the specified object.
	 * Alias of "get"
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @return object
	 */
	public function getInstance($instanceName) {
		return $this->container->get($instanceName);
	}
	
	/**
	 * Returns true if the instance name passed in parameter is defined in Mouf.
	 *
	 * @deprecated
	 * @param string $instanceName
	 */
	public function instanceExists($instanceName) {
		return $this->container->has($instanceName);
	}
	
	/**
	 * Returns true if the instance name passed in parameter is defined in Mouf.
	 * Alias of "instanceExists"
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @return bool
	 */
	public function has($instanceName) {
		return $this->container->has($instanceName);
	}

	/**
	 * Returns the list of all instances of objects in Mouf.
	 * Objects are not instanciated. Instead, a list containing the name of the instance in the key
	 * and the name of the class in the value is returned.
	 *
	 * @deprecated
	 * @return array<string, string>
	 */
	public function getInstancesList() {
		return $this->container->getInstancesList();
	}

	/**
	 * Sets at one all the instances of all the components.
	 * This is used internally to load the state of Mouf very quickly.
	 * Do not use directly.
	 *
	 * @deprecated
	 * @param array $definition A huge array defining all the declared instances definitions.
	 */
	public function addComponentInstances(array $definition) {
		$this->container->addComponentInstances($definition);
		
		// If we are here, we are on a MoufComponent from Mouf 2.0.
		// Let's rewrite the file!		
		$this->rewriteMouf();
		//$this->container->write();
	}
	

	/**
	 * Declares a new component.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $className
	 * @param boolean $external Whether the component is external or not. Defaults to false.
	 * @param int $mode Depending on the mode, the behaviour will be different if an instance with the same name already exists.
	 * @param bool $weak If the object is weak, it will be destroyed if it is no longer referenced.
	 */
	public function declareComponent($instanceName, $className, $external = false, $mode = self::DECLARE_ON_EXIST_EXCEPTION, $weak = false) {
		$this->container->declareComponent($instanceName, $className, $external, $mode, $weak);
	}

	/**
	 * Removes an instance.
	 * Sets to null any property linking to that component.
	 *
	 * @deprecated
	 * @param string $instanceName
	 */
	public function removeComponent($instanceName) {
		$this->container->removeInstance($instanceName);
	}

	/**
	 * Renames an instance.
	 * All properties are redirected to the new instance accordingly.
	 *
	 * @deprecated
	 * @param string $instanceName Old name
	 * @param string $newInstanceName New name
	 */
	public function renameComponent($instanceName, $newInstanceName) {
		$this->container->renameInstance($instanceName, $newInstanceName);
	}

	/**
	 * Return the type of the instance.
	 *
	 * @deprecated
	 * @param string $instanceName The instance name
	 * @return string The class name of the instance
	 */
	public function getInstanceType($instanceName) {
		return $this->container->getInstanceType($instanceName);
	}

	/**
	 * Binds a parameter to the instance.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @param string $paramValue
	 * @param string $type Can be one of "string|config|request|session|php"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameter($instanceName, $paramName, $paramValue, $type = "string", array $metadata = array()) {
		$this->container->setParameter($instanceName, $paramName, $paramValue, $type, $metadata);
	}

	/**
	 * Binds a parameter to the instance using a setter.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @param string $paramValue
	 * @param string $type Can be one of "string|config|request|session|php"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameterViaSetter($instanceName, $setterName, $paramValue, $type = "string", array $metadata = array()) {
		$this->container->setParameterViaSetter($instanceName, $setterName, $paramValue, $type, $metadata);
	}

	/**
	 * Binds a parameter to the instance using a constructor parameter.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $index
	 * @param string $paramValue
	 * @param string $parameterType Can be one of "primitive" or "object".
	 * @param string $type Can be one of "string|config|request|session|php"
	 * @param array $metadata An array containing metadata
	 */
	public function setParameterViaConstructor($instanceName, $index, $paramValue, $parameterType, $type = "string", array $metadata = array()) {
		$this->setParameterViaConstructor($instanceName, $index, $paramValue, $parameterType, $type, $metadata);
	}


	/**
	 * Unsets all the parameters (using a property or a setter) for the given instance.
	 *
	 * @deprecated
	 * @param string $instanceName The instance to consider
	 */
	public function unsetAllParameters($instanceName) {
		$this->container->unsetAllParameters($instanceName);
	}

	/**
	 * Returns the value for the given parameter.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @return mixed
	 */
	public function getParameter($instanceName, $paramName) {
		return $this->container->getParameter($instanceName, $paramName);
	}
	
	/**
	 * Returns true if the value of the given parameter is set.
	 * False otherwise.
	 * 
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @return boolean
	 */
	public function isParameterSet($instanceName, $paramName) {
		return $this->container->isParameterSet($instanceName, $paramName);
	}
	
	/**
	 * Completely unset this parameter from the DI container.
	 *
	 * @param string $instanceName
	 * @param string $paramName
	 */
	public function unsetParameter($instanceName, $paramName) {
		$this->container->unsetParameter($instanceName, $paramName);
	}

	/**
	 * Returns the value for the given parameter that has been set using a setter.
	 *
	 * @param string $instanceName
	 * @param string $setterName
	 * @return mixed
	 */
	public function getParameterForSetter($instanceName, $setterName) {
		return $this->container->getParameterForSetter($instanceName, $setterName);
	}
	
	/**
	 * Returns true if the value of the given setter parameter is set.
	 * False otherwise.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @return boolean
	 */
	public function isParameterSetForSetter($instanceName, $setterName) {
		return $this->container->isParameterSetForSetter($instanceName, $setterName);
	}
	
	/**
	 * Completely unset this setter parameter from the DI container.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 */
	public function unsetParameterForSetter($instanceName, $setterName) {
		$this->container->unsetParameterForSetter($instanceName, $setterName);
	}

	/**
	 * Returns the value for the given parameter that has been set using a constructor.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param int $index
	 * @return mixed
	 */
	public function getParameterForConstructor($instanceName, $index) {
		return $this->container->getParameterForConstructor($instanceName, $index);
	}

	/**
	 * The type of the parameter for a constructor parameter. Can be one of "primitive" or "object".
	 * 
	 * @deprecated
	 * @param string $instanceName
	 * @param int $index
	 * @return string
	 */
	public function isConstructorParameterObjectOrPrimitive($instanceName, $index) {
		return $this->container->isConstructorParameterObjectOrPrimitive($instanceName, $index);
	}

	/**
	 * Returns true if the value of the given constructor parameter is set.
	 * False otherwise.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param int $index
	 * @return boolean
	 */
	public function isParameterSetForConstructor($instanceName, $index) {
		return $this->container->isParameterSetForConstructor($instanceName, $index);
	}
	
	/**
	 * Completely unset this constructor parameter from the DI container.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param int $index
	 */
	public function unsetParameterForConstructor($instanceName, $index) {
		return $this->container->unsetParameterForConstructor($instanceName, $index);
	}	

	/**
	 * Returns the type for the given parameter (can be one of "string", "config", "session" or "request")
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @return string
	 */
	public function getParameterType($instanceName, $paramName) {
		return $this->container->getParameterType($instanceName, $paramName);
	}

	/**
	 * Returns the type for the given parameter that has been set using a setter (can be one of "string", "config", "session" or "request")
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @return string
	 */
	public function getParameterTypeForSetter($instanceName, $setterName) {
		return $this->container->getParameterTypeForSetter($instanceName, $setterName);
	}

	/**
	 * Returns the type for the given parameter that has been set using a setter (can be one of "string", "config", "session", "request" or "php")
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param int $index
	 * @return string
	 */
	public function getParameterTypeForConstructor($instanceName, $index) {
		return $this->container->getParameterForConstructor($instanceName, $index);
	}

	/**
	 * Sets the type for the given parameter (can be one of "string", "config", "session" or "request")
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @param string $type
	 */
	public function setParameterType($instanceName, $paramName, $type) {
		$this->container->setParameterType($instanceName, $paramName, $type);
	}

	/**
	 * Sets the type for the given parameter that has been set using a setter (can be one of "string", "config", "session" or "request")
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @param string $type
	 */
	public function setParameterTypeForSetter($instanceName, $setterName, $type) {
		return $this->container->setParameterTypeForSetter($instanceName, $setterName, $type);
	}

	/**
	 * Sets the type for the given parameter that has been set using a constructor parameter (can be one of "string", "config", "session" or "request")
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param int $index
	 * @param string $type
	 */
	public function setParameterTypeForConstructor($instanceName, $index, $type) {
		return $this->container->setParameterTypeForConstructor($instanceName, $index, $type);
	}

	/**
	 * Returns the metadata for the given parameter.
	 * Metadata is an array of key=>value, containing additional info.
	 * For instance, it could contain information on the way to represent a field in the UI, etc...
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @return string
	 */
	public function getParameterMetadata($instanceName, $paramName) {
		return $this->container->getParameterMetadata($instanceName, $paramName);
	}

	/**
	 * Returns the metadata for the given parameter that has been set using a setter.
	 * Metadata is an array of key=>value, containing additional info.
	 * For instance, it could contain information on the way to represent a field in the UI, etc...
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @return string
	 */
	public function getParameterMetadataForSetter($instanceName, $setterName) {
		return $this->container->getParameterMetadataForSetter($instanceName, $setterName);
	}

	/**
	 * Returns the metadata for the given parameter that has been set using a constructor parameter.
	 * Metadata is an array of key=>value, containing additional info.
	 * For instance, it could contain information on the way to represent a field in the UI, etc...
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param int $index
	 * @return string
	 */
	public function getParameterMetadataForConstructor($instanceName, $index) {
		return $this->container->getParameterMetadataForConstructor($instanceName, $index);
	}






	/**
	 * Returns true if the param is set for the given instance.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @return boolean
	 */
	public function hasParameter($instanceName, $paramName) {
		return $this->container->hasParameter($instanceName, $paramName);
	}

	/**
	 * Returns true if the param is set for the given instance using a setter.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @return boolean
	 */
	public function hasParameterForSetter($instanceName, $setterName) {
		return $this->container->hasParameterForSetter($instanceName, $setterName);
	}

	/**
	 * Binds another instance to the instance.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @param string $paramValue the name of the instance to bind to.
	 */
	public function bindComponent($instanceName, $paramName, $paramValue) {
		$this->container->bindComponent($instanceName, $paramName, $paramValue);
	}

	/**
	 * Binds another instance to the instance via a setter.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @param string $paramValue the name of the instance to bind to.
	 */
	public function bindComponentViaSetter($instanceName, $setterName, $paramValue) {
		$this->container->bindComponentsViaSetter($instanceName, $setterName, $paramValue);
	}

	/**
	 * Binds an array of instance to the instance.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $paramName
	 * @param array $paramValue an array of names of instance to bind to.
	 */
	public function bindComponents($instanceName, $paramName, $paramValue) {
		$this->container->bindComponents($instanceName, $paramName, $paramValue);
	}

	/**
	 * Binds an array of instance to the instance via a setter.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @param array $paramValue an array of names of instance to bind to.
	 */
	public function bindComponentsViaSetter($instanceName, $setterName, $paramValue) {
		$this->container->bindComponentsViaSetter($instanceName, $setterName, $paramValue);
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
			throw new MoufException("Error, unable to write file ".$dirname."/".$filename);
		}
		
		// Let's first write the container files.
		$this->container->write();
		
		// Then let's write the old "MoufComponents.php" file.
		$fp = fopen(dirname(__FILE__)."/".$this->componentsFileName, "w");
		fwrite($fp, "<?php\n");
		fwrite($fp, "/**\n");
		fwrite($fp, " * This is a file automatically generated by the Mouf framework. Do not modify it, as it could be overwritten.\n");
		fwrite($fp, " */\n");
		fwrite($fp, "use Mouf\MoufManager;\n");
		fwrite($fp, "use Interop\Container\ContainerInterface;\n");
		
		// TODO: idea: store this in a "di.php" file autogenerated from composer.json files on "composer update" (using an installer).
		fwrite($fp, "MoufManager::initMoufManager(".var_export($this->configFile,true).", ".var_export($this->className,true).", ".var_export($this->classFile,true).");\n");
		fwrite($fp, "\$moufManager = MoufManager::getMoufManager();\n");
		fwrite($fp, "\n");
		fwrite($fp, "\$moufManager->getConfigManager()->setConstantsDefinitionArray(".var_export($this->getConfigManager()->getConstantsDefinitionArray(), true).");\n");
		fwrite($fp, "\n");

		// Import all variables
		fwrite($fp, "\$moufManager->setAllVariables(".var_export($this->variables, true).");\n");
		fwrite($fp, "\n");

		fwrite($fp, "unset(\$moufManager);\n");
		
		fwrite($fp, '/**
 * Bridge class to enable compatibility with Mouf 2.0
 *
 * @deprecated
 */
class '.$this->oldv20className.' {
	public static function __callstatic($name, $arguments) {
		if (substr($name, 0, 3) == "get") {
			$moufManager = MoufManager::getMoufManager();
			$uppercaseInstanceName = substr($name, 3);
			if ($uppercaseInstanceName) {
				$lowercaseInstanceName = strtolower(substr($uppercaseInstanceName, 0 , 1)).substr($uppercaseInstanceName, 1);
				if ($moufManager->has($lowercaseInstanceName)) {
					return $moufManager->get($lowercaseInstanceName);
				}
				$lowercaseInstanceName = str_replace("_", ".", $lowercaseInstanceName);
				if ($moufManager->has($lowercaseInstanceName)) {
					return $moufManager->get($lowercaseInstanceName);
				}
				if ($moufManager->has($uppercaseInstanceName)) {
					return $moufManager->get($uppercaseInstanceName);
				}
				$uppercaseInstanceName = str_replace("_", ".", $uppercaseInstanceName);
				if ($moufManager->has($uppercaseInstanceName)) {
					return $moufManager->get($uppercaseInstanceName);
				}
			}
				
		}

		throw new Mouf\MoufException("Unknown method \'$name\' in '.$this->oldv20className.' class.");
	}
}');
		
		fwrite($fp, "\n");

		fclose($fp);
		
		// Note: rewriting MoufUI here is useless, since it is only modified on update or install of packages.
		$selfEdit = ($this->scope == MoufManager::SCOPE_ADMIN);
		$composerService = new ComposerService($selfEdit);
		$composerService->rewriteMoufUi();
	}

	/**
	 * Return all instances names whose instance type is (or extends or inherits) the provided $instanceType.
	 * Note: this will silently ignore any instance whose class cannot be found.
	 *
	 * @deprecated
	 * @param string $instanceType
	 * @return array<string>
	 */
	public function findInstances($instanceType) {
		return $this->container->findInstances($instanceType);
	}

	/**
	 * Returns the name(s) of the component bound to instance $instanceName on property $propertyName.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $propertyName
	 * @return string or array<string> if there are many components.
	 */
	public function getBoundComponentsOnProperty($instanceName, $propertyName) {
		return $this->container->getBoundComponentsOnProperty($instanceName, $propertyName);
	}

	/**
	 * Returns the name(s) of the component bound to instance $instanceName on setter $setterName.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @param string $setterName
	 * @return string or array<string> if there are many components.
	 */
	public function getBoundComponentsOnSetter($instanceName, $setterName) {
		return $this->container->getBoundComponentsOnSetter($instanceName, $setterName);
	}

	/**
	 * Returns the list of all components bound to that component.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @return array<string, comp(s)> where comp(s) is a string or an array<string> if there are many components for that property. The key of the array is the name of the property.
	 */
	public function getBoundComponents($instanceName) {
		// TODO: check usage and remove.
		return $this->container->getBoundComponents($instanceName);
	}

	/**
	 * Returns the list of instances that are pointing to this instance through one of their properties.
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @return array<string, string> The instances pointing to the passed instance are returned in key and in the value
	 */
	public function getOwnerComponents($instanceName) {
		return $this->container->getOwnerComponents($instanceName);
	}

	/**
	 * Returns the name of a Mouf instance from the object.
	 * Note: this quite be pretty slow as all instances are searched.
	 * FALSE is returned if nothing is found.
	 *
	 * @deprecated
	 * @param object $instance
	 * @return string The name of the instance.
	 */
	public function findInstanceName($instance) {
		return $this->container->findInstanceName($instance);
	}

	/**
	 * Duplicates an instance.
	 *
	 * @deprecated
	 * @param string $srcInstanceName The name of the source instance.
	 * @param string $destInstanceName The name of the new instance (can be null if we are duplicating an anonymous instance)
	 * @return string the destination instance name
	 */
	public function duplicateInstance($srcInstanceName, $destInstanceName) {
		return $this->container->duplicateInstance($srcInstanceName, $destInstanceName);
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
	 * 
	 * @deprecated
	 */
	public function purgeUnreachableWeakInstances() {
		$this->container->purgeUnreachableWeakInstances();
	}

	/**
	 * Returns true if the instance is week
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @return bool
	 */
	public function isInstanceWeak($instanceName) {
		return $this->container->isInstanceWeak($instanceName);
	}

	/**
	 * Decides whether an instance should be weak or not.
	 * 
	 * @deprecated
	 * @param string $instanceName
	 * @param bool $weak
	 */
	public function setInstanceWeakness($instanceName, $weak) {
		return $this->container->setInstanceWeakness($instanceName, $weak);
	}


	/**
	 * Returns true if the instance is anonymous
	 *
	 * @deprecated
	 * @param string $instanceName
	 * @return bool
	 */
	public function isInstanceAnonymous($instanceName) {
		return $this->container->isInstanceAnonymous($instanceName);
	}

	/**
	 * Decides whether an instance is anonymous or not.
	 * 
	 * @deprecated
	 * @param string $instanceName
	 * @param bool $anonymous
	 */
	public function setInstanceAnonymousness($instanceName, $anonymous) {
		return $this->container->setInstanceAnonymousness($instanceName, $anonymous);
	}

	/**
	 * Returns an "anonymous" name for an instance.
	 * "anonymous" names start with "__anonymous__" and is followed by a number.
	 * This function will return a name that is not already used.
	 * 
	 * The number suffixing "__anonymous__" is returned in a random fashion. This way,
	 * VCS merges are easier to handle.
	 *
	 * @deprecated
	 * @return string
	 */
	public function getFreeAnonymousName() {
		return $this->container->getFreeAnonymousName();
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
	 * @deprecated
	 * @param string $name
	 * @return MoufInstanceDescriptor
	 */
	public function getInstanceDescriptor($name) {
		return $this->container->getInstanceDescriptor($name);
	}

	/**
	 * Creates a new instance and returns the instance descriptor.
	 * 
	 * @deprecated
	 * @param string $className The name of the class of the instance.
	 * @param int $mode Depending on the mode, the behaviour will be different if an instance with the same name already exists.
	 * @return MoufInstanceDescriptor
	 */
	public function createInstance($className, $mode = self::DECLARE_ON_EXIST_EXCEPTION) {
		return $this->container->createInstance($className, $mode);
	}
	
	/**
	 * A list of descriptors.
	 *
	 * @var array<string, MoufXmlReflectionClass>
	 */
	//private $classDescriptors = array();

	/**
	 * Returns an object describing the class passed in parameter.
	 * This method should only be called in the context of the Mouf administration UI.
	 *
	 * @param string $className The name of the class to import
	 * @return MoufXmlReflectionClass
	 */
	/*public function getClassDescriptor($className) {
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
	}*/
	
	/**
	 * @deprecated
	 * @param unknown $name
	 */
	public function getClassDescriptor($name) {
		return $this->container->getReflectionClassManager()->getReflectionClass($name);
	}
	
	/**
	* Returns the list of public properties' names configured for this instance.
	*
	* @param string $instanceName
	* @return string[]
	*/
	public function getParameterNames($instanceName) {
		return $this->container->getParameterNames($instanceName);
	}
	
	/**
	* Returns the list of setters' names configured for this instance.
	*
	* @param string $instanceName
	* @return string[]
	*/
	public function getParameterNamesForSetter($instanceName) {
		return $this->container->getParameterNamesForSetter($instanceName);
	}
	
	/**
	* Returns the list of constructor parameters (index position of the parameter) configured for this instance.
	*
	* @param string $instanceName
	* @return int[]
	*/
	public function getParameterNamesForConstructor($instanceName) {
		return $this->container->getParameterNamesForConstructor($instanceName);
	}
	
	/**
	 * Creates a new instance declared by PHP code.
	 *
	 * @return MoufInstanceDescriptor
	 */
	public function createInstanceByCode() {
		return $this->container->createInstanceByCode();
	}
	
	/**
	 * For instance created via callback (created using `createInstanceByCode`), 
	 * sets the PHP code to be executed to create the instances.
	 * 
	 * @param string $instanceName
	 * @param string $code
	 */
	public function setCode($instanceName, $code) {
		return $this->container->setCode($instanceName, $code);
	}
	
	/**
	 * Returns a string containing the PHP code that will be executed to instantiate this instance (if 
	 * PHP code was passed using setCode), or "null" if this is a "normal" instance.
	 * 
	 * @param string $instanceName
	 * @return string|NULL
	 */
	public function getCode($instanceName) {
		return $this->container->getCode($instanceName);
	}
	
	/**
	 * Returns any error that would have been triggered by executing the php code that creates the instance (if 
	 * PHP code was passed using setCode), or "null" if this is a "normal" instance or there is no error.
	 * 
	 * @param string $instanceName
	 * @return string
	 */
	public function getErrorOnInstanceCode($instanceName) {
		return $this->container->getErrorOnInstanceCode($instanceName);
		
	}
	
	/**
	 * Returns the type of an instance defined by callback.
	 * For this, the instanciation code will be executed and the result will be returned.
	 *
	 * @param string $instanceName The name of the instance to analyze.
	 * @return string
	 */
	private function findInstanceByCallbackType($instanceName) {
		return $this->container->findInstanceByCallbackType($instanceName);
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
	
}
