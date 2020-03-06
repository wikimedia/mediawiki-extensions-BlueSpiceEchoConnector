<?php

namespace BlueSpice\EchoConnector\Hook\BSUserManagerAfterAddUser;

use BlueSpice\EchoConnector\Notification\AddUserNotification;
use BlueSpice\UserManager\Hook\BSUserManagerAfterAddUser;

class NotifyUsers extends BSUserManagerAfterAddUser {

	protected function doProcess() {
		$notificationsManager = \BlueSpice\Services::getInstance()->getService( 'BSNotificationManager' );

		$notifier = $notificationsManager->getNotifier();

		$notification = new AddUserNotification(
			$this->performer,
			$this->user
		);

		$notifier->notify( $notification );

		return true;
	}
}
