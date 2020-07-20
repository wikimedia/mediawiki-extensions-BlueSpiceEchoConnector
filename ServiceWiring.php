<?php

use BlueSpice\EchoConnector\UserLocator;

return [
	'BSEchoConnectorUserLocator' => function ( \MediaWiki\MediaWikiServices $services ) {
		return new UserLocator( $services->getDBLoadBalancer(), RequestContext::getMain() );
	}
];
