<?php

namespace Mouf\Reflection;


use Mouf\TestClasses\ClassWithErrors;
use Mouf\TestClasses\TestClassWithAs;
use Mouf\TestClasses\TestClassWithClosureBeforeClass;

class MoufReflectionClassTest extends \PHPUnit_Framework_TestCase
{
    public function test_getUseNamespaces_with_alias() {
        $reflection = new MoufReflectionClass(TestClassWithAs::class);
        $useMap = $reflection->getUseNamespaces();
        $this->assertCount(1, $useMap);
        $this->assertArrayHasKey("Alias", $useMap);
        $this->assertEquals("Mouf\\TestClasses\\MyClass", $useMap["Alias"]);
    }

    public function test_getUseNamespaces_with_no_use() {
        $reflection = new MoufReflectionClass(ClassWithErrors::class);
        $useMap = $reflection->getUseNamespaces();
        $this->assertCount(0, $useMap);
    }

    public function test_getUseNamespaces_with_closure_before_class() {
        $reflection = new MoufReflectionClass(TestClassWithClosureBeforeClass::class);
        $useMap = $reflection->getUseNamespaces();
        $this->assertCount(0, $useMap);
    }
}
