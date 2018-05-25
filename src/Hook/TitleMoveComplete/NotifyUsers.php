<?php

namespace BlueSpice\EchoConnector\Hook\TitleMoveComplete;
use BlueSpice\Hook\TitleMoveComplete;

class NotifyUsers extends TitleMoveComplete {
	protected function doProcess() {
		if( $this->user->isAllowed( 'bot' ) ) {
			return true;
		}

		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier( 'bsecho' );

		$realname = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper( $this->user )->getDisplayName();

		$notification = $notifier->getNotificationObject(
			'bs-move',
			[
				'agent' => $this->user,
				'title' => $this->newTitle,
				'extra-params' => [
					'oldtitle' => $this->title,
					'realname' => $realname,
					'movereason' => $this->reason
				]
			]
		);

		$notifier->notify( $notification );

		return true;
	}
}