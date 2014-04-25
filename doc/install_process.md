Writing an install process for your package
===========================================

When you install a package, Mouf2 looks for an install procedure.
If you provide an install procedure for your package, Mouf will execute that install procedure the next time you access the Mouf user interface. Writing install procedures is very useful for initializing instances, writing custom files, etc...

Different kind of install procedures
------------------------------------

An install procedure can be silent (in this case, it is automatically executed without asking the user), or it can be dynamic (in this case, the user can be asked for various parameters).

For instance, a package that provides a database connection might ask for the user the default parameters to access the database.

Declaring the install process
-----------------------------

The install process is declared in the composer.json file. Here is a sample:

```js
{
    "name": "mouf/database.dbconnection",
    ....
    "type": "mouf-library",
    ....
    "extra": {
        "mouf": {
    	    "install": [
    		    {
    			"type": "class",
    			"class": "MyPackage\\MyClass",
    			"scope": "local",
    			"description": "My description"
    		    },
    		    {
    			"type": "url",
    			"url": "dbconnectioninstall",
    			"scope": "local",
    			"description": "My description"
    		    },
    		    {
    			"type": "file",
    			"file": "src/install.php",
    			"scope": "global",
    			"description": "My description"
    		    }
    	    ]
        }
    }
}
```

The first important thing to notice is that the type of the package is `mouf-library`. 
This is important, because if you don't set the type to `mouf-library`, the install process will be ignored.
In this sample, you can see an install process can contain several steps. 
Each step is either a class (that contains an `install` method), an URL (that will be called in the install process), or a file (that will be executed in the install process).

- **Class:** you must give the fully-qualified name of the class (e.g. with the namespace)
- **URL:** The page is relative to Mouf's URL
- **File:** (*deprecated*) The file is relative to the root of the package

There are 2 more parameters that are optional:

- *comment*: This is a simple comment that will be displayed on the Mouf page that lists installation items.
- *scope*: The scope is either `local` or `global`. If an installation is declared "global", the installation has
  to be performed once when the package is installed. Then, if you share your code with other people, the install
  process will not need to be run. If the scope is "local", each time someone will install your code, Mouf will
  propose to run the install process again. This is very useful if you have some files that need to be written
  and that depends on the environment, etc...  

A typical install class
-----------------------

If you opt for a silent install, we advise you to use the `class` approach. Here is a typical install class:

```php
<?php
namespace Mouf\Utils\Log\Psr;

use Mouf\Installer\PackageInstallerInterface;
use Mouf\MoufManager;

/**
 * A logger class that writes messages into the php error_log.
 */
class ErrorLogLoggerInstaller implements PackageInstallerInterface {

	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Installer\PackageInstallerInterface::install()
	 */
	public static function install(MoufManager $moufManager) {
		if (!$moufManager->instanceExists("psr.errorLogLogger")) {
			$errorLogLogger = $moufManager->createInstance("Mouf\\Utils\\Log\\Psr\\ErrorLogLogger");
			
			// Let's set a name for this instance (otherwise, it would be anonymous)
			$errorLogLogger->setName("psr.errorLogLogger");
			$errorLogLogger->getProperty("level")->setValue('warning');
		}
		
		// Let's rewrite the MoufComponents.php file to save the component
		$moufManager->rewriteMouf();
	}
}
```

The `install` method will be called by the Mouf's when the user triggers the installation.
Each `install` method is triggered in its own process, and is run *in the context of the application*.
This means you can access any classes of the application if required.

A typical dynamic install process
---------------------------------

Now, let's have a look at a more complex install process. In this sample, the package will ask the user if he wants to create the "myInstance" instance or not. The user will select the choice using 2 buttons ("yes" or "no").

Internally, Mouf is using the [Splash MVC framework](http://mouf-php.com/packages/mouf/mvc.splash).
Therefore, to interact with the user, we will be writing a [Splash controller](http://mouf-php.com/packages/mouf/mvc.splash/doc/writing_controllers_manually.md).
Here is the controller:

###controller/MyInstallController.php

```php
namespace Test\MyPackage\Controllers;

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Mvc\Splash\Controllers\Controller;



/**
 * The controller managing the install process.
 *
 * @Component
 */
class MyInstallController extends Controller  {
	public $selfedit;
	
	/**
	 * The active MoufManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $moufManager;
	
	/**
	 * The template used by the install process.
	 *
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @var HtmlBlock
	 */
	public $contentBlock;
	
	/**
	 * Displays the install screen.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function defaultAction($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}

		$this->contentBlock->addFile(dirname(__FILE__)."/../../../../views/install.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * The user clicked "no". Let's skip the install process.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
	 */
	public function skip($selfedit = "false") {
		InstallUtils::continueInstall($selfedit == "true");
	}
	
	/**
	 * The user clicked "yes". Let's create the instance.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
	 */
	public function install($selfedit = "false") {
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}

		if (!$this->moufManager->instanceExists("myInstance")) {
			$myInstance = $moufManager->createInstance("MyClass");
			$myInstance->setName("myInstance");
		}		

		$this->moufManager->rewriteMouf();		
		
		InstallUtils::continueInstall($selfedit == "true");
	}
}
```

###views/install.php

```html
<h1>Setting up your instance</h1>

<p>Our package can create automatically a <em>myInstance</em> instance for the class <em>myClass</em>.
So you want to create it?</p>

<form action="install">
	<input type="hidden" name="selfedit" value="<?php echo $this->selfedit ?>" />
	<button>Yes</button>
</form>
<form action="skip">
	<input type="hidden" name="selfedit" value="<?php echo $this->selfedit ?>" />
	<button>No</button>
</form>
```

Ok, we have written our install process. Now, we must create the MyInstallController instance. The problem is we cannot use the Mouf admin interface, since the MyInstallController instance must be created only when the package is enabled.
Hopefully, we can do this using the *composer.json* file. Here is a sample:

###composer.json

```js
{
	...
    "extra": {
    	"mouf": {
	    	"install": [
	    		{
	    			"type": "url",
	    			"url": "myinstall/"
	    		}
	    	],
    		"require-admin": [
    			"src/InstallAdmin.php"
    		]
    	}
    }
}
```

We have already seen the "install" section. Let's focus on the "require-admin" section.
When you declare files in "require-admin", these files will be included each time you load a page in the Mouf user interface. So the files you put inside the "adminRequires" are loaded for each page of the Mouf administration pages (and not in your application). Therefore, it is the perfect place to request our controller.

You will also notice we include a second file: **InstallAdmin.php**. We haven't yet introduced that file. We use that file to create an instance of the MyInstallController class on the fly. Here is the content:


###InstallAdmin.php

```php
use Mouf\MoufManager;

// Let's declare the contoller
MoufManager::getMoufManager()->declareComponent('myinstall', 'Test\\MyPackage\\Controllers\\MyInstallController', true);
// Let's bind the 'template' property of the controller to the 'installTemplate' instance
MoufManager::getMoufManager()->bindComponents('myinstall', 'template', 'moufInstallTemplate');
MoufManager::getMoufManager()->bindComponents('myinstall', 'contentBlock', 'block.content');
```


The 'moufInstallTemplate' instance is an instance of template declared in the admin that contains no menu bars.
It is very useful to display install pages, where you want your user to stay on the page and not click on a menu item that would bring it out of the install process.

That's it for the dynamic install process. You should now know enough to create your own install processes.
Do not hesitate to <a href="http://mouf-php.com/packages/mouf/mvc.splash/index.md">learn more about Splash</a> if you want to write complex controllers.


A typical install file
----------------------

<div class="alert">This is a legacy approach and has been superseded by the "class" technique.</div>

If you opt for a silent install, we can use the "file" approach. Here is a typical install file:

```php
require_once __DIR__."/../../../autoload.php";

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();
if (!$moufManager->instanceExists("errorLogLogger")) {
	
	$errorLogLogger = $moufManager->createInstance("Mouf\\Utils\\Log\\ErrorLogLogger");
	// Let's set a name for this instance (otherwise, it would be anonymous)
	$errorLogLogger->setName("errorLogLogger");
	$errorLogLogger->getProperty("level")->setValue(4);
	/*$moufManager->declareComponent("errorLogLogger", "ErrorLogLogger");
	$moufManager->setParameter("errorLogLogger", "level", 4);*/
}

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
```

The parts of this code that are specific to install lies in the **InstallUtils** class.

The <code>InstallUtils::init</code> static method will load Mouf. In the case of an install process, you can load Mouf in 2 different "contexts".

- Using <code>InstallUtils::init(InstallUtils::$INIT_APP);</code>, you can load Mouf in the context of the application that is developed. This is useful if you want to create a new instance in our application. This is what we are doing in the sample.
- Using <code>InstallUtils::init(InstallUtils::$INIT_ADMIN);</code>, you can load Mouf in the context of the Mouf administration interface. This is useful if you have special actions to perform in the context of the admin (like adding a menu, etc...)

The second important part of this code is the call to <code>InstallUtils::continueInstall()</code> method.
This call is required to continue the global install process. If you do not call <code>InstallUtils::continueInstall()</code>, the process to enable your package will halt.
