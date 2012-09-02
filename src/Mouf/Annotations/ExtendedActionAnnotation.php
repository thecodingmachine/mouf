<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Annotations;


/**
 * The @ExtendedAction annotation.
 * This annotation is used in @Components classes to provide additional screens to configure the component.
 * 
 * The ExtendedAction annotation takes a JSON array in attribute in this form: @ExtendedAction {"name":"Edit", "url":"mouf/mysqlconnectionedit", "default":true}
 * 
 */
class ExtendedActionAnnotation 
{
	private $name;
	private $url;
	private $default;

    public function __construct($value)
    {
    	$result = json_decode($value);
    	if ($result == null) {
    		throw new MoufException("Error in a @ExtendedActionAnnotation. The parameter passed is not a valid JSON string. String passed: ".$value);
    	}
    	if (!isset($result->name)) {
    		throw new MoufException('Error in a @ExtendedActionAnnotation. The parameter "name" is compulsory. For instance: @ExtendedAction {"name":"Edit", url:"mouf/mysqlconnectionedit", default:true}');
    	}
    	if (!isset($result->url)) {
    		throw new MoufException('Error in a @ExtendedActionAnnotation. The parameter "url" is compulsory. For instance: @ExtendedAction {"name":"Edit", url:"mouf/mysqlconnectionedit", default:true}');
    	}
    	$this->name = $result->name;
    	$this->url = $result->url;
    	if (isset($result->default)) {
    		$this->default = $result->default;
    	}
    }
    
    /**
     * Returns the name of the extended action (the label of the link in the menu).
     *
     * @return string
     */
    public function getName() {
    	return $this->name;
    }
    
    /**
     * Returns the url of the extended action (the label of the link in the menu).
     *
     * @return string
     */
    public function getUrl() {
    	return $this->url;
    }
    
    /**
     * True if this action is supposed to be the default action to be displayed for that component.
     *
     * @return bool
     */
    public function isDefault() {
    	return $this->default;
    }
    
}

?>
