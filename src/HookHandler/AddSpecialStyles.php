<?php

namespace BlueSpice\EchoConnector\HookHandler;

use MediaWiki\Hook\BeforePageDisplayHook;

class AddSpecialStyles implements BeforePageDisplayHook {

	/**
	 * @inheritDoc
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$title = $out->getTitle();
		if ( $title->isSpecial( 'Notifications' ) ) {
			$out->addModuleStyles( [ 'ext.bluespice.echoConnector.special.styles' ] );
		}
	}
}
