<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\BaseNotification;

class CreateNotification extends BaseNotification {
	/**
	 * @var string
	 */
	protected $summary;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param string $summary
	 * @param string|null $key
	 */
	public function __construct( $agent, $title, $summary, $key = 'bs-create' ) {
		parent::__construct( $key, $agent, $title );
		$this->summary = $summary;
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		if ( $this->summary === '' ) {
			$this->summary = '-';
		}
		return [
			'summary' => $this->summary,
			'realname' => $this->getUserRealName(),
			'time' => $this->title->getTouched()
		];
	}
}
