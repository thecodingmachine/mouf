#!/usr/bin/php
<?php
$licenseHeaderTxt = file_get_contents(__DIR__."/license_header_2.txt");

applyToCurrentDir();

function applyToCurrentDir() {
	foreach (glob("*.php") as $filename) {
		applyMITLicenseToFile($filename);
	}
	foreach (glob("*", GLOB_ONLYDIR) as $directory) {
		$oldDir = getcwd();
		chdir($directory);
		applyToCurrentDir();
		chdir($oldDir);
	}
}

function applyMITLicenseToFile($filename) {
	global $licenseHeaderTxt;

	$content = file_get_contents($filename);
	if (strpos($content, "(c)") === false && strpos($content, "Copyright") === false && strpos($content, "copyright") === false) {
		if (strpos($content, "<?php") !== 0) {
			$content = "<?php\n?>\n".$content;
		}
		
		// Get first line
		$linePos = strpos($content, "\n");
		$firstLine = substr($content, 0, $linePos+1);
		$end = substr($content, $linePos+1);
		
		$newFileContent = $firstLine.$licenseHeaderTxt.$end;
		file_put_contents($filename, $newFileContent);
	} else {
		echo "Skipping file $filename: already licensed\n";
	}
}