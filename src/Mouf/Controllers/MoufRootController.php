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

use Mouf\Splash\Controller;

/**
 * The base controller for Mouf (when the "mouf/" url is typed).
 *
 * @Component
 */
class MoufRootController extends Controller {
	
	/**
	 * The default action will redirect to the MoufController defaultAction.
	 *
	 * @Action
	 * @Logged
	 */
	public function defaultAction() {
		header("Location: ".ROOT_URL."mouf/validate/");
	}
}
?>