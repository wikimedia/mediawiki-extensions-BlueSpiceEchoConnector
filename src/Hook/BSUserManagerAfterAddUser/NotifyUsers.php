<?php

namespace BlueSpice\EchoConnector\Hook\BSUserManagerAfterAddUser;

use BlueSpice\UserManager\Hook\BSUserManagerAfterAddUser;
use BlueSpice\EchoConnector\Notification\AddUserNotification;

class NotifyUsers extends BSUserManagerAfterAddUser {

	protected function doProcess() {
		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier();

		$realname = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper( $this->user )->getDisplayName();

		if ( $realname !== $this->user->getName() ) {
			$realname = wfMessage( 'bs-notifications-param-realname-with-username', $realname, $this->user->getName() )->plain();
		}
		$performerRealName = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper()->getDisplayName( $this->performer );

		$extraParams = [
			'realname' => $realname,
			'user' => $this->user
		];

		$notification = new AddUserNotification(
			$this->performer,
			$this->user->getUserPage(),
			$extraParams
		);
		$notification->addSecondaryLink( 'performer', [
			'url' => $this->performer->getUserPage()->getFullURL(),
			'label-params' => [ $performerRealName ]
		] );

		$notifier->notify( $notification );

		return true;
	}
}