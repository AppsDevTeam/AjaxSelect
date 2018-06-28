<?php

namespace ADT\Components\AjaxSelect\Services;

use ADT\Components\AjaxSelect;
use Nette\SmartObject;

class AjaxService {

	use SmartObject;

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
	 * @param string|AjaxSelect\Entities\AbstractEntity $entityName
	 * @param AjaxSelect\Interfaces\IAjaxServiceControl $clientControl
	 * @return AjaxSelect\Entities\AbstractEntity
	 */
	public function createEntity($entityName, AjaxSelect\Interfaces\IAjaxServiceControl $clientControl = NULL) {
		if ($entityName instanceof AjaxSelect\Entities\AbstractEntity) {
			$entity = $entityName;
		} else {
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

			// set entity name
			$entity->setName($entityName);
		}

		// set config
		$entity->setConfig($this->config);


		if ($clientControl) {
			// set control
			$entity->setControl($clientControl)->done();

			// add to pool (no reason to add entity without control)
			$this->entityPoolService->add($entity);
		}

		return $entity;
	}

	public function setConfig(array $config) {
		$this->config = $config;
	}
}
