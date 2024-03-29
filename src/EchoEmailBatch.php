<?php

namespace BlueSpice\EchoConnector;

use MediaWiki\MediaWikiServices;
use MWException;

class EchoEmailBatch extends \MWEchoEmailBatch {

	/**
	 *
	 * @param int $userId
	 * @param bool|true $enforceFrequency
	 * @return bool|EchoEmailBatch
	 */
	public static function newFromUserId( $userId, $enforceFrequency = true ) {
		$services = MediaWikiServices::getInstance();
		$user = $services->getUserFactory()->newFromId( intval( $userId ) );

		$userOptionsLookup = $services->getUserOptionsLookup();
		$userEmailSetting = intval( $userOptionsLookup->getOption( $user, 'echo-email-frequency' ) );

		// clear all existing events if user decides not to receive emails
		if ( $userEmailSetting == -1 ) {
			$emailBatch = new self( $user, $services->getUserOptionsManager() );
			$emailBatch->clearProcessedEvent();

			return false;
		}

		// @Todo - There may be some items idling in the queue, eg, a bundle job is lost
		// and there is not never another message with the same hash or a user switches from
		// digest to instant.  We should check the first item in the queue, if it doesn't
		// have either web or email bundling or created long ago, then clear it, this will
		// prevent idling item queuing up.

		// user has instant email delivery
		if ( $userEmailSetting == 0 ) {
			return false;
		}

		$userLastBatch = $userOptionsLookup->getOption( $user, 'echo-email-last-batch' );

		// send email batch, if
		// 1. it has been long enough since last email batch based on frequency
		// 2. there is no last batch timestamp recorded for the user
		// 3. user has switched from batch to instant email, send events left in the queue
		if ( $userLastBatch ) {
			// use 20 as hours per day to get estimate
			$nextBatch = wfTimestamp( TS_UNIX, $userLastBatch ) + $userEmailSetting * 20 * 60 * 60;
			if ( $enforceFrequency && wfTimestamp( TS_MW, $nextBatch ) > wfTimestampNow() ) {
				return false;
			}
		}

		return new self( $user, $services->getUserOptionsManager() );
	}

	/**
	 * @throws MWException
	 */
	public function sendEmail() {
		global $wgPasswordSender, $wgNoReplyAddress;

		$services = MediaWikiServices::getInstance();
		$userEmailSetting = $services->getUserOptionsLookup()
			->getOption( $this->mUser, 'echo-email-frequency' );
		if ( $userEmailSetting == \EchoEmailFrequency::WEEKLY_DIGEST ) {
			$frequency = 'weekly';
			$emailDeliveryMode = 'weekly_digest';
		} else {
			$frequency = 'daily';
			$emailDeliveryMode = 'daily_digest';
		}

		$factory = $services->getService( 'BSEchoConnectorFormatterFactory' );
		$textEmailDigestFormatter = $factory->getForFormat( 'plain-text-digest', true, [
			$this->mUser,
			$this->language,
			$frequency
		] );

		try {
			// Prevent failure and stop of the entire process if format fails
			$content = $textEmailDigestFormatter->format( $this->events, 'email' );
		} catch ( \Throwable $e ) {
			return;
		}

		if ( !$content ) {
			// no event could be formatted
			return;
		}

		if ( !$this->mUser->isRegistered() ) {
			// Prevent failure and stop of the entire process if user is not registered
			return;
		}

		$format = \MWEchoNotifUser::newFromUser( $this->mUser )->getEmailFormat();
		if ( $format == \EchoEmailFormat::HTML ) {
			$htmlEmailDigestFormatter = $factory->getForFormat( 'html-digest', true, [
				$this->mUser,
				$this->language,
				$frequency
			] );
			$htmlContent = $htmlEmailDigestFormatter->format( $this->events, 'email' );

			$content = [
				'body' => [
					'text' => $content['body'],
					'html' => $htmlContent['body'],
				],
				'subject' => $htmlContent['subject'],
			];
		}

		$toAddress = \MailAddress::newFromUser( $this->mUser );
		$fromAddress = new \MailAddress(
			$wgPasswordSender,
			wfMessage( 'emailsender' )->inContentLanguage()->text()
		);
		$replyTo = new \MailAddress( $wgPasswordSender, $wgNoReplyAddress );

		// @Todo Push the email to job queue or just send it out directly?
		\UserMailer::send(
			$toAddress,
			$fromAddress,
			$content['subject'],
			$content['body'],
			[ 'replyTo' => $replyTo ]
		);
	}
}
