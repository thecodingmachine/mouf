<?php
namespace Mouf\Composer;

/**
 * The class maps a class name to one or many possible file names according to PSR-0 or PSR-4 rules.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class ClassNameMapperTest extends \PHPUnit_Framework_TestCase {
	public function testGetManagedNamespaces() {
		$classNameMapper = new ClassNameMapper();
		$classNameMapper->registerPsr0Namespace("Mouf", "src/");
		$classNameMapper->registerPsr0Namespace("Mouf", "test/");
		$classNameMapper->registerPsr4Namespace("Test", ["dir1", "dir2"]);
		
		$namespaces = $classNameMapper->getManagedNamespaces();
		$this->assertContains("Mouf\\", $namespaces);
		$this->assertContains("Test\\", $namespaces);
	}
	
	public function testCreateFromComposerFile() {
		$classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__."/../../../composer.json");
		$namespaces = $classNameMapper->getManagedNamespaces();
		$this->assertContains("Mouf\\", $namespaces);
	}
	
	public function testGetPossibleFileNames() {
		$classNameMapper = new ClassNameMapper();
		$classNameMapper->registerPsr0Namespace("Mouf", "src/");
		$classNameMapper->registerPsr0Namespace("Mouf", "test/");
		$classNameMapper->registerPsr4Namespace("Test\\", ["dir1", "dir2"]);
		
		$possibleFileNames = $classNameMapper->getPossibleFileNames('Mouf\\Toto\\Test');
		$this->assertContains("src/Mouf/Toto/Test.php", $possibleFileNames);
		$this->assertContains("test/Mouf/Toto/Test.php", $possibleFileNames);
		
		$possibleFileNames = $classNameMapper->getPossibleFileNames('Test\\Toto\\Test');
		$this->assertContains("dir1/Toto/Test.php", $possibleFileNames);
		$this->assertContains("dir2/Toto/Test.php", $possibleFileNames);
		
		$classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__."/../../../composer.json", __DIR__);
		$possibleFileNames = $classNameMapper->getPossibleFileNames('Mouf\\Toto\\Test');
		$this->assertContains("../../../src/Mouf/Toto/Test.php", $possibleFileNames);
		
	}
	
	
}
