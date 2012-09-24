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

use Mouf\Reflection\MoufReflectionProxy;

use Mouf\MoufManager;

use Mouf\MoufClassExplorer;

use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller checking for existing classes in project and printing classes that can't be included
 * because of software problems.
 *
 * @Component
 */
class IncludesAnalyzerController extends Controller {

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
	 * 
	 * @var array<string, string> key: classname, value: file
	 */
	protected $classMap;
	
	/**
	 * 
	 * @var array<string, string> key: classname, value: error output when file included.
	 */
	protected $errors;
	
	/**
	 * List of warnings triggered by some classes.
	 * 
	 * @var array<classname, array<msg>>
	 */
	protected $warnings = array();
	
	/**
	 * Displays the list of doc files from the packages
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit
	 */
	public function index($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$classExplorer = new MoufClassExplorer($selfedit == "true");
		$this->classMap = $classExplorer->getClassMap();
		$this->errors = $classExplorer->getErrors();
		
		// Next step: let's get all classes and let's see what errors are triggered.
		// Note: this is suboptimal, as the get_all_classes call is calling itself MoufClassExplorer.
		$allClasses = MoufReflectionProxy::getAllClasses($selfedit == "true");
		//var_export($allClasses["classes"]);
		
		foreach ($allClasses["classes"] as $class) {
			foreach ($class["methods"] as $method) {
				foreach ($method["parameters"] as $parameter) {
					if (isset($parameter["classinerror"])) {
						$this->warnings[$class["name"]][] = $parameter["classinerror"];
						unset($this->classMap[$class["name"]]);
					}
				}
			}
		}
		
		$this->contentBlock->addFile(ROOT_PATH."src/views/includes/index.php", $this);
		$this->template->toHtml();	
	}	
}
?>