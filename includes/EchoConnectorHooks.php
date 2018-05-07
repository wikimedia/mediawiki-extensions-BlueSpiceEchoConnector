<?php

class EchoConnectorHooks {

	/**
	 * extension.json callback
	 * @global array $wgEchoNotificationIcons
	 */
	public static function onRegistration () {
		$echoIconPath = "Echo/modules/icons";

		$GLOBALS[ 'wgEchoNotificationIcons' ] = array (
			'chat' => array (
				'path' => "$echoIconPath/chat.svg",
			),
			'checkmark' => array (
				'path' => "$echoIconPath/Reviewed.png",
			),
			'edit' => array (
				'path' => array (
					'ltr' => "$echoIconPath/ooui-edit-ltr-progressive.svg",
					'rtl' => "$echoIconPath/ooui-edit-rtl-progressive.svg",
				),
			),
			'edit-user-talk' => array (
				'path' => "$echoIconPath/edit-user-talk.svg",
			),
			'emailuser' => array (
				'path' => "$echoIconPath/emailuser.svg",
			),
			'featured' => array (
				'path' => "$echoIconPath/Featured.png",
			),
			'global' => array (
				'path' => "$echoIconPath/global.svg"
			),
			'gratitude' => array (
				'path' => "$echoIconPath/Gratitude.png",
			),
			'linked' => array (
				'path' => "$echoIconPath/link-blue.svg",
			),
			'mention' => array (
				'path' => "$echoIconPath/mention.svg",
			),
			'placeholder' => array (
				'path' => "$echoIconPath/Generic.png",
			),
			'reviewed' => array (
				'path' => "$echoIconPath/reviewed.svg",
			),
			'revert' => array (
				'path' => "$echoIconPath/revert.svg",
			),
			'site' => array (
				'url' => false
			),
			'tagged' => array (
				'path' => "$echoIconPath/ReviewedWithTags.png",
			),
			'trash' => array (
				'path' => "$echoIconPath/trash.svg",
			),
			'user-rights' => array (
				'path' => "$echoIconPath/user-rights.svg",
			),
		);

		foreach( $GLOBALS['wgEchoNotifications'] as $sEventName => &$aConfig ) {
			if( isset( $aConfig[EchoAttributeManager::ATTR_LOCATORS] ) ) {
				$aNewLocators = [];
				foreach( $aConfig[EchoAttributeManager::ATTR_LOCATORS] as $mLocatorCallback ) {
					/* Example for $mLocatorCallback
					  array (
						0 => 'EchoUserLocator::locateFromEventExtra',
						1 =>
							array (
								0 => 'user',
							)
						)
					*/
					$sLocatorCallback = $mLocatorCallback;
					if ( is_array( $mLocatorCallback ) ) {
						$sLocatorCallback = $mLocatorCallback[0];
					}

					if( !is_string( $sLocatorCallback ) ) { //Could be any Callable
						$aNewLocators[] = $mLocatorCallback;
						continue;
					}

					$sNewLocatorsCallback = $sLocatorCallback;
					switch( $sLocatorCallback ) {
						case 'EchoUserLocator::locateUsersWatchingTitle':
							$sNewLocatorsCallback = 'BsEchoUserLocator::locateUsersWatchingTitle';
							break;
						case 'EchoUserLocator::locateTalkPageOwner':
							$sNewLocatorsCallback = 'BsEchoUserLocator::locateTalkPageOwner';
							break;
						case 'EchoUserLocator::locateEventAgent':
							$sNewLocatorsCallback = 'BsEchoUserLocator::locateEventAgent';
							break;
						case 'EchoUserLocator::locateArticleCreator':
							$sNewLocatorsCallback = 'BsEchoUserLocator::locateArticleCreator';
							break;
						case 'EchoUserLocator::locateFromEventExtra':
							$sNewLocatorsCallback = 'BsEchoUserLocator::locateFromEventExtra';
							break;
					}

					if( is_array( $mLocatorCallback ) ) {
						$mLocatorCallback[0] = $sNewLocatorsCallback;
					}
					else {
						$mLocatorCallback = $sNewLocatorsCallback;
					}

					$aNewLocators[] = $mLocatorCallback;
				}

				$aConfig[EchoAttributeManager::ATTR_LOCATORS] = $aNewLocators;
			}
		}
	}

	public static function onBeforeNotificationsInit () {
		BSNotifications::registerNotificationHandler (
			'BsEchoNotificationHandler'
		);

		return true;
	}

	/**
	 * Processes "extra data":
	 * - 'affected-users': array of User objects, user ids, user names
	 * - 'affected-groups': array of strings
	 * Removes ids of users that have been deleted (MediaWiki does not allow
	 * user deletion, BlueSpice does; Echo will throw exception)
	 * @param EchoEvent $event
	 * @param array $users in form of [ <user_id> => <User object>, ...]
	 * @return boolean
	 */
	public static function onEchoGetDefaultNotifiedUsers ( $event, &$users ) {
		$aAffectedUsers = $event->getExtraParam( 'affected-users', array () );
		$aAffectedGroups = $event->getExtraParam( 'affected-groups', array () );

		//Step 1: resolve groups to user_ids
		if ( !empty ( $aAffectedGroups ) ) {
			$dbr = wfGetDB ( DB_SLAVE );
			$res = $dbr->select ( 'user_groups', 'ug_user', array ( 'ug_group' => $aAffectedGroups ) );
			foreach ( $res as $row ) {
			$aAffectedUsers[] = $row->ug_user; //Append id to list of users.
			//If a user is already on the list he/she will be filtered out below
			}
		}

		//Step 2: normalize list of users
		foreach ( $aAffectedUsers as $mUser ) {
			$oUser = $mUser;
			if ( is_int ( $mUser ) ) { //user_id
				if ( isset ( $users[ $mUser ] ) ) {
					continue;
				}
				$oUser = User::newFromId ( $mUser );
			}

			if ( is_string ( $mUser ) ) { //user_name
				$oUser = User::newFromName ( $mUser );
			}

			if ( $oUser instanceof User && !$oUser->isAnon () ) {
				$users[ $oUser->getId () ] = $oUser;
			}
		}

		//Step 3: remove deleted users
		foreach( $users as $iUserId => $oUser ) {
			if( $oUser->isAnon() ) {
				unset( $users[$iUserId] );
			}
		}

		return true;
	}

	public static function onEchoGetBundleRules ( $event, &$bundleString ) {
		$bundleString = $event->getType ();
		if ( $event->getTitle () ) {
			$bundleString .= '-' . $event->getTitle ()->getNamespace () . '-' . $event->getTitle ()->getDBkey ();
		}
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return boolean
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		if( $out->getTitle()->isSpecial( 'Notifications' ) ) {
			$out->addModules( 'ext.bluespice.echoconnector.fixer' );
			$out->addModuleStyles( 'ext.bluespice.echoconnector.fixer.styles' );
		}
		return true;
	}
}
