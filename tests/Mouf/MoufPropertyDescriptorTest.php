<?php
namespace Mouf;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\TestClasses\TestClass1;

require __DIR__.'/../../vendor/autoload.php';

class MoufPropertyDescriptorTest extends \PHPUnit_Framework_TestCase {
	public function testTestClass1() {
		$moufReflectionClass = new MoufReflectionClass("\\Mouf\\TestClasses\\TestClass1");
		
		$indexedArrayProperty = $moufReflectionClass->getProperty("indexedArray");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($indexedArrayProperty);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("array", $type);
		$this->assertEquals("int", $subtype);
		$this->assertEquals(null, $keytype);
		
		$property = $moufReflectionClass->getProperty("associativeArray");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("array", $type);
		$this->assertEquals("string", $subtype);
		$this->assertEquals("string", $keytype);
		
		$property = $moufReflectionClass->getProperty("int");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("int", $type);
		$this->assertEquals(null, $subtype);
		$this->assertEquals(null, $keytype);
		
		$property = $moufReflectionClass->getProperty("testClass1");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type, "Testing \$testClass1 property");
		$this->assertEquals(null, $subtype, "Testing \$testClass1 property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testClass1 property keytype");
		
		$property = $moufReflectionClass->getProperty("testClass1FullyQualifiedNamespace");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type, "Testing \$testClass1FullyQualifiedNamespace property");
		$this->assertEquals(null, $subtype, "Testing \$testClass1FullyQualifiedNamespace property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testClass1FullyQualifiedNamespace property keytype");
		
		$property = $moufReflectionClass->getProperty("testSubNamespace");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\Subname\\Subclass", $type, "Testing \$testSubNamespace property");
		$this->assertEquals(null, $subtype, "Testing \$testSubNamespace property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testSubNamespace property keytype");
		
		$property = $moufReflectionClass->getProperty("testUse");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\Subname\\Subclass", $type, "Testing \$testUse property");
		$this->assertEquals(null, $subtype, "Testing \$testUse property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testUse property keytype");
		
		$property = $moufReflectionClass->getProperty("testRelativeUse");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\Subname\\Subclass", $type, "Testing \$testRelativeUse property");
		$this->assertEquals(null, $subtype, "Testing \$testRelativeUse property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testRelativeUse property keytype");
		
		
		$property = $moufReflectionClass->getMethod("setSetterProperty1");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type, "Testing \$setSetterProperty1 property");
		$this->assertEquals(null, $subtype, "Testing \$setSetterProperty1 property subtype");
		$this->assertEquals(null, $keytype, "Testing \$setSetterProperty1 property keytype");
		
		$property = $moufReflectionClass->getMethod("setSetterProperty2");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type, "Testing \$setSetterProperty2 property");
		$this->assertEquals(null, $subtype, "Testing \$setSetterProperty2 property subtype");
		$this->assertEquals(null, $keytype, "Testing \$setSetterProperty2 property keytype");
		
		$property = $moufReflectionClass->getConstructor()->getParameters();
		$property = $property[0];
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("string", $type, "Testing \$constructorParamA property");
		$this->assertEquals(null, $subtype, "Testing \$constructorParamA property subtype");
		$this->assertEquals(null, $keytype, "Testing \$constructorParamA property keytype");
		
		$property = $moufReflectionClass->getConstructor()->getParameters();
		$property = $property[1];
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass2", $type, "Testing \$constructorParamB property");
		$this->assertEquals(null, $subtype, "Testing \$constructorParamB property subtype");
		$this->assertEquals(null, $keytype, "Testing \$constructorParamB property keytype");
		
		$property = $moufReflectionClass->getConstructor()->getParameters();
		$property = $property[2];
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$type = $moufPropertyDescriptor->getType();
		$subtype = $moufPropertyDescriptor->getSubType();
		$keytype = $moufPropertyDescriptor->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass2", $type, "Testing \$constructorParamC property");
		$this->assertEquals(null, $subtype, "Testing \$constructorParamC property subtype");
		$this->assertEquals(null, $keytype, "Testing \$constructorParamC property keytype");
		
		
		
	}
	
	static function main() {
		$suite = new \PHPUnit_Framework_TestSuite( __CLASS__);
		\PHPUnit_TextUI_TestRunner::run( $suite);
	}
}

if (!defined('PHPUnit_MAIN_METHOD')) {
	MoufPropertyDescriptorTest::main();
}