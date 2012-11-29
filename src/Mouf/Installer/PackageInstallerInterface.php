<?php 
namespace Mouf\Installer;

use Mouf\MoufManager;

/**
 * Any class implementing the PackageInstallerInterface should have an empty constructor
 * and a static method "install" that will be triggered for the 
 * Please remember the class must be declared in the "install" section of the composer.json file
 * and that the file must have the "mouf-library" type
 * 
 * @author David Négrier
 */
interface PackageInstallerInterface {
	
	/**
	 * Performs the custom install process for the package.
	 * 
	 * @param MoufManager $moufManager
	 */
	public static function install(MoufManager $moufManager);
}