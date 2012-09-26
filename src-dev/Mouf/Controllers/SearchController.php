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

/**
 * The controller managing full-text searches inside Mouf.
 *
 * @Component
 */
class SearchController extends Controller {

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
	 * The search service references all the services that can searched in full-text.
	 *
	 * @Property
	 * @Compulsory
	 * @var MoufSearchService
	 */
	public $searchService;

	protected $searchUrls;
	protected $query;
	
	/**
	 * Performs a full-text search in Mouf.
	 * 
	 * @Action
	 * @Logged
	 * @param string $query The text to search.
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function defaultAction($query, $selfedit = "false") {
		$this->selfedit = $selfedit;
		$this->query = $query;
		
		/*if ($selfedit == "true") {*/
			$this->moufManager = MoufManager::getMoufManager();
		/*} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}*/
		
		$this->searchUrls = array();
		foreach ($this->searchService->searchableServices as $service) {
			/* @var $service MoufSearchable */
			$this->searchUrls[] = array("name"=>$service->getSearchModuleName(), "url"=>ROOT_URL."mouf/".$this->moufManager->findInstanceName($service)."/search");
		}
		
		$this->contentBlock->addFile(ROOT_PATH."src/views/search/results.php", $this);
		$this->template->toHtml();	
	}
	
	protected $repositoryId = null;
	
	/**
	 * Display the add screen for repositories.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function add($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		
		
		$this->contentBlock->addFile(ROOT_PATH."src/views/packages/editRepository.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Display the edit screen for repositories.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function edit($id, $selfedit = "false") {
		$this->selfedit = $selfedit;
		$this->repositoryId = $id;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		$this->repositoryUrls = $this->moufManager->getVariable("repositoryUrls");
		
		$this->contentBlock->addFile(ROOT_PATH."src/views/packages/editRepository.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Saves the repository.
	 * 
	 * @Action
	 * @Logged
	 * @param unknown_type $name
	 * @param unknown_type $url
	 * @param unknown_type $id
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
	 */
	public function save($name, $url, $id=null, $selfedit = "false") {
		$this->selfedit = $selfedit;
		$this->repositoryId = $id;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->repositoryUrls = $this->moufManager->getVariable("repositoryUrls");
		
		if ($id !== null) {
			$this->repositoryUrls[$id] = array("name"=>$name, "url"=>$url);
		} else {
			$this->repositoryUrls[] = array("name"=>$name, "url"=>$url);
		}
		
		$this->moufManager->setVariable("repositoryUrls", $this->repositoryUrls);
		$this->moufManager->rewriteMouf();
		header("Location: .?selfedit=".$selfedit);
	}
	
}
?>