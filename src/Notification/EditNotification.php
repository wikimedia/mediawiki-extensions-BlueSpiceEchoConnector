<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\BaseNotification;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;

class EditNotification extends BaseNotification {
	/**
	 * @var RevisionRecord
	 */
	protected $revision;

	/**
	 * @var string
	 */
	protected $summary;

	/**
	 *
	 * @var RevisionLookup
	 */
	protected $revisionLookup = null;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param RevisionRecord $revision
	 * @param string $summary
	 * @param string $key
	 */
	public function __construct( $agent, $title, $revision, $summary, $key = 'bs-edit' ) {
		parent::__construct( $key, $agent, $title, [
			'digestUseSecondaryLink' => 'difflink'
		] );
		$this->revision = $revision;
		$this->summary = $summary;
		$this->revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
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
			$previousRevision = $this->revisionLookup->getPreviousRevision( $this->revision );
			if ( is_object( $previousRevision ) ) {
				$diffParams[ 'oldid' ] = $previousRevision->getId();
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
		if ( $this->summary === '' ) {
			$this->summary = '-';
		}
		return array_merge( parent::getParams(), [
			'summary' => $this->summary,
			'titlelink' => true,
			'realname' => $this->getUserRealName(),
			'time' => $ts
		] );
	}
}
