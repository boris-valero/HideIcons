<?php

declare(strict_types=1);

namespace OCA\GhostIcons\Controller;

use OCA\GhostIcons\Service\ConfigProxy;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-suppress UnusedClass
 */
class AdminApiController extends Controller {
	private const PROTECTED_APPS = ['files', 'activity'];

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		string $AppName,
		IRequest $request,
		private IAppManager $appManager,
		private ConfigProxy $config,
		private IUserSession $userSession,
		private INavigationManager $navigationManager,
	) {
		parent::__construct($AppName, $request);
	}

	#[NoCSRFRequired]
	public function getApps(): DataResponse {
		$navigationEntries = $this->navigationManager->getAll();
		$hiddenApps = $this->config->getAppValueArray('hidden_apps');
		/** @var array<array<string, mixed>> $appsData */
		$appsData = [];
		$user = $this->userSession->getUser();
		foreach ($navigationEntries as $entry) {
			if (!is_array($entry) || !isset($entry['id']) || !is_string($entry['id'])) {
				continue;
			}
			$appId = $entry['id'];
			if ($user && $this->appManager->isEnabledForUser($appId, $user)) {
				$appsData[] = [
					'id' => $appId,
					'name' => (string)($entry['name'] ?? $appId),
					'hidden' => in_array($appId, $hiddenApps),
					'protected' => in_array($appId, self::PROTECTED_APPS)
				];
			}
		}
		return new DataResponse($appsData);
	}

	#[NoCSRFRequired]
	public function setHidden(): DataResponse {
		/** @psalm-suppress MixedAssignment */
		$hiddenParam = $this->request->getParam('hidden', []);
		$hidden = is_array($hiddenParam) ? $hiddenParam : [];

		// Validate all items are strings
		$validatedApps = array_values(array_filter($hidden, 'is_string'));

		// Filter out protected apps
		$filteredApps = array_diff($validatedApps, self::PROTECTED_APPS);
		$ignoredProtected = array_intersect($validatedApps, self::PROTECTED_APPS);

		$this->config->setAppValueArray('hidden_apps', $filteredApps);

		return new DataResponse([
			'success' => true,
			'hidden_apps' => $filteredApps,
			'ignored_protected_apps' => array_values($ignoredProtected),
		]);
	}
}
