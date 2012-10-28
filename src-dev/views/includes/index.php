<?php 
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

/* @var $this \Mouf\Controllers\IncludesAnalyzerController */
  ?>
<style>
.hidden {
	display:none;
}
</style>
<script type="text/javascript">
$(document).ready(function() {
	$(".showmore").click(function() {
		var block = $(this).parent().parent().find(".hiddeable");
		block.toggle();
		if ($(this).text() == "Hide") {
			$(this).text("Show more");
			block.addClass("hidden");
		} else {
			$(this).text("Hide");
			block.removeClass("hidden");
		}
		return false;
	});
});
</script>
  
<h1>PHP Included files analysis</h1>

<p>Mouf was able to find the list of classes below, using the autoloading mechanism of Composer.</p>
<p>There is one strong requirement to be able to use a class in Mouf: when the class file is included,
there should be no output (so no errors, no HTML, ...). Any class trigerring some output will be reported below.</p>

<h2>Classes that trigger errors</h2>
<?php 
if (empty($this->errors)) {
	echo "Perfect! No classes are triggering errors.";	
} else {
	foreach ($this->errors as $className=>$errorMsg):
	?>
		<div class="alert alert-error">
			<div>Error while including <strong><?php echo $className; ?></strong>. <a href="#" class="showmore">Show more</a></div>
			<div class="hiddeable hidden"><?php echo $errorMsg; ?></div>
		</div>
	<?php 
	endforeach;
}
?>

<h2>Classes that may contain problems in annotations</h2>
<?php 
if (empty($this->warnings)) {
	echo "Perfect! No classes are triggering warnings.";	
} else {
	foreach ($this->warnings as $className=>$errorMsgs):
	?>
		<div class="alert alert-block">
			<div>You might encounter problems while using <strong><?php echo $className; ?></strong>. <a href="#" class="showmore">Show more</a></div>
			<div class="hiddeable hidden"><ul><?php 
			foreach ($errorMsgs as $errorMsg) {
				echo "<li>".$errorMsg."</li>";
			} ?></ul></div>
		</div>
	<?php 
	endforeach;
}
?>



<h2>Included classes</h2>

<?php 
foreach ($this->classMap as $className=>$file):
?>
	<div class="alert alert-success">
	<?php echo $className; ?>
	</div>
<?php 
endforeach;
?>
</ul>