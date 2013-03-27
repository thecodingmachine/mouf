#Create a Mouf2 Package

##Should I build a package?

Before diving into the technical details packages declaration, we might want to ask what a package is and why we might want to build one.
A package is a reusable set of classes. Therefore, you should build a package if you are developing a set of classes that you will be using later, in another project. There is no need to group your components in a package if they are specific to your web-application. If your classes are specific to your project, our advice would be to <a href="http://getcomposer.org/doc/01-basic-usage.md#autoloading" target="_blank">use Composer's autoloader mechanism</a> to load your classes.

##Packages overview

In Mouf2, packages are completely based on Composer. Therefore, in order to build a package, the first thing you might want to do is <a href="http://getcomposer.org/doc/02-libraries.md" target="_blank">to learn how Composer packages are working</a>.
However, Mouf2 provides a special kind of Composer packages. You might want to use these special features if you want to set-up an installer for your package. You can setup graphical installers (web-based installers using the Mouf user interface), or silent installers (that usually create default instances based on your classes).

