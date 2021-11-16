<?php

namespace BlueSpice\EchoConnector\HookHandler;

use MediaWiki\Hook\PersonalUrlsHook;
use MWEchoNotifUser;
use SkinTemplate;
use SpecialPage;
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

		if ( $skin->getSkinName() !== 'bluespicediscovery' ) {
			return;
		}
		if ( $skin->getUser()->isAnon() ) {
			return;
		}
		if ( isset( $personal_urls['notifications-alert'] ) ) {
			unset( $personal_urls['notifications-alert'] );
		}
		if ( isset( $personal_urls['notifications-notice'] ) ) {
			unset( $personal_urls['notifications-notice'] );
		}
		$notifUser = MWEchoNotifUser::newFromUser( $skin->getUser() );
		$count = $notifUser->getAlertCount() + $notifUser->getMessageCount();
		$url = SpecialPage::getTitleFor( 'Notifications' )->getLocalURL();
		$msg = $skin->msg( 'bs-echoconnector-personalurl-notifications' )->params( $count );
		$personal_urls['bsec-notifications'] = [
			'href' => $url,
			'text' => $msg->text(),
			'active' => $url == $title->getLocalURL(),
			'data' => [
				'counter-num' => $count,
				'counter-text' => $msg->text(),
				'attentionindicator' => 'notifications',
			],
			'position' => 100,
		];
	}

}
