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
 *
 * This file is part of BlueSpice for MediaWiki
 * For further information visit http://bluespice.com
 *
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    BlueSpice_Distrubution
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */
class BSEchoNotificationHandler extends BSNotificationHandler {

	public static function init() {
		self::registerNotificationCategory( 'bs-admin-cat', 3, null, null, array( 'sysop' ) );
		self::registerNotificationCategory( 'bs-page-actions-cat', 3 );

		self::registerNotification(
			'bs-adduser',
			'bs-admin-cat',
			'bs-notifications-addacount',
			array( 'username' ),
			'bs-notifications-email-addaccount-subject',
			array( 'username', 'username' ),
			'bs-notifications-email-addaccount-body',
			array( 'userlink', 'username', 'username', 'user' )
		);

		self::registerNotification(
			'bs-edit',
			'bs-page-actions-cat',
			'bs-notifications-edit',
			array( 'title' ),
			'bs-notifications-email-edit-subject',
			array( 'title', 'agent', 'realname' ),
			'bs-notifications-email-edit-body',
			array( 'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname' )
		);

		self::registerNotification(
			'bs-create',
			'bs-page-actions-cat',
			'bs-notifications-create',
			array( 'title' ),
			'bs-notifications-email-create-subject',
			array( 'title', 'agent', 'realname' ),
			'bs-notifications-email-create-body',
			array( 'title', 'agent', 'summary', 'titlelink', 'difflink', 'realname' )
		);

		self::registerNotification(
			'bs-delete',
			'bs-page-actions-cat',
			'bs-notifications-delete',
			array( 'title' ),
			'bs-notifications-email-delete-subject',
			array( 'title', 'agent', 'realname' ),
			'bs-notifications-email-delete-body',
			array( 'title', 'agent', 'summary', 'deletereason', 'realname' )
		);

		self::registerNotification(
			'bs-move',
			'bs-page-actions-cat',
			'bs-notifications-move',
			array( 'title', 'agent' ),
			'bs-notifications-email-move-subject',
			array( 'title', 'agent', 'realname' ),
			'bs-notifications-email-move-body',
			array( 'title', 'agent', 'newtitle', 'difflink', 'realname' )
		);

		Hooks::register( 'ArticleDeleteComplete', 'BSEchoNotificationHandler::onArticleDeleteComplete' );
		Hooks::register( 'ArticleSaveComplete', 'BSEchoNotificationHandler::onArticleSaveComplete' );
		Hooks::register( 'BSUserManagerAfterAddUser', 'BSEchoNotificationHandler::onBSUserManagerAfterAddUser' );
		Hooks::register( 'TitleMoveComplete', 'BSEchoNotificationHandler::onTitleMoveComplete' );
		Hooks::register( 'EchoGetDefaultNotifiedUsers', 'BSEchoNotificationHandler::onEchoGetDefaultNotifiedUsers' );
		Hooks::register( 'EchoGetNotificationTypes', 'BSEchoNotificationHandler::onEchoGetNotificationTypes' );
	}

	/**
	 * @see BSNotificationHandlerInterface::registerIcon
	 *
	 * @param String  $sKey
	 * @param String  $sLocation
	 * @param String  $sLocationType
	 * @param Boolean $bOverride
	 */
	public static function registerIcon( $sKey, $sLocation, $sLocationType = 'path', $bOverride = false ) {
		global $wgEchoNotificationIcons;

		// Don't override the icon definition until the caller explicitly wants to override it.
		if ( is_array( $wgEchoNotificationIcons[ $sKey ] ) && !$bOverride ) {
			return;
		}

		// Make sure we have a proper location type
		if ( $sLocationType != 'path' ) {
			$sLocationType = 'url';
		}

		$wgEchoNotificationIcons[ $sKey ] = array(
			$sLocationType => $sLocation
		);
	}

	/**
	 * @see BSNotificationHandlerInterface::registerNotificationCategory
	 *
	 * @param String  $sKey
	 * @param Integer $iPriority
	 * @param Array   $aNoDismiss
	 * @param String  $sTooltipMsgKey
	 * @param Array   $aUserGroups
	 * @param Array   $aActiveDefaultUserOptions
	 */
	public static function registerNotificationCategory(
		$sKey,
		$iPriority = 10,
		$aNoDismiss = null,
		$sTooltipMsgKey = null,
		$aUserGroups = null,
		$aActiveDefaultUserOptions = null
	) {
		global $wgEchoNotificationCategories, $wgDefaultUserOptions;

		$aCategory = array(
			'priority' => $iPriority
		);

		if ( $aNoDismiss && is_array( $aNoDismiss ) ) {
			$aCategory[ 'no-dismiss' ] = $aNoDismiss;
		}

		if ( $sTooltipMsgKey ) {
			$aCategory[ 'tooltip' ] = $sTooltipMsgKey;
		}

		if ( $aUserGroups && is_array( $aUserGroups ) ) {
			$aCategory[ 'usergroups' ] = $aUserGroups;
		}

		$wgEchoNotificationCategories[ $sKey ] = $aCategory;

		if ( $aActiveDefaultUserOptions && is_array( $aActiveDefaultUserOptions ) ) {
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
	public static function registerNotification(
		$sKey,
		$sCategory,
		$sSummaryMsgKey,
		$aSummaryParams,
		$sEmailSubjectMsgKey,
		$aEmailSubjectParams,
		$sEmailBodyMsgKey,
		$aEmailBodyParams,
		$aExtraParams = null
	) {
		global $wgEchoNotifications;

		if ( !is_array( $aExtraParams ) ) {
			$aExtraParams = array();
		}

		if ( !isset( $aExtraParams[ 'formatter-class' ] ) ) {
			$aExtraParams[ 'formatter-class' ] = 'BsNotificationsFormatter';
		}
		if ( !isset( $aExtraParams[ 'presentation-model' ] ) ) {
			$aExtraParams[ 'presentation-model' ] = 'EchoBlueSpicePresentationModel';
		}

		$wgEchoNotifications[ $sKey ] = $aExtraParams + array(
				'category' => $sCategory,
				'title-message' => $sSummaryMsgKey,
				'title-params' => $aSummaryParams,
				'email-subject-message' => $sEmailSubjectMsgKey,
				'email-subject-params' => $aEmailSubjectParams,
				'email-body-batch-message' => $sEmailBodyMsgKey,
				'email-body-batch-params' => $aEmailBodyParams
			);
	}

	/**
	 * @see BSNotificationHandlerInterface::unregisterNotification
	 *
	 * @param $sKey
	 */
	public static function unregisterNotification( $sKey ) {
		global $wgEchoNotifications;
		unset( $wgEchoNotifications[ $sKey ] );
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
	public static function notify(
		$sKey,
		$oAgent = null,
		$oTitle = null,
		$aExtraParams = null
	) {
		$aNotification = array(
			'type' => $sKey
		);

		if ( $oAgent ) {
			$aNotification[ 'agent' ] = $oAgent;
		}

		if ( $oTitle ) {
			$aNotification[ 'title' ] = $oTitle;
		}

		if ( $aExtraParams && is_array( $aExtraParams ) ) {
			$aNotification[ 'extra' ] = $aExtraParams;
		}

		EchoEvent::create( $aNotification );
	}

	/**
	 * Sends a notification on article creation and edit.
	 *
	 * @param Article  $oArticle       The article that is created.
	 * @param User     $oUser          User that saved the article.
	 * @param String   $sText          New text.
	 * @param String   $sSummary       Edit summary.
	 * @param Boolean  $bMinorEdit     Marked as minor.
	 * @param Boolean  $bWatchThis     Put on watchlist.
	 * @param Integer  $iSectionAnchor Not in use any more.
	 * @param Integer  $iFlags         Bitfield.
	 * @param Revision $oRevision      New revision object.
	 * @param Status   $oStatus        Status object (since MW1.14)
	 * @param Integer  $iBaseRevId     Revision ID this edit is based on (since MW1.15)
	 * @param Boolean  $bRedirect      Redirect user back to page after edit (since MW1.17)
	 *
	 * @return bool allow other hooked methods to be executed. Always true
	 */
	public static function onArticleSaveComplete( $oArticle, $oUser, $sText, $sSummary, $bMinorEdit, $bWatchThis, $iSectionAnchor, $iFlags, $oRevision, $oStatus, $iBaseRevId, $bRedirect = false ) {
		if ( $oUser->isAllowed( 'bot' ) ) return true;
		if ( $oArticle->getTitle()->getNamespace() === NS_USER_TALK ) return true;

		if ( $iFlags & EDIT_NEW ) {
			BSNotifications::notify(
				'bs-create',
				$oUser,
				$oArticle->getTitle(),
				array(
					'summary' => $sSummary,
					'titlelink' => true,
					'realname' => BsUserHelper::getUserDisplayName( $oUser ),
					'difflink' => '',
				)
			);

			return true;
		}

		$aDiffParams = array( 'diffparams' => array() );
		if( is_object( $oRevision ) ) {
			$aDiffParams['diffparams']['diff'] = $oRevision->getId();
			if( is_object($oRevision->getPrevious()) ) {
				$aDiffParams['diffparams']['oldid'] = $oRevision->getPrevious()->getId();
			}
		}
		BSNotifications::notify(
			'bs-edit',
			$oUser,
			$oArticle->getTitle(),
			array(
				'summary' => $sSummary,
				'titlelink' => true,
				'difflink' => $aDiffParams,
				'agentlink' => true,
				'realname' => BsUserHelper::getUserDisplayName( $oUser ),
			)
		);

		return true;
	}

	/**
	 * Sends a notification on article deletion
	 *
	 * @param Article $oArticle The article that is being deleted.
	 * @param User    $oUser    The user that deletes.
	 * @param string  $sReason  A reason for article deletion
	 * @param int     $iId      Id of article that was deleted.
	 *
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public static function onArticleDeleteComplete( &$oArticle, &$oUser, $sReason, $iId ) {
		if ( $oUser->isAllowed( 'bot' ) ) return true;
		BSNotifications::notify(
			'bs-delete',
			$oUser,
			$oArticle->getTitle(),
			array(
				'deletereason' => $sReason,
				'title' => $oArticle->getTitle()->getText(),
				'realname' => BsUserHelper::getUserDisplayName( $oUser ),
			)
		);

		return true;
	}

	/**
	 * Sends a notification when an article is moved.
	 *
	 * @param Title $oTitle    Old title of the moved article.
	 * @param Title $oNewTitle New tite of the moved article.
	 * @param User  $oUser     User that moved the article.
	 * @param int   $iOldId    ID of the page that has been moved.
	 * @param int   $iNewId    ID of the newly created redirect.
	 *
	 * @return bool allow other hooked methods to be executed. Always true.
	 */
	public static function onTitleMoveComplete( $oTitle, $oNewTitle, $oUser, $iOldId, $iNewId ) {
		if ( $oUser->isAllowed( 'bot' ) ) return true;

		BSNotifications::notify(
			'bs-move',
			$oUser,
			$oTitle,
			array(
				'newtitle' => $oNewTitle,
				'realname' => BsUserHelper::getUserDisplayName( $oUser ),
			)
		);

		return true;
	}


	public function onBSUserManagerAfterAddUser( UserManager $oUserManager, User $oUser, $aMetaData, &$oStatus ) { #$aUserDetails

		BSNotifications::notify(
			'bs-adduser',
			$oUserManager->getUser(),
			Title::newFromText("Test"),
			array(
				'username' => $oUser->getName(),
				'userlink' => $oUser->getUserPage()->getFullURL(),
				'user' => $oUser->getName() //user means username here! not userobject, otherwise exception ist thrown when user object given in here!
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
	public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
		// Everyone deserves to know when something happens
		// on their user talk page
		$dbr = wfGetDB( DB_SLAVE );
		switch ( $event->getType() ) {
			case 'bs-adduser':
				//Get admin users
				$resSysops = $dbr->select("user_groups", "ug_user", 'ug_group = "sysop"');
				foreach($resSysops as $row){
					$user = User::newFromId($row->ug_user);
					$users[$user->getId()] = $user;
				}
				break;
			case 'bs-create':
			case 'bs-edit':
				//Get all user, notification abo will be checked later...
				$resUser = $dbr->select( "user", "user_id" );
				foreach( $resUser as $row ){
					$user = User::newFromId($row->user_id);
					$users[$user->getId()] = $user;
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
	public static function onEchoGetNotificationTypes( User $user, $event, &$notifyTypes ) {
		$type = $event->getType();
		if ( $type == "bs-adduser" ) {
			$arrUserOptions = $user->getOptions();

			$notifyTypes = array_diff( $notifyTypes, array( 'web', 'email' ) );

			if( isset( $arrUserOptions['echo-subscriptions-web-bs-admin-cat'] ) &&
				$arrUserOptions['echo-subscriptions-web-bs-admin-cat'] == 1 ){
				$notifyTypes[] =  'web';
			}
			if( isset( $arrUserOptions['echo-subscriptions-email-bs-admin-cat'] ) &&
				$arrUserOptions['echo-subscriptions-email-bs-admin-cat'] == 1 ) {
				$notifyTypes[] = 'email';
			}
		}
		return true;
	}
}