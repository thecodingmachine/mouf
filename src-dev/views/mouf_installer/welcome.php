<?php /* @var $this Mouf\Controllers\MoufInstallController */ ?>
<img src="src-dev/views/images/MoufLogo.png">
<h1>Welcome!</h1>

<?php
define('MOUF_DIR', dirname(__FILE__)."/../../..");

if (!is_writable(MOUF_DIR."/../../..") || (file_exists(MOUF_DIR."/../../../mouf") && !is_writable(MOUF_DIR."/../../../mouf"))) {
?>

		<div class="alert">Web directory must be writable for the Apache user</div>
		<p>In order to run Mouf, you will first need to change the permissions on the web directory so that the Apache user can write into it.
		Especially, you should check that those directories can be written into:</p>
		<ul>
			<?php if(!is_writable(MOUF_DIR."/../../..")) {?>
				<li><?php echo realpath(MOUF_DIR."/../../..") ?></li>
			<?php }
			if(file_exists(MOUF_DIR."/../../../mouf") && !is_writable(MOUF_DIR."/../../../mouf")) {?>
				<li><?php echo realpath(MOUF_DIR."/../../../mouf") ?></li>
			<?php }?>
		</ul>
		<?php if (function_exists("posix_getpwuid")) {
			$processUser = posix_getpwuid(posix_geteuid());
			$processUserName = $processUser['name'];
		?>
			<h2>Solution 1 (best solution):</h2>
		
			<p>Your current user must be able to access and edit the files,
but Mouf will also need to access and edit some of those files. Since Mouf is a PHP application,
it will be executed using the "Apache" user (assuming you are using Apache).</p>

			<p>The name for the Apache user is <strong><?php echo $processUserName; ?></strong></p>
			
			<p>The easiest and more portable way of sharing your rights with the Apache user is to be part of the same
Unix group.</p>

			<p>To do this, on <strong>Debian/Ubuntu</strong> based distributions, you can run:</p>

<pre><code>sudo adduser `whoami` <?php echo $processUserName; ?> 
sudo adduser <?php echo $processUserName; ?> `whoami` 
sudo chmod g+w <?php echo realpath(MOUF_DIR."/../../..") ?> -R</code></pre>

			<p>On other distributions (<strong>Redhat/CentOS ...</strong>), run:</p>
			
<pre><code>sudo useradd -G `whoami` <?php echo $processUserName; ?> 
sudo useradd -G <?php echo $processUserName; ?> `whoami`
sudo chmod g+w <?php echo realpath(MOUF_DIR."/../../..") ?> -R</code></pre>
			

<p>This will add your current user to the <strong><?php echo $processUserName; ?></strong> group, and add 
the <strong><?php echo $processUserName; ?></strong> group to your current user.
Then it will give write access to the group.</p>
		
			<h2>Solution 2:</h2>
		
			<p>You can try these commands:</p>
			<pre><code><?php if(!is_writable(MOUF_DIR."/../../..")) {?>
sudo chown <?php echo $processUserName.":".$processUserName." ".realpath(MOUF_DIR."/../../..") ?> 
<?php }
			if(!is_writable(MOUF_DIR)) {?>
sudo chown <?php echo $processUserName.":".$processUserName." ".realpath(MOUF_DIR)."<br/>";
			}
			if(file_exists(MOUF_DIR."/../../../mouf") && !is_writable(MOUF_DIR."/../../../mouf")) {?>
sudo chown <?php echo $processUserName.":".$processUserName." ".realpath(MOUF_DIR."/../../../mouf");
			} ?>
</code></pre>
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

<form action="<?php echo ROOT_URL; ?>install" method="post" class="form-horizontal">

	<p>Apparently, this is the first time you are running Mouf. You will need to install it.</p>
	<?php if (file_exists(MOUF_DIR."/../../../mouf/no_commit/MoufUsers.php")): ?>
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
	<p>Please click the install button below. This will create and install a <code>config.php</code> file in your root 
	directory and a <code>mouf</code> directory containing a number of files (if they don't already exist)</p>
	<p>Please make sure that the root directory is writable by your web-server.</p>

	<input type="submit" value="Install" class="btn btn-primary" />
</form>

<script type="text/javascript">
$(function () { $("input,select,textarea").not("[type=submit]").jqBootstrapValidation(); } );
</script>
