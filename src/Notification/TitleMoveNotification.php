<?php

namespace BlueSpice\EchoConnector\Notification;

class TitleMoveNotification extends EchoNotification {
	public function __construct( $agent, $title = null, $params = [] ) {
		parent::__construct( 'bs-move', $agent, $title, $params );
	}
}
