<?php

namespace BlueSpice\EchoConnector;

use BlueSpice\Data\ReaderParams;
use BlueSpice\Data\Watchlist\Record;
use BlueSpice\Data\Watchlist\Store;
use IContextSource;
use LoadBalancer;
use User;

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
	 * @param string $prefixedTitle
	 * @return array
	 */
	public function getWatchers( $prefixedTitle ) {
		$watchers = [];
		$wlStore = new Store( $this->context );
		$readerParams = new ReaderParams( [
			'filter' => [ [
				'comparison' => 'eq',
				'property' => Record::PAGE_PREFIXED_TEXT,
				'value' => $prefixedTitle,
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

		return $watchers;
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

		$users = [];
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$resSysops = $dbr->select(
			"user_groups",
			"ug_user",
			$conds,
			__METHOD__
		);

		foreach ( $resSysops as $row ) {
			$user = User::newFromId( $row->ug_user );
			$user->load();
			if ( $user->isAnon() ) {
				continue;
			}
			$users[ $user->getId() ] = $user;
		}
		return $users;
	}

	/**
	 * Get all users subscribed to the particular category
	 *
	 * @param string $cat Notification category
	 * @return array
	 */
	public function getAllSubscribed( $cat ) {
		$subscribers = [];
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$resUser = $dbr->select(
			"user_properties",
			"DISTINCT up_user",
			[
				"up_property" => [
					"echo-subscriptions-web-$cat",
					"echo-subscriptions-email-$cat"
				],
				"up_value" => 1
			]
		);

		foreach ( $resUser as $row ) {
			$user = User::newFromId( $row->up_user );
			$user->load();
			if ( $user->isAnon() ) {
				continue;
			}
			$subscribers[ $user->getId() ] = $user;
		}

		return $subscribers;
	}
}
