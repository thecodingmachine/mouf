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
    			"type": "file",
    			"file": "src/install.php"
    		    },
    		    {
    			"type": "url",
    			"url": "dbconnectioninstall"
    		    }
    	    ]
        }
    }
}
```

The first important thing to notice is that the type of the package is "mouf-library". This is important, because if you don't set the type to "mouf-library", the install process will be ignored.
In this sample, you can see an install process can contain several steps. Each step is either a file (that will be executed directly), or an URL (that will be called in the install process).

- <b>File:</b> The file is relative to the root of the package
- <b>URL:</b> The page is relative to Mouf's URL

A typical install file
----------------------

If you opt for a silent install, we advise you to use the "file" approach. Here is a typical install file:

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

The parts of this code that are specific to install lies in the <b>InstallUtils</b> class.

The <code>InstallUtils::init</code> static method will load Mouf. In the case of an install process, you can load Mouf in 2 different "contexts".

- Using <code>InstallUtils::init(InstallUtils::$INIT_APP);</code>, you can load Mouf in the context of the application that is developed. This is useful if you want to create a new instance in our application. This is what we are doing in the sample.
- Using <code>InstallUtils::init(InstallUtils::$INIT_ADMIN);</code>, you can load Mouf in the context of the Mouf administration interface. This is useful if you have special actions to perform in the context of the admin (like adding a menu, etc...)

The second important part of this code is the call to <code>InstallUtils::continueInstall()</code> method.
This call is required to continue the global install process. If you do not call <code>InstallUtils::continueInstall()</code>, the process to enable your package will halt.

A typical dynamic install process
---------------------------------

TODO! Continue here!!!!!!!

Now, let's have a look at a more complex install process. In this sample, the package will ask the user if he wants to create the "myInstance" instance or not. The user will select the choice using 2 buttons ("yes" or "no").

Internally, Mouf is using the <a href="/package/mvc/splash">Splash MVC framework</a>. Therefore, to interact with the user, we will be writing a <a href="/package_doc/mvc/splash/3.2/writing_controllers.html">Splash controller</a>. Here is the controller:

<h3>controller/MyInstallController.php</h3>
<pre class="brush:php">
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
	 * @Property
	 * @Compulsory
	 * @var TemplateInterface
	 */
	public $template;
	
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
				
		$this->template->addContentFile(dirname(__FILE__)."/../views/install.php", $this);
		$this->template->draw();
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
</pre>

<h3>views/install.php</h3>

<pre class="brush:html">
&lt;h1&gt;Setting up your instance&lt;/h1&gt;

&lt;p&gt;Our package can create automatically a &lt;em&gt;myInstance&lt;/em&gt; instance for the class &lt;em&gt;myClass&lt;/em&gt;.
So you want to create it?&lt;/p&gt;

&lt;form action="install"&gt;
	&lt;input type="hidden" name="selfedit" value="&lt;?php echo $this-&gt;selfedit ?&gt;" /&gt;
	&lt;button&gt;Yes&lt;/button&gt;
&lt;/form&gt;
&lt;form action="skip"&gt;
	&lt;input type="hidden" name="selfedit" value="&lt;?php echo $this-&gt;selfedit ?&gt;" /&gt;
	&lt;button&gt;No&lt;/button&gt;
&lt;/form&gt;
</pre>

Ok, we have written our install process. Now, we must create the MyInstallController instance. The problem is we cannot use the Mouf admin interface, since the MyInstallController instance must be created only when the package is enabled.
Hopefully, we can do this using the package.xml file. Here is a sample:

<pre class="brush:xml">
&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;package&gt;
	...
	&lt;adminRequires&gt;
		&lt;require&gt;controllers/MyInstallController.php&lt;/require&gt;
		&lt;require&gt;InstallAdmin.php&lt;/require&gt;
	&lt;/adminRequires&gt;
	&lt;install&gt;
		&lt;url&gt;mouf/myinstall/&lt;/url&gt;
	&lt;/install&gt;
&lt;/package&gt;
</pre>

We have already seen the &lt;install&gt; tag. Let's focus on the &lt;adminRequires&gt; tag. This tag is a bit like the &lt;requires&gt; tag, except it includes files in the admin. So the files you put inside the <adminRequires> tag are loaded for each page of the Mouf administration pages (and not in your application). Therefore, it is the perfect place to request our controller.

You will also notice we include a second file: InstallAdmin.php. We haven't yet introduced that file. We use that file to create an instance of the MyInstallController class on the fly. Here is the content:


<pre class="brush:php">
// Let's declare the contoller
MoufManager::getMoufManager()->declareComponent('myinstall', 'MyInstallController', true);
// Let's bind the 'template' property of the controller to the 'installTemplate' instance
MoufManager::getMoufManager()->bindComponents('myinstall', 'template', 'instanceTemplate');
</pre>


The 'installTemplate' instance is an instance of template declared in the admin that contains no menu bars.
It is very useful to display install pages, where you want your user to stay on the page and not click on a menu item that would bring it out of the install process.

That's it for the dynamic install process. You should now know enough to create your own install processes.
Do not hesitate to <a href="/package/mvc/splash">learn more about Splash</a> if you want to write complex controllers.