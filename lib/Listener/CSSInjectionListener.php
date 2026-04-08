<?php

declare(strict_types=1);

namespace OCA\AppOrder\Listener;

use OCA\AppOrder\Service\ConfigProxy;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @implements IEventListener<BeforeTemplateRenderedEvent>
 */
class CSSInjectionListener implements IEventListener {

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private ConfigProxy $configProxy,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}

		$hiddenApps = $this->sanitizeAppIds($this->configProxy->getAppValueArray('hidden_apps'));
		$orderedApps = $this->sanitizeAppIds($this->configProxy->getAppValueArray('ordered_apps'));

		if (empty($hiddenApps) && empty($orderedApps)) {
			return;
		}

		$this->injectTopMenuCSS($hiddenApps, $orderedApps);
	}

	private function injectTopMenuCSS(array $hiddenApps, array $orderedApps): void {
		$css = $this->generateHiddenAppsCSS($hiddenApps) . $this->generateOrderedAppsCSS($orderedApps);
		Util::addHeader('style', ['id' => 'apporder-top-menu-customization'], $css);
	}

	private function generateHiddenAppsCSS(array $hiddenApps): string {
		$css = '';

		/** @var string $appId */
		foreach ($hiddenApps as $appId) {
			// Use CSS escaped slashes to avoid quote escaping in injected style headers.
			$css .= ".app-menu-entry:has(a.app-menu-entry__link[href\$=\\2f apps\\2f {$appId}\\2f ]) { display: none; }";
		}

		return $css;
	}

	private function generateOrderedAppsCSS(array $orderedApps): string {
		$css = '';

		/** @var string $appId */
		foreach ($orderedApps as $index => $appId) {
			$order = $index + 1;
			$css .= ".app-menu-entry:has(a.app-menu-entry__link[href\$=\\2f apps\\2f {$appId}\\2f ]) { order: {$order}; }";
		}

		return $css;
	}

	private function sanitizeAppIds(array $apps): array {
		$seen = [];
		$sanitized = [];

		foreach ($apps as $appId) {
			if (!is_string($appId) || $appId === '') {
				continue;
			}

			if (preg_match('/^[a-zA-Z0-9_-]+$/', $appId) !== 1) {
				continue;
			}

			if (isset($seen[$appId])) {
				continue;
			}

			$seen[$appId] = true;
			$sanitized[] = $appId;
		}

		return $sanitized;
	}
}
