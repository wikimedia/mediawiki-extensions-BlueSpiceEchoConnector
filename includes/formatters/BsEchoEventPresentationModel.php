<?php

class BsEchoEventPresentationModel extends EchoEventPresentationModel {

    public function canRender () {
	return ( bool ) $this->event->getTitle ();
    }

    public function getIconType () {
	return $this->getIcon ();
    }

    public function getIcon () {
	global $wgEchoNotifications;
	if ( isset ( $wgEchoNotifications[ $this->type ][ 'icon' ] ) ) {
	    return $wgEchoNotifications[ $this->type ][ 'icon' ];
	}
	return $this->getFormatter ( $wgEchoNotifications[ $this->type ] )->icon;
    }

    public function getHeaderMessage () {
	$aContent = $this->getHeaderMessageContent ();
	$oMsg = $this->msg ( $aContent[ 'key' ] );

	if ( $this->isBundled () ) {
	    if ( $aContent[ 'bundle-key' ] ) {
		$oMsg = $this->msg ( $aContent[ 'bundle-key' ] );
		$oMsg->params ( $this->getBundleCount () );
	    }
	}

	$oFormatter = $this->getFormatter ();
	$aParams = $aContent[ 'params' ];
	if ( $this->isBundled () ) {
	    $aParams = $aContent[ 'bundle-params' ];
	}
	if ( empty ( $aParams ) ) {
	    return $oMsg;
	}

	foreach ( $aParams as $param ) {
	    $oFormatter->processParam (
		    $this->event, $param, $oMsg, $this->event->getAgent ()
	    );
	}

	return $oMsg;
    }

    public function getBodyMessage () {
	$aContent = $this->getBodyMessageContent ();
	if ( !$aContent[ 'key' ] ) {
	    return false;
	}
	$oMsg = $this->msg ( $aContent[ 'key' ] );
	if ( empty ( $aContent[ 'params' ] ) ) {
	    return $oMsg;
	}

	$oFormatter = $this->getFormatter ();
	foreach ( $aContent[ 'params' ] as $param ) {
	    $oFormatter->processParam (
		    $this->event, $param, $oMsg, $this->event->getAgent ()
	    );
	}
	return $oMsg;
    }

    public function getCompactHeaderMessage () {
	// This is the header message for individual notifications
	// *inside* the bundle
	$msg = parent::getCompactHeaderMessage ();
	return $msg;
    }

    public function getFormatter () {
	global $wgEchoNotifications;
	return new BsNotificationsFormatter (
		$wgEchoNotifications[ $this->type ]
	);
    }

    public function getPrimaryLink () {
	return $this->event->getTitle () ? array (
	    'url' => $this->event->getTitle ()->getFullURL (),
	    'label' => $this->event->getTitle ()->getText ()
		) : false;
    }

    public function getHeaderMessageContent () {
	global $wgEchoNotifications;

	$sBundleKey = '';
	$aBundleParams = array ();
	if ( isset ( $wgEchoNotifications[ $this->type ][ 'bundle' ] ) ) {
	    $sBundleKey = $wgEchoNotifications[ $this->type ][ 'bundle' ][ 'bundle-message' ];
	    $aBundleParams = $wgEchoNotifications[ $this->type ][ 'bundle' ][ 'bundle-params' ];
	}

	return array (
	    'key' => $wgEchoNotifications[ $this->type ][ 'title-message' ],
	    'params' => $wgEchoNotifications[ $this->type ][ 'title-params' ],
	    'bundle-key' => $sBundleKey,
	    'bundle-params' => $aBundleParams
	);
    }

    public function getBodyMessageContent () {
	global $wgEchoNotifications;
	return array (
	    'key' => $wgEchoNotifications[ $this->type ][ 'web-body-message' ],
	    'params' => $wgEchoNotifications[ $this->type ][ 'web-body-params' ]
	);
    }

}
