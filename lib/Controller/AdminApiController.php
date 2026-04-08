<?php

declare(strict_types=1);

namespace OCA\AppOrder\Controller;

use OCA\AppOrder\Service\ConfigProxy;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AdminRequired;
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

	#[AdminRequired]
	public function getApps(): DataResponse {
		$navigationEntries = $this->navigationManager->getAll();
		$availableApps = $this->extractAvailableApps($navigationEntries);
		$availableAppIds = array_column($availableApps, 'id');

		$hiddenApps = $this->sanitizeAppList($this->config->getAppValueArray('hidden_apps'), $availableAppIds);
		$orderedApps = $this->sanitizeAppList($this->config->getAppValueArray('ordered_apps'), $availableAppIds);

		/** @var array<array<string, mixed>> $appsData */
		$appsData = [];
		foreach ($availableApps as $app) {
			$appId = $app['id'];
			$appsData[] = [
				'id' => $appId,
				'name' => $app['name'],
				'hidden' => in_array($appId, $hiddenApps, true),
				'protected' => in_array($appId, self::PROTECTED_APPS, true),
			];
		}

		$appsData = $this->applyConfiguredOrder($appsData, $orderedApps);

		return new DataResponse($appsData);
	}

	#[AdminRequired]
	public function setHidden(): DataResponse {
		$navigationEntries = $this->navigationManager->getAll();
		$availableAppIds = array_column($this->extractAvailableApps($navigationEntries), 'id');

		/** @psalm-suppress MixedAssignment */
		$hiddenParam = $this->request->getParam('hidden', []);
		$validatedApps = $this->sanitizeAppList($hiddenParam, $availableAppIds);

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

	#[AdminRequired]
	public function setPreferences(): DataResponse {
		$navigationEntries = $this->navigationManager->getAll();
		$availableAppIds = array_column($this->extractAvailableApps($navigationEntries), 'id');

		/** @psalm-suppress MixedAssignment */
		$hiddenParam = $this->request->getParam('hidden', []);
		/** @psalm-suppress MixedAssignment */
		$orderedParam = $this->request->getParam('ordered', []);

		$validatedHidden = $this->sanitizeAppList($hiddenParam, $availableAppIds);
		$validatedOrder = $this->sanitizeAppList($orderedParam, $availableAppIds);

		$filteredHidden = array_values(array_diff($validatedHidden, self::PROTECTED_APPS));
		$ignoredProtected = array_values(array_intersect($validatedHidden, self::PROTECTED_APPS));
		$completeOrder = $this->appendMissingAppIds($validatedOrder, $availableAppIds);

		$this->config->setAppValueArray('hidden_apps', $filteredHidden);
		$this->config->setAppValueArray('ordered_apps', $completeOrder);

		return new DataResponse([
			'success' => true,
			'hidden_apps' => $filteredHidden,
			'ordered_apps' => $completeOrder,
			'ignored_protected_apps' => $ignoredProtected,
		]);
	}

	/**
	 * @param array<array-key, mixed> $navigationEntries
	 * @return array<int, array{id: string, name: string}>
	 */
	private function extractAvailableApps(array $navigationEntries): array {
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
				];
			}
		}

		return $appsData;
	}

	/**
	 * @param mixed $rawList
	 * @param array<int, string> $allowedAppIds
	 * @return array<int, string>
	 */
	private function sanitizeAppList(mixed $rawList, array $allowedAppIds): array {
		$list = is_array($rawList) ? $rawList : [];
		$allowedLookup = array_fill_keys($allowedAppIds, true);
		$seen = [];
		$sanitized = [];

		foreach ($list as $appId) {
			if (!is_string($appId) || $appId === '') {
				continue;
			}

			if (preg_match('/^[a-zA-Z0-9_-]+$/', $appId) !== 1) {
				continue;
			}

			if (!isset($allowedLookup[$appId]) || isset($seen[$appId])) {
				continue;
			}

			$seen[$appId] = true;
			$sanitized[] = $appId;
		}

		return $sanitized;
	}

	/**
	 * @param array<int, string> $orderedAppIds
	 * @param array<int, string> $availableAppIds
	 * @return array<int, string>
	 */
	private function appendMissingAppIds(array $orderedAppIds, array $availableAppIds): array {
		$lookup = array_fill_keys($orderedAppIds, true);

		foreach ($availableAppIds as $appId) {
			if (!isset($lookup[$appId])) {
				$orderedAppIds[] = $appId;
			}
		}

		return $orderedAppIds;
	}

	/**
	 * @param array<int, array<string, mixed>> $appsData
	 * @param array<int, string> $orderedAppIds
	 * @return array<int, array<string, mixed>>
	 */
	private function applyConfiguredOrder(array $appsData, array $orderedAppIds): array {
		if ($orderedAppIds === []) {
			return $appsData;
		}

		$appsById = [];
		foreach ($appsData as $app) {
			if (isset($app['id']) && is_string($app['id'])) {
				$appsById[$app['id']] = $app;
			}
		}

		$orderedAppsData = [];
		foreach ($orderedAppIds as $appId) {
			if (isset($appsById[$appId])) {
				$orderedAppsData[] = $appsById[$appId];
				unset($appsById[$appId]);
			}
		}

		foreach ($appsData as $app) {
			if (!isset($app['id']) || !is_string($app['id'])) {
				continue;
			}

			if (isset($appsById[$app['id']])) {
				$orderedAppsData[] = $app;
			}
		}

		return $orderedAppsData;
	}
}
