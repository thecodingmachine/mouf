Writing a Mouf validator
========================

What is a Mouf validator?
-------------------------

When you connect to the front page of Mouf, you can see the status of your application.

<img src="images/status-screen.png" alt="" />

Each little box validating a part of your application is a "Mouf validator".
To make things simple, a validator is a piece of software that helps the developer to check
for errors in his code. The nice thing with validators is they can be provided by any class.
So if you are developing your own package, you can provide custom validators to help the developers
use your package correctly.

Things you should know about validators
---------------------------------------

There are 3 kinds of validators:

- Class validators (each class generates one validator) 
- Instance validators (each instance generates one validator)
- Custom validators (almost always useless and out of scope of this document. Check the legacy validators code to learn more)

Developing a class validator
----------------------------

In order to add class validator, your class just needs to implement the <code>MoufStaticValidatorInterface</code> interface.

Here is a sample (from the Splash MVC package), that validates there is a .htaccess file in the root directory:

```php
class SplashHtaccessValidator implements MoufStaticValidatorInterface {
	
	/**
	 * Runs the validation of the class.
	 * Returns a MoufValidatorResult explaining the result.
	 *
	 * @return MoufValidatorResult
	 */
	public static function validateClass() {
		if (file_exists(ROOT_PATH.".htaccess")) {
			return new MoufValidatorResult(MoufValidatorResult::WARN, "Unable to find .htaccess file.");
		} else {
			return new MoufValidatorResult(MoufValidatorResult::SUCCESS, ".htaccess file found.");
		}
	}

}
```

As you can see, you have a single method to implement: <code>validateClass</code>. This method must be **static**.
<code>validateClass</code> must return a <code>MoufValidatorResult</code>.

The first parameter of the <code>MoufValidatorResult</code> constructor is the result type.
It can be one amongst:

- MoufValidatorResult::SUCCESS
- MoufValidatorResult::WARN
- MoufValidatorResult::ERROR

The second parameter is the text that will be displayed.

<div class="alert alert-info"><strong>Note:</strong> When you create a new validator, for your validator to appear 
in the Mouf status page, please be sure to click the <strong>purge the code cache</strong> button.</div>

Developing an instance validator
--------------------------------

Developing an instance validator is quite similar to a class validator: your class just needs to implement the <code>MoufValidatorInterface</code> interface.

Here is a sample where a validator where a sample Controller checks there is a template associated to it.

```php
class MyController implements MoufValidatorInterface {
	
	public $template;
	
	/**
	 * Runs the validation of the instance.
	 * Returns a MoufValidatorResult explaining the result.
	 *
	 * @return MoufValidatorResult
	 */
	public function validateInstance() {
		if ($this->template == null) {
			return new MoufValidatorResult(MoufValidatorResult::ERROR, "You must associate a template to the controller.");
		} else {
			return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "Template found in controller.");
		}
	}

}
```

For each instance of the class declared in Mouf, the validator will be run once. This also means that if you do not
create an instance of this class, the validator will be ignored.

Including Mouf validators in your Composer packages
---------------------------------------------------

You might want to include Mouf validators in your Composer packages, but without making your package dependant on the
whole Mouf package. For this reason, the Mouf validator interfaces have been isolated in a very small package:
**mouf/mouf-validators-interface**.

This way, in your Composer package, if you decide to use Mouf validators, you just need to add a dependency on this package:

####composer.json
```js
{
	...
    "require": {
    	"mouf/mouf-validators-interface": "~2.0"
    }
}
```
