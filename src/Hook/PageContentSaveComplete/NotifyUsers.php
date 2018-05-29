<?php

namespace BlueSpice\EchoConnector\Hook\PageContentSaveComplete;

use BlueSpice\Hook\PageContentSaveComplete;

class NotifyUsers extends PageContentSaveComplete {
	
	protected function doProcess() {
		if ( $this->user->isAllowed( 'bot' ) ) {
			return true;
		}
			
		if ( $this->wikipage->getTitle()->getNamespace() === NS_USER_TALK ) {
			return true;
		}

		$notificationsManager = \BlueSpice\Services::getInstance()->getBSNotificationManager();

		$notifier = $notificationsManager->getNotifier( 'bsecho' );

		if( !$notifier ) {
			return true;
		}

		$realname = \BlueSpice\Services::getInstance()->getBSUtilityFactory()
			->getUserHelper( $this->user )->getDisplayName();

		$title = $this->wikipage->getTitle();

		if ( $this->flags & EDIT_NEW ) {
			$notification = $notifier->getNotificationObject(
				'bs-create',
				[
					'agent' => $this->user,
					'title' => $title,
					'extra-params' => [
						'summary' => $this->summary,
						'realname' => $realname
					]
				]
			);

			$notifier->notify( $notification );
			return true;
		}

		$diffParams = [];
		if ( is_object ( $this->revision ) ) {
			$diffParams[ 'diff' ] = $this->revision->getId ();
			if ( is_object ( $this->revision->getPrevious () ) ) {
				$diffParams[ 'oldid' ] = $this->revision->getPrevious()->getId ();
			}
		}

		$diffUrl = $title->getFullURL( [
			'type' => 'revision',
			'diff' => $diffParams['diff'],
			'oldid' => $diffParams['oldid']
		] );

		$notification = $notifier->getNotificationObject(
			'bs-edit',
			[
				'agent' => $this->user,
				'title' => $title,
				'extra-params' => [
					'summary' => $this->summary,
					'titlelink' => true,
					'realname' => $realname,
					'secondary-links' => [
						'difflink' => $diffUrl
					]
				]
			]
		);

		$notifier->notify( $notification );

		return true;
	}

}