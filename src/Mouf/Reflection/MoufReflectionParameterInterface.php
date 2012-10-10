<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Reflection;

/**
 * The interface implemented by objects representing a parameter.
 * 
 * @author David Negrier
 */
interface MoufReflectionParameterInterface {
	
	/**
	* Returns the class of the parameter (if any)
	*
	* @return string
	*/
	public function getType();
}