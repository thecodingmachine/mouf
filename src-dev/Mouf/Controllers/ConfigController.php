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

use Mouf\MoufManager;

use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\HtmlElement\HtmlBlock;

/**
 * The controller managing the config.php file.
 *
 * @Component
 */
class ConfigController extends Controller {

	public $selfedit;
	
	/**
	 * The active MoufManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $moufManager;
	
	/**
	 * The active ConfigManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $configManager;
	
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
	 * The list of constants defined.
	 *
	 * @var array<string, string>
	 */
	protected $constantsList;
		
	/**
	 * Displays the list of defined parameters in config.php
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 * @param string $validation The validation message to display (either null, or confirmok).
	 */
	public function index($selfedit = "false", $validation = null) {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		//$this->constantsList = $this->moufManager->getConfigManager()->getDefinedConstants();
		$this->constantsList = $this->moufManager->getConfigManager()->getMergedConstants();
		
		$this->contentBlock->addFile(ROOT_PATH."src/views/constants/displayConstantsList.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * The action to save the configuration.
	 *
	 * @Action
	 * @Logged
	 */
	public function saveConfig($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->configManager = $this->moufManager->getConfigManager();
		$this->constantsList = $this->configManager->getMergedConstants();
		
		$constants = array();
		foreach ($this->constantsList as $key=>$def) {
			if ($def['defined'] == true && $def['type'] == 'bool') {
				$constants[$key] = (get($key)=="true")?true:false;
			} else {
				$constants[$key] = get($key);
			}
		}
		$this->configManager->setDefinedConstants($constants);
		
		header("Location: .?selfedit=".$selfedit);
	}
	
	protected $name;
	protected $defaultvalue;
	protected $value;
	protected $type;
	protected $comment;
	
	/**
	 * Displays the screen to register a constant definition.
	 *
	 * @Action
	 * @Logged
	 * @param string $name
	 * @param string $selfedit
	 */
	public function register($name = null, $defaultvalue = null, $value = null, $type = null, $comment = null, $selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		$this->configManager = $this->moufManager->getConfigManager();
		
		$this->name = $name;
				
		$this->defaultvalue = $defaultvalue;
		$this->value = $value;
		$this->type = $type;
		$this->comment = $comment;
		
		if ($name != null) {
			$def = $this->configManager->getConstantDefinition($name);
			if ($def != null) {
				if ($this->comment == null) {
					$this->comment = $def['comment'];
				}
				if ($this->defaultvalue == null) {
					$this->defaultvalue = $def['defaultValue'];
				}
				if ($this->type == null) {
					$this->type = $def['type'];
				}
			}
			if ($this->value == null) {
				$constants = $this->configManager->getDefinedConstants();
				if (isset($constants[$name])) {
					$this->value = $constants[$name];
				}
			}
		}
		
		// TODO: manage type!
		//$this->type = $comment;
		
		$this->contentBlock->addFile(ROOT_PATH."src/views/constants/registerConstant.php", $this);
		$this->template->toHtml();
	}

	/**
	 * Actually saves the new constant declared.
	 *
	 * @Action
	 * @Logged
	 * @param string $name
	 * @param string $defaultvalue
	 * @param string $value
 	 * @param string $comment
 	 * @param string $type
 	 * @param string $selfedit
	 */
	public function registerConstant($name, $comment, $type, $defaultvalue = "", $value = "", $selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		$this->configManager = $this->moufManager->getConfigManager();
		
		if ($type == "int") {
			$value = (int) $value;
			$defaultvalue = (int) $defaultvalue;
		} else if ($type == "float") {
			$value = (float) $value;
			$defaultvalue = (float) $defaultvalue;
		} else if ($type == "bool") {
			if ($value == "true") {
				$value = true;
			} else {
				$value = false;
			}
			if ($defaultvalue == "true") {
				$defaultvalue = true;
			} else {
				$defaultvalue = false;
			}
		}
		
		$this->configManager->registerConstant($name, $type, $defaultvalue, $comment);
		$this->moufManager->rewriteMouf();
		
		// Now, let's write the constant for our environment:
		$this->constantsList = $this->configManager->getDefinedConstants();
		$this->constantsList[$name] = $value;
		$this->configManager->setDefinedConstants($this->constantsList);
		
		header("Location: .?selfedit=".$selfedit);
	}

	/**
	 * Deletes a constant.
	 *
	 * @Action
	 * @Logged
	 * @param string $name
	 */
	public function deleteConstant($name, $selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		$this->configManager = $this->moufManager->getConfigManager();
		
		$this->configManager->unregisterConstant($name);
		
		$this->moufManager->rewriteMouf();

		// Now, let's write the constant for our environment:
		$this->constantsList = $this->configManager->getDefinedConstants();
		unset($this->constantsList[$name]);
		$this->configManager->setDefinedConstants($this->constantsList);
		
		
		header("Location: .?selfedit=".$selfedit);
	}
}
?>