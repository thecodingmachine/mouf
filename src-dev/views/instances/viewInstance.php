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

<<<<<<< HEAD
<style>
div.editInstance {
	float: right;
}

div.classComment {
	word-wrap: pre;
}

</style>

<div id="messages"></div>


<div id="renderedInstance"></div>

<div>
<button onclick="MoufUI.showSourceFile('src/Mouf/Test.php',6);return false;">Show PHP file</button>
</div>



=======
<div id="renderedInstance"></div>

>>>>>>> 2bd2962e578651194158c58784ae073c42fc2abf
<script type="text/javascript">

MoufInstanceManager.getInstance(<?php echo json_encode($this->instanceName) ?>).then(function(instance) {
	instance.render('big').appendTo(jQuery("#renderedInstance"));
}).onError(function(e) {
	addMessage("<pre>"+e+"</pre>", "error");
});

</script>
