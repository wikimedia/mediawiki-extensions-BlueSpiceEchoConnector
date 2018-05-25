<?php

namespace BlueSpice\EchoConnector\Notifier;

use \BlueSpice\EchoConnector\Notification\EchoNotification;
use \BlueSpice\EchoConnector\EchoEventPresentationModel;
use \BlueSpice\EchoConnector\NotificationFormatter;

/**
 * This class has unfortunate naming, since Echo uses similar naming
 * for its notifiers. This is BlueSpiceNotifications notifier,
 * not override of Echo default notifier
 */
class NotificationsEchoNotifier implements \BlueSpice\INotifier {
	protected $echoNotifications;
	protected $echoNotificationCategories;
	protected $echoIcons;
	
	public function getNotificationObject( $key, $params ) {
		return new EchoNotification( $key, $params );
	}

	public function init() {
		global $wgEchoNotifications, $wgEchoNotificationCategories, $wgEchoNotifiers,
				$wgEchoNotificationIcons;

		$wgEchoNotifiers = [
			'web' => [
				\BlueSpice\EchoConnector\Notifier\EchoNotifier::class,
				'notifyWithNotification'
			],
			'email' => [
				\BlueSpice\EchoConnector\Notifier\EchoNotifier::class,
				'notifyWithEmail'
			]
		];

		$this->echoNotifications = &$wgEchoNotifications;
		$this->echoNotificationCategories = &$wgEchoNotificationCategories;
		$this->echoIcons = &$wgEchoNotificationIcons;

		$this->registerIconsFromAttribute();
	}

	public function notify( $notification ) {
		if( $notification instanceof EchoNotification == false ) {
			return;
		}

		$echoNotif = [
			'type' => $notification->getKey(),
			'agent' => $notification->getUser(),
			'title' => $notification->getTitle(),
			'extra' => $notification->getParams()
		];

		if( !empty( $notification->getAudience() ) ) {
			$echoNotif['extra']['affected-users'] = $notification->getAudience();
		}

		\EchoEvent::create ( $echoNotif );

		return \Status::newGood();
	}

	protected function registerIconsFromAttribute() {
		$icons = \ExtensionRegistry::getInstance()->getAttribute(
			'BlueSpiceEchoConnectorNotificationIcons'
		);

		foreach( $icons as $key => $params ) {
			$this->registerIcon( $key, $params );
		}
	}

	public function registerIcon( $key, $params ) {
		$this->echoIcons[$key] = $params;
	}

	public function registerNotification( $key, $params ) {
		$extraParams = [];
		if ( !empty( $params[ 'extra-params' ] ) ) {
			$extraParams = $params[ 'extra-params' ];
		}

		if ( !isset ( $extraParams[ 'presentation-model' ] ) ) {
			$extraParams[ 'presentation-model' ] = EchoEventPresentationModel::class;
		}

		if ( isset ( $params[ 'icon' ] ) ) {
			$extraParams[ 'icon' ] = $params[ 'icon' ];
		}

		if( !isset( $params['user-locators'] ) || !is_array( $params['user-locators'] ) ) {
			$params['user-locators'] = [self::class . '::setUsersToNotify'];
		} else {
			$params['user-locators'][] = self::class . '::setUsersToNotify';
		}

		$section = \EchoAttributeManager::ALERT;
		if( isset( $params['section'] ) && $params['section'] == 'message' ) {
			$section = \EchoAttributeManager::MESSAGE;
		}

		$this->echoNotifications[$key] = $extraParams + [
			'category' => $params[ 'category' ],
			'section' => $section,
			'title-message' => $params[ 'summary-message' ],
			'title-params' => $params[ 'summary-params' ],
			'web-body-message' => $params[ 'web-body-message' ],
			'web-body-params' => $params[ 'web-body-params' ],
			'email-subject-message' => $params[ 'email-subject-message' ],
			'email-subject-params' => $params[ 'email-subject-params' ],
			'email-body-message' => $params[ 'email-body-message' ],
			'email-body-params' => $params[ 'email-body-params' ],
			'user-locators' => $params['user-locators']
		];
	}

	public function registerNotificationCategory( $key, $params ) {
		$this->echoNotificationCategories[$key] = $params;
	}

	public function unRegisterNotification( $key ) {
		if( isset( $this->echoNotifications[$key] ) ) {
			unset( $this->echoNotifications[$key] );
		}
	}

	public static function setUsersToNotify( $event ) {
		$users = $event->getExtraParam( 'affected-users', [] );

		$res = [];
		foreach( $users as $user ) {
			if( $user instanceof \User ) {
				$res[$user->getId()] = $user;
				continue;
			}
			$res[$user] = \User::newFromId( $user );
		}

		return $res;
	}

	public static function filterUsersToNotify( $event ) {
	}

}