<?php

namespace V3s3;

use Zend\I18n\Translator\Translator as ZF3_Translator;
use Zend\I18n\Translator\TranslatorServiceFactory as ZF3_TranslatorServiceFactory;
use Zend\Db\Adapter\AdapterInterface as ZF3_AdapterInterface;
use Zend\Db\ResultSet\ResultSet as ZF3_ResultSet;
use Zend\Db\TableGateway\TableGateway as ZF3_TableGateway;
use Zend\ModuleManager\Feature\ConfigProviderInterface as ZF3_ConfigProviderInterface;

use V3s3\Model\V3s3;
use V3s3\Model\V3s3Table;
use V3s3\Controller\V3s3Controller;

class Module implements ZF3_ConfigProviderInterface {
	public function getConfig() {
		return include __DIR__ . '/../config/module.config.php';
	}

	public function getServiceConfig() {
		return [
			'factories' => [
				V3s3Table::class => function($container) {
					$tableGateway = $container->get(V3s3TableGateway::class);
					return new V3s3Table($tableGateway);
				},
				V3s3TableGateway::class => function ($container) {
					$dbAdapter = $container->get(ZF3_AdapterInterface::class);
					$resultSetPrototype = new ZF3_ResultSet();
					$resultSetPrototype->setArrayObjectPrototype(new V3s3());
					return new ZF3_TableGateway('store', $dbAdapter, null, $resultSetPrototype);
				},
				ZF3_Translator::class => ZF3_TranslatorServiceFactory::class,
			],
		];
	}

	public function getControllerConfig() {
		return [
			'factories' => [
				V3s3Controller::class => function($container) {
					return new V3s3Controller(
						$container->get(V3s3Table::class),
						$container->get(ZF3_Translator::class)
					);
				},
			],
		];
	}
}