<?php

namespace BlueSpice\EchoConnector\Notification;


class CreateNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-create', $agent, $title, $params);
	}
}
