<?php
namespace Mouf;

/**
 * This class is in charge of returning an absolute unique ID for:
 * - this computer
 * - this project (this directory)
 * - the branch of the directory (if using GIT)
 * 
 * A unique ID is useful to prefix anonymous instances in MoufComponents. This is very useful to avoid conflicts
 * in MoufComponents when working in team.
 * 
 */
class UniqueIdService {
	
	public static function getUniqueId() {
		
		$moufCache = new MoufCache();
		$id = $moufCache->get('computerUniqueId');
		
		if (!$id) {
			// Let's get the Mac address from this computer
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				ob_start();
				system('ipconfig /all'); //Execute external program to display output
				$output=ob_get_clean();
			} else {
				ob_start();
				system('ifconfig'); //Execute external program to display output
				$output=ob_get_clean();
			}

			preg_match('/..:..:..:..:..:../', $output, $matches);
			if (isset($matches[0])) {
				$macAddress = $matches[0];
			} else {
				$macAddress = ''; 
			}
					
			$branch = exec('git rev-parse --abbrev-ref HEAD');
			
			$totalString = $macAddress.__DIR__.$branch;
			$md5 = md5($totalString);
			
			// Only keep the first 4 characters to keep the ID short.
			$id = substr($md5, 0, 4);
			
			$moufCache->set('computerUniqueId', $id);
		}
		
		return $id;
	}
}
