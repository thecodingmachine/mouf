<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Actions;

/**
 * The result of an action.
 * 
 * @author david
 */
class MoufActionDoneResult {

	/**
	 * Returns the status of the action.
	 * Can be one of: "done", "error", "redirect".
	 * @return string
	 */
	public function getStatus() {
		return "done";
	}
	
	/**
	 * Returns the URL we should redirect to.
	 * Returns null if no redirect is requested by the action.
	 * @return string
	 */
	public function getRedirectUrl() {
		return null;
	}
}