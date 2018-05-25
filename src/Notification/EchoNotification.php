<?php

namespace BlueSpice\EchoConnector\Notification;

class EchoNotification implements \BlueSpice\INotification {
	protected $key;
	protected $title = null;
	protected $agent;
	protected $audience = [];
	protected $extra = [];

	public function __construct( $key, $params ) {
		$this->key = $key;
		if( isset( $params['title'] ) && $params['title'] instanceof \Title ) {
			$this->title = $params['title'];
		}

		$this->agent = $params['agent'];

		if( isset( $params['extra-params'] ) && is_array( $params['extra-params'] ) ) {
			$this->extra = $params['extra-params'];
		}

		if( isset( $params['affected-users'] ) && is_array( $params['affected-users'] ) ) {
			$this->addUsersToAudience( $params['affected-users'] );
		}

		if( isset( $params['affected-groups'] ) && is_array( $params['affected-groups'] ) ) {
			$this->addUsersFromGroupsToAudience( $params['affected-groups'] );
		}
	}
	
	public function getAudience() {
		//If audience is empty, notification will be sent
		//to everyone who are subscibed
		return $this->audience;
	}

	public function getKey() {
		return $this->key;
	}

	public function getParams() {
		return $this->extra;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getUser() {
		return $this->agent;
	}

	protected function addUsersToAudience( $users ) {
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

	protected function addUsersFromGroupsToAudience( $groups ) {
		$users = \BsGroupHelper::getUserInGroups( $groups );
		$this->addUsersToAudience( $users );
	}
}