<?php
/**
 * Text Email Decorator class for notifications
 *
 * Part of BlueSpice for MediaWiki
 *
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    BlueSpice_Distrubution
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

/**
 * Text email decorator
 */
class BsEchoTextEmailDecorator extends EchoTextEmailDecorator {
	/**
	 * Adds the BS default footer to the text mail
	 * @global String $wgSitename
	 *
	 * @param String  $address
	 * @param User    $user
	 *
	 * @return String
	 */
	public function decorateFooter( $address, User $user ) {
		global $wgSitename;

		$sFooter = parent::decorateFooter( $address, $user );

		return $sFooter .
		"\n---------------------\n\n"
		. wfMessage( 'bs-email-footer', $wgSitename )->text()
		. "\n\n---------------------";
	}
}