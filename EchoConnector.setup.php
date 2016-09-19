<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension("BlueSpiceEchoConnector");
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['BlueSpiceEchoConnector'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for BlueSpiceEchoConnector extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the BlueSpiceEchoConnector extension requires MediaWiki 1.25+' );
}


$echoRessourcePackages = array(
	'ext.echo.base', 'ext.echo.overlay', 'ext.echo.overlay.init',
	'ext.echo.special', 'ext.echo.alert', 'ext.echo.badge'
);

foreach( $echoRessourcePackages as $package ) {
	$wgResourceModules[$package]['remoteExtPath'] = 'BlueSpiceDistribution/Echo/modules';
}

unset( $echoRessourcePackages );
