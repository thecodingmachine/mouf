<?php /* @var $this Mouf\Controllers\MoufInstallController */ ?>
<img src="src-dev/views/images/MoufLogo.png">
<h1>Welcome!</h1>

<?php
define('MOUF_DIR', dirname(__FILE__)."/../../..");

if (!is_writable(MOUF_DIR) || !is_writable(MOUF_DIR."/../../..") || (file_exists(MOUF_DIR."/../../../mouf") && !is_writable(MOUF_DIR."/../../../mouf"))) {
?>

		<div class="alert">Web directory must be writable for the Apache user</div>
		<p>In order to run Mouf, you will first need to change the permissions on the web directory so that the Apache user can write into it.
		Especially, you should check that those directories can be written into:</p>
		<ul>
			<?php if(!is_writable(MOUF_DIR."/../../..")) {?>
				<li><?php echo realpath(MOUF_DIR."/../../..") ?></li>
			<?php }
			if(!is_writable(MOUF_DIR)) {?>
				<li><?php echo realpath(MOUF_DIR) ?></li>
			<?php }
			if(file_exists(MOUF_DIR."/../../../mouf") && !is_writable(MOUF_DIR."/../../../mouf")) {?>
				<li><?php echo realpath(MOUF_DIR."/../../../mouf") ?></li>
			<?php }?>
		</ul>
		<?php if (function_exists("posix_getpwuid")) {
			$processUser = posix_getpwuid(posix_geteuid());
			$processUserName = $processUser['name'];
		?>
			<p>You can try these commands:</p>
			<pre>
			<?php if(!is_writable(MOUF_DIR."/../../..")) {?>
sudo chown <?php echo $processUserName.":".$processUserName." ".realpath(MOUF_DIR."/../../..") ?><br/>
			<?php }
			if(!is_writable(MOUF_DIR)) {?>
sudo chown <?php echo $processUserName.":".$processUserName." ".realpath(MOUF_DIR);
			}
			if(file_exists(MOUF_DIR."/../../../mouf") && !is_writable(MOUF_DIR."/../../../mouf")) {?>
sudo chown <?php echo $processUserName.":".$processUserName." ".realpath(MOUF_DIR."/../../../mouf");
			} ?>
</pre>
		<?php 
		}
		?>
		<p>If after running the commands above and refreshing this page, you are still seeing this page, you might want to consider one of the following troubleshooting advices:</p>
		<ul>
			<li>If you are running SELinux: be sure that the directories are parts of the SELinux Apache context. <a href="http://wiki.centos.org/HowTos/SELinux">Get more info about SELinux</a></li>
			<li>If you are running Plesk and virtual domains: in Plesk, in your domain settings, you might want to switch "PHP Support" from "Apache module" to "FastCGI application".</li>
		</ul>

<?php
	return;
}
?>

<form action="src/install.php" method="post" class="form-horizontal">

	<p>Apparently, this is the first time you are running Mouf. You will need to install it.</p>
	<?php if (file_exists(MOUF_DIR."/../../../mouf/MoufUsers.php")): ?>
		<p>The <code>MoufUsers.php</code> file has been detected. Logins/passwords from this file will be used to access Mouf.
		If you want to reset your login or password, delete the MoufUsers.php file and start again the installation procedure.</p>		
	<?php else: ?>
				<p>In order to connect to Mouf, you will need to create a login and a password.</p>

	<div class="control-group">
		<label class="control-label" for="login">Login</label>
		<div class="controls">
			<input name="login" id="login" value="admin" type="text" required />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="password">Password</label>
		<div class="controls">
			<input name="password" id="password" value="" type="password" required />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="password2">Password (check)</label>
		<div class="controls">
			<input name="password2" id="password2" value="" type="password" required data-validation-matches-match="password" data-validation-matches-message=
"Passwords must match"   />
		</div>
	</div>
	
			<?php endif ?>
	<p>Please click the install button below. This will create and install a <code>.htaccess</code> file in the <code>vendor/mouf/mouf</code> directory.
	This will also create a <code>config.php</code> file in your root directory and a <code>mouf</code> directory containing a number of files (if they don't already exist)</p>
	<p>Please make sure that the root directory is writable by your web-server.</p>
	<p>Finally, please make sure that the <strong>Apache Rewrite</strong> module is enabled on your server. Since this install process will create a <code>.htaccess</code> file, 
	you must make sure it will be taken into account. If after clicking the "Install" button, nothing happens, it is likely that your Apache server
	has been configured to ignore the <code>.htaccess</code> files. In this case, please dive into your Apache configuration and look for a <code>AllowOverride</code> directive.
	You should set this directive to: <code>AllowOverride All</code>.</p>

	<input type="submit" value="Install" class="btn btn-primary" />
</form>

<script type="text/javascript">
$(function () { $("input,select,textarea").not("[type=submit]").jqBootstrapValidation(); } );
</script>