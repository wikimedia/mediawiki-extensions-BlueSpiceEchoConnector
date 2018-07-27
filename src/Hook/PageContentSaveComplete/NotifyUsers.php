<?php

namespace BlueSpice\EchoConnector\Hook\PageContentSaveComplete;

use BlueSpice\Hook\PageContentSaveComplete;
use BlueSpice\EchoConnector\Notification\CreateNotification;
use BlueSpice\EchoConnector\Notification\EditNotification;

class NotifyUsers extends PageContentSaveComplete {

	protected function doProcess() {
		if ( $this->user->isAllowed( 'bot' ) ) {
			return true;
		}

		if ( $this->wikipage->getTitle()->getNamespace() === NS_USER_TALK ) {
			return true;
		}

		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier();

		if ( !$notifier ) {
			return true;
		}

		$realname = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper( $this->user )->getDisplayName();

		$title = $this->wikipage->getTitle();

		if ( $this->flags & EDIT_NEW ) {
			$extraParams = [
				'summary' => $this->summary,
				'realname' => $realname
			];

			$notification = new CreateNotification( $this->user, $title, $extraParams );
			$notifier->notify( $notification );

			return true;
		}

		$diffParams = [];
		if ( is_object( $this->revision ) ) {
			$diffParams[ 'diff' ] = $this->revision->getId();
			if ( is_object( $this->revision->getPrevious() ) ) {
				$diffParams[ 'oldid' ] = $this->revision->getPrevious()->getId();
			}
		}

		$diffUrl = $title->getFullURL( [
			'type' => 'revision',
			'diff' => $diffParams['diff'],
			'oldid' => $diffParams['oldid']
		] );

		$extraParams = [
			'summary' => $this->summary,
			'titlelink' => true,
			'realname' => $realname
		];

		$notification = new EditNotification( $this->user, $title, $extraParams );
		$notification->addSecondaryLink( 'difflink', $diffUrl );

		$notifier->notify( $notification );

		return true;
	}

}