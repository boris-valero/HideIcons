<?php

declare(strict_types=1);

namespace OCA\AppOrder\Controller;

use OCA\AppOrder\Metrics\Calendars;
use OCA\AppOrder\Metrics\Chats;
use OCA\AppOrder\Metrics\Circles;
use OCA\AppOrder\Metrics\Collectives;
use OCA\AppOrder\Metrics\Conversations;
use OCA\AppOrder\Metrics\Deck;
use OCA\AppOrder\Metrics\Filecache;
use OCA\AppOrder\Metrics\Forms;
use OCA\AppOrder\Metrics\Tables;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\ICacheFactory;
use OCP\IRequest;

class StatsController extends OCSController {
	private const CACHE_TTL_SECONDS = 6 * 3600;
	private const CACHE_NAMESPACE = 'apporder-stats';
	private const CACHE_KEY = 'all-metrics-v1';

	public function __construct(
		string $appName,
		IRequest $request,
		private ICacheFactory $cacheFactory,
		private Deck $deck,
		private Tables $tables,
		private Forms $forms,
		private Collectives $collectives,
		private Circles $circles,
		private Calendars $calendars,
		private Conversations $conversations,
		private Chats $chats,
		private Filecache $filecache,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get statistics for all apps
	 *
	 * @return DataResponse<200, array, array{}>
	 */
	#[NoCSRFRequired]
	#[CORS]
	#[ApiRoute(verb: 'GET', url: '/api/v1/stats')]
	public function getStats(): DataResponse {
		$cache = $this->cacheFactory->createLocal(self::CACHE_NAMESPACE);
		/** @var mixed $cachedMetrics */
		$cachedMetrics = $cache->get(self::CACHE_KEY);

		if (is_array($cachedMetrics)) {
			return new DataResponse($cachedMetrics);
		}

		$metrics = [
			'deck' => $this->deck->getMetrics(),
			'tables' => $this->tables->getMetrics(),
			'forms' => $this->forms->getMetrics(),
			'collectives' => $this->collectives->getMetrics(),
			'circles' => $this->circles->getMetrics(),
			'calendars' => $this->calendars->getMetrics(),
			'talk' => array_merge(
				$this->conversations->getMetrics(),
				$this->chats->getMetrics()
			),
			'filecache' => $this->filecache->getMetrics()
		];

		$cache->set(self::CACHE_KEY, $metrics, self::CACHE_TTL_SECONDS);

		return new DataResponse($metrics);
	}
}
