<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2013 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Reflection;

/**
 * This class acts as a MoufReflectionClass factory.
 * 
 */
class MoufReflectionClassManager implements ReflectionClassManagerInterface {
	
	/**
	 * A list of descriptors.
	 *
	 * @var array<string, MoufXmlReflectionClass>
	 */
	private $classDescriptors = array();
	
	/**
	 * Returns an object describing the class passed in parameter.
	 * This method should only be called in the context of the Mouf administration UI.
	 *
	 * @param string $className The name of the class to import
	 * @return MoufXmlReflectionClass
	*/
	public function getReflectionClass($className) {
		if (!isset($this->classDescriptors[$className])) {
			$this->classDescriptors[$className] = new MoufReflectionClass($className);
		}
		return $this->classDescriptors[$className];
	}
	
}
?>