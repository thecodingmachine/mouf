Mouf 2: Dependency injection... on steroids
===========================================
[![Latest Stable Version](https://poser.pugx.org/mouf/mouf/v/stable.png)](https://packagist.org/packages/mouf/mouf) [![Latest Unstable Version](https://poser.pugx.org/mouf/mouf/v/unstable.png)](https://packagist.org/packages/mouf/mouf) [![Total Downloads](https://poser.pugx.org/mouf/mouf/downloads.png)](https://packagist.org/packages/mouf/mouf) [![License](https://poser.pugx.org/mouf/mouf/license.png)](https://packagist.org/packages/mouf/mouf)

What is Mouf?
-------------

Mouf is a PHP dependency injection framework with a nice web-based user interface.

Why Mouf?
---------

Dependency injection solves the **spaghetti code** problem by externalizing all the instances declaration
into a **configuration file**. But soon, this file becomes a **spaghetti configuration file**.  
Mouf solves that problem by providing a nice **web-based UI** to edit your file.

By solving that problem, Mouf opens a whole new world of possibities where most of your application is 
declared using a user interface instead of written in pure PHP code. 

Want to learn more about dependency injection? [Check out the dependency injection guide](doc/dependency_injection.md).  
Want to see Mouf basic principles in action? [Check the introduction to graphical dependency injection video](http://mouf-php.com/packages/mouf/mouf/doc/mouf_di_ui.md).

Getting started
---------------

Mouf is at the same time a graphical dependency injection framework (the core of Mouf), and a full featured web-framework with
hundreds of packages available.

- If you are interested in Mouf's core dependency injection framework, stay here! You are at the right place.
- If you are interested in the global Mouf ecosystem, the MVC library, the database layer, etc..., you might want to start by
having a look at the [main packages chart](http://mouf-php.com/skills) or at the  [Getting things done with Mouf project](http://mouf-php.com/packages/mouf/getting-things-done-basic-edition/index.md). This
is a kind of "distribution" of the most common libraries used with Mouf.
- You can also be interested in extending your existing project with Mouf. Mouf integrates easily with
[Wordpress](http://mouf-php.com/packages/mouf/integration.wordpress.moufpress/README.md), 
[Drupal](http://mouf-php.com/packages/mouf/integration.drupal.druplash/README.md), 
[Symfony 2](http://mouf-php.com/packages/mouf/interop.symfony.di/README.md), 
[Silex](https://github.com/moufmouf/pimple-interop), [Doctrine](http://mouf-php.com/packages/mouf/database.doctrine-orm-wrapper/README.md) 
or any project compatible with the [container-interop](https://github.com/container-interop/container-interop) project. 

Installation
------------

Mouf 2 is provided as a [Composer](http://getcomposer.org) package. The name of the package is *mouf/mouf*.
Follow the [installation guide](doc/installing_mouf.md) to learn more.

Classes
-------

Each class can be injected using the Mouf dependency injection features. Because it is graphical and easy to use, 
you can push dependency injection to new limits.
And if you want a nice graphical representation, you can add annotations to your code to add custom logos 
for your classes, etc...

To learn more about using dependency injection, please read the [Dependency Injection guide](doc/dependency_injection.md).

Packages
--------

Mouf provides a user interface to help you manage your composer dependencies.
Using an extension to the `composer.json` file format, you can [extend the Mouf user interface](doc/extending_mouf_ui.md) to add features to help your developer.

- You are developing a cache package? Add a "Purge" button in Mouf UI to let your developers purge the cache
- You are developing a database connection? Add a screen to create a new database connection, etc...
