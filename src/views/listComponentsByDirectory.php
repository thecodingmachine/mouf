<?php /* @var $this SearchController */
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
if (empty($this->instancesByPackage)) {
	echo "<p>No instances found<p>";
} elseif ($this->query) {
?>
<h2>Instances list found</h2>
<?php 
} else {
?>
<h1>Available component instances</h1>
<?php 
}

if (!$this->ajax && !empty($this->inErrorInstances)) {
	echo "<div class='error'>";
	echo "The following instances are erroneous. They are pointing to a class that no longer exist. You should delete those to avoid any problem.<br/><ul>";
	foreach ($this->inErrorInstances as $instanceName=>$className) {
		echo "<li>".$instanceName." - class not found: ".$className." : <a href='".ROOT_URL."mouf/instance/saveComponent?originalInstanceName=".plainstring_to_htmlprotected($instanceName)."&instanceName=&delete=true&selfedit=".$this->selfedit."'>Delete</a></li>";
	}
	echo "</ul>";
	echo "</div>";
}

foreach ($this->instancesByPackage as $package=>$instancesByClass) {
	echo "<div class='directorytitle'>$package</div>";
	echo "<div class='directorycontent'>";
	foreach ($instancesByClass as $class=>$instances) {
		foreach ($instances as $instance) {
			echo "<a href='".ROOT_URL."mouf/mouf/displayComponent?name=".plainstring_to_urlprotected($instance)."&selfedit=".$this->selfedit."'>";
			echo plainstring_to_htmlprotected($instance);
			echo "</a> - ".plainstring_to_htmlprotected($class)."<br/>";	
		}
	}
	echo "</div>";
}

?>