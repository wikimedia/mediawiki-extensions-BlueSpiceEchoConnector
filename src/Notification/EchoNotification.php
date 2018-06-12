<?php

namespace BlueSpice\EchoConnector\Notification;

class EchoNotification implements \BlueSpice\INotification {
	/**
	 *
	 * @var string
	 */
	protected $key;

	/**
	 *
	 * @var \Title|null
	 */
	protected $title = null;

	/**
	 *
	 * @var \User
	 */
	protected $agent;

	protected $audience = [];
	protected $extra = [];

	/**
	 *
	 * @var boolean
	 */
	protected $immediateEmail = false;

	/**
	 *
	 * @var boolean
	 */
	protected $useJobQueue = false;

	/**
	 *
	 * @var array
	 */
	protected $secondaryLinks = [];

	public function __construct( $key, \User $agent, $title = null, $extraParams = [] ) {
		$this->key = $key;
		$this->setAgent( $agent );
		if( $title instanceof \Title ) {
			$this->setTitle( $title );
		}

		if( !empty( $extraParams ) ) {
			$this->extra = $extraParams;
		}
	}

	/**
	 *
	 * @param \User $user
	 */
	public function setAgent( \User $user ) {
		$this->agent = $user;
	}

	/**
	 *
	 * @param \Title $title
	 */
	public function setTitle( \Title $title ) {
		$this->title = $title;
	}

	/**
	 * Adds new secondary link to this notification
	 *
	 * @param string $name
	 * @param array|string $config
	 */
	public function addSecondaryLink( $name, $config ) {
		$this->secondaryLinks[$name] = $config;
	}

	/**
	 * Gets configuration for secondary links
	 * if any exist
	 *
	 * @return array
	 */
	public function getSecondaryLinks() {
		return $this->secondaryLinks;
	}

	/**
	 *
	 * @param type boolean
	 */
	public function setImmediateEmail( bool $value = true ) {
		$this->immediateEmail = $value;
	}

	/**
	 * Whether mail for this notification should
	 * be sent immediately regardless of user settings
	 *
	 * @return boolean
	 */
	public function getImmediateEmail() {
		return $this->immediateEmail;
	}

	/**
	 *
	 * @param bool $value
	 */
	public function setUseJobQueue( bool $value = true ) {
		$this->useJobQueue = $value;
	}

	/**
	 * Whether job queue should be used
	 * to send this notification
	 *
	 * @return boolean
	 */
	public function useJobQueue() {
		return $this->useJobQueue;
	}

	/**
	 * Get all users that should receive this notification.
	 * If not set users will be retrieved from default user getter function
	 *
	 * @return array
	 */
	public function getAudience() {
		//If audience is empty, notification will be sent
		//to everyone who are subscibed
		return $this->audience;
	}

	/**
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->extra;
	}

	/**
	 *
	 * @return \Title|null
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 *
	 * @return \User
	 */
	public function getUser() {
		return $this->agent;
	}

	/**
	 * Adds array of \User object of user IDs
	 * to list of users to receive this notification
	 *
	 * @param array $users;
	 */
	public function addAffectedUsers( $users ) {
		foreach( $users as $user ) {
			if( $user instanceof \User ) {
				$this->audience[] = $user->getId();
				continue;
			}

			if( is_int( $user ) ) {
				$this->audience[] = $user;
			}
		}
	}

	/**
	 * Adds users from given groups to the list
	 * of the users to receive this notification
	 *
	 * @param array $groups
	 */
	public function addAffectedGroups( $groups ) {
		$users = \BsGroupHelper::getUserInGroups( $groups );
		$this->addAffectedUsers( $users );
	}
}