<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
/* @var $this MoufAjaxInstanceController */

?>

<div id="renderedInstance"></div>

<script type="text/javascript">

MoufInstanceManager.getInstance(<?php echo json_encode($this->instanceName) ?>).then(function(instance) {
	instance.render('big').appendTo(jQuery("#renderedInstance"));
}).onError(function(e) {
	addMessage("<pre>"+e+"</pre>", "error");
});


$(document).ready(function() {
	$('#instanceList').fixedFromPos();
});

</script>
