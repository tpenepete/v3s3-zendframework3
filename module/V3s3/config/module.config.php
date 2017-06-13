<?php

namespace V3s3;

use Zend\Router\Http\Regex as ZF3_RouteMatch_Regex;

use V3s3\Controller\V3s3Controller;

return [
	'router' => [
		'routes' => [
			'v3s3' => [
				'type'    => ZF3_RouteMatch_Regex::class,
				'options' => [
					'regex'    => '(?<id>/.+?)',
					'spec' => '%id%',
					/*
					'constraints' => array(
						'id'     => '[0-9]+',
					),
					*/
					'defaults' => [
						'controller' => V3s3Controller::class,
					],
				],
			],
		],
	],

	'view_manager' => [
		'strategies' => [
			'ViewJsonStrategy'
		],
	],

	'translator' => [
		'locale' => 'en_US',
		'translation_file_patterns' => [
			[
				'type'     => 'phparray',
				'base_dir' => __DIR__ . '/../language',
				'pattern'  => '%s.php',
			],
		],
	],
];