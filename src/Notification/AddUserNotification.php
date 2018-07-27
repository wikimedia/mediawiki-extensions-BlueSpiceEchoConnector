<?php

namespace BlueSpice\EchoConnector\Notification;


class AddUserNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-adduser', $agent, $title, $params);
	}
}
