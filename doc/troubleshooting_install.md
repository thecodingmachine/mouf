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

Mouf status page displays "Unable to unserialize message" errors after install
----------------------------------------------
You just installed Mouf on a remote server and you try to access your Mouf instance using "http://[myserver]/[myapp]/vendor/mouf/mouf".
You get an error that looks like this: "Unable to unserialize... URL in error... get_class_map.php".

![troubleshooting_domain_name.png](Mouf error on startup)

This error is caused by Mouf introspection mechanism. Mouf must itself trigger requests on the server. To do so, it uses the CURL library. So let's imagine your server URL is "foo.example.com". When you access the status page, the PHP code of Mouf will perform additional queries on "foo.example.com". Most of the time, this works... unless the server does not know its name is "foo.example.com". This can happen if the server is having DNS issues, or if the "foo.example.com" domain name is not shared with the server (for instance if this is a fake domain name that you added on the `/etc/hosts` file of your development environment).

To fix this, make sure that from the server, you can ping the same server using your hostname. So by connecting in SSH to the server and typing `ping foo.example.com`, I should see the IP address of the server.

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

<a name="composer_hangs"></a>
Composer hangs while checking out some project
------------------------------------------------------------------------
You start the installation using "php composer.phar install", and Composer hangs on one of the projects. This is a Composer related issue.
This behaviour has been spoted on Windows 8, using Cygwin.

To solve this problem, instead of using Cygwin, try using the Windows command-line client, or gitbash if you have it. One of them might help you with the install.


Mouf causes Apache to stop and restart on Windows (but everyting is fine with Linux)
------------------------------------------------------------------------------------
If you are encountering such a problem, it is likely that your Apache installation is limited to a stack trace of 1Mo.
Although this is fine with Mouf, you might encounter very disturbing errors in Mouf with some PHP files. You should edit your <em>httpd.conf</em> file and add these lines:
<pre>
&lt;IfModule mpm_winnt_module&gt;
   ThreadStackSize 8388608
&lt;/IfModule&gt;
</pre>
This will increase the Apache stacktrace to 8Mo. You can learn more about it <a href="http://stackoverflow.com/questions/5058845/how-do-i-increase-the-stack-size-for-apache-running-under-windows-7">on StackOverflow</a>.


Fatal error on mouf validation using MAC OSX
------------------------------------------------------------------------------------
When installing mouf 2.0, during the validation step, if you run into this error : 

	<b>Fatal error</b>:  Uncaught exception 'Symfony\Component\Process\Exception\RuntimeException' with message 'The process stopped because of a &quot;0&quot; signal.' in phar:///Users/camk/Web/www/mouf/vendor/mouf/mouf/composer.phar/vendor/symfony/process/Symfony/Component/Process/Process.php:446

You need to change a configuration file of MAMP. Open the <em>envvars</em> file located at the following path : /Applications/MAMP/Library/bin/envvars , you will notice the two lines : 

	DYLD_LIBRARY_PATH="/Applications/MAMP/Library/lib:$DYLD_LIBRARY_PATH"
	export DYLD_LIBRARY_PATH

Comment them (add # in front of each line).
Restart your server and reload the mouf validation page.

Fatal error on Mouf validation
------------------------------

When running Mouf for the first time, on Mouf status page, you get this error:

	<div class="alert alert-error">Exception: Unable to unserialize message:

	&lt;br/&gt;URL in error: &lt;a href='http://localhost:80/project/vendor/mouf/mouf/../../../vendor/mouf/mouf/src/direct/get_class_map.php?selfedit=false'&gt;http://localhost:80/project/vendor/mouf/mouf/../../../vendor/mouf/mouf/src/direct/get_class_map.php?selfedit=false&lt;/a&gt; in /Users/root/Documents/Projet/src/vendor/mouf/mouf/src/Mouf/Reflection/MoufReflectionProxy.php on line 207</div>

This error has been spotted on MacOS X (MAMP) with PHP 5.5 (but might occur on other environments) when the *memory_limit* setting is too low in *php.ini*. Try increasing this limit (to 256M or even higher).

Packages installation seems to be failing, but succeeds after some time
-----------------------------------------------------------------------
When installing a package, the status of the package stays to "Awaiting installation".
After some time, the status misteriously changes to "Done".

This error occurs in PHP 5.5+ (or in PHP 5.x with Zend Optimizer+ installed).
The status of your packages is stored in a PHP file and the PHP Opcache does not refresh its cache.

You should change this parameter in *php.ini*:

	opcache.revalidate_freq = 0

Page not found on Mouf start-up
---------------------------------------
If you are using PHP 5.5, this error could be related to the Opcache.
Mouf uses Splash as an MVC framework, and Splash relies on annotations.
Annotations are stored in the PHP Docblock and Opcache sometimes drop those docblocks.

Check your *php.ini* file and change this parameter:

	opcache.save_comments = 1
