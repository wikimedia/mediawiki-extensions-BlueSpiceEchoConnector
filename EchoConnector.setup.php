<?php

$wgMessagesDirs['EchoConnector'] = __DIR__ . '/i18n';
$wgMessagesDirs['BSFoundation'] = __DIR__ . '/../../BlueSpiceFoundation/i18n';
$wgAutoloadClasses['EchoConnectorHooks'] = __DIR__."/includes/EchoConnectorHooks.php";
$wgAutoloadClasses['BSEchoNotificationHandler'] = __DIR__."/includes/EchoNotificationHandler.php";
$wgAutoloadClasses['BsNotificationsFormatter'] = __DIR__."/includes/NotificationsFormatter.php";
$wgAutoloadClasses['BsEchoEmailSingle'] = __DIR__."/includes/EchoEmailSingle.php";
$wgAutoloadClasses['BsEchoTextEmailFormatter'] = __DIR__."/includes/EchoTextEmailFormatter.php";
$wgAutoloadClasses['BsEchoTextEmailDecorator'] = __DIR__."/includes/EchoTextEmailDecorator.php";

$wgHooks['BeforeNotificationsInit'][] = "EchoConnectorHooks::onBeforeNotificationsInit";
$wgHooks['EchoGetDefaultNotifiedUsers'][] = "EchoConnectorHooks::onEchoGetDefaultNotifiedUsers";

$echoIconPath = "BlueSpiceDistribution/Echo/modules/icons";

// Defines icons, which are 30x30 images. This is passed to BeforeCreateEchoEvent so
// extensions can define their own icons with the same structure.  It is recommended that
// extensions prefix their icon key. An example is myextension-name.  This will help
// avoid namespace conflicts.
//
// You can use either a path or a url, but not both.
// The value of 'path' is relative to $wgExtensionAssetsPath.
//
// The value of 'url' should be a URL.
//
// You should customize the site icon URL, which is:
// $wgEchoNotificationIcons['site']['url']
$wgEchoNotificationIcons = array(
	'chat' => array(
		'path' => "$echoIconPath/chat.svg",
	),
	'checkmark' => array(
		'path' => "$echoIconPath/Reviewed.png",
	),
	'edit' => array(
		'path' => array(
			'ltr' => "$echoIconPath/ooui-edit-ltr-progressive.svg",
			'rtl' => "$echoIconPath/ooui-edit-rtl-progressive.svg",
		),
	),
	'edit-user-talk' => array(
		'path' => "$echoIconPath/edit-user-talk.svg",
	),
	'emailuser' => array(
		'path' => "$echoIconPath/emailuser.svg",
	),
	'featured' => array(
		'path' => "$echoIconPath/Featured.png",
	),
	'global' => array(
		'path' => "$echoIconPath/global.svg"
	),
	'gratitude' => array(
		'path' => "$echoIconPath/Gratitude.png",
	),
	'linked' => array(
		'path' => "$echoIconPath/link-blue.svg",
	),
	'mention' => array(
		'path' => "$echoIconPath/mention.svg",
	),
	'placeholder' => array(
		'path' => "$echoIconPath/Generic.png",
	),
	'reviewed' => array(
		'path' => "$echoIconPath/reviewed.svg",
	),
	'revert' => array(
		'path' => "$echoIconPath/revert.svg",
	),
	'site' => array(
		'url' => false
	),
	'tagged' => array(
		'path' => "$echoIconPath/ReviewedWithTags.png",
	),
	'trash' => array(
		'path' => "$echoIconPath/trash.svg",
	),
	'user-rights' => array(
		'path' => "$echoIconPath/user-rights.svg",
	),
);

unset( $echoIconPath );

$echoRessourcePackages = array(
	'ext.echo.base', 'ext.echo.overlay', 'ext.echo.overlay.init',
	'ext.echo.special', 'ext.echo.alert', 'ext.echo.badge'
);

foreach( $echoRessourcePackages as $package ) {
	$wgResourceModules[$package]['remoteExtPath'] = 'BlueSpiceDistribution/Echo/modules';
}

unset( $echoRessourcePackages );
