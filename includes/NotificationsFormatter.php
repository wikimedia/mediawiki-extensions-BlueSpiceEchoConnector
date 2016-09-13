<?php
/**
 * Formatter class for notifications
 *
 * Part of BlueSpice for MediaWiki
 *
 * @author     Stefan Widmann <widmann@hallowelt.com>
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    BlueSpice_Distribution
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */
class BsNotificationsFormatter extends EchoBasicFormatter {

	//This fixes the EchoBasicFormatter 
	protected $validOutputFormats = array(
		'text',
		'email',
		'htmlemail',
		'special', //Special:Notifications
		'model' //Api call
	);

	public function __construct( $params ) {
		parent::__construct( $params );
	}

	public function format( $event, $user, $type ) {
		$this->setDistributionType( $type );
		$this->applyChangeBeforeFormatting( $event, $user, $type );

		if ( $this->outputFormat !== 'model' ) {
			return parent::format( $event, $user, $type );
		}

		return array(
			'header' => $this->formatNotificationTitle( $event, $user )->parse(),
			'body' => '', //$this->formatFragment( $this->email['batch-body'], $event, $user )->parse() //only plain text message :(
			'iconUrl' => $this->getIconUrl( $this->icon, 'ltr' ),
		);
	}

	/**
	 * Create text version and/or html version for email notification
	 *
	 * @param $event EchoEvent
	 * @param $user User
	 * @param $type string deprecated
	 * @return array
	 */
	protected function formatEmail( $event, $user, $type ) {
		// Email should be always sent in user language
		$this->language = $user->getOption( 'language' );

		// Email digest
		if ( $this->distributionType === 'emaildigest' ) {
			return $this->formatEmailDigest( $event, $user );
		}

		// Echo single email
		$emailSingle = $this->newEmailSingle( $event, $user );

		$textEmailFormatter = $this->newTextEmailFormatter( $emailSingle );
		global $wgSitename;
		$content = array(
			// Single email subject, there is no need to to escape it for either html
			// or text email since it's always treated as plain text by mail client
			'subject' => "[$wgSitename] ".$this->formatFragment( $this->email['subject'], $event, $user )->text(),
			// Single email text body
			'body' => $textEmailFormatter->formatEmail(),
		);

		$format = MWEchoNotifUser::newFromUser( $user )->getEmailFormat();
		if ( $format == EchoHooks::EMAIL_FORMAT_HTML ) {
			$htmlEmailFormatter = new EchoHTMLEmailFormatter( $emailSingle );
			$outputFormat = $this->outputFormat;
			$this->setOutputFormat( 'htmlemail' );
			// Add single email html body if user prefers html format
			$content['body'] = array (
				'text' => $content['body'],
				'html' => $htmlEmailFormatter->formatEmail()
			);
			$this->setOutputFormat( $outputFormat );
		}

		return $content;
	}

	/**
	 * Process given Params in order specified on ...::registerNotification
	 *
	 * Example:
	 *
	 * Register Notification with message keys from i18n and params with insert order
	 *
	 * BSNotifications::registerNotification(
	 *		'bs-shoutbox-mention',
	 *		'bs-shoutbox-mention-cat',
	 *		'bs-shoutbox-notifications-summary',
	 *		array( 'agent', 'title', 'titlelink' ),
	 *		'bs-shoutbox-notifications-title-message-subject',
	 *		array(),
	 *		'bs-shoutbox-notifications-title-message-text', (has $1...$5)
	 *		array( 'agent'($1), 'title'($2), 'titlelink'($3), 'agentprofile'($4), 'customparam1'($5) )
	 *	);
	 *
	 * Notification call, unknown params will be processed as is in last else case:
	 *
	 * BSNotifications::notify(
	 *			"bs-shoutbox-{$sAction}",
	 *			$oAgent,
	 *			$oTitle,
	 *			array(
	 *				'mentioned-user-id' => $oUser->getId(),
	 *				'realname' => $sCurrentUserName,
	 *				'title' => $sTitleText,
	 *				'agentprofile' => $oCurrentUser->getUserPage()->getFullURL(),
	 *				'customparam1' => 'customvalue1'
	 *			)
	 *		);
	 *
	 * @param EchoEvent $event
	 * @param String $param
	 * @param Message $message
	 * @param User $user
	 */
	protected function processParam($event, $param, $message, $user) {
		//check if key is precessed by echo base formatter class EchoBasicFormatter::processParam
		$arrParamsInBasicFormatter = array(
				'agent',
				'agent-other-display',
				'agent-other-count',
				'user',
				'title',
				'titlelink',
				'text-notification'
			);
		if(in_array( $param, $arrParamsInBasicFormatter) ) {
			parent::processParam( $event, $param, $message, $user );
		} else if( $param === 'titlelink' ) {
			$this->setTitleLink(
				$event,
				$message,
				array(
					'class' => 'mw-echo-title',
				)
			);
		} else if ( $param === 'difflink' ) {
			$aEvent = $event->getExtra();
			$diffparams = ( isset( $aEvent['difflink'] ) && isset( $aEvent['difflink']['diffparams'] ) )
				? $aEvent['difflink']['diffparams']
				: ''
			;

			$this->setDiffLink(
				$event,
				$message,
				array(
					'class' => 'mw-echo-diff',
					'param' => $diffparams,
				)
			);
		} else if( $param === 'agentlink' ) {
			if( $event->getAgent()->isAnon() ) {
				$message->params( "'''".wfMessage( 'bs-echo-anon-user' )."'''" )->parse();
			} else {
				$this->setUserpageLink(
					$event,
					$message,
					array(
						'class' => 'mw-echo-userpage'
					)
				);
			}
		} else if( $param === 'userlink') {
			$this->setUserpageLink(
				$event,
				$message,
				array(
					'class' => 'mw-echo-userpage',
					'created' => true,
				)
			);
		} else if ( $param === 'newtitle' ) {
			$aExtra = $event->getExtra();
			$oNewTitle = $aExtra['newtitle'];
			$message->params( $oNewTitle->getPrefixedText() );
		} else if ( $param === 'newtitlelink' ) {
			$aExtra = $event->getExtra();
			$oNewTitle = $aExtra['newtitle'];
			$this->buildLink(
				$oNewTitle,
				$message,
				array(
					'class' => 'mw-echo-title',
				)
			);
		}
		else {
			//process Generic params for given index, insert content as is
			$aEventData = $event->getExtra();
			$message->params( $aEventData[ $param ] );
		}
	}

	/**
	 * Should create a difflink for the given title
	 * @param EchoEvent $event
	 * @param Message $message
	 * @param Array $props
	 */
	public function setDiffLink( $event, $message, $props = array() ) {
		$title = $event->getAgent()->getUserPage();
		$this->buildLink($title, $message, $props);
	}


	/**
	 *  Creates a link to the user page (user given by event)
	 * @param EchoEvent $event
	 * @param Message $message
	 * @param Array $props
	 */
	public function setUserpageLink ( $event, $message, $props = array() ) {
		if( isset( $props['created'] ) && $props['created'] ) {
			unset( $props['created'] );
			$aExtra = $event->getExtra();
			if(!is_string($aExtra['user'])){
			    throw new Exception('User must be "username" string here.');
			}
			$oUser = User::newFromName( $aExtra['user'] );
			if( is_object( $oUser ) ) {
				$title = $oUser->getUserPage();
			} else {
				$title = null;
			}
		} else {
			$title = $event->getAgent()->getUserPage();
		}

		if( $title === null ) {
			$message->params( "'''".wfMessage( 'bs-echo-unknown-user' )."'''" )->parse();
		} else {
			$this->buildLink($title, $message, $props, false );
		}
	}

	/**
	 *
	 * @param Title $title
	 * @param Message $message
	 * @param Array $props
	 * @param Boolean $bLinkWithPrefixedText
	 */
	public function buildLink( $title, $message, $props, $bLinkWithPrefixedText = true ) {
		$param = array();
		if ( isset( $props['param'] ) ) {
			$param = (array)$props['param'];
		}

		if ( isset( $props['fragment'] ) ) {
			$title->setFragment( '#' . $props['fragment'] );
		}

		if ( $this->outputFormat === 'html' || $this->outputFormat === 'flyout' ) {
			$class = array();
			if ( isset( $props['class'] ) ) {
				$class['class'] = $props['class'];
			}

			if ( isset( $props['linkText'] ) ) {
				$linkText = $props['linkText'];
			} else {
				if( $bLinkWithPrefixedText ) {
					$linkText = htmlspecialchars( $title->getPrefixedText() );
				} else {
					$linkText = htmlspecialchars( $title->getText() );
				}
			}

			$message->rawParams( Linker::link( $title, $linkText, $class, $param ) );
		} elseif ( $this->outputFormat === 'email' ) {
			$message->params( $title->getCanonicalURL( $param ) );
		} else {
			$message->params( $title->getFullURL( $param ) );
		}
	}

	/**
	 * Factory method for EchoEmailSingle object. Can be overridden by subclasses
	 * @param EchoEvent $event
	 * @param User $user
	 * @return EchoEmailSingle
	 */
	protected function newEmailSingle( $event, $user ) {
		return new BsEchoEmailSingle( $this, $event, $user );
	}

	/**
	 * Factory method for EchoTextEmailFormatter object. Can be overridden by subclasses
	 * @param EchoEmailSingle $emailSingle
	 * @return EchoTextEmailFormatter
	 */
	protected function newTextEmailFormatter( $emailSingle ) {
		return new BsEchoTextEmailFormatter( $emailSingle );
	}
}