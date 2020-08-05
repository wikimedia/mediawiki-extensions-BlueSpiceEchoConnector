<?php

namespace BlueSpice\EchoConnector;

use BlueSpice\Data\ReaderParams;
use BlueSpice\Data\Watchlist\Record;
use BlueSpice\Data\Watchlist\Store;
use IContextSource;
use LoadBalancer;
use MWException;
use Title;
use User;
use WikiPage;
use WikitextContent;

class UserLocator {
	protected $loadBalancer = null;
	protected $context = null;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param IContextSource $context
	 */
	public function __construct( LoadBalancer $loadBalancer, IContextSource $context ) {
		$this->loadBalancer = $loadBalancer;
		$this->context = $context;
	}

	/**
	 * Get all uses watching the title
	 *
	 * @param string $title
	 * @return array
	 */
	public function getWatchers( $title ) {
		$watchers = [];
		$wlStore = new Store( $this->context );
		$readerParams = new ReaderParams( [
			'limit' => 99999,
			'filter' => [ [
				'comparison' => 'eq',
				'property' => Record::PAGE_PREFIXED_TEXT,
				'value' => $title,
				'type' => 'string'
			] ]
		] );

		$records = $wlStore->getReader()->read( $readerParams );

		foreach ( $records->getRecords() as $record ) {
			$userId = $record->get( Record::USER_ID, false );
			if ( $userId ) {
				$user = User::newFromId( $userId );
				$user->load();
				if ( $user->isAnon() ) {
					continue;
				}
				$watchers[$user->getId()] = $user;
			}
		}

		return array_values( $watchers );
	}

	/**
	 * Get all users from the particular group
	 *
	 * @param array $groups
	 * @return array
	 */
	public function getUsersFromGroups( $groups ) {
		if ( !is_array( $groups ) ) {
			$conds = 'ug_group = "' . $groups . '"';
		} else {
			$conds = [];
			foreach ( $groups as $group ) {
				$conds[] = 'ug_group = "' . $group . '"';
			}
			$conds = implode( ' OR ', $conds );
		}

		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select(
			[
				"user_groups",
				"user",
			],
			[
				"user.*"
			],
			$conds,
			__METHOD__,
			[],
			[ 'user' => [ 'INNER JOIN', 'user_properties.up_user = user.user_id' ] ]
		);

		return array_values( $this->usersFromRows( $res ) );
	}

	/**
	 * Get all users subscribed to the particular category
	 *
	 * @param string $cat Notification category
	 * @return array
	 */
	public function getAllSubscribed( $cat ) {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$resUser = $dbr->select(
			[
				"user_properties",
				"user",
			],
			[
				"DISTINCT up_user",
				"user.*",
			],
			[
				"up_property" => [
					"echo-subscriptions-web-$cat",
					"echo-subscriptions-email-$cat"
				],
				"up_value" => 1
			],
			__METHOD__,
			[],
			[ 'user' => [ 'INNER JOIN', 'user_properties.up_user = user.user_id' ] ]
		);

		return array_values( $this->usersFromRows( $resUser ) );
	}

	/**
	 * @param int $nsId
	 * @param string $action
	 *
	 * @return User[]|array
	 */
	public function getUsersSubscribedToNamespace( $nsId, $action ) {
		return $this->getUsersWithSubscriptionPreference( 'namespace', $action, $nsId );
	}

	/**
	 * @param Title $title
	 * @param string $action
	 * @return User[]|array
	 * @throws MWException
	 */
	public function getUsersSubscribedToTitleCategories( Title $title, $action ) {
		$wikipage = WikiPage::factory( $title );
		$content = $wikipage->getContent();

		if ( !$content instanceof WikitextContent ) {
			return [];
		}

		$categories = $content->getParserOutput( $title )->getCategoryLinks();

		$users = [];
		foreach ( $categories as $cat ) {
			$users += $this->getUsersWithSubscriptionPreference( 'category', $action, $cat );
		}

		return $users;
	}

	/**
	 * Get all users that have a particular user preference
	 *
	 * @param string $type
	 * @param string $action
	 * @param string $key
	 * @return User[]|array
	 */
	private function getUsersWithSubscriptionPreference( $type, $action, $key ) {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		// Sanity, in case something calls this on install, before tables are created
		if ( !$db->tableExists( 'user_properties' ) || !$db->tableExists( 'user' ) ) {
			return [];
		}

		$prefName = "notify-$type-selectionpage-$action-$key";
		$res = $db->select(
			[
				'user_properties',
				'user'
			],
			[ 'user.*' ],
			[ 'up_property' => $prefName ],
			__METHOD__,
			[],
			[ 'user' => [ 'INNER JOIN', 'user_properties.up_user = user.user_id' ] ]
		);

		return array_values( $this->usersFromRows( $res ) );
	}

	/**
	 * @param mixed $rows
	 * @return array
	 */
	private function usersFromRows( $rows ) {
		if ( !$rows ) {
			return [];
		}
		$users = [];
		foreach ( $rows as $row ) {
			$user = User::newFromRow( $row );
			if ( $user->isAnon() ) {
				continue;
			}
			$users[$user->getId()] = $user;
		}

		return $users;
	}
}
