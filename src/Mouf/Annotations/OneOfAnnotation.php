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

use Mouf\Reflection\MoufAnnotationHelper;

/**
 * The @OneOf annotation.
 * This annotation contains a list of possible values for a property.
 *
 */
class OneOfAnnotation 
{
	private $possibleValues;

    public function __construct($value)
    {
        $this->possibleValues = MoufAnnotationHelper::getValueAsList($value);
    }
    
    /**
     * Returns the list of possible values.
     *
     * @return array<string>
     */
    public function getPossibleValues() {
    	return $this->possibleValues;
    }
    
}

?>
