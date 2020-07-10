<?php

namespace BlueSpice\EchoConnector\Hook\PageSaveComplete;

use BlueSpice\EchoConnector\Notification\CreateNotification;
use BlueSpice\EchoConnector\Notification\EditNotification;
use BlueSpice\Hook\PageSaveComplete;
use MediaWiki\MediaWikiServices;

class NotifyUsers extends PageSaveComplete {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( $this->wikiPage->getTitle()->getNamespace() === NS_USER_TALK ) {
			return true;
		}
		return $this->getServices()->getPermissionManager()->userHasRight(
			$this->user,
			'bot'
		);
	}

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$notificationsManager = MediaWikiServices::getInstance()->getService( 'BSNotificationManager' );
		$notifier = $notificationsManager->getNotifier();

		if ( !$notifier ) {
			return true;
		}

		$title = $this->wikiPage->getTitle();

		if ( $this->flags & EDIT_NEW ) {
			$notification = new CreateNotification( $this->user, $title, $this->summary );
			$notifier->notify( $notification );

			return true;
		}

		$notification = new EditNotification( $this->user, $title, $this->revisionRecord, $this->summary );
		$notifier->notify( $notification );

		return true;
	}

}
