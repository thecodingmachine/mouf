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
	 * The list of classes that have no errors nor warnings.
	 * @var array<string> value: classname
	 */
	protected $classList = array();
	
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
		
		// Let's get all classes and let's see what errors are triggered.
		$allClasses = MoufReflectionProxy::getAllClasses($selfedit == "true");
		//var_export($allClasses["classes"]);
		
		$this->errors = $allClasses['errors'];
		
		foreach ($allClasses["classes"] as $class) {
			$hasErrors = false;
			foreach ($class["methods"] as $method) {
				$hasErrors = false;
				if (isset($parameter["classinerror"])) {
					$this->warnings[$class["name"]][] = $parameter["classinerror"];
					$hasErrors = true;
				}
				foreach ($method["parameters"] as $parameter) {
					if (isset($parameter["classinerror"])) {
						$this->warnings[$class["name"]][] = $parameter["classinerror"];
						$hasErrors = true;
					}
				}
			}
			if (!$hasErrors) {
				$this->classList[] = $class["name"];
			}
		}
		
		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/includes/index.php", $this);
		$this->template->toHtml();	
	}	
	
	/**
	 * Purge cache and redirects to analysis page.
	 *
	 * @Action
	 * @Logged
	 * @param string $selfedit
	 */
	public function refresh($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		$moufCache = new MoufCache();
		$moufCache->purgeAll();
		
		header("Location: .?selfedit=".$selfedit);
	}
}
?>