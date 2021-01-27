<?php

namespace BlueSpice\EchoConnector\Hook\PageMoveComplete;

use BlueSpice\EchoConnector\Notification\TitleMoveNotification;
use BlueSpice\Hook\PageMoveComplete;
use MediaWiki\MediaWikiServices;
use Title;
use User;

class NotifyUsers extends PageMoveComplete {
	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		return $this->getServices()->getPermissionManager()->userHasRight(
			$this->userIdentity,
			'bot'
		);
	}

	protected function doProcess() {
		$notificationsManager = MediaWikiServices::getInstance()->getService(
			'BSNotificationManager'
		);

		$new = Title::newFromLinkTarget( $this->new );
		$old = Title::newFromLinkTarget( $this->old );
		$notifier = $notificationsManager->getNotifier();
		$notification = new TitleMoveNotification(
			User::newFromIdentity( $this->userIdentity ),
			$new,
			$old,
			$this->reason
		);
		$notifier->notify( $notification );

		return true;
	}
}
