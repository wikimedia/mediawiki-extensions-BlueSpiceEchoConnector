<?php

namespace BlueSpice\EchoConnector\Hook\PageContentSaveComplete;

use BlueSpice\EchoConnector\Notification\CreateNotification;
use BlueSpice\EchoConnector\Notification\EditNotification;
use BlueSpice\Hook\PageContentSaveComplete;
use MediaWiki\MediaWikiServices;

class NotifyUsers extends PageContentSaveComplete {
	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		if ( $this->wikipage->getTitle()->getNamespace() === NS_USER_TALK ) {
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

		$title = $this->wikipage->getTitle();

		if ( $this->flags & EDIT_NEW ) {
			$notification = new CreateNotification( $this->user, $title, $this->summary );
			$notifier->notify( $notification );

			return true;
		}

		$notification = new EditNotification( $this->user, $title, $this->revision, $this->summary );
		$notifier->notify( $notification );

		return true;
	}

}
