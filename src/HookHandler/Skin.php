<?php

namespace BlueSpice\EchoConnector\HookHandler;

use MediaWiki\Hook\PersonalUrlsHook;
use SkinTemplate;
use Title;

class Skin implements PersonalUrlsHook {

	/**
	 * @param array &$personal_urls
	 * @param Title &$title
	 * @param SkinTemplate $skin
	 * @return void
	 */
	public function onPersonalUrls( &$personal_urls, &$title, $skin ): void {
		if ( isset( $personal_urls['notifications-alert'] ) ) {
			if ( !isset( $personal_urls['notifications-alert']['data'] ) ) {
				$personal_urls['notifications-alert']['data'] = [];
			}
			$personal_urls['notifications-alert']['data']['attentionindicator']
				= 'notifications-alert';
			$personal_urls['notifications-alert']['position'] = 100;
		}
		if ( isset( $personal_urls['notifications-notice'] ) ) {
			if ( !isset( $personal_urls['notifications-notice']['data'] ) ) {
				$personal_urls['notifications-notice']['data'] = [];
			}
			$personal_urls['notifications-notice']['data']['attentionindicator']
				= 'notifications-notice';
			$personal_urls['notifications-notice']['position'] = 110;
		}
	}

}
