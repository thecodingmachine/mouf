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
	 * Returns the property name
	 *
	 * @return string
	 */
	public function getName();
	
	/**
	 * Returns the default value
	 *
	 * @return mixed
	 */
	public function getDefaultValue();
	
	/**
	 * Returns the declaring function containing this parameter.
	 *
	 * @return MoufReflectionMethodInterface
	 */
	public function getDeclaringFunction();
	
	/**
	 * Returns the class of the parameter (if any)
	 *
	 * @return string
	 */
	public function getType();
	
	/**
	 * Returns the position of the parameter in the parameters list (starting 0)
	 *
	 * @return number
	 */
	public function getPosition();
}