<?php
namespace Mouf\Composer;

/**
 * A simple utility class to split namespace and classname in a fully qualified class name.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class ClassNameUtils
{
	/**
	 * Returns the namespace from a fully qualified class name.
	 * Trailing \ will be removed.
	 * Returns null if there is no namespace.
	 * 
	 * @param string $fullyQualifiedClassName
	 * @return string
	 */
	public static function getNamespace($fullyQualifiedClassName) {
		$fullyQualifiedClassName = ltrim($fullyQualifiedClassName, '\\');
		if ($lastNsPos = strripos($tmpClassName, '\\') !== false) {
			return substr($fullyQualifiedClassName, 0, $lastNsPos);
		} else {
			return null;
		}
	}
	
	/**
	 * Returns the short class name from a fully qualified class name.
	 *
	 * @param string $fullyQualifiedClassName
	 * @return string
	 */
	public static function getClassName($fullyQualifiedClassName) {
		$fullyQualifiedClassName = ltrim($fullyQualifiedClassName, '\\');
		if ($lastNsPos = strripos($tmpClassName, '\\') !== false) {
			return substr($fullyQualifiedClassName, $lastNsPos+1);
		} else {
			return $fullyQualifiedClassName;
		}
	}
}
