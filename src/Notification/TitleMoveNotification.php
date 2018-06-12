<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\EchoConnector\Notification\EchoNotification;

class TitleMoveNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-move', $agent, $title, $params);
	}
}
