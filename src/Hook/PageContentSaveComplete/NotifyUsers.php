<?php

namespace BlueSpice\EchoConnector\Hook\PageContentSaveComplete;

use BlueSpice\EchoConnector\Notification\CreateInNamespaceOrCategoryNotification;
use BlueSpice\EchoConnector\Notification\CreateNotification;
use BlueSpice\EchoConnector\Notification\EditInNamespaceOrCategoryNotification;
use BlueSpice\EchoConnector\Notification\EditNotification;
use BlueSpice\Hook\PageContentSaveComplete;
use BlueSpice\INotifier;
use MediaWiki\MediaWikiServices;
use Title;

class NotifyUsers extends PageContentSaveComplete {

	protected function doProcess() {
		$notificationsManager = MediaWikiServices::getInstance()->getService( 'BSNotificationManager' );
		$notifier = $notificationsManager->getNotifier();

		if ( !$notifier ) {
			return true;
		}

		$title = $this->wikipage->getTitle();
		$this->fireNamespaceCategoryNotifications( $notifier, $title );

		if ( $this->wikipage->getTitle()->getNamespace() === NS_USER_TALK ) {
			return true;
		}

		if ( $this->flags & EDIT_NEW ) {
			$notification = new CreateNotification( $this->user, $title, $this->summary );
			$notifier->notify( $notification );

			return true;
		}

		$notification = new EditNotification( $this->user, $title, $this->revision, $this->summary );
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
		if ( $this->flags & EDIT_NEW ) {
			$notifier->notify(
				new CreateInNamespaceOrCategoryNotification( $this->user, $title, $this->summary )
			);
		} else {
			$notifier->notify( new EditInNamespaceOrCategoryNotification(
				$this->user, $title,  $this->revision, $this->summary
			) );
		}
	}

}
