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
 * This class represents a page of a documentation of a package.
 * It maps the <page> XML descriptor you can find in a package.xml file.
 * 
 * @author David Negrier
 */
class MoufDocumentationPageDescriptor {
	
	/**
	 * The title of the page (to be displayed in the menu)
	 * 
	 * @var string
	 */
	private $title;
	
	/**
	 * The URL of the page, relative to the "doc" directory (should not start with /).
	 * 
	 * @var string
	 */
	private $url;
	
	/**
	 * The page children pages (if you want to show a hierarchy in the doc pages)
	 * @var array<MoufDocumentationPageDescriptor>
	 */
	private $children;
	
	/**
	 * The package owning that documentation page.
	 * 
	 * @var MoufPackage
	 */
	private $package;
	
	public function __construct(\SimpleXmlElement $elem, $package) {
		$this->package = $package;
		
		$docPagesList = array();
		$docPages = $elem->doc;
		if ($docPages) {
			foreach ($elem->doc->children() as $page) {
				/* @var $page SimpleXmlElement */
				$docPagesList[] = new MoufDocumentationPageDescriptor($page, $package);
			}
		}
		$this->children = $docPagesList;	
		
		$attributesArray = (array)$elem;
		if(isset($attributesArray['@attributes'])) {
			if(isset($attributesArray['@attributes']['title'])) {
				$this->title = $attributesArray['@attributes']['title'];
			}
			if(isset($attributesArray['@attributes']['url'])) {
				$this->url = $attributesArray['@attributes']['url'];
			}
		}
	}
	
	/**
	 * Returns the title of the page (to be displayed in the menu).
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * Returns the URL of the page, relative to the "doc" directory (should not start with /).
	 * @return string
	 */
	public function getURL() {
		return $this->url;
	}
	
	/**
	 * Returns the children of the page (if you want to show a hierarchy in the doc pages)
	 * @return array<MoufDocumentationPageDescriptor>
	 */
	public function getChildren() {
		return $this->children;
	}
	
	/**
	 * 
	 * @return MoufPackage
	 */
	public function getPackage() {
		return $this->package;
	}
		
}