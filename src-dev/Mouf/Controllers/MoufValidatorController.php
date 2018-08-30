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

use Mouf\Html\HtmlElement\HtmlBlock;

use Mouf\Html\Template\TemplateInterface;
use Mouf\MoufException;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Mvc\Splash\HtmlResponse;
use Mouf\Reflection\MoufReflectionProxy;
use Mouf\Security\Logged;
use Mouf\Validator\MoufValidatorService;
use TheCodingMachine\Splash\Annotations\Action;
use TheCodingMachine\Splash\Annotations\URL;

/**
 * The controller that will call all validators on Mouf.
 *
 */
class MoufValidatorController extends Controller {
	
	/**
	 * The validation service.
	 * 
	 * @var MoufValidatorService
	 */
	private $validatorService;
	
	/**
	 * The template used by the main page for mouf.
	 *
	 * @var TemplateInterface
	 */
    private $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @var HtmlBlock
	 */
    private $contentBlock;

    public function __construct(MoufValidatorService $validatorService, TemplateInterface $template, HtmlBlock $contentBlock)
    {
        $this->validatorService = $validatorService;
        $this->template = $template;
        $this->contentBlock = $contentBlock;
    }


    /**
	 * The default action will redirect to the MoufController defaultAction.
	 *
	 * @URL("validate")
	 * @Logged()
	 */
	public function defaultAction($selfedit = "false") {
		// Before running the other validation steps, we should make sure we can successfully cURL
		// into the server, from the server.
		try {
			if (!MoufReflectionProxy::checkConnection()) {
				$this->contentBlock->addFile(ROOT_PATH."src-dev/views/connection-problem.php", $this);
				return new HtmlResponse($this->template);
			}
		} catch (MoufException $e) {
			$this->contentBlock->addFile(ROOT_PATH."src-dev/views/connection-problem.php", $this);
            return new HtmlResponse($this->template);
		}

		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/validate.php", $this);
        return new HtmlResponse($this->template);
	}
}
