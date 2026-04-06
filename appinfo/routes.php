<?php

return [
	'ocs' => [
		[
			'name' => 'stats#getStats',
			'url' => '/api/v1/stats',
			'verb' => 'GET',
		],
	],
	'resources' => [],
	'routes' => [
		[
			'name' => 'adminApi#getApps',
			'url' => '/api/admin/apps',
			'verb' => 'GET'
		],
		[
			'name' => 'adminApi#setHidden',
			'url' => '/api/admin/hidden',
			'verb' => 'POST'
		],
	]
];
