<?php

namespace BlueSpice\EchoConnector\Job;

use Title;

class SendNotification extends \Job {

	/**
	 *
	 * @param \Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		if ( $title instanceof Title ) {
			// parent::__construct Backwards compatibility for old signature
			// ($command, $title, $params) seems to have an issue retrieving the
			// actual correct data and ends up writing -1|FullText into the DB
			// ERM:24241
			$this->title = $title;
			$params['namespace'] = $title->getNamespace();
			$params['title'] = $title->getDBkey();
		}
		parent::__construct( 'sendNotification', $params );
	}

	public function run() {
		\EchoEvent::create( $this->params );
	}
}
