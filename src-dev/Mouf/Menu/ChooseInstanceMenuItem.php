<?php
/*
 * Copyright (c) 2012 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */
namespace Mouf\Menu;

use Mouf\Html\Widgets\Menu\MenuItem;

use Mouf\Utils\Common\ConditionInterface\ConditionInterface;
use Mouf\Utils\I18n\Fine\Translate\LanguageTranslationInterface;

/**
 * This class represent a menu item that when clicked will display a popup to choose an instance and
 * then redirect to a page, passing the instance in parameter.
 * Very useful in Mouf.
 *
 * @Component
 */
class ChooseInstanceMenuItem extends MenuItem {
	
	/**
	 * Any instance selected will have to inherit or implement this type.
	 * @var string
	 */
	private $type;
	
	/**
	 * Constructor.
	 *
	 * @param string $label
	 * @param string $url
	 * @param array<MenuItemInterface> $children
	 */
	public function __construct($label=null, $url=null, $type=null) {
		parent::__construct($label, $url);
		$this->type = $type;
	}

	/**
	 * Returns the URL for this menu. This URL is actually Javascript that will display the menu.
	 * @return string
	 */
	public function getLink() {
		$url = 'javascript:chooseInstancePopup('.json_encode($this->type).', "'.ROOT_URL.$this->getUrl().'?name=", "'.ROOT_URL.'")';
		return $url;
	}
	
	/**
	 * Any instance selected will have to inherit or implement this type.
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}
}
?>