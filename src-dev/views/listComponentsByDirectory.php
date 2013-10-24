<?php /* @var $this SearchController */
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

if ($this->showAnonymous) {
	?>
<a href="<?echo ROOT_URL ?>mouf/?selfedit=<?php echo $this->selfedit ?>&query=<?php echo $this->query ?>&show_anonymous=false" class="btn btn-danger pull-right">Hide anonymous instances</a>
<?php 
} else {
?>
<a href="<?echo ROOT_URL ?>mouf/?selfedit=<?php echo $this->selfedit ?>&query=<?php echo $this->query ?>&show_anonymous=true" class="btn btn-success pull-right">Show anonymous instances</a>
<?php 
}


if (empty($this->instancesByPackage)) {
?><div class="alert alert-info">No instances found</div>
<p>Use the <strong>Instances &gt; Create a new instance</strong> menu to populate your app with new instances.</p>
<?php 
} elseif ($this->query) {
?>
<h2>Instances list found</h2>
<?php 
} else {
?>
<h1>Available instances</h1>
<?php 
}

$count = 0;
foreach ($this->instancesByPackage as $package=>$instancesByClass) {
	$count += count($instancesByClass);
}
?>
Nb instances found: <strong><?php echo $count; ?></strong>
<?php 


if (!$this->ajax && !empty($this->inErrorInstances)) {
	echo "<div class='error'>";
	echo "The following instances are erroneous. They are pointing to a class that no longer exist. You should delete those to avoid any problem.<br/><ul>";
	foreach ($this->inErrorInstances as $instanceName=>$className) {
		echo "<li>".$instanceName." - class not found: ".$className." : <a href='".ROOT_URL."mouf/deleteInstance?instanceName=".plainstring_to_htmlprotected($instanceName)."&selfedit=".$this->selfedit."'>Delete</a></li>";
	}
	echo "</ul>";
	echo "</div>";
}

foreach ($this->instancesByPackage as $package=>$instancesByClass) {
	echo "<div class='directorytitle'>$package</div>";
	echo "<div class='directorycontent'>";
	foreach ($instancesByClass as $class=>$instances) {
		foreach ($instances as $instance) {
			echo "<a href='".ROOT_URL."ajaxinstance/?name=".plainstring_to_urlprotected($instance)."&selfedit=".$this->selfedit."'>";
			echo plainstring_to_htmlprotected(isset($this->anonymousNames[$instance])?$this->anonymousNames[$instance]:$instance);
			echo "</a> - ".plainstring_to_htmlprotected($class)."<br/>";	
		}
	}
	echo "</div>";
}

?>