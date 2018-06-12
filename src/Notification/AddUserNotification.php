<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\EchoConnector\Notification\EchoNotification;

class AddUserNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-adduser', $agent, $title, $params);
	}
}
