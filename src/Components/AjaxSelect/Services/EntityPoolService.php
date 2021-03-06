<?php

namespace ADT\Components\AjaxSelect\Services;

use ADT\Components\AjaxSelect;
use Nette\SmartObject;

/**
 * @method void onAdd(AjaxSelect\Entities\AbstractEntity $entity)
 */
class EntityPoolService {

	use SmartObject;

	/** @var callable[] */
	public $onAdd = [ ];

	/** @var AjaxSelect\Entities\AbstractEntity[] */
	protected $pool = [ ];

	/**
	 * @param AjaxSelect\Entities\AbstractEntity $entity
	 * @return $this
	 */
	public function add(AjaxSelect\Entities\AbstractEntity $entity) {
		$this->pool[] = $entity;

		// trigger hooked callbacks
		$this->onAdd($entity);

		return $this;
	}

	/**
	 * @return $this
	 */
	public function invokeDone() {
		foreach ($this->pool as $entity) {
			$entity->done();
		}
		return $this;
	}

}
