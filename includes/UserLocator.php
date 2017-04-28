<?php

class BsEchoUserLocator {
	/**
	 * Return all users watching the event title.
	 *
	 * The echo job queue must be enabled to prevent timeouts submitting to
	 * heavily watched pages when this is used.
	 *
	 * @param EchoEvent $event
	 * @return User[]
	 */
	public static function locateUsersWatchingTitle( EchoEvent $event, $batchSize = 500 ) {
		$users = EchoUserLocator::locateUsersWatchingTitle( $event, $batchSize );
		return self::filterDeleted( $users );
	}

	/**
	 * If the event occured on the talk page of a registered
	 * user return that user.
	 *
	 * @param EchoEvent $event
	 * @return User[]
	 */
	public static function locateTalkPageOwner( EchoEvent $event ) {
		$users = EchoUserLocator::locateTalkPageOwner( $event );
		return self::filterDeleted( $users );
	}

	/**
	 * Return the event agent
	 *
	 * @param EchoEvent $event
	 * @return User[]
	 */
	public static function locateEventAgent( EchoEvent $event ) {
		$users = EchoUserLocator::locateEventAgent( $event );
		return self::filterDeleted( $users );
	}

	/**
	 * Return the user that created the first revision of the
	 * associated title.
	 *
	 * @param EchoEvent $evnet
	 * @return User[]
	 */
	public static function locateArticleCreator( EchoEvent $event ) {
		$users = EchoUserLocator::locateArticleCreator( $event );
		return self::filterDeleted( $users );
	}

	/**
	 * Fetch user ids from the event extra data.  Requires additional
	 * parameter.  Example $wgEchoNotifications parameter:
	 *
	 *   'user-locator' => array( array( 'event-extra', 'mentions' ) ),
	 *
	 * The above will look in the 'mentions' parameter for a user id or
	 * array of user ids.  It will return all these users as notification
	 * targets.
	 *
	 * @param EchoEvent $event
	 * @param string[] $keys one or more keys to check for user ids
	 * @return User[]
	 */
	public static function locateFromEventExtra( EchoEvent $event, array $keys ) {
		$users = EchoUserLocator::locateFromEventExtra( $event, $keys );
		return self::filterDeleted( $users );
	}
	
	protected static function filterDeleted( $users ) {
		foreach( $users as $iUserId => $oUser ) {
			$oUser->load(); //This is important as a User object created by User::newFromId may not know the user has already been deleted
			if( $oUser->isAnon() ) {
				unset( $users[$iUserId] );
			}
		}
		return $users;
	}
}
