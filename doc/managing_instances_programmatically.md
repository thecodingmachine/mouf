Managing instances programmatically
===================================

As an application developer, you will use the Mouf **user interface** to edit instances in your application.
However, as a package developer, you will need to edit/create instances programmatically.
For instance, you may want to provide an install process that creates instances
(or a user interface in Mouf that creates/modifies instances...).

For this you will need to access the `MoufContainer`. `MoufContainer` is the class used to add/edit instances of your application.

<div class="alert alert-info">Note: <code>MoufContainer</code> is compatible with <a href="https://github.com/container-interop/container-interop/">ContainerInterop</a>,
the DIC compatibility standard. In particular, it implements the <a href="https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php">ContainerInterface</a> 
and the <a href="https://github.com/container-interop/container-interop/blob/master/docs/Delegate-lookup.md">delegate lookup feature</a>.</div>

Getting a *MoufContainer* instance
----------------------------------
###Getting the default container of your Mouf application

The first thing you want to do is to get an instance of `MoufContainer`.

If you are in the context of your application, use:

```php
$moufContainer = MoufManager::getMoufManager()->getContainer();
```

If you are in the context of Mouf (if you are developing a controller that extends the Mouf interface), use:

```php
$moufContainer = MoufManager::getHiddenMoufManager()->getContainer();
```

<div class="alert alert-info">Your PHP code can run in 2 different contexts: your application's context or Mouf's context.
In your application's context, your application's autoloader is used, and all your classes are directly
accessible. In Mouf's context, Mouf autoloader is used. This means Mouf classes and dependencies are available.</div>

Mouf is developed using Mouf (yes, this is recursive). If you use the 
<code>MoufManager::getMoufManager()->getContainer()</code> method inside the Mouf context, you will get the instances used
by Mouf, not your instances.

###Creating a new container

Sometimes, you don't want to access the default container that comes with your Mouf application. Instead, you might
want create a new container.

A valid MoufContainer comes in 2 parts:

- a configuration file (that contains the list of instances) This file is usually called `instances.php`
- a class that extends `Mouf\MoufContainer`

To build the container, you use:

```php
use Mouf\MoufContainer;

// createContainer takes 3 arguments:
// - the path to the configuration file
// - the name of the class to be generated
// - (optional): the path to the class
$container = MoufContainer::createContainer('path/to/instances.php', 'MyProject\\Container', 'src/MyProject/Container/php');

// A call to "write" will generate both files.
$container->write();
```

Once the container has been generated, you can get an instance of the container by simply calling:

```
$container = new MyProject\Container();
```

You can optionally pass a <a href="https://github.com/container-interop/container-interop/blob/master/docs/Delegate-lookup.md">delegate lookup container</a> as an argument to the container:

```
$container = new MyProject\Container($rootContainer);
```

Creating a new instance
-----------------------

In order to create a new instance, use the `createInstance` method:

```php
// Creates an anonymous instance for class MyNamespace\MyClass
$instanceDescriptor = $moufContainer->createInstance("MyNamespace\\MyClass");

// Let's give the instance a name:
$instanceDescriptor->setName('myInstance');

// Finally, save the instance:
$moufContainer->write();
```

As you noticed, the `createInstance` method returns an "instance descriptor". This is an object that
describes the instance.

Each time you modify an instance or create a new instance, changes will only be saved once you call
the `$moufContainer->write()` method.	

Getting an instance descriptor from the *MoufManager*
-----------------------------------------------------

Use the `getInstanceDescriptor()` method to retrieve an instance descriptor.

```php
$instanceDescriptor = $moufContainer->getInstanceDescriptor('myInstance');
```

Setting a property in an instance
---------------------------------

###Injecting a primitive type

```php
// Filling a constructor argument
$instanceDescriptor->getConstructorArgumentProperty('parameterName')->setValue('aValue');

// Filling a setter
$instanceDescriptor->getSetterProperty('setterName')->setValue('aValue');

// Filling a public field
$instanceDescriptor->getPublicFieldProperty('myField')->setValue('aValue');
```

###Injecting another instance

If you want to inject another instance, pass an instance descriptor to the `setValue` method.

For instance:

```php
$anotherInstanceDescriptor = $moufContainer->getInstanceDescriptor('anotherInstance');

$instanceDescriptor->getConstructorArgumentProperty('parameterName')->setValue(anotherInstanceDescriptor);
```

###Injecting a constant in a property

```php
define('MY_CONSTANT', 42);

$instanceDescriptor->getConstructorArgumentProperty('parameterName')
                   ->setOrigin("constant")
                   ->setValue("MY_CONSTANT");
```


###Injecting PHP code in a property

PHP code is passed to the `setValue` method. It must contain a `return` statement.

```php
$instanceDescriptor->getConstructorArgumentProperty('parameterName')
                   ->setOrigin("php")
                   ->setValue("return [ 42 => 'aValue' ]");
```

Creating an instance defined by PHP code
----------------------------------------

You can also declare an instance completely from PHP code.

```php
// Creates an anonymous instance by PHP code
$instanceDescriptor = $moufContainer->createInstanceByCode();

// Let's give the instance a name:
$instanceDescriptor->setName('myInstance');

// Sets the PHP code (as a string). It must contain a `return` statement.
$instanceDescriptor->setCode('return MyObject::getInstance();');

// Finally, save the instance:
$moufContainer->write();
```

Utility functions
-----------------

Fairly often, when you write install scripts, you will need to get an instance by its name,
or create that instance of that instance does not exist yet.

There is an utility function that help you do this:

```php
use Mouf\Actions\InstallUtils;

$instanceDescriptor = InstallUtils::getOrCreateInstance($instanceName, $className, $moufContainer);
```

Exporting instances
-------------------

When you write complex install scripts with dozens of instances, it can be quite tedious to
write the install script by yourself. Hopefully, Mouf comes with 
[**Package builder**](http://mouf-php.com/packages/mouf/utils.package-builder): 
a tool that can help you export a set of instances and build the PHP
code that generates the instances for you.  

<a href="http://mouf-php.com/packages/mouf/utils.package-builder" class="btn">Check out "Package builder" &gt;</a>
