<?php

namespace BlueSpice\EchoConnector;

use BlueSpice\EchoConnector\Notifier\NotificationsEchoNotifier;

class Extension {
	/**
	 *
	 * @global type $bsgNotifierClass
	 */
	public static function onRegistration() {
		global $bsgNotifierClass;
		$bsgNotifierClass = NotificationsEchoNotifier::class;
	}

	public static function registerNotifications( \BlueSpice\NotificationManager $notificationsManager ) {
		$echoNotifier = $notificationsManager->getNotifier();

		$echoNotifier->registerNotificationCategory(
			'bs-admin-cat',
			[
				'priority' => 3,
				'usergroups' => [ 'sysop' ]
			]
		);
		$echoNotifier->registerNotificationCategory( 'bs-page-actions-cat', [ 'priority' => 3 ] );

		$notificationsManager->registerNotification(
			'bs-adduser',
			[
				'category' => 'bs-admin-cat',
				'summary-message' => 'bs-notifications-addaccount',
				'summary-params' => [
					'username'
				],
				'email-subject-message' => 'bs-notifications-email-addaccount-subject',
				'email-subject-params' => [
					'username', 'realname'
				],
				'email-body-message' => 'bs-notifications-email-addaccount-body',
				'email-body-params' => [
					'username', 'realname'
				],
				'web-body-message' => 'bs-notifications-web-addaccount-body',
				'web-body-params' => [
					'username', 'realname'
				],
				'extra-params' => [
					'secondary-links' => [
						'performer' => [
							'label' => 'bs-notifications-addaccout-performer',
							'prioritized' => true,
							'icon' => 'userAvatar'
						]
					],
					'icon' => 'edit-user-talk'
				],
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
				'category' => 'bs-page-actions-cat',
				'summary-message' => 'bs-notifications-create',
				'summary-params' => [
					'title'
				],
				'email-subject-message' => 'bs-notifications-email-create-subject',
				'email-subject-params' => [
					'title', 'agent', 'realname'
				],
				'email-body-message' => 'bs-notifications-email-create-body',
				'email-body-params' => [
					'title', 'agent', 'realname', 'summary'
				],
				'web-body-message' => 'bs-notifications-web-create-body',
				'web-body-params' => [
					'title', 'agent', 'realname'
				],
				'extra-params' => [
					'icon' => 'edit',
					'secondary-links' => [
						'agentlink' => []
					]
				],
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);

		$notificationsManager->registerNotification(
			'bs-delete',
			[
				'category' => 'bs-page-actions-cat',
				'summary-message' => 'bs-notifications-delete',
				'summary-params' => [
					'title'
				],
				'email-subject-message' => 'bs-notifications-email-delete-subject',
				'email-subject-params' => [
					'title', 'agent', 'realname'
				],
				'email-body-message' => 'bs-notifications-email-delete-body',
				'email-body-params' => [
					'title', 'agent', 'realname', 'deletereason'
				],
				'web-body-message' => 'bs-notifications-web-delete-body',
				'web-body-params' => [
					'title', 'agent', 'realname'
				],
				'extra-params' => [
					// usually only existing titles can produce notifications
					// we do not have a title after its deleted
					'forceRender' => true,
					'secondary-links' => [
						'agentlink' => []
					],
					'icon' => 'delete'
				],
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);

		$notificationsManager->registerNotification(
			'bs-move',
			[
				'category' => 'bs-page-actions-cat',
				'category' => 'bs-page-actions-cat',
				'summary-message' => 'bs-notifications-move',
				'summary-params' => [
					'oldtitle'
				],
				'email-subject-message' => 'bs-notifications-email-move-subject',
				'email-subject-params' => [
					'oldtitle', 'agent', 'title', 'realname'
				],
				'email-body-message' => 'bs-notifications-email-move-body',
				'email-body-params' => [
					'oldtitle', 'agent', 'title', 'realname', 'movereason'
				],
				'web-body-message' => 'bs-notifications-web-move-body',
				'web-body-params' => [
					'oldtitle', 'agent', 'title', 'realname'
				],
				'extra-params' => [
					'secondary-links' => [
						'agentlink' => []
					]
				],
				'user-locators' => [ self::class . '::getUsersToNotify' ]
			]
		);
	}

	// This seems like a shootgun approach, not everyone should
	// be notified of every change on every page
	public static function getUsersToNotify( $event ) {
		$users = [];

		$dbr = wfGetDB( DB_REPLICA );
		switch ( $event->getType() ) {
			case 'bs-adduser':
			// Get admin users
			$resSysops = $dbr->select( "user_groups", "ug_user", 'ug_group = "sysop"' );
			foreach ( $resSysops as $row ) {
				$user = \User::newFromId( $row->ug_user );
				$users[ $user->getId() ] = $user;
			}
			break;
			case 'bs-create':
			case 'bs-edit':
			case 'bs-move':
			case 'bs-delete':
				// We need to pre-filter for the subscription user setting here.
				// Otherwise a large user base (2000+) will result in bad performance
				$resUser = $dbr->select(
					"user_properties",
					"DISTINCT up_user",
					[
						"up_property" => [
							"echo-subscriptions-web-bs-page-actions-cat",
							"echo-subscriptions-email-bs-page-actions-cat"
						],
						"up_value" => 1
					]
				);

				foreach ( $resUser as $row ) {
					$user = \User::newFromId( $row->up_user );
					$users[ $user->getId() ] = $user;
				}
			break;
		}

		return $users;
	}
}