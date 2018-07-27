<?php

namespace BlueSpice\EchoConnector\Notification;

class EditNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-edit', $agent, $title, $params );
	}
}
