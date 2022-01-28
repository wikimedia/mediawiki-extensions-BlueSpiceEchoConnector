<?php

namespace BlueSpice\EchoConnector\Job;

use Title;
use User;

class SendNotification extends \Job {

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		parent::__construct( 'sendNotification', $title, $this->compressParams( $params ) );
	}

	public function run() {
		$this->expandParams();
		if ( $this->params['title'] instanceof Title && $this->params['agent'] instanceof User ) {
			\EchoEvent::create( $this->params );
		}
	}

	/**
	 * @param array $params
	 * @return array
	 */
	private function compressParams( $params ) {
		if ( isset( $params['title'] ) && $params['title'] instanceof Title ) {
			$params['title'] = $params['title']->getArticleID();
		}

		if ( isset( $params['agent'] ) && $params['agent'] instanceof User ) {
			$params['agent'] = $params['agent']->getId();
		}
		return $params;
	}

	private function expandParams() {
		if ( isset( $this->params['title'] ) && is_int( $this->params['title'] ) ) {
			$this->params['title'] = Title::newFromID( $this->params['title'] );
		}

		if ( isset( $this->params['agent'] ) && is_int( $this->params['agent'] ) ) {
			$this->params['agent'] = User::newFromId( $this->params['agent'] );
		}
	}
}
