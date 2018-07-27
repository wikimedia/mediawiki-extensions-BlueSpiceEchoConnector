<?php

namespace BlueSpice\EchoConnector\Notification;

class DeleteNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-delete', $agent, $title, $params );
	}
}
