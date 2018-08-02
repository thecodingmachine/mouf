<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf;

/**
 * This interface should be implemented by any controller that can be accessed for full-text search.
 * 
 * @author David
 */
interface MoufSearchable {
	
	/**
	 * Outputs HTML that will be displayed in the search result screen.
	 * If there are no results, this should not return anything.
	 * 
	 * @Action
	 * @param string $query The full-text search query performed.
	 * @param string $selfedit Whether we are in self-edit mode or not.
	 */
	public function search($query, $selfedit = "false");
	
	/**
	 * Returns the name of the search module.
	 * This name in displayed when the search is pending.
	 * 
	 * @return string
	 */
	public function getSearchModuleName();
}