<?php

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

use MediaWiki\Logger\LoggerFactory;

use BlueSpice\Services;
use BlueSpice\INotifier;

class TestNotification extends Maintenance {

	/**
	 *
	 * @var User
	 */
	protected $agentUser = null;

	/**
	 *
	 * @var INotifier
	 */
	protected $notifier = null;

	/**
	 *
	 * @var Title
	 */
	protected $title = null;

	/**
	 *
	 * @var type
	 */
	protected $extraParams = [];

	/**
	 *
	 * @var stdClass[]
	 */
	protected $notificationConfigs = [];

	public function __construct() {
		parent::__construct();

		$this->addOption(
			"keys",
			"Comma seperated list of notification to trigger. If not set, all notifications will be triggerd",
			false,
			true
		);
		$this->addOption( "agent", "The user that triggers the notification" );
		$this->addOption( "title", "The title to associate the notification with" );
		$this->addOption( "affectedusers", "Comma seperated list of usernames", true );
		$this->addOption( "affectedgroups", "Comma seperated list of groups", false );
		$this->addOption( "outputMail", "Whether the mails should be put out to the console", false, true );

		$this->addOption( 'maxjobs', 'Maximum number of jobs to run', false, true );
		$this->addOption( 'maxtime', 'Maximum amount of wall-clock time', false, true );
		$this->addOption( 'type', 'Type of job to run', false, true );
		$this->addOption( 'procs', 'Number of processes to use', false, true );
		$this->addOption( 'nothrottle', 'Ignore job throttling configuration', false, false );
		$this->addOption( 'result', 'Set to "json" to print only a JSON response', false, true );
		$this->addOption( 'wait', 'Wait for new jobs instead of exiting', false, false );
	}

	public function execute() {
		$this->setupAlternateUserMailer();
		$this->makeAgentUser();
		$this->makeTitle();
		$this->makeNotifier();
		$this->makeNotficationConfigs();
		// error_log(var_export($this->notificationConfigs,1));
		$keys = empty( $this->getOption( 'keys', '' ) )
			? array_keys( $this->notificationConfigs )
			: explode( ',', $this->getOption( 'keys' ) );

		$notifications = [];
		foreach ( $keys as $key ) {
			$notification = $this->createNotification( $key );
			if ( !$notification ) {
				continue;
			}
			$notifications[$key] = $notification;
		}
		foreach ( $notifications as $notification ) {
			$this->notifier->notify( $notification );
		}

		$this->output( "Created: '" . count( $notifications ) . "' Notifications\n" );

		if ( $this->hasOption( 'procs' ) ) {
			$procs = intval( $this->getOption( 'procs' ) );
			if ( $procs < 1 || $procs > 1000 ) {
				$this->fatalError( "Invalid argument to --procs" );
			} elseif ( $procs != 1 ) {
				$fc = new ForkController( $procs );
				if ( $fc->start() != 'child' ) {
					exit( 0 );
				}
			}
		}

		$outputJSON = ( $this->getOption( 'result' ) === 'json' );
		$wait = $this->hasOption( 'wait' );

		$runner = new JobRunner( LoggerFactory::getInstance( 'runJobs' ) );
		if ( !$outputJSON ) {
			$runner->setDebugHandler( [ $this, 'debugInternal' ] );
		}

		$type = $this->getOption( 'type', false );
		$maxJobs = $this->getOption( 'maxjobs', false );
		$maxTime = $this->getOption( 'maxtime', false );
		$throttle = !$this->hasOption( 'nothrottle' );

		while ( true ) {
			$response = $runner->run( [
				'type'     => $type,
				'maxJobs'  => $maxJobs,
				'maxTime'  => $maxTime,
				'throttle' => $throttle,
			] );

			if ( $outputJSON ) {
				$this->output( FormatJson::encode( $response, true ) );
			}

			if (
				!$wait ||
				$response['reached'] === 'time-limit' ||
				$response['reached'] === 'job-limit' ||
				$response['reached'] === 'memory-limit'
			) {
				break;
			}

			if ( $maxJobs !== false ) {
				$maxJobs -= count( $response['jobs'] );
			}

			sleep( 1 );
		}
	}

	public function memoryLimit() {
		if ( $this->hasOption( 'memory-limit' ) ) {
			return parent::memoryLimit();
		}

		// Don't eat all memory on the machine if we get a bad job.
		return "150M";
	}

	/**
	 * @param string $s
	 */
	public function debugInternal( $s ) {
		$this->output( $s );
	}

	protected function createNotification( $key ) {
		if ( !isset( $this->notificationConfigs[$key] ) ) {
			$this->output( "Not available notification: '$key'\n" );
			return false;
		}
		if ( empty( $this->notificationConfigs[$key]->class ) ) {
			$this->output( "Invalid definition for: '$key'\n" );
			return false;
		}
		if ( empty( $this->notificationConfigs[$key]->requires ) ) {
			$this->output( "No requirement for: '$key'\n" );
			return false;
		}
		$extensionLoaded = \ExtensionRegistry::getInstance()->isLoaded(
			$this->notificationConfigs[$key]->requires
		);
		if ( !$extensionLoaded ) {
			$this->output(
				"Extension '{$this->notificationConfigs[$key]->requires}' not loaded for: '$key'\n"
			);
			return false;
		}
		$this->output( "Adding notification '$key'\n" );
		$params = [];

		foreach ( (array)$this->notificationConfigs[$key]->params as $name => $param ) {
			$params[$name] = isset( $param->value )
				? $param->value
				: call_user_func( function () use( $param ) {
					eval( $param->callback );
					return $val;
				} );
		}
		if ( $this->getOption( 'agent', false ) ) {
			$params['agent'] = $this->agentUser;
		}
		if ( $this->getOption( 'title', false ) ) {
			$params['title'] = $this->title;
		}
		$notification = new $this->notificationConfigs[$key]->class(
			...array_values( $params )
		);

		$this->output( "Done.\n" );
		return $notification;
	}

	protected function makeAgentUser() {
		$agent = $this->getOption( 'agent', '' );
		if ( !empty( $agent ) ) {
			$this->agentUser = User::newFromName( $agent );
			if ( !$this->agentUser || $this->agentUser->isAnon() ) {
				throw  new Exception( "Invalid or not existing user: $agent" );
			}
			return;
		}
		$this->agentUser = $this->getServices()->getBSUtilityFactory()
			->getMaintenanceUser()->getUser();
	}

	protected function makeTitle() {
		$title = $this->getOption( 'title', '' );
		if ( !empty( $title ) ) {
			$this->title = Title::newFromText( $title );
		} else {
			$this->title = Title::newMainPage();
		}
	}

	protected function makeExtraParams() {
		$extra = [];
		$affectedUsers = $this->getAffectedUsers();
		if ( !empty( $affectedUsers ) ) {
			$extra['affected-users'] = $affectedUsers;
		}
		$affectedGroups = $this->getAffectedGroups();
		if ( !empty( $affectedGroups ) ) {
			$extra['affected-groups'] = $affectedGroups;
		}

		return $extra;
	}

	protected function setupAlternateUserMailer() {
		if ( !$this->getOption( 'outputMail', true ) ) {
			return;
		}

		$GLOBALS['wgEmailAuthentication'] = false;
		$GLOBALS['wgEchoEnableEmailBatch'] = false;
		$GLOBALS['wgEchoUseJobQueue'] = true;

		$that = $this;
		Hooks::register( 'EchoGetDefaultNotifiedUsers', function ( $event, &$users ) use ( $that )  {
			$users = $that->getAffectedUsers();
			return false;
		} );
		Hooks::register( 'AlternateUserMailer', function ( $headers, $to, $from, $subject, $body ) {
			error_log( var_export( $to, 1 ) );
			error_log( var_export( $subject, 1 ) );
			$this->outputMail( $headers, $to, $from, $subject, $body );
			return false;
		} );

		// Do a test
		/*UserMailer::send(
			[ new MailAddress( 'support@hallowelt.com' ) ],
			new MailAddress( 'info@hallowelt.com' ),
			'Hello World',
			'Lorem ipsum dolor sit amet'
		);*/
	}

	/**
	 *
	 * @param array $headers
	 * @param MailAddress[] $to
	 * @param MailAddress $from
	 * @param string $subject
	 * @param string $body
	 */
	protected function outputMail( $headers, $to, $from, $subject, $body ) {
		$out = [];
		$out[] = '###############';
		foreach ( $headers as $headerName => $headerValue ) {
			$out[] = "$headerName: $headerValue";
		}

		$out[] = '---------------------------------------------';
		$out[] = 'FROM: ' . $from->toString();

		$tos = [];
		foreach ( $to as $mailAdress ) {
			$tos[] = $mailAdress->toString();
		}
		$out[] = 'TO: ' . implode( '; ', $tos );
		$out[] = 'SUBJECT: ' . $subject;
		$out[] = 'BODY:';
		$out[] = $body;
		$out[] = '###############';
		$out[] = "\n";

		$this->output( implode( "\n", $out ) );
	}

	public function getAffectedUsers() {
		$sAffectedUsers = $this->getOption( 'affectedusers', '' );
		$userNames = explode( ',', $sAffectedUsers );
		$users = [];
		foreach ( $userNames as $userName ) {
			$users[] = User::newFromName( $userName );
		}
		return $users;
	}

	protected function getAffectedGroups() {
		$affectedGroups = $this->getOption( 'affectedgroups', '' );
		$groups = explode( ',', $affectedGroups );
		$groups = array_map( 'trim', $groups );
		return $groups;
	}

	/**
	 * @return Services
	 */
	protected function getServices() {
		return Services::getInstance();
	}

	/**
	 * @return INotifier|null
	 */
	protected function makeNotifier() {
		$this->notifier = $this->getServices()->getBSNotificationManager()->getNotifier();
	}

	public function makeNotficationConfigs() {
		$cfg = \FormatJson::decode( file_get_contents(
			__DIR__ . '/testNotification.json'
		) );
		$this->notificationConfigs = (array)$cfg;
	}

}

$maintClass = "TestNotification";
require_once RUN_MAINTENANCE_IF_MAIN;
