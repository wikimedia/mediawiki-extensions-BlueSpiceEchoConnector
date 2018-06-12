<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\EchoConnector\Notification\EchoNotification;

class EditNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-edit', $agent, $title, $params);
	}
}
