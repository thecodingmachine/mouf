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
use Mouf\Security\Logged;
use TheCodingMachine\Splash\Annotations\URL;
use Zend\Diactoros\Response\HtmlResponse;


/**
 * The controller displaying the PHP Info page.
 *
 */
class PhpInfoController {
	
	/**
	 * Displays the PHP info page.
	 * 
	 * @URL("phpInfo/")
	 * @Logged()
	 */
	public function defaultAction() {
	    \ob_start();
        phpinfo();
        $html = \ob_get_clean();
		return new HtmlResponse($html);
	}
}
