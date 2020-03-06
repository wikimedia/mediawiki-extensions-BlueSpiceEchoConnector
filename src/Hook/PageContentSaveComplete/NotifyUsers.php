<?php

namespace BlueSpice\EchoConnector\Hook\PageContentSaveComplete;

use BlueSpice\EchoConnector\Notification\CreateNotification;
use BlueSpice\EchoConnector\Notification\EditNotification;
use BlueSpice\Hook\PageContentSaveComplete;

class NotifyUsers extends PageContentSaveComplete {

	protected function doProcess() {
		if ( $this->user->isAllowed( 'bot' ) ) {
			return true;
		}

		if ( $this->wikipage->getTitle()->getNamespace() === NS_USER_TALK ) {
			return true;
		}

		$notificationsManager = \BlueSpice\Services::getInstance()->getService( 'BSNotificationManager' );

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
