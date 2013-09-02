Welcome to the Mouf 2 framework
===============================

What is Mouf?
-------------

Mouf is a PHP dependency injection framework with a nice web-based user interface. It is combining a Inversion of control (IOC) framework and a powerful extension mechanism so that any package can extend the web-based user interface with their own features.

The goal of Mouf is to help you use and re-use components. By itself, it does not provide anything useful. However, it will help you download and install libraries of components, and bind those components together.

Getting started
---------------

Mouf is at the same time a graphical dependency injection framework (the core of Mouf), and a full featured web-framework with
hundreds of packages available.

If you are interested in Mouf's core dependency injection framework, stay here! You are at the right place.
If you are interested in the global Mouf ecosystem, the MVC library, the database layer, etc..., you might want to start by
having a look at the [Getting things done with Mouf project](http://mouf-php.com/packages/mouf/getting-things-done-basic-edition/index.md). This
is a kind of "distribution" of the most common libraries used with Mouf.

Installation
------------

Mouf 2 is provided as a [Composer](http://getcomposer.org) package. The name of the package is *mouf/mouf*.
Follow the [installation guide](doc/installing_mouf.md) to learn more.

Packages
--------

Mouf provides a user interface to help you manage your composer dependencies.
Using an extension to the `composer.json` file format, you can extend the Mouf user interface to add features to help your developer.

- You are developing a cache package? Add a "Purge" button in Mouf UI to let your developers purge the cache
- You are developing a database connection? Add a screen to create a new database connection, etc...

Classes
-------

Each class can be injected using the Mouf dependency injection features. Because it is graphical and easy to use, you can push dependency injection to new limits.
And if you want a nice graphical representation, you can add annotations to your code to add custom logos for your classes, etc...

To learn more about using dependency injection, please read the <a href="doc/dependency_injection.md">Dependency Injection guide</a>.
