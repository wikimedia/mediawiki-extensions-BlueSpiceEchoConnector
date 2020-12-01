<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\BaseNotification;
use MediaWiki\MediaWikiServices;

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
	 * @param \Title $title
	 * @param \Revision $revision
	 * @param string $summary
	 * @param string $key
	 */
	public function __construct( $agent, $title, $revision, $summary, $key = 'bs-edit' ) {
		parent::__construct( $key, $agent, $title, [
			'digestUseSecondaryLink' => 'difflink'
		] );
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
		$lastRevision = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionById(
			$this->title->getLatestRevID()
		);
		$ts = '';
		if ( $lastRevision ) {
			$ts = $lastRevision->getTimestamp();
		}
		return array_merge( parent::getParams(), [
			'summary' => $this->summary,
			'titlelink' => true,
			'realname' => $this->getUserRealName(),
			'time' => $ts
		] );
	}
}
