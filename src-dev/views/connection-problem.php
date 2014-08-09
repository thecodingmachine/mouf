<?php use Mouf\Reflection\MoufReflectionProxy; ?>
<h1>Mouf status</h1>
<div class="alert alert-error">A problem occured with your Mouf installation</div>

<p>For Mouf to work correctly, Mouf needs to be able to call itself from the server, via HTTP requests.</p>

<p>
<img src="<?php echo MOUF_URL ?>src-dev/views/images/connection-problem.png" />
</p>

<div class="alert alert-error">There is a problem with your installation of Mouf. You can successfully call Mouf from your browser,
but <strong>Mouf cannot call itself from the server</strong>. This kind of errors does not usually
happens when accessing Mouf on your localhost.</div>

<h2>Possible causes:</h2>

<ul>
<li><strong>Load balancer issues:</strong> You are accessing Mouf on serveral remote hosts behind a load balancer. <strong>Using Mouf behind a load balancer is not supported.</strong></li>
<li><strong>DNS issues:</strong> There is a DNS problem and the server does not know its domain name. This can happen if you tweaked your <code>/etc/hosts</code> files
or if you have special DNS settings. Here is a test you can do to check this:
<ul>
	<li>Your webserver URL should be <code><?php echo $_SERVER['HTTP_HOST']; ?></code>. Open a terminal on your server,
	for instance using SSH.</li>
	<li>On the server, run this command:
	<pre>wget <?php echo MoufReflectionProxy::getLocalUrlToProject().'src/direct/test_connection.php'; ?></pre>
	</li>
	<li>The download should be successfull. If you have a look at the downloaded file (<code>cat test_connection.php</code>),
	the file should contain just the word "ok".</li>
</ul>
</li>
<li><strong>Authorization issues:</strong> If access to the Mouf URL requires some sort of authentication (apart from the 
standard Mouf authentication process), access to Mouf will fail. Here is a list of possible causes:
	<ul>
		<li>Your Apache configuration requires a Basic HTTP authentication.</li>
		<li>You are using Mouf in a Cloud development environment (like Cloud9) and HTTP access
		to your virtual machine is closed (you are in <i>private</i> mode. In this case,
		opening you environment in <i>public</i> mode will solve the problem.</li>
	</ul>
</li>
</ul>