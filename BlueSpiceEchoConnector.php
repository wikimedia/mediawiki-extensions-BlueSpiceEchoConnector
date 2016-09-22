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
