<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012-2015 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
?>
<h1>Configure local URL</h1>

<?php if ($this->status) { ?>
<div class="alert alert-success">Local URL configuration is OK.</div>
<?php } else { ?>
<div class="alert alert-error">Local URL configuration is KO. You need to configure it.</div>
<?php } ?>

<h2>What is this?</h2>

<p>For Mouf to work correctly, Mouf needs to be able to call itself from the server, via HTTP requests.
If the URL to access Mouf from your browser cannot be directly called by the server Mouf is running on to access Mouf,
then you need to provide this URL to Mouf.</p>

<p>
	<img src="<?php echo MOUF_URL ?>src-dev/views/images/connection-problem.png" />
</p>

<h2>Configure local URL</h2>

<?php echo plainstring_to_htmlprotected($this->localUrl); ?>