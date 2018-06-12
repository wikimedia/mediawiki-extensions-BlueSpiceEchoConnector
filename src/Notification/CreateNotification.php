<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\EchoConnector\Notification\EchoNotification;

class CreateNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-create', $agent, $title, $params);
	}
}
