<?php

namespace BlueSpice\EchoConnector\Hook\ArticleDeleteComplete;
use BlueSpice\Hook\ArticleDeleteComplete;

class NotifyUsers extends ArticleDeleteComplete {
	protected function doProcess() {
		if( $this->user->isAllowed( 'bot' ) ) {
			return true;
		}

		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier( 'bsecho' );

		$realname = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper( $this->user )->getDisplayName();

		//Since at this point Title object for this page no longer ::exists(),
		//we need to pass it inside extra-params to avoid automatic deletion
		$notification = $notifier->getNotificationObject(
			'bs-delete',
			[
				'agent' => $this->user,
				'extra-params' => [
					'deletereason' => $this->reason,
					'realname' => $realname,
					'title' => $this->wikipage->getTitle()
				]
			]
		);

		$notifier->notify( $notification );

		return true;
	}
}