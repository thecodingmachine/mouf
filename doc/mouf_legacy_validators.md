Mouf legacy validators
======================

Validators are the self-check items displayed on the status page.

<div class="alert">If you want to provide your own validators, we strongly advise you to use the new [Mouf2 validators](writing_mouf_validator.md).</div>

If for some reason, your validator cannot be bound to a class, nor to an instance, you might still want
to have a look at the old Mouf 1 validators that are directly implemented as Ajax calls.

Things you should know about legacy validators
----------------------------------------------

Validators are run asynchronously. Each validator is run as an Ajax request that returns a JSON message.
The format of the JSON message is:

```js
{
	code: "ok|warn|error",
 	html: "HTML code to be displayed on the Mouf validate screen"
}
```

Developing you own validator
----------------------------

###Writing the asynchronous script

The first thing to do will be to write the validator script that will return the JSON message. 
By convention, all Ajax calls are placed in a <em>/direct</em> directory at the root of your package directory.<br/>
Below is an exemple of validator for the Splash package. It checks that the ".htaccess" file does indeed exist at the root of the Mouf project.

```php
// This file validates that a .htaccess file is defined at the root of the project.
// If not, an alert is raised.

require_once dirname(__FILE__)."/../../../../../mouf/Mouf.php";

$jsonObj = array();

if (file_exists(ROOT_PATH.".htaccess")) {
	$jsonObj['code'] = "ok";
	$jsonObj['html'] = "Splash .htaccess file found";
} else {
	$jsonObj['code'] = "warn";
	$jsonObj['html'] = 'Unable to find Splash .htaccess file. You should <a href="'.ROOT_URL.'mouf/splashApacheConfig/">configure the Apache redirection</a>.';
}

echo json_encode($jsonObj);
exit;
```

<div class="alert alert-info">Advice: when you finished coding your asynchronous script, try to access it directly from your browser, to be sure it is working correctly.</div>

###Registering the script

Once you checked your asynchronous script and you know is is working, it is time to register your script into Mouf. 
The validator must be registered each time you access the Mouf UI. So the first step will be to add a PHP file in your package directory, and register that file in the <em>package.xml</em> 
package descriptor.

As an example, we will create a <em>declareValidator.php</em> in the **src/** directory of your package.
Now, open the **composer.json** file, and add those lines:

```js
{
	...
	"extra": {
    	"mouf": {
			"require-admin": [
    			"src/declareValidator.php"
    		],
    	}
    }
}
```

All the files declared inside the <code>require-admin</code> tags will be included when the developer accesses the Mouf UI.
Now, let's edit the content of declareValidator.php. In Mouf, the component responsible for running the validations is the <code>MoufValidatorService</code>.
The <code>MoufValidatorService</code> has a simple method named <code>registerBasicValidator</code> that can be called to add new validators. So you can just write this:

```php
use Mouf\MoufUtils;

MoufAdmin::getValidatorService()->registerBasicValidator('My validator', MoufUtils::getUrlPathFromFilePath(__DIR__.'/direct/apc_validator.php', true));
```

The first parameter is the name of the validator. The name of the validator is displayed when the validator is asynchronously running and has not yet returned the result.
The second parameter is the URL. The URL of the validator is appended to the ROOT_URL. The MoufUtils::getUrlPathFromFilePath method will automatically find the URL of the package for you.
Please note that this assumes your "vendor" directory is accessible from the web, which might not always be the case (especially if you are using Symfony or ZF2).

<div class="alert alert-info">Note: there are even more advanced ways to register validators. See below for more information.</div>

Our validator is finished. You can now go to the Mouf home page, and it should appear along the other validators.

Going further with (legacy) validators
--------------------------------------

Internally, the MoufValidatorService contains a list of validation providers (instances of MoufBasicValidationProvider that implements the interface MoufValidationProviderInterface).
You can use the method <code>registerValidator(MoufValidationProviderInterface $validationProvider)</code> of the <code>MoufValidatorService</code> to code your own validator provider instance.
