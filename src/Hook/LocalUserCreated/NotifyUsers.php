<?php

namespace BlueSpice\EchoConnector\Hook\LocalUserCreated;

use BlueSpice\EchoConnector\Notification\RegisterUserNotification;

class NotifyUsers extends \BlueSpice\Hook\LocalUserCreated {

	protected function skipProcessing() {
		if ( $this->autocreated || !$this->getContext()->getUser()->isAnon() ) {
			// only notify, when the user created his own account by him self.
			return true;
		}
		return false;
	}

	protected function doProcess() {
		$notificationsManager = \BlueSpice\Services::getInstance()->getService( 'BSNotificationManager' );

		$notifier = $notificationsManager->getNotifier();

		$notification = new RegisterUserNotification(
			$this->user,
			$this->user
		);
		$notifier->notify( $notification );

		return true;
	}
}
