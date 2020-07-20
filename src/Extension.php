<?php

namespace BlueSpice\EchoConnector;

use BlueSpice\EchoConnector\Notifier\NotificationsEchoNotifier;
use MediaWiki\MediaWikiServices;

class Extension {

	public static function onRegistration() {
		$GLOBALS['bsgNotifierClass'] = NotificationsEchoNotifier::class;
	}

	/**
	 *
	 * @param \BlueSpice\NotificationManager $notificationsManager
	 */
	public static function registerNotifications(
		\BlueSpice\NotificationManager $notificationsManager
	) {
		$notificationsManager->registerNotificationCategory(
			'bs-admin-cat',
			[
				'priority' => 3,
				'usergroups' => [ 'sysop' ],
				'tooltip' => "echo-pref-tooltip-bs-admin-cat"
			]
		);
		$notificationsManager->registerNotificationCategory( 'bs-page-create-cat', [
			'priority' => 3,
			'tooltip' => "echo-pref-tooltip-bs-page-create-cat"
		] );

		$notificationsManager->registerNotificationCategory( 'bs-page-actions-cat', [
			'priority' => 3,
			'tooltip' => "echo-pref-tooltip-bs-page-actions-cat"
		] );

		$notificationsManager->registerNotification(
			'bs-adduser',
			[
				'category' => 'bs-admin-cat',
				'presentation-model' => PresentationModel\AddUserPresentationModel::class,
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);

		$notificationsManager->registerNotification(
			'bs-edit',
			[
				'category' => 'bs-page-actions-cat',
				'presentation-model' => PresentationModel\EditPresentationModel::class,
				'bundle' => [
					'web' => true,
					'email' => true,
					'expandable' => true
				],
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);

		$notificationsManager->registerNotification(
			'bs-create',
			[
				'category' => 'bs-page-create-cat',
				'presentation-model' => PresentationModel\CreatePresentationModel::class,
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);

		$notificationsManager->registerNotification(
			'bs-delete',
			[
				'category' => 'bs-page-actions-cat',
				'presentation-model' => PresentationModel\DeletePresentationModel::class,
				'extra-params' => [
					// usually only existing titles can produce notifications
					// we do not have a title after its deleted
					'forceRender' => true
				],
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);

		$notificationsManager->registerNotification(
			'bs-move',
			[
				'category' => 'bs-page-actions-cat',
				'presentation-model' => PresentationModel\MovePresentationModel::class,
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);

		$notificationsManager->registerNotification(
			'bs-registeruser',
			[
				'category' => 'bs-admin-cat',
				'presentation-model' => PresentationModel\RegisterUserPresentationModel::class,
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);
	}

	/**
	 * Get users to notify if none are set explicitly
	 *
	 * @param \EchoEvent $event
	 * @return array
	 */
	public static function getUsersToNotify( $event ) {
		$users = [];
		/** @var UserLocator $userLocator */
		$userLocator = MediaWikiServices::getInstance()->getService(
			'BSEchoConnectorUserLocator'
		);

		switch ( $event->getType() ) {
			case 'bs-registeruser':
			case 'bs-adduser':
				// Get admin users
				$users = $userLocator->getUsersFromGroups( [ 'sysop' ] );
				break;
			case 'bs-create':
				// Who should be notified if new page is created?
				$users = $userLocator->getAllSubscribed( 'bs-create' );
				break;
			case 'bs-edit':
			case 'bs-move':
				// Get all users watching the page
				$users = $userLocator->getWatchers( $event->getTitle()->getPrefixedText() );
				break;
			case 'bs-delete':
				// Get deleted Title form extra params
				$extra = $event->getExtra();
				if ( isset( $extra['title'] ) && $extra['title'] instanceof \Title ) {
					$title = $extra['title'];
					$users = $userLocator->getWatchers( $title->getPrefixedText() );
				}
				break;
		}

		return $users;
	}
}
