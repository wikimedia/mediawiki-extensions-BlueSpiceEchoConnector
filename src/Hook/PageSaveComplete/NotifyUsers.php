<?php

namespace BlueSpice\EchoConnector\Hook\PageSaveComplete;

use BlueSpice\EchoConnector\Notification\CreateInNamespaceOrCategoryNotification;
use BlueSpice\EchoConnector\Notification\CreateNotification;
use BlueSpice\EchoConnector\Notification\EditInNamespaceOrCategoryNotification;
use BlueSpice\EchoConnector\Notification\EditNotification;
use BlueSpice\Hook\PageSaveComplete;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\Notifications\INotifier;
use Title;

class NotifyUsers extends PageSaveComplete {

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		return $this->revisionRecord->isMinor() ||
			$this->editResult->isNullEdit() ||
			$this->isBot();
	}

	/**
	 * @return bool
	 */
	private function isBot() {
		return $this->getServices()->getPermissionManager()->userHasRight(
			$this->user,
			'bot'
		);
	}

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$notificationsManager = MediaWikiServices::getInstance()->getService( 'BSNotificationManager' );
		$notifier = $notificationsManager->getNotifier();

		if ( !$notifier ) {
			return true;
		}

		$title = $this->wikiPage->getTitle();
		$this->fireNamespaceCategoryNotifications( $notifier, $title );

		if ( $title->getNamespace() === NS_USER_TALK ) {
			return true;
		}

		$agent = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $this->user );
		if ( $this->flags & EDIT_NEW ) {
			$notification = new CreateNotification( $agent, $title, $this->summary );
			$notifier->notify( $notification );

			return true;
		}

		$notification = new EditNotification( $agent, $title, $this->revisionRecord, $this->summary );
		$notifier->notify( $notification );

		return true;
	}

	/**
	 * Send notifications for the namespace and category subscriptions
	 *
	 * @param INotifier $notifier
	 * @param Title $title
	 */
	private function fireNamespaceCategoryNotifications( INotifier $notifier, Title $title ) {
		$agent = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $this->user );
		if ( $this->flags & EDIT_NEW ) {
			$notifier->notify(
				new CreateInNamespaceOrCategoryNotification( $agent, $title, $this->summary )
			);
		} else {
			$notifier->notify( new EditInNamespaceOrCategoryNotification(
				$agent, $title, $this->revisionRecord, $this->summary
			) );
		}
	}

}
