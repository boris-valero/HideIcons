<?php

declare(strict_types=1);

namespace OCA\AppOrder\AppInfo;

use OCA\AppOrder\Listener\CSSInjectionListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'apporder';
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, CSSInjectionListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
