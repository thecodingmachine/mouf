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

use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\HtmlElement\HtmlBlock;

/**
 * The controller managing the list of developer components included in the Mouf app.
 *
 * @Component
 */
class ComponentsController extends Controller {

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
	 * The errors returned by analyzing the files.
	 * @var array
	 */
	protected $analyzeErrors;
	
	/**
	 * Displays the list of component files
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function defaultAction($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
				
		$template = $this->template;
		//$this->template->addJsFile(ROOT_URL."src/views/displayComponent.js");
		
		$this->analyzeErrors = MoufReflectionProxy::analyzeIncludes($selfedit == "true");
		$template->addContentFile(dirname(__FILE__)."/../views/displayComponentList.php", $this);
		$template->toHtml();
	}
	
	/**
	 * Saves the list of files to be edited
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function save($files = array(), $autoloads = array(), $selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->moufManager->setRegisteredComponentFiles($files, $autoloads);
		
		
		$this->moufManager->rewriteMouf();
		
		header("Location: ".ROOT_URL."mouf/components/?selfedit=".$selfedit);
	}
	
}
?>