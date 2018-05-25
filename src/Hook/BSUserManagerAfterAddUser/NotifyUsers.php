<?php

namespace BlueSpice\EchoConnector\Hook\BSUserManagerAfterAddUser;

use BlueSpice\UserManager\Hook\BSUserManagerAfterAddUser;

class NotifyUsers extends BSUserManagerAfterAddUser {
	
	protected function doProcess() {
		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier( 'bsecho' );

		$realname = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper( $this->user )->getDisplayName();

		if( $realname !== $this->user->getName() ) {
			$realname = wfMessage( 'bs-notifications-param-realname-with-username', $realname, $this->user->getName() )->plain();
		}
		$performerRealName = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper()->getDisplayName( $this->performer );

		$notification = $notifier->getNotificationObject(
			'bs-adduser',
			[
				'title' => $this->user->getUserPage(),
				'agent' => $this->performer,
				'extra-params' => [
					'realname' => $realname,
					'user' => $this->user,
					'secondary-links' => [
						'performer' => [
							'url' => $this->performer->getUserPage()->getFullURL(),
							'label-params' => [$performerRealName]
						]
					]
				]
			]
		);

		$notifier->notify( $notification );

		return true;
	}
}