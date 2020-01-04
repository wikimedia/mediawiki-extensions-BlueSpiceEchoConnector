<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\BaseNotification;

class EditNotification extends BaseNotification {
	/**
	 * @var \Revision
	 */
	protected $revision;

	/**
	 * @var string
	 */
	protected $summary;

	/**
	 *
	 * @param \User $agent
	 * @param \Titöe $title
	 * @param \Revision $revision
	 * @param string $summary
	 */
	public function __construct( $agent, $title, $revision, $summary ) {
		parent::__construct( 'bs-edit', $agent, $title );
		$this->revision = $revision;
		$this->summary = $summary;
	}

	/**
	 *
	 * @return array
	 */
	public function getSecondaryLinks() {
		$diffParams = [
			'diff' => 0,
			'oldid' => 0,
		];
		if ( is_object( $this->revision ) ) {
			$diffParams[ 'diff' ] = $this->revision->getId();
			if ( is_object( $this->revision->getPrevious() ) ) {
				$diffParams[ 'oldid' ] = $this->revision->getPrevious()->getId();
			}
		}

		$diffUrl = $this->title->getFullURL( [
			'type' => 'revision',
			'diff' => $diffParams['diff'],
			'oldid' => $diffParams['oldid']
		] );

		return [
			'difflink' => $diffUrl
		];
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		return [
			'summary' => $this->summary,
			'titlelink' => true,
			'realname' => $this->getUserRealName()
		];
	}
}
