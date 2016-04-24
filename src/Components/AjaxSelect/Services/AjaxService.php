<?php

namespace ADT\Components\AjaxSelect\Services;

use ADT\Components\AjaxSelect;

/**
 * @method $this setConfig(array $config)
 */
class AjaxService extends \Nette\Object {

	/** @var array */
	protected $config;

	/** @var array */
	protected $entityFactories = [ ];

	/** @var AjaxSelect\Services\EntityPoolService */
	protected $entityPoolService;

	public function __construct(AjaxSelect\Services\EntityPoolService $entityPoolService) {
		$this->entityPoolService = $entityPoolService;
	}

	public function addEntityFactory($entityName, $entityFactory) {
		$this->entityFactories[$entityName] = $entityFactory;
		return $this;
	}

	/**
	 * @param string $entityName
	 * @param AjaxSelect\Interfaces\IAjaxServiceControl $clientControl
	 * @return AjaxSelect\Entities\AbstractEntity
	 */
	public function createEntity($entityName, AjaxSelect\Interfaces\IAjaxServiceControl $clientControl = NULL) {
		// check factory
		if (!isset($this->entityFactories[$entityName])) {
			throw new \Nette\InvalidArgumentException("Unknown entity name: $entityName");
		}

		// create instance
		$entity = $this->entityFactories[$entityName]->create();

		// check entity type
		if (!$entity instanceof AjaxSelect\Entities\AbstractEntity) {
			throw new \Nette\InvalidStateException("Entity $entityName must inherit from " . AjaxSelect\Entities\AbstractEntity::class);
		}

		// set config
		$entity->setConfig($this->config);

		// set entity name
		$entity->setName($entityName);


		if ($clientControl) {
			// set control
			$entity->setControl($clientControl)->done();

			// add to pool (no reason to add entity without control)
			$this->entityPoolService->add($entity);
		}

		return $entity;
	}
}