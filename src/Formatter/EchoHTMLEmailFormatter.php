<?php

namespace BlueSpice\EchoConnector\Formatter;

use BlueSpice\EchoConnector\EchoEventPresentationModel as BSEchoPresentationModel;
use Html;
use MediaWiki\MediaWikiServices;

class EchoHTMLEmailFormatter extends \EchoHtmlEmailFormatter {
	public const PRIMARY_LINK = 'primary_link';
	public const SECONDARY_LINK = 'secondary_link';

	/**
	 *
	 * @var \Config
	 */
	protected $config;

	/**
	 *
	 * @var string
	 */
	protected $sitename;

	/**
	 *
	 * @var \TemplateParser
	 */
	protected $templateParser;

	/**
	 *
	 * @var array
	 */
	protected $templateNames;

	/**
	 *
	 * @param \User $user
	 * @param \Language $language
	 */
	public function __construct( \User $user, \Language $language ) {
		parent::__construct( $user, $language );
		global $wgSitename;

		$this->sitename = $wgSitename;
		$this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );

		$path = $this->config->get( 'EchoHtmlMailTemplatePath' );
		$this->templateParser = new \TemplateParser( $path );

		$this->templateNames = $this->config->get( 'EchoHtmlMailTemplateNames' );
	}

	/**
	 *
	 * @param \EchoEventPresentationModel $model
	 * @return array
	 */
	protected function formatModel( \EchoEventPresentationModel $model ) {
		if ( $model instanceof BSEchoPresentationModel ) {
			$model->setDistributionType( 'email' );
		}

		$subject = $model->getSubjectMessage()->parse();

		$realname = MediaWikiServices::getInstance()->getService( 'BSUtilityFactory' )
			->getUserHelper( $model->getUser() )->getDisplayName();

		$greeting = $this->msg(
			'bs-notifications-htmlmail-greeting', $realname
		)->parse();
		$senderMessage = $this->msg(
			'bs-notifications-htmlmail-sender-info', $this->sitename
		)->plain();

		$bodyMsg = $model->getBodyMessage();
		$summary = $bodyMsg ? $bodyMsg->parse() : '';

		$actions = [
			'primary' => false,
			'secondary_label' => $this->msg(
				'bs-notifications-mail-additional-links-label'
			)->plain(),
			'secondary' => false
		];

		$primaryLink = $model->getPrimaryLinkWithMarkAsRead();
		if ( $primaryLink ) {
			$renderedLink = $this->renderLink( $primaryLink, self::PRIMARY_LINK );
			$actions['primary'] = $this->makeLinkList( [ $renderedLink ] );
		}

		$secondaryLinks = [];
		foreach ( array_filter( $model->getSecondaryLinks() ) as $secondaryLink ) {
			$secondaryLinks[] = $this->renderLink( $secondaryLink, self::SECONDARY_LINK );
		}
		if ( !empty( $secondaryLinks ) ) {
			$actions['secondary'] = $this->makeLinkList( $secondaryLinks );
		}

		$iconUrl = wfExpandUrl(
			\EchoIcon::getUrl( $model->getIconType(), $this->language->getCode() ),
			PROTO_CANONICAL
		);

		$body = $this->renderBody(
			$this->language,
			$iconUrl,
			$summary,
			$actions,
			$greeting,
			$senderMessage,
			'',
			$this->getFooter()
		);

		return [
			'body' => $body,
			'subject' => $subject,
		];
	}

	/**
	 *
	 * @param array $links
	 * @return string
	 */
	protected function makeLinkList( $links ) {
		if ( empty( $links ) ) {
			// Must not be rendered by mustache
			return '';
		}
		$list = implode( '</li><li>', $links );
		return Html::rawElement( 'ul', [], "<li>$list</li>" );
	}

	/**
	 *
	 * @param \Language $lang
	 * @param string $emailIcon
	 * @param string $summary
	 * @param array $actions
	 * @param string $greeting
	 * @param string $senderMessage
	 * @param string $messageHeader
	 * @param string $footer
	 * @return string
	 */
	protected function renderBody( \Language $lang, $emailIcon, $summary, $actions, $greeting,
		$senderMessage, $messageHeader, $footer ) {
		$data = [
			'icon_url' => $emailIcon,
			'body' => $summary,
			'actions' => $actions,
			'footer' => $footer,
			'greeting' => $greeting,
			'sender-info' => $senderMessage
		];
		if ( $messageHeader !== '' ) {
			$data['header'] = $messageHeader;
		}
		$html = $this->templateParser->processTemplate(
			$this->templateNames['single'],
			$data
		);

		return $html;
	}

	/**
	 *
	 * @param array $link
	 * @param string $type
	 * @return string
	 */
	public function renderLink( $link, $type ) {
		$html = $this->templateParser->processTemplate(
			$this->templateNames[$type],
			[
				'url' => wfExpandUrl( $link['url'], PROTO_CANONICAL ),
				'label' => $link['label']
			]
		);

		return $html;
	}

	/**
	 *
	 * @return string
	 */
	public function getFooter() {
		$special = \SpecialPage::getTitleFor(
			'Preferences',
			false,
			'mw-prefsection-echo'
		);
		$preferenceLink = $this->renderLink(
			[
				'label' => $this->msg( 'echo-email-html-footer-preference-link-text', $this->user )->text(),
				'url' => $special->getFullURL( '', false, PROTO_CANONICAL ),
			],
			self::SECONDARY_LINK
		);

		$footer = $this->msg( 'echo-email-html-footer-with-link' )
			->rawParams( $preferenceLink )
			->params( $this->user )
			->parse();

		return $footer;
	}
}
