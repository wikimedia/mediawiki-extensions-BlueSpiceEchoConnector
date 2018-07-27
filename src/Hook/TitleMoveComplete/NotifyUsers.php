<?php

namespace BlueSpice\EchoConnector\Hook\TitleMoveComplete;

use BlueSpice\Hook\TitleMoveComplete;
use BlueSpice\EchoConnector\Notification\TitleMoveNotification;

class NotifyUsers extends TitleMoveComplete {
	protected function doProcess() {
		if ( $this->user->isAllowed( 'bot' ) ) {
			return true;
		}

		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier();

		$realname = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper( $this->user )->getDisplayName();

		$extraParams = [
			'oldtitle' => $this->title,
			'realname' => $realname,
			'movereason' => $this->reason
		];

		$notification = new TitleMoveNotification( $this->user, $this->newTitle, $extraParams );

		$notifier->notify( $notification );

		return true;
	}
}
