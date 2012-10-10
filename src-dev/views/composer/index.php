<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

/* @var $this Mouf\Controllers\Composer\InstalledPackagesController */

use Mouf\Composer\PackageInterface;
?>
<script type="text/javascript">
$(document).ready(function() {
	/*$(".searchbig").change(function() {
		Composer.search(this.value);
	});*/
	$(".searchpackageform").submit(function() {
		Composer.cleanPackageList();
		Composer.search($(".searchbig").val());
		return false;
	});
});
</script>

<h1>Search packages</h1>

<div class="composersearch">

<pre id="composeroutput" class="hidden"></pre>
<iframe id="tmploading" width="1" height="1" style="border: none"></iframe>

<form class="searchpackageform">
<input type="text" name="searchpackages" class="searchbig" placeholder="Search for a package" />
<button type="submit">Go</button>
</form>


<div id="composerpackagessearch"></div>

<div id="packageinstall" style="display:none" title="Install a new package">

<form action="install" method="post">

<p>Installing package <strong class="packagename"></strong></p>
<p>Please select the version you want to insert in your project</p>
<div>
<label>Version:</label><select id="packageversiondropdown" name="versiondropdown"></select>
</div>
<div id="manualselectdiv" class="hidden">
<label>Version requirements:</label><input id="packageversionmanual" name="versionmanual"></select>
</div>
<div>
<input type="checkbox" name="fromsource" id="packagefromsource" />Download from source
</div>
<input id="packagenamehidden" name="name" type="hidden" />
<input id="packageversionhidden" name="version" type="hidden" />
<input name="selfedit" type="hidden" value="<?php echo htmlentities($this->selfedit); ?>" />

<p>Need some help about versionning? Check out the <a href="http://getcomposer.org/doc/01-basic-usage.md#package-versions" target="_blank">Composer documentation</a>.</p>

<button type="submit">Install</button>

</form>

</div>

</div>

<h1>Installed packages</h1>

<?php 
foreach ($this->packageList as $package):
	/* @var $package PackageInterface */
	echo $this->getHtmlForPackage($package);
?>
<?php 
endforeach;
?>