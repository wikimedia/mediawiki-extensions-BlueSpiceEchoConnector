<?php

use BlueSpice\EchoConnector\FormatterFactory;
use BlueSpice\EchoConnector\UserLocator;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'BSEchoConnectorUserLocator' => static function ( MediaWikiServices $services ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser(
			$services->getService( 'BSUtilityFactory' )->getMaintenanceUser()->getUser()
		);

		return new UserLocator(
			$services->getDBLoadBalancer(),
			$context,
			$services->getPermissionManager(),
			$services->getHookContainer(),
			$services->getUserFactory()
		);
	},
	'BSEchoConnectorFormatterFactory' => static function ( MediaWikiServices $services ) {
		$config = $services->getConfigFactory()->makeConfig( 'bsg' );
		$specs = $config->get( 'EchoEmailFormatterClasses' );
		return new FormatterFactory( $services->getObjectFactory(), $specs );
	},
];

// @codeCoverageIgnoreEnd
