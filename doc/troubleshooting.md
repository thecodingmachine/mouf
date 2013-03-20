Troubleshooting Mouf2 installation
==================================

While installing Mouf2, you might run in a variety of problems depending on your environment. We will try to list here the most common problems and how to solve those.

Composer complains because "https" is not available
-----------------------------------------------------------------------------
You see this error because Mouf2 relies on a package in "dev" mode. You are probably trying to install a dev release of Mouf.

<strong>To fix this, you must be sure to enable the *php-openssl* extension.</strong>

<div class="alert"><strong>WAMP users, warning!</strong> There are 2 php.ini files in WAMP. Because this extension is used by the php CLI (command-line interface), you must enable this extension in the php.ini file relative to the CLI. This is *php.ini* that you can find in the php directory (not the apache directory). Using the WAMP icon will not work as this changes the apache php.ini file instead of the PHP CLI php.ini file. The <em>php.ini</em> you are looking for is by default installed in the <em>C:/wamp/bin/php/phpX.XX/</em> directory</div>

Composer asks for a login and password
-----------------------------------------------------------
You see this error because Mouf2 relies on a package in "dev" mode. You are probably trying to install a dev release of Mouf.
Mouf2 is hosted in Github (https://github.com/)
Just create an account on Github and setup a public/private key as explained here: https://help.github.com/articles/generating-ssh-keys.

Composer fails to checkout a project
-----------------------------------------------------
You see an error message similar to this:


	Installing dependencies
	  - Installing mouf/mouf-installer (2.0.x-dev 4235344)
	    Cloning 4235344c10be22bdf33afe5372bc5c9440a96a50

	  [RuntimeException]
	  Failed to execute git clone "https://bc07916c5bb6a757cd3111c3f00461cad859ad
	  c5:***@github.com/thecodingmachine/mouf-installer.git" "E:\wamp\www\mouf2\v
	  endor/mouf/mouf-installer" && cd "E:\wamp\www\mouf2\vendor/mouf/mouf-instal
	  ler" && git remote add composer "https://bc07916c5bb6a757cd3111c3f00461cad8
	  59adc5:***@github.com/thecodingmachine/mouf-installer.git" && git fetch com
	  poser

	  error: error setting certificate verify locations:
	    CAfile: bincurl-ca-bundle.crt
	    CApath: none while accessing https://bc07916c5bb6a757cd3111c3f00461cad859
	  adc5:x-oauth-basic@github.com/thecodingmachine/mouf-installer.git/info/refs
	  fatal: HTTP request failed

You see this error because Mouf2 relies on a package in "dev" mode. You are probably trying to install a dev release of Mouf.
Composer will try to fetch the packages from Github. Composer might have troubles connecting to your Github account.
Be sure you correctly set up your public/private key, as explained here: https://help.github.com/articles/generating-ssh-keys

Also, if you did configure a public/private key with a passphrase, this might be a problem. Composer does not know how to deal with this passphrase.

Either install a ssh-agent, or generate a new public/private key without a pasphrase.

Composer locks while checking out a project
----------------------------------------------------------------
You are running Windows, and use Cygwin as a command-line.
When you install Composer, the install process gets stuck while checking out the first project.

Solution: Reboot your computer (when freezing, some processes like **ssh** or **git** do not exit at all). Then, **try running Composer from the Windows command-line** instead of Cygwin.

<a name="question_marks"></a>
Composer does nothing but displays a bunch of questions marks (?????????)
-----------------------------------------------------------------------------------------------------------------
Have a look at your phar settings. In your _php.ini_ file, try the following settings:

	detect_unicode = Off
	phar.readonly = Off
	phar.require_hash = Off
	suhosin.executor.include.whitelist = phar

<div class="warning"><strong>Warning!</strong> On many PHP installs, there are 2 php.ini files. One applies to Apache and the other one to the command line interface. Because Composer is used by both the CLI and Apache, you should modify both php.ini files.</div>

<a name="moufmanager_notfound"></a>
The "class 'Mouf\\MoufManager' not found" error
----------------------------------------------------------------------
Mouf works most of the time, but at times, you get a strange error;

	PHP Notice:  Trying to get property of non-object in /var/www/vendor/composer/ClassLoader.php on line 218
	PHP Warning:  Invalid argument supplied for foreach() in /var/www/vendor/composer/ClassLoader.php on line 218
	PHP Notice:  Trying to get property of non-object in /var/www/vendor/composer/ClassLoader.php on line 228
	PHP Warning:  Invalid argument supplied for foreach() in /var/www/vendor/composer/ClassLoader.php on line 228
	PHP Notice:  Trying to get property of non-object in /var/www/vendor/composer/ClassLoader.php on line 234
	PHP Fatal error:  Class 'Mouf\\MoufManager' not found in /var/www/mouf/MoufComponents.php on line 6

We have found this error can be triggerred by a bug in PHP 5.3.3. It might be related to APC, although we are not sure. Try upgrading PHP to the latest version and reinstalling APC. This should solve this problem.

<a name="composer_hangs"></a>
Composer hangs while checking out some project
------------------------------------------------------------------------
You start the installation using "php composer.phar install", and Composer hangs on one of the projects. This is a Composer related issue.
This behaviour has been spoted on Windows 8, using Cygwin.

To solve this problem, instead of using Cygwin, try using the Windows command-line client, or gitbash if you have it. One of them might help you with the install.
