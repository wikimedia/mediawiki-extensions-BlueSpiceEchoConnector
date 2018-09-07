<?php

/**
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * ,
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://bluespice.com
 *
 * @author Patric Wirth <wirth@hallowelt.com>
 * @package BlueSpice_Distrubution
 * @copyright Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */
class BsEchoNotificationHandler extends BSNotificationHandler {

	public static function init () {
		$GLOBALS['wgEchoNotifiers'] = array(
			'web' => array( 'BsEchoNotifier', 'notifyWithNotification' ),
			'email' => array( 'BsEchoNotifier', 'notifyWithEmail' ),
		);

		self::registerNotificationCategory ( 'bs-admin-cat', 3, null, null, array ( 'sysop' ) );
		self::registerNotificationCategory ( 'bs-page-actions-cat', 3 );

		self::registerNotification (
			array ( array (
				'type' => 'bs-adduser',
				'category' => 'bs-admin-cat',
				'summary-message' => 'bs-notifications-addacount',
				'summary-params' => array (
					'username'
				),
				'email-subject' => 'bs-notifications-email-addaccount-subject',
				'email-subject-params' => array (
					'username', 'username'
				),
				'email-body' => 'bs-notifications-email-addaccount-body',
				'email-body-params' => array (
					'userlink', 'username', 'username', 'user'
				),
				'web-body-message' => 'bs-notifications-email-addaccount-body',
				'web-body-params' => array (
					'userlink', 'username', 'username', 'user'
				),
				'extra-params' => array ()
			) )
		);

		self::registerNotification (
			array ( array (
				'type' => 'bs-edit',
				'category' => 'bs-page-actions-cat',
				'summary-message' => 'bs-notifications-edit',
				'summary-params' => array (
					'title'
				),
				'email-subject' => 'bs-notifications-email-edit-subject',
				'email-subject-params' => array (
					'title', 'agent', 'realname'
				),
				'email-body' => 'bs-notifications-email-edit-body',
				'email-body-params' => array (
					'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname'
				),
				'web-body-message' => 'bs-notifications-email-edit-body',
				'web-body-params' => array (
					'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname'
				),
				'extra-params' => array ()
			) )
		);

		self::registerNotification (
			array ( array (
				'type' => 'bs-create',
				'category' => 'bs-page-actions-cat',
				'summary-message' => 'bs-notifications-create',
				'summary-params' => array (
					'title'
				),
				'email-subject' => 'bs-notifications-email-create-subject',
				'email-subject-params' => array (
					'title', 'agent', 'realname'
				),
				'email-body' => 'bs-notifications-email-create-body',
				'email-body-params' => array (
					'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname'
				),
				'web-body-message' => 'bs-notifications-email-create-body',
				'web-body-params' => array (
					'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname'
				),
				'extra-params' => array ()
			) )
		);

		self::registerNotification (
			array ( array (
				'type' => 'bs-delete',
				'category' => 'bs-page-actions-cat',
				'summary-message' => 'bs-notifications-delete',
				'summary-params' => array (
					'title'
				),
				'email-subject' => 'bs-notifications-email-delete-subject',
				'email-subject-params' => array (
					'title', 'agent', 'realname'
				),
				'email-body' => 'bs-notifications-email-delete-body',
				'email-body-params' => array (
					'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname'
				),
				'web-body-message' => 'bs-notifications-email-delete-body',
				'web-body-params' => array (
					'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname'
				),
				'extra-params' => array ()
			) )
		);

		self::registerNotification (
			array ( array (
				'type' => 'bs-move',
				'category' => 'bs-page-actions-cat',
				'summary-message' => 'bs-notifications-move',
				'summary-params' => array (
					'title', 'agent'
				),
				'email-subject' => 'bs-notifications-email-move-subject',
				'email-subject-params' => array (
					'title', 'agent', 'newtitle', 'realname'
				),
				'email-body' => 'bs-notifications-email-move-body',
				'email-body-params' => array (
					'title', 'agent', 'newtitle', 'titlelink', 'realname'
				),
				'web-body-message' => 'bs-notifications-email-move-body',
				'web-body-params' => array (
					'title', 'agent', 'newtitle', 'titlelink', 'realname'
				),
				'extra-params' => array ()
			) )
		);

		Hooks::register ( 'ArticleDeleteComplete', 'BsEchoNotificationHandler::onArticleDeleteComplete' );
		Hooks::register ( 'ArticleSaveComplete', 'BsEchoNotificationHandler::onArticleSaveComplete' );
		Hooks::register ( 'BSUserManagerAfterAddUser', 'BsEchoNotificationHandler::onBSUserManagerAfterAddUser' );
		Hooks::register ( 'TitleMoveComplete', 'BsEchoNotificationHandler::onTitleMoveComplete' );
		Hooks::register ( 'EchoGetDefaultNotifiedUsers', 'BsEchoNotificationHandler::onEchoGetDefaultNotifiedUsers' );
		Hooks::register ( 'EchoGetNotificationTypes', 'BsEchoNotificationHandler::onEchoGetNotificationTypes' );
	}

	/**
	 * @see BSNotificationHandlerInterface::registerIcon
	 *
	 * @param String  $sKey
	 * @param String  $sLocation
	 * @param String  $sLocationType
	 * @param Boolean $bOverride
	 */
	public static function registerIcon ( $sKey, $sLocation, $sLocationType = 'path', $bOverride = false ) {
		global $wgEchoNotificationIcons;

		// Don't override the icon definition until the caller explicitly wants to override it.
		if ( is_array ( $wgEchoNotificationIcons[ $sKey ] ) && !$bOverride ) {
			return;
		}

		// Make sure we have a proper location type
		if ( $sLocationType != 'path' ) {
			$sLocationType = 'url';
		}

		$wgEchoNotificationIcons[ $sKey ] = array (
			$sLocationType => $sLocation
		);
	}

	/**
	 *
	 * @see BSNotificationHandlerInterface::registerNotificationCategory
	 *
	 * @param String  $sKey
	 * @param Integer $iPriority
	 * @param Array   $aNoDismiss
	 * @param String  $sTooltipMsgKey
	 * @param Array   $aUserGroups
	 * @param Array   $aActiveDefaultUserOptions
	 */
	public static function registerNotificationCategory (
		$sKey, $iPriority = 10, $aNoDismiss = null, $sTooltipMsgKey = null, $aUserGroups = null, $aActiveDefaultUserOptions = null
		) {
		global $wgEchoNotificationCategories, $wgDefaultUserOptions;
		$aCategory = array (
			'priority' => $iPriority
		);

		if ( $aNoDismiss && is_array ( $aNoDismiss ) ) {
			$aCategory[ 'no-dismiss' ] = $aNoDismiss;
		}

		if ( $sTooltipMsgKey ) {
			$aCategory[ 'tooltip' ] = $sTooltipMsgKey;
		}

		if ( $aUserGroups && is_array ( $aUserGroups ) ) {
			$aCategory[ 'usergroups' ] = $aUserGroups;
		}

		$wgEchoNotificationCategories[ $sKey ] = $aCategory;

		if ( $aActiveDefaultUserOptions && is_array ( $aActiveDefaultUserOptions ) ) {
			foreach ( $aActiveDefaultUserOptions as $sNotificationType ) {
				$wgDefaultUserOptions[ "echo-subscriptions-{$sNotificationType}-{$sKey}" ] = true;
			}
		}
	}

	/**
	 * @see BSNotificationHandlerInterface::registerNotification
	 *
	 * @param String $sKey
	 * @param String $sCategory
	 * @param String $sSummaryMsgKey
	 * @param Array  $aSummaryParams
	 * @param String $sEmailSubjectMsgKey
	 * @param Array  $aEmailSubjectParams
	 * @param String $sEmailBodyMsgKey
	 * @param Array  $aEmailBodyParams
	 * @param Array  $aExtraParams
	 */
	public static function registerNotification ( $aParams ) {
		global $wgEchoNotifications;

		//Support for old signature, for backwards compatibility
		if ( is_array ( $aParams[ 0 ] ) ) {
			$aValues = $aParams[ 0 ];
		} else {
			$aValues[ 'type' ] = $aParams[ 0 ];
			$aValues[ 'category' ] = $aParams[ 1 ];
			$aValues[ 'summary-message' ] = $aParams[ 2 ];
			$aValues[ 'summary-params' ] = $aParams[ 3 ];
			$aValues[ 'email-subject' ] = $aParams[ 4 ];
			$aValues[ 'email-subject-params' ] = $aParams[ 5 ];
			$aValues[ 'email-body' ] = $aParams[ 6 ];
			$aValues[ 'email-body-params' ] = $aParams[ 7 ];
			$aValues[ 'web-body-message' ] = $aParams[ 6 ];
			$aValues[ 'web-body-params' ] = $aParams[ 7 ];
			if ( isset ( $aParams[ 8 ] ) && is_array ( $aParams[ 8 ] ) ) {
				$aValues[ 'extra-params' ] = $aParams[ 8 ];
			} else {
				$aValues[ 'extra-params' ] = array ();
			}
		}

		$aExtraParams = [];
		if ( !empty( $aValues[ 'extra-params' ] ) ) {
			$aExtraParams = $aValues[ 'extra-params' ];
		}

		if ( !isset ( $aExtraParams[ 'formatter-class' ] ) ) {
			$aExtraParams[ 'formatter-class' ] = 'BsNotificationsFormatter';
		}
		if ( !isset ( $aExtraParams[ 'presentation-model' ] ) ) {
			$aExtraParams[ 'presentation-model' ] = 'BsEchoEventPresentationModel';
		}

		if ( isset ( $aValues[ 'icon' ] ) ) {
			$aExtraParams[ 'icon' ] = $aValues[ 'icon' ];
		}

		$wgEchoNotifications[ $aValues[ 'type' ] ] = $aExtraParams + array (
			'category' => $aValues[ 'category' ],
			'title-message' => $aValues[ 'summary-message' ],
			'title-params' => $aValues[ 'summary-params' ],
			'web-body-message' => $aValues[ 'web-body-message' ],
			'web-body-params' => $aValues[ 'web-body-params' ],
			'email-subject-message' => $aValues[ 'email-subject' ],
			'email-subject-params' => $aValues[ 'email-subject-params' ],
			'email-body-batch-message' => $aValues[ 'email-body' ],
			'email-body-batch-params' => $aValues[ 'email-body-params' ]
		);
	}

	/**
	 * @see BSNotificationHandlerInterface::unregisterNotification
	 *
	 * @param $sKey
	 */
	public static function unregisterNotification ( $sKey ) {
		global $wgEchoNotifications;
		unset ( $wgEchoNotifications[ $sKey ] );
	}

	/**
	 * @see BSNotificationHandlerInterface::notify
	 *
	 * @param String $sKey
	 * @param User   $oAgent
	 * @param Title  $oTitle
	 * @param Array  $aExtraParams
	 *
	 * @throws MWException
	 * @throws ReadOnlyError
	 */
	public static function notify (
		$sKey, $oAgent = null, $oTitle = null, $aExtraParams = null
		) {
		$aNotification = array (
			'type' => $sKey
		);

		if ( $oAgent ) {
			$aNotification[ 'agent' ] = $oAgent;
		}

		if ( $oTitle ) {
			$aNotification[ 'title' ] = $oTitle;
		}

		if ( $aExtraParams && is_array ( $aExtraParams ) ) {
			$aNotification[ 'extra' ] = $aExtraParams;
		}

		EchoEvent::create ( $aNotification );
	}

	/**
	 * Sends a notification on article creation and edit.
	 *
	 * @param Article  $oArticle The article that is created.
	 * @param User	 $oUser User that saved the article.
	 * @param String   $sText New text.
	 * @param String   $sSummary Edit summary.
	 * @param Boolean  $bMinorEdit Marked as minor.
	 * @param Boolean  $bWatchThis Put on watchlist.
	 * @param Integer  $iSectionAnchor Not in use any more.
	 * @param Integer  $iFlags Bitfield.
	 * @param Revision $oRevision New revision object.
	 * @param Status   $oStatus Status object (since MW1.14)
	 * @param Integer  $iBaseRevId Revision ID this edit is based on (since MW1.15)
	 * @param Boolean  $bRedirect Redirect user back to page after edit (since MW1.17)
	 *
	 * @return bool allow other hooked methods to be executed. Always true
	 */
	public static function onArticleSaveComplete ( $oArticle, $oUser, $sText, $sSummary, $bMinorEdit, $bWatchThis, $iSectionAnchor, $iFlags, $oRevision, $oStatus, $iBaseRevId, $bRedirect = false ) {
		if ( $oUser->isAllowed ( 'bot' ) )
			return true;
		if ( $oArticle->getTitle ()->getNamespace () === NS_USER_TALK )
			return true;

		if ( $iFlags & EDIT_NEW ) {
			BSNotifications::notify (
				'bs-create', $oUser, $oArticle->getTitle (), array (
					'summary' => $sSummary,
					'titlelink' => true,
					'realname' => BsUserHelper::getUserDisplayName( $oUser ),
					'difflink' => '',
				)
			);

			return true;
		}

		$aDiffParams = array ( 'diffparams' => array () );
		if ( is_object ( $oRevision ) ) {
			$aDiffParams[ 'diffparams' ][ 'diff' ] = $oRevision->getId ();
			if ( is_object ( $oRevision->getPrevious () ) ) {
				$aDiffParams[ 'diffparams' ][ 'oldid' ] = $oRevision->getPrevious ()->getId ();
			}
		}
		BSNotifications::notify (
			'bs-edit', $oUser, $oArticle->getTitle (), array (
				'summary' => $sSummary,
				'titlelink' => true,
				'difflink' => $aDiffParams,
				'agentlink' => true,
				'realname' => BsUserHelper::getUserDisplayName( $oUser )
			)
		);

		return true;
	}

	/**
	 * Sends a notification on article deletion
	 *
	 * @param Article $oArticle The article that is being deleted.
	 * @param User$oUser The user that deletes.
	 * @param string $sReason A reason for article deletion
	 * @param int $iId Id of article that was deleted.
	 *
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public static function onArticleDeleteComplete ( &$oArticle, &$oUser, $sReason, $iId ) {
		if ( $oUser->isAllowed ( 'bot' ) )
			return true;
		BSNotifications::notify (
			'bs-delete', $oUser, $oArticle->getTitle (), array (
				'deletereason' => $sReason,
				'title' => $oArticle->getTitle ()->getText (),
				'realname' => BsUserHelper::getUserDisplayName( $oUser ),
			)
		);

		return true;
	}

	/**
	 * Sends a notification when an article is moved.
	 *
	 * @param Title $oTitle Old title of the moved article.
	 * @param Title $oNewTitle New tite of the moved article.
	 * @param User  $oUser User that moved the article.
	 * @param int   $iOldId ID of the page that has been moved.
	 * @param int   $iNewId ID of the newly created redirect.
	 *
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public static function onTitleMoveComplete ( $oTitle, $oNewTitle, $oUser, $iOldId, $iNewId ) {
		if ( $oUser->isAllowed ( 'bot' ) ) {
			return true;
		}

		BSNotifications::notify (
			'bs-move', $oUser, $oTitle, array (
				'newtitle' => $oNewTitle,
				'realname' => BsUserHelper::getUserDisplayName( $oUser ),
			)
		);

		return true;
	}

	public function onBSUserManagerAfterAddUser ( UserManager $oUserManager, User $oUser, $aMetaData, &$oStatus ) { #$aUserDetails
		BSNotifications::notify (
			'bs-adduser', $oUserManager->getUser (), Title::newFromText ( "Test" ), array (
			'username' => $oUser->getName (),
			'userlink' => $oUser->getUserPage ()->getFullURL (),
			'user' => $oUser->getName () //user means username here! not userobject, otherwise exception ist thrown when user object given in here!
			)
		);

		return true;
	}

	/**
	 * Handler for EchoGetDefaultNotifiedUsers hook.
	 * @param $event EchoEvent to get implicitly subscribed users for
	 * @param &$users Array to append implicitly subscribed users to.
	 * @return bool true in all cases
	 */
	public static function onEchoGetDefaultNotifiedUsers ( $event, &$users ) {
		// Everyone deserves to know when something happens
		// on their user talk page
		$dbr = wfGetDB ( DB_SLAVE );
		switch ( $event->getType () ) {
			case 'bs-adduser':
			//Get admin users
			$resSysops = $dbr->select ( "user_groups", "ug_user", 'ug_group = "sysop"' );
			foreach ( $resSysops as $row ) {
				$user = User::newFromId ( $row->ug_user );
				$users[ $user->getId () ] = $user;
			}
			break;
			case 'bs-create':
			case 'bs-edit':
			case 'bs-move':
			case 'bs-delete':
				//We need to pre-filter for the subscription user setting here.
				//Otherwise a large user base (2000+) will result in bad performance
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
					$user = User::newFromId ( $row->up_user );
					$users[ $user->getId () ] = $user;
				}
			break;
		}

		return true;
	}

	/**
	 * Handler for EchoGetNotificationTypes hook, Adjust the notify types (e.g. web, email) which
	 * are applicable to this event and user based on various user options. In other words, allow
	 * certain non-echo user options to override the echo notification options.
	 * @param $user User
	 * @param $event EchoEvent
	 * @param $notifyTypes
	 * @return bool
	 */
	public static function onEchoGetNotificationTypes ( User $user, $event, &$notifyTypes ) {
		$type = $event->getType ();
		if ( $type == "bs-adduser" ) {
			$arrUserOptions = $user->getOptions ();
			$notifyTypes = array_diff ( $notifyTypes, array ( 'web', 'email' ) );

			if ( isset ( $arrUserOptions[ 'echo-subscriptions-web-bs-admin-cat' ] ) &&
				$arrUserOptions[ 'echo-subscriptions-web-bs-admin-cat' ] == 1 ) {
				$notifyTypes[] = 'web';
			}
			if ( isset ( $arrUserOptions[ 'echo-subscriptions-email-bs-admin-cat' ] ) &&
				$arrUserOptions[ 'echo-subscriptions-email-bs-admin-cat' ] == 1 ) {
				$notifyTypes[] = 'email';
			}
		}
		return true;
	}
}
