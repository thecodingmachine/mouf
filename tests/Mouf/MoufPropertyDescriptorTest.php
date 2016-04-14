<?php
namespace Mouf;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\TestClasses\TestClass1;

require_once __DIR__.'/../../vendor/autoload.php';

class MoufPropertyDescriptorTest extends \PHPUnit_Framework_TestCase {
	public function testTestClass1() {
		$moufReflectionClass = new MoufReflectionClass("\\Mouf\\TestClasses\\TestClass1");
		
		$indexedArrayProperty = $moufReflectionClass->getProperty("indexedArray");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($indexedArrayProperty);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals(true, $type->isArray());
		$this->assertEquals("int", $subtype->getType());
		$this->assertEquals(null, $keytype);
		
		$property = $moufReflectionClass->getProperty("associativeArray");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("array", $type->getType());
		$this->assertEquals("string", $subtype->getType());
		$this->assertEquals("string", $keytype);
		
		$property = $moufReflectionClass->getProperty("int");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("int", $type->getType());
		$this->assertEquals(null, $subtype);
		$this->assertEquals(null, $keytype);
		
		$property = $moufReflectionClass->getProperty("testClass1");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type->getType(), "Testing \$testClass1 property");
		$this->assertEquals(null, $subtype, "Testing \$testClass1 property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testClass1 property keytype");
		
		$property = $moufReflectionClass->getProperty("testClass1FullyQualifiedNamespace");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type->getType(), "Testing \$testClass1FullyQualifiedNamespace property");
		$this->assertEquals(null, $subtype, "Testing \$testClass1FullyQualifiedNamespace property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testClass1FullyQualifiedNamespace property keytype");
		
		$property = $moufReflectionClass->getProperty("testSubNamespace");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\Subname\\Subclass", $type->getType(), "Testing \$testSubNamespace property");
		$this->assertEquals(null, $subtype, "Testing \$testSubNamespace property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testSubNamespace property keytype");
		
		$property = $moufReflectionClass->getProperty("testUse");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\Subname\\Subclass", $type->getType(), "Testing \$testUse property");
		$this->assertEquals(null, $subtype, "Testing \$testUse property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testUse property keytype");
		
		$property = $moufReflectionClass->getProperty("testRelativeUse");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\Subname\\Subclass", $type->getType(), "Testing \$testRelativeUse property");
		$this->assertEquals(null, $subtype, "Testing \$testRelativeUse property subtype");
		$this->assertEquals(null, $keytype, "Testing \$testRelativeUse property keytype");
		
		
		$property = $moufReflectionClass->getMethod("setSetterProperty1");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type->getType(), "Testing \$setSetterProperty1 property");
		$this->assertEquals(null, $subtype, "Testing \$setSetterProperty1 property subtype");
		$this->assertEquals(null, $keytype, "Testing \$setSetterProperty1 property keytype");
		
		$property = $moufReflectionClass->getMethod("setSetterProperty2");
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass1", $type->getType(), "Testing \$setSetterProperty2 property");
		$this->assertEquals(null, $subtype, "Testing \$setSetterProperty2 property subtype");
		$this->assertEquals(null, $keytype, "Testing \$setSetterProperty2 property keytype");
		
		$property = $moufReflectionClass->getConstructor()->getParameters();
		$property = $property[0];
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("string", $type->getType(), "Testing \$constructorParamA property");
		$this->assertEquals(null, $subtype, "Testing \$constructorParamA property subtype");
		$this->assertEquals(null, $keytype, "Testing \$constructorParamA property keytype");
		
		$property = $moufReflectionClass->getConstructor()->getParameters();
		$property = $property[1];
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass2", $type->getType(), "Testing \$constructorParamB property");
		$this->assertEquals(null, $subtype, "Testing \$constructorParamB property subtype");
		$this->assertEquals(null, $keytype, "Testing \$constructorParamB property keytype");
		
		$property = $moufReflectionClass->getConstructor()->getParameters();
		$property = $property[2];
		$moufPropertyDescriptor = new MoufPropertyDescriptor($property);
		$types = $moufPropertyDescriptor->getTypes();
		$type = $types->getTypes()[0];
		$subtype = $type->getSubType();
		$keytype = $type->getKeyType();
		$this->assertEquals("\\Mouf\\TestClasses\\TestClass2", $type->getType(), "Testing \$constructorParamC property");
		$this->assertEquals(null, $subtype, "Testing \$constructorParamC property subtype");
		$this->assertEquals(null, $keytype, "Testing \$constructorParamC property keytype");
					
	}
}
