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
 * This class is in charge for referencing all the services that can be searched in Mouf
 * using full-text search.
 * 
 * @author David
 * @Component
 */
class MoufSearchService {
	/**
	 * An array of URLs that will be queried in Ajax to return search results.
	 * The URLs should accept the "query" and "selfedit" parameters, and return direct HTML. 
	 * 
	 * @Property
	 * @Compulsory
	 * @var array<MoufSearchable>
	 */
	public $searchableServices = array();
}