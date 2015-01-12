<?php /* @var $this Mouf\Controllers\MoufInstallController */ ?>
<img src="src-dev/views/images/MoufLogo.png">
<h1>Welcome!</h1>
<h2>Apache configuration problem</h2>

<div class="alert">In order to run Mouf, you need to use Apache, and be allowed to use <code>.htaccess</code> files.</div>

<p>It is likely that your Apache server	has been configured to ignore the <code>.htaccess</code> files.
Please dive into your Apache configuration and look for a <code>AllowOverride</code> directive.
You should set this directive to: <code>AllowOverride All</code>.</p>

<p>You can follow the <a href="http://mouf-php.com/packages/mouf/mouf/doc/installing_mouf.md">installation document</a> if you need help.</p>
