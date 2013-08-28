Getting started with dependency injection
=========================================

At the core of Mouf, there is a high performance graphical dependency injection library.
The whole Mouf framework revolves around that feature, so if you don't know about dependency injection,
it's time to get a quick course.

What is dependency injection, and why bother?
---------------------------------------------

Dependency injection is a way (a design pattern) to organize your application cleanly.

A typical PHP application contains a bunch of objects (or instances) that come from classes.
We can really split those objects into 2 kind of objects:

- objects that are different each time your application runs (for instance, an instance of a class representing 
a database row, or user input)
- objects that are almost the same each time your application runs (for instance, an object representing
a service to send mails, or an object representing a controller)

**Dependency injection** focuses on the second kind of objects. It will help you to instanciate cleanly these objects
and to reduce the coupling between these objects.

Without dependency injection, your instances management can go wrong
--------------------------------------------------------------------

Here is a sample. I have a `Mailer` class in charge of sending mails. I want my class to be able to log
each mail sent in a file. For this, I want to use the `Logger` class.

The first coding attempt would be something like this:

```php

class Mailer {
	private $logger;
	
	public function __construct() {
		$this->logger = new Logger();
	}
	
	public function sendMail($to, $title, $text) {
		// Do stuff to send the mail
		// Once sent, let's log it.
		$this->logger->log("Mail sent");
	}
}

class Logger {
	private $fp;

	public function __construct() {
		$this->fp = fopen("logfile.txt", "a");
	}
	
	public function log($text) {
		fwrite($this->fp, $text);
	}

}

// Usage:
$mailer = new Mailer();
$mailer->sendMail('toto@example.com', 'title', 'body');
```

This first attempt has a number of **drawbacks**.
First of all, the `Mailer` class needs the `Logger` class. There is a **"dependency"**. Because we instanciate 
the `Logger` in the Mailer's constructor, there is no easy way to get a mailer with a different logger.

Furthermore, if you want to have another service to access the logger, it will have to create its own instance,
and things might go wrong if we try to open the same file several times. We should really have only one
instance of the logger.

Finally, the logfile name should be configurable.

Second try, let's get rid of the dependency
-------------------------------------------

In this second try, we will move the dependency out of the classes.

```php

class Mailer {
	private $logger;
	
	/**
	 * @var $logger Logger
	 */
	public function __construct($logger) {
		$this->logger = $logger;
	}
	
	public function sendMail($to, $title, $text) {
		// Do stuff to send the mail
		// Once sent, let's log it.
		$this->logger->log("Mail sent");
	}
}

class Logger {
	private $fp;

	public function __construct($logfile) {
		$this->fp = fopen($logfile, "a");
	}
	
	public function log($text) {
		fwrite($this->fp, $text);
	}

}

// Usage => less easy
$logger = new Logger("logfile.txt");
$mailer = new Mailer($logger);
$mailer->sendMail('toto@example.com', 'title', 'body');
```

This approach is **cleaner**. Because the `Mailer` class is not bound to the `Logger` class, we could
replace our class with another class (provided they share a common *interface*). However, using the class
is now difficult. In particular, we have to create the logger object first, and pass it to the mailer
object. In this exemple, these are only 2 lines of code, but imagine instanciating a controller that requires
a mailer, a database connection, a logger, a templating service, etc... Your code creating instances can
quickly become complex and difficult to maintain. This is the **spaghetti code effect**.

This is where **Mouf** comes to the rescue.

Mouf will manage the instanciation code for you. Instead of writing spaghetti code, you use Mouf's 
web-based user interface. You define your instances easily, they are stored in a "container" (this is
actually one big configuration file managed by Mouf), and you can get your instances from the container 
easily as well.

[So let's get started!](mouf_di_ui.md)