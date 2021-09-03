<?php

namespace ADT\Components\AjaxSelect\DI;

use ADT\Components\AjaxSelect;


class AjaxSelectExtension extends \Nette\DI\CompilerExtension {

	const CONFIG_GET_ITEMS_SIGNAL_NAME = 'getItemsSignalName';

	const CONFIG_INVALID_VALUE_MODE = 'invalidValueMode';
	const INVALID_VALUE_MODE_EXCEPTION = 'exception';
	const INVALID_VALUE_MODE_EMPTY = 'empty';

	const AJAX_SERVICE_NAME = 'ajax';
	const ENTITY_POOL_SERVICE_NAME = 'entityPool';

	const CONFIG_OR_BY_ID_FILTER = 'orByIdFilter';

	const ENTITY_FACTORY_TAG = 'ajax-select.entity-factory';

	public function loadConfiguration() {
		$this->config = $this->config + [
			static::CONFIG_GET_ITEMS_SIGNAL_NAME => 'getAjaxItems',
			static::CONFIG_INVALID_VALUE_MODE => static::INVALID_VALUE_MODE_EXCEPTION,
			static::CONFIG_OR_BY_ID_FILTER => TRUE,
		];

		$builder = $this->getContainerBuilder();

		// register entity pool service
		$builder->addDefinition($this->prefix(static::ENTITY_POOL_SERVICE_NAME))
			->setClass(AjaxSelect\Services\EntityPoolService::class);

		// register ajax service
		$builder->addDefinition($this->prefix(static::AJAX_SERVICE_NAME))
			->setClass(AjaxSelect\Services\AjaxService::class)
			->addSetup('setConfig', [ $this->config ]);
	}

	public function beforeCompile() {
		$builder = $this->getContainerBuilder();

		// register entity factories
		$ajaxService = $builder->getDefinition($this->prefix(static::AJAX_SERVICE_NAME));
		foreach ($builder->findByTag(static::ENTITY_FACTORY_TAG) as $entityFactory => $_) {
			$serviceDefinition = $builder->getDefinition($entityFactory);
			$interfaceClass = $serviceDefinition->getImplement();
			$entityClass = $serviceDefinition->getClass();

			$entityName = NULL;
			if (defined("$interfaceClass::ENTITY_NAME")) {
				// interface defines ENTITY_NAME constant
				$entityName = $interfaceClass::ENTITY_NAME;
			}

			if ($entityName === NULL && substr($interfaceClass, -13) === 'EntityFactory') {
				// interface name matches with I(Something)EntityFactory
				$matches = \Nette\Utils\Strings::match($interfaceClass, '~I([a-zA-Z0-9_]+)EntityFactory$~');

				if (isset($matches[1])) {
					$entityName = static::convertCase($matches[1]);
				}
			}

			if ($entityName === NULL && substr($entityClass, -6) === 'Entity') {
				// entity class ends with Entity
				$matches = \Nette\Utils\Strings::match($entityClass, '~([a-zA-Z0-9_]+)Entity$~');

				if (isset($matches[1])) {
					$entityName = static::convertCase($matches[1]);
				}
			}

			if ($entityName === NULL) {
				throw new \Nette\NotSupportedException("Could not determine entity name for $entityClass");
			}

			$ajaxService->addSetup('addEntityFactory', [ $entityName, '@' . $entityFactory ]);
		}
	}

	public function afterCompile(\Nette\PhpGenerator\ClassType $class) {
		// register extension methods
		$initialize = $class->getMethod('initialize');
		$initialize->addBody(__CLASS__ . '::register($this, ?);', [ $this->config ]);
	}

	public static function register(\Nette\DI\Container $container, $globalConfig) {
		// lazy service getter
		$serviceGetter = function () use ($container) {
			return $container->getByType(AjaxSelect\Services\AjaxService::class);
		};

		// control factory factory :)
		$factory = function ($class) use ($serviceGetter, $globalConfig) {

			if (in_array($class, [AjaxSelect\AjaxSelect::class, AjaxSelect\AjaxMultiSelect::class])) {
				// pro ajax entity
				return function (\Nette\Forms\Container $container, $name, $label = NULL, $entityName = NULL, $entitySetupCallback = NULL, $config = []) use ($class, $serviceGetter, $globalConfig) {

					if (is_array($entityName) && $entitySetupCallback === null && $config === []) {
						// $entityName and $entitySetupCallback are omitted

						$config = $entityName;
						$entityName = null;

					} else if (is_callable($entityName) && $config == []) {
						// $entityName is omitted

						if (is_array($entitySetupCallback)) {
							$config = $entitySetupCallback;
						}
						$entitySetupCallback = $entityName;
						$entityName = null;

					} else if (is_array($entitySetupCallback) && $config === []) {
						// $entitySetupCallback is omitted

						$config = $entitySetupCallback;
						$entitySetupCallback = null;
					}

					/** @var AjaxSelect\AjaxSelect|AjaxSelect\DynamicSelect|mixed $control */
					$control = new $class($label);

					$config = array_intersect_key($config, array_flip([static::CONFIG_INVALID_VALUE_MODE])) + $globalConfig;

					// set invalid value mode
					$control->setInvalidValueMode($config[static::CONFIG_INVALID_VALUE_MODE]);

					// inject ajax entity

					/** @var AjaxSelect\Services\AjaxService $ajaxService */
					$ajaxService = $serviceGetter();
					$ajaxEntity = $ajaxService->createEntity($entityName ? : $name, $control);
					$control->setAjaxEntity($ajaxEntity);

					if ($entitySetupCallback) {
						call_user_func($entitySetupCallback, $ajaxEntity);
					}

					return $container[$name] = $control;
				};

			} elseif (in_array($class, [AjaxSelect\DynamicSelect::class, AjaxSelect\DynamicMultiSelect::class])) {

				// pro dymanic select
				return function (\Nette\Forms\Container $container, $name, $label = NULL, $items = NULL, $itemFactory = NULL, $config = []) use ($class, $serviceGetter, $globalConfig) {
					$config = static::processConfigOptions($config, $globalConfig, [static::CONFIG_INVALID_VALUE_MODE, static::CONFIG_OR_BY_ID_FILTER]);

					// if $items are not array of values, we have received query object
					if ($items instanceof \ADT\BaseQuery\BaseQuery) {
						AjaxSelect\Traits\OrByIdFilterTrait::applyOrByIdFilter($config, $container, $name, $items);

						if ($items->callSelectPairsAuto()) {
							$items->selectPairs();
						}

						// it needs to be fetched here, because \Nette\Forms\Controls\SelectBox constructor requires array passed in $items
						$items = $items->fetch();
					}

					/** @var AjaxSelect\AjaxSelect|AjaxSelect\DynamicSelect|mixed $control */
					$control = new $class($label, $items);

					// set invalid value mode
					$control->setInvalidValueMode($config[static::CONFIG_INVALID_VALUE_MODE]);

					$control->setItemFactory($itemFactory);

					return $container[$name] = $control;
				};
			} else {
				throw new Nette\InvalidArgumentException;
			}
		};

		// register control factories
		\Nette\Forms\Container::extensionMethod('addAjaxSelect', $factory(AjaxSelect\AjaxSelect::class));
		\Nette\Forms\Container::extensionMethod('addAjaxMultiSelect', $factory(AjaxSelect\AjaxMultiSelect::class));
		\Nette\Forms\Container::extensionMethod('addDynamicSelect', $factory(AjaxSelect\DynamicSelect::class));
		\Nette\Forms\Container::extensionMethod('addDynamicMultiSelect', $factory(AjaxSelect\DynamicMultiSelect::class));
	}

	private static function convertCase($string) {
		return strtolower(
			preg_replace(
				["/([A-Z]+)/", "/-([A-Z]+)([A-Z][a-z])/"],
				["-$1", "-$1-$2"],
				lcfirst($string)
			)
		);
	}

	/**
	 * @param array|string $config
	 * @param array $globalConfig
	 * @param array $availableOptions
	 * @return array
	 */
	private static function processConfigOptions($config, array $globalConfig, array $availableOptions)
	{
		return array_intersect_key($config, array_flip($availableOptions)) + $globalConfig;
	}
}