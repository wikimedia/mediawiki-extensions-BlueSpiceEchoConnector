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
     * @param EchoEvent $event
     * @param array $users in form of [ <user_id> => <User object>, ...]
     * @return boolean
     */
    public static function onEchoGetDefaultNotifiedUsers ( $event, &$users ) {
	$aAffectedUsers = $event->getExtraParam ( 'affected-users', array () );
	$aAffectedGroups = $event->getExtraParam ( 'affected-groups', array () );

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

	return true;
    }

    public static function onEchoGetBundleRules ( $event, &$bundleString ) {
	$bundleString = $event->getType ();
	if ( $event->getTitle () ) {
	    $bundleString .= '-' . $event->getTitle ()->getNamespace () . '-' . $event->getTitle ()->getDBkey ();
	}
    }

}
