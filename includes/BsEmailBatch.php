<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class BsEmailBatch extends MWEchoEmailBatch {

    /**
     * @inheritDoc
     * @param type $userId
     * @param type $enforceFrequency
     * @return \self|boolean
     */
    public static function newFromUserId( $userId, $enforceFrequency = true ) {
		$user = User::newFromId( intval( $userId ) );

		$userEmailSetting = intval( $user->getOption( 'echo-email-frequency' ) );

		// clear all existing events if user decides not to receive emails
		if ( $userEmailSetting == -1 ) {
			$emailBatch = new self( $user );
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

		$userLastBatch = $user->getOption( 'echo-email-last-batch' );

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

		return new self( $user );
	}
	
	/**
	 * Send the batch email
	 */
	public function sendEmail() {
		global $wgNotificationSender, $wgNotificationReplyName;

		if ( $this->mUser->getOption( 'echo-email-frequency' )
			== EchoHooks::EMAIL_WEEKLY_DIGEST
		) {
			$frequency = 'weekly';
			$emailDeliveryMode = 'weekly_digest';
		} else {
			$frequency = 'daily';
			$emailDeliveryMode = 'daily_digest';
		}

		// Echo digest email mode
		$emailDigest = new BsEchoEmailDigest( $this->mUser, $this->content, $frequency );

		$textEmailFormatter = new BsEchoTextEmailFormatter( $emailDigest );

		$body = $textEmailFormatter->formatEmail();

		$format = MWEchoNotifUser::newFromUser( $this->mUser )->getEmailFormat();
		if ( $format == EchoHooks::EMAIL_FORMAT_HTML ) {
			$htmlEmailFormatter = new BsEchoHTMLEmailFormatter( $emailDigest );
			$body = array(
				'text' => $body,
				'html' => $htmlEmailFormatter->formatEmail()
			);
		}

		// Give grep a chance to find the usages:
		// echo-email-batch-subject-daily, echo-email-batch-subject-weekly
		$subject = wfMessage( 'echo-email-batch-subject-' . $frequency )
			->inLanguage( $this->mUser->getOption( 'language' ) )
			->params( $this->count, $this->count )->text();

		$toAddress = MailAddress::newFromUser( $this->mUser );
		$fromAddress = new MailAddress( $wgNotificationSender, EchoHooks::getNotificationSenderName() );
		$replyTo = new MailAddress( $wgNotificationSender, $wgNotificationReplyName );

		// @Todo Push the email to job queue or just send it out directly?
		UserMailer::send( $toAddress, $fromAddress, $subject, $body, array( 'replyTo' => $replyTo ) );
		MWEchoEventLogging::logSchemaEchoMail( $this->mUser, $emailDeliveryMode );
	}
}
