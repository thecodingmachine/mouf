<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
?>
<html>

	<head>

                <link href="../plugins/html/template/MoufTemplate/1.0/css/style.css" rel="stylesheet" type="text/css">
		<title>Welcome to Mouf</title>
	</head>
        <body>
        <div id="page">
        <div id="header">
                <div id="logo">
                        <a href="/Mouf_Website/">
                                <img src="../mouf/views/images/MoufLogo.png" alt="Mouf" />
                        </a>
                </div>
        </div>

        <div id="content">
            

<?php 
if (!extension_loaded("curl")) {
?>
	
		<h1>Missing dependencies</h1>

		<p>In order to run Mouf, you will first need to enable the "php_curl" extension on your server.</p>
		<p>Please enable this extension and refresh this page.</p>
                <p>Help topic : <a href="http://mouf-php.com/node/12" target="_blank">link</a>.</p>
                </div>
            </div>
	</body>
</html>
<?php 
	exit();
}

if (!is_writable(dirname(__FILE__)) || !is_writable(dirname(__FILE__)."/..")) {
?>

		<h1>Web directory must be writable for the Apache user</h1>
		<p>In order to run Mouf, you will first need to change the permissions on the web directory so that the Apache user can write into it.
		Especially, you should check that those 2 directories can be written into:</p>
		<ul>
			<?php if(!is_writable(dirname(__FILE__)."/..")) {?>
				<li><?php echo realpath(dirname(__FILE__)."/..") ?></li>
			<?php }
			if(!is_writable(dirname(__FILE__))) {?>
				<li><?php echo realpath(dirname(__FILE__)) ?></li>
			<?php }?>
		</ul>
		<?php if (function_exists("posix_getpwuid")) {
			$processUser = posix_getpwuid(posix_geteuid());
			$processUserName = $processUser['name'];
		?>
			<p>You can try these commands:</p>
			<pre>
			<?php if(!is_writable(dirname(__FILE__)."/..")) {?>
chown <?php echo $processUserName.":".$processUserName." ".realpath(dirname(__FILE__)."/..") ?><br/>
			<?php }
			if(!is_writable(dirname(__FILE__))) {?>
chown <?php echo $processUserName.":".$processUserName." ".realpath(dirname(__FILE__));
			}?>
</pre>
		<?php 
		}
		?>
		<p>If after running the commands above and refreshing this page, you are still seeing this page, you might want to consider one of the following troubleshooting advices:</p>
		<ul>
			<li>If you are running SELinux: be sure that the directories are parts of the SELinux Apache context. <a href="http://wiki.centos.org/HowTos/SELinux">Get more info about SELinux</a></li>
			<li>If you are running Plesk and virtual domains: in Plesk, in your domain settings, you might want to switch "PHP Support" from "Apache module" to "FastCGI application".</li>
		</ul>
                </div>
                </div>
	</body>
</html>
<?php
	exit();
}
?>
		<h1>Welcome to the Mouf framework</h1>
		<form action="install.php" method="post">
		
			<p>Apparently, this is the first time you are running Mouf. You will need to install it.</p>
			<?php if (file_exists(dirname(__FILE__)."/../MoufUsers.php")): ?>
				<p>The MoufUsers.php file has been detected. Logins/passwords from this file will be used to access Mouf.
				If you want to reset your login or password, delete the MoufUsers.php file and start again the installation procedure.</p>		
			<?php else: ?>
				<p>In order to connect to Mouf, you will need a login and a password.</p>

                                    <table><tr><td>
                                    <label>Login: </td><td><input name="login" value="admin" type="text" /></label></td></tr>
                                    <tr><td>
                                    <label>Password: </td><td><input name="password" type="password" /></td></tr>
                                    </table>

			<?php endif ?>
			<p>Please click the install button below. This will create and install a ".htaccess" file in the "Mouf" directory.
			This will also create 7 files in your root directory: config.php, Mouf.php, MoufComponents.php, MoufRequire.php, MoufUI.php, MoufUniversalParameters.php and MoufUsers.php (if they don't already exist)</p>
			<p>Please make sure that the Mouf directory is writable by your web-server.</p>
			<p>Finally, please make sure that the Apache Rewrite module is enabled on your server. Since this install process will create a ".htaccess" file, 
			you must make sure it will be taken into account. If after clicking the "Install" button, nothing happens, it is likely that your Apache server
			has been configured to ignore the ".htaccess" files. In this case, please dive into your Apache configuration and look for a "<code>AllowOverride</code>" directive.
			You should set this directive to: "<code>AllowOverride All</code>".</p>
		
			<input type="submit" value="Install" />
		</form>
                </div>
            </div>
	</body>
</html>