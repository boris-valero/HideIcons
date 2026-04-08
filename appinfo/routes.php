<?php

return [
	'ocs' => [],
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
		[
			'name' => 'adminApi#setPreferences',
			'url' => '/api/admin/preferences',
			'verb' => 'POST'
		],
	]
];
