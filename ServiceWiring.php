<?php

use BlueSpice\EchoConnector\UserLocator;
use MediaWiki\MediaWikiServices;

return [
	'BSEchoConnectorUserLocator' => function ( MediaWikiServices $services ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser(
			$services->getService( 'BSUtilityFactory' )->getMaintenanceUser()->getUser()
		);

		return new UserLocator( $services->getDBLoadBalancer(), $context );
	}
];
