<?php

namespace BlueSpice\EchoConnector;

use IContextSource;
use MediaWiki\Block\BlockManager;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\UserFactory;
use MWException;
use Title;
use User;
use Wikimedia\Rdbms\ILoadBalancer;
use WikitextContent;

class UserLocator {
	protected $loadBalancer = null;
	protected $context = null;
	/**
	 *
	 * @var PermissionManager
	 */
	protected $permissionManager = null;

	/**
	 *
	 * @var HookContainer
	 */
	protected $hookContainer = null;

	/**
	 *
	 * @var UserFactory
	 */
	protected $userFactory = null;

	/**
	 * @var BlockManager
	 */
	protected $blockManager = null;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param IContextSource $context
	 * @param PermissionManager $permissionManager
	 * @param HookContainer $hookContainer
	 * @param UserFactory $userFactory
	 * @param BlockManager $blockManager
	 */
	public function __construct( ILoadBalancer $loadBalancer, IContextSource $context,
		PermissionManager $permissionManager, HookContainer $hookContainer, UserFactory $userFactory,
		BlockManager $blockManager
	) {
		$this->loadBalancer = $loadBalancer;
		$this->context = $context;
		$this->permissionManager = $permissionManager;
		$this->hookContainer = $hookContainer;
		$this->userFactory = $userFactory;
		$this->blockManager = $blockManager;
	}

	/**
	 * Get all uses watching the title
	 *
	 * @param string $prefixedText unused, for B/C
	 * @param Title $title
	 *
	 * @return array
	 */
	public function getWatchers( $prefixedText, Title $title ) {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $db->select(
			'watchlist',
			[ 'wl_user' ],
			[ 'wl_namespace' => $title->getNamespace(), 'wl_title' => $title->getDBkey() ],
			__METHOD__
		);

		$ids = [];
		foreach ( $res as $row ) {
			$ids[] = (int)$row->wl_user;
		}

		return $this->getValidUsersFromIds( array_unique( $ids ), $title );
	}

	/**
	 * Get all users from the particular group
	 *
	 * @param array $groups
	 * @return User[]
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
			'user_groups',
			'ug_user',
			$conds,
			__METHOD__
		);

		$users = [];
		foreach ( $res as $row ) {
			$users[] = $row->ug_user;
		}
		if ( empty( $users ) ) {
			return [];
		}
		return $this->getValidUsersFromIds( $users );
	}

	/**
	 * Get all users subscribed to the particular category
	 *
	 * @param string $cat Notification category
	 * @param Title $title
	 * @return User[]
	 */
	public function getAllSubscribed( $cat, Title $title ) {
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
			],
			__METHOD__
		);
		$users = [];
		foreach ( $resUser as $row ) {
			$users[] = $row->up_user;
		}
		if ( empty( $users ) ) {
			return [];
		}
		return $this->getValidUsersFromIds( $users, $title );
	}

	/**
	 * @param int $nsId
	 * @param string $action
	 * @param Title $title
	 *
	 * @return User[]
	 */
	public function getUsersSubscribedToNamespace( $nsId, $action, Title $title ) {
		$users = $this->getUsersWithSubscriptionPreference( 'namespace', $action, $nsId );
		if ( empty( $users ) ) {
			return [];
		}
		return $this->getValidUsersFromIds( $users, $title );
	}

	/**
	 * @param Title $title
	 * @param string $action
	 * @return User[]
	 * @throws MWException
	 */
	public function getUsersSubscribedToTitleCategories( Title $title, $action ) {
		$services = MediaWikiServices::getInstance();
		$wikipage = $services->getWikiPageFactory()->newFromTitle( $title );
		$content = $wikipage->getContent();

		if ( !$content instanceof WikitextContent ) {
			return [];
		}

		$contentRenderer = $services->getContentRenderer();
		$categories = $contentRenderer->getParserOutput( $content, $title )->getCategoryNames();

		$users = [];
		foreach ( $categories as $cat ) {
			$users += $this->getUsersWithSubscriptionPreference( 'category', $action, $cat );
		}
		if ( empty( $users ) ) {
			return [];
		}

		return $this->getValidUsersFromIds( $users, $title );
	}

	/**
	 * Get all users that have a particular user preference
	 *
	 * @param string $type
	 * @param string $action
	 * @param string $key
	 * @return int[]|array
	 */
	private function getUsersWithSubscriptionPreference( $type, $action, $key ) {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		// Sanity, in case something calls this on install, before tables are created
		if ( !$db->tableExists( 'user_properties' ) ) {
			return [];
		}

		$prefName = "notify-$type-selectionpage-$action-$key";
		$res = $db->select(
			'user_properties',
			'up_user',
			[ 'up_property' => $prefName, 'up_value' => 1 ],
			__METHOD__
		);
		$return = [];
		foreach ( $res as $row ) {
			$return[] = $row->up_user;
		}

		return $return;
	}

	/**
	 *
	 * @param int[] $users
	 * @param Title|null $title
	 * @return User[]
	 */
	private function getValidUsersFromIds( array $users, Title $title = null ) {
		$return = [];
		foreach ( $users as $id ) {
			if ( empty( $id ) ) {
				continue;
			}
			$user = $this->userFactory->newFromId( $id );
			if ( !$user->isRegistered() ||
				$this->blockManager->getUserBlock( $user, null, true )
			) {
				continue;
			}
			if ( isset( $return[$user->getId()] ) ) {
				continue;
			}
			if ( $title ) {
				if ( !$this->permissionManager->quickUserCan( 'read', $user, $title ) ) {
					continue;
				}
			} elseif ( !$this->permissionManager->userHasRight( $user, 'read' ) ) {
				continue;
			}
			$return[$user->getId()] = $user;
		}
		$this->hookContainer->run( 'BlueSpiceEchoConnectorUserLocatorValidUsers', [
			&$return,
			$title
		] );
		return $return;
	}
}
