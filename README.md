Welcome to the Mouf 2 framework
===============================

What is Mouf?
-------------

Mouf is a PHP dependency injection framework with a nice web-based user interface. It is combining a Inversion of control (IOC) framework and a powerful extension mechanism so that any package can extend the web-based user interface with their own features.

The goal of Mouf is to help you use and re-use components. By itself, it does not provide anything useful. However, it will help you download and install libraries of components, and bind those components together.

Installation
------------

Mouf 2 is provided as a [Composer](http://getcomposer.org) package. The name of the package is *mouf/mouf*.
Follow the [installation guide](/doc/installing_mouf) to learn more.

Packages
--------

Mouf provides a user interface to help you manage your composer dependencies.
Using an extension to the composer.json file format, you can extend the Mouf user interface to add features to help your developer.
- You are developing a cache package? Add a "Purge" button in Mouf UI to let your developers purge the cache
- You are developing a database connection? Add a screen to create a new database connection, etc...

Classes
-------

Each class can be injected using the Mouf dependency injection features. Because it is graphical and easy to use, you can push dependency injection to new limits.
And if you want a nice graphical representation, you can add annotations to your code to add custom logos for your classes, etc...

To learn more about using dependency injection, please read the <a href="doc/using_components.md">Mouf Using Components guide</a>.
To learn more about Mouf annotations, please read the <a href="doc/building_a_new_mouf_component.md">Mouf Building Components guide</a>.
