<?php

namespace BlueSpice\EchoConnector\Hook\ArticleDeleteComplete;

use BlueSpice\EchoConnector\Notification\DeleteNotification;
use BlueSpice\Hook\ArticleDeleteComplete;

class NotifyUsers extends ArticleDeleteComplete {
	protected function doProcess() {
		if ( $this->user->isAllowed( 'bot' ) ) {
			return true;
		}

		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier();
		$notification = new DeleteNotification( $this->user, $this->wikipage->getTitle(), $this->reason );
		$notifier->notify( $notification );

		return true;
	}
}
