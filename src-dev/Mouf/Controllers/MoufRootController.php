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
use Zend\Diactoros\Response\RedirectResponse;

/**
 * The base controller for Mouf (when the "mouf/" url is typed).
 *
 */
class MoufRootController {

    private $rootUrl;

    public function __construct(string $rootUrl)
    {
        $this->rootUrl = $rootUrl;
    }

	/**
	 * The default action will redirect to the MoufController defaultAction.
	 *
	 * @URL("/")
	 * @Logged()
	 */
	public function defaultAction() {
	    return new RedirectResponse($this->rootUrl.'validate');
	}
}
