<?php 
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
/**
 * Analyses all included PHP files to detect whether one is not behaving correctly (outputing some text, which is strictly forbidden)
 */

// Disable output buffering
while (ob_get_level() != 0) {
	ob_end_clean();
}

ini_set('display_errors', 1);
// Add E_ERROR to error reporting if it is not already set
error_reporting(E_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	//require_once '../../Mouf.php';
	require_once '../../MoufComponents.php';
	require_once '../../MoufUniversalParameters.php';
	require_once '../../config.php';
	//require_once 'MoufRequire.php';
	$mouf_base_path = ROOT_PATH;
	$selfEdit = false;
} else {
	require_once '../MoufManager.php';
	MoufManager::initMoufManager();
	require_once '../../MoufUniversalParameters.php';
	//require_once '../MoufAdmin.php';
	require_once '../MoufAdminComponents.php';
	$mouf_base_path = ROOT_PATH."mouf/";
	$selfEdit = true;
}
//require_once '../Moufspector.php';
require_once '../MoufPackageManager.php';

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';


$moufResponse = array();


$missingPackages = MoufManager::getMoufManager()->getMissingPackages();
if ($missingPackages) {
	$html = "<p>One or more packages are supposed to be included, but cannot be found on the server:</p><ul>";
	foreach ($missingPackages as $packageDescriptor) {
		/* @var $packageDescriptor MoufPackageDescriptor */
		$html .= "<li>Missing package <b>".$packageDescriptor->getGroup()."/".$packageDescriptor->getName()." (version ".$packageDescriptor->getVersion().")</b>. Please download this package.</li>";
	}
	$html .= "</ul>";
	$moufResponse = array("errorType"=>"packagedoesnotexist", "errorMsg"=>$html);
} else  {

	$moufDeclaredClassesByPackagesFiles = array();
	$moufDeclaredFunctionsByPackagesFiles = array();
	$moufDeclaredInterfacesByPackagesFiles = array();
	
	$moufDeclaredClasses = get_declared_classes();
	$moufDeclaredFunctions = get_defined_functions();
	$moufDeclaredInterfaces = get_declared_interfaces();

	foreach (MoufManager::getMoufManager()->getFilesListRequiredByPackages() as $packageFile) {
            
		require_once ROOT_PATH.$packageFile;
		
		$moufDeclaredClassesNew = get_declared_classes();
		$moufDeclaredClassesByPackagesFiles[$packageFile] = array_diff($moufDeclaredClassesNew, $moufDeclaredClasses);
		$moufDeclaredClasses = $moufDeclaredClassesNew;
		
		$moufDeclaredFunctionsNew = get_defined_functions();
		$moufDeclaredFunctionsByPackagesFiles[$packageFile] = array_diff($moufDeclaredFunctionsNew['user'], $moufDeclaredFunctions['user']);
		$moufDeclaredFunctions = $moufDeclaredFunctionsNew;
		
		$moufDeclaredInterfacesNew = get_declared_interfaces();
		$moufDeclaredInterfacesByPackagesFiles[$packageFile] = array_diff($moufDeclaredInterfacesNew, $moufDeclaredInterfaces);
		$moufDeclaredInterfaces = $moufDeclaredInterfacesNew;
	}

	$moufDeclaredClassesByFiles = array();
	$moufDeclaredFunctionsByFiles = array();
	$moufDeclaredInterfacesByFiles = array();
	
	// Ok, now, we can start including our files.
	foreach (MoufManager::getMoufManager()->getRegisteredIncludeFiles() as $registeredFile) {
		if (file_exists($mouf_base_path.$registeredFile)) {
			require_once $mouf_base_path.$registeredFile;
		
			$moufFile=null;
			$moufLine=null;
			$isSent = headers_sent($moufFile, $moufLine);
			
			if ($isSent) {
				$moufResponse = array("errorType"=>"outputStarted", "errorMsg"=>"Error! Output started on line ".$moufLine." in file ".$moufFile.", while including file $registeredFile");
				break;
			}
			
			if ($selfEdit) {
				// TODO:check this!
				$registeredFile = "mouf/".$registeredFile;
			}
			
			$moufDeclaredClassesNew = get_declared_classes();
			$moufDeclaredClassesByFiles[$registeredFile] = array_diff($moufDeclaredClassesNew, $moufDeclaredClasses);
			$moufDeclaredClasses = $moufDeclaredClassesNew;
			
			$moufDeclaredFunctionsNew = get_defined_functions();
			$moufDeclaredFunctionsByFiles[$registeredFile] = array_diff($moufDeclaredFunctionsNew['user'], $moufDeclaredFunctions['user']);
			$moufDeclaredFunctions = $moufDeclaredFunctionsNew;
			
			$moufDeclaredInterfacesNew = get_declared_interfaces();
			$moufDeclaredInterfacesByFiles[$registeredFile] = array_diff($moufDeclaredInterfacesNew, $moufDeclaredInterfaces);
			$moufDeclaredInterfaces = $moufDeclaredInterfacesNew;
		} else {
			$moufResponse = array("errorType"=>"filedoesnotexist", "errorMsg"=>"Error! Included file '".$registeredFile."' does not exist.");
			break;
		}
	}
}

// Unique ID that is unlikely to be in the bottom of the message
echo "\nX4EVDX4SEVX548DSVDXCDSF489\n";

if (!isset($moufResponse['errorType'])) {
	$moufResponse["packages"]["classes"] = $moufDeclaredClassesByPackagesFiles;
	$moufResponse["packages"]["functions"] = $moufDeclaredFunctionsByPackagesFiles;
	$moufResponse["packages"]["interfaces"] = $moufDeclaredInterfacesByPackagesFiles;
	$moufResponse["classes"] = $moufDeclaredClassesByFiles;
	$moufResponse["functions"] = $moufDeclaredFunctionsByFiles;
	$moufResponse["interfaces"] = $moufDeclaredInterfacesByFiles;
}

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}


if ($encode == "php") {
	echo serialize($moufResponse);
} elseif ($encode == "json") {
	echo json_encode($moufResponse);
} else {
	echo "invalid encode parameter";
}
