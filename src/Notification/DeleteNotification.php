<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\EchoConnector\Notification\EchoNotification;

class DeleteNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-delete', $agent, $title, $params);
	}
}
