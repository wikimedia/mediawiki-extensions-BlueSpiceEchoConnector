<?php

class EchoBlueSpicePresentationModel extends EchoEventPresentationModel {
	public function getIconType() {
		global $wgEchoNotifications;
		return $this->getFormatter( $wgEchoNotifications[$this->type] )->icon;
	}

	public function getPrimaryLink() {
		return $this->event->getTitle()
			? array(
				'url' => $this->event->getTitle()->getFullURL(),
				'label' => $this->event->getTitle()->getText()
			)
			: false
		;
	}

	public function getHeaderMessageKey() {
		global $wgEchoNotifications;
		return $wgEchoNotifications[$this->type]['email-subject-message'];
	}

	public function getHeaderMessage() {
		$oMsg = $this->msg( $this->getHeaderMessageKey() );
		$oFormatter = $this->getFormatter();

		$aParams = $GLOBALS['wgEchoNotifications'][$this->type]
			['email-subject-params'];
		if( empty( $aParams ) ) {
			return $oMsg;
		}

		foreach( $aParams as $param ) {
			$oFormatter->processParam(
				$this->event,
				$param,
				$oMsg,
				$this->event->getAgent()
			);
		}
		return $oMsg;
	}

	public function getFormatter() {
		global $wgEchoNotifications;
		return new BsNotificationsFormatter(
			$wgEchoNotifications[$this->type]
		);
	}
}
