<?php
/**
 * TextEmailFormatter class for notifications
 *
 * Part of BlueSpice MediaWiki
 *
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    BlueSpice_Distrubution
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

class BsEchoTextEmailFormatter extends EchoTextEmailFormatter {
	/**
	 * @param $emailMode EchoEmailMode
	 */
	public function __construct( EchoEmailMode $emailMode ) {
		parent::__construct( $emailMode );
		$this->emailMode->attachDecorator( new BsEchoTextEmailDecorator() );
	}
}