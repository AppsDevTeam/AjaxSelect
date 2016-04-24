<?php

namespace ADT\Components\AjaxSelect\Traits;


use ADT\Components\AjaxSelect;

trait AjaxServiceControlTrait {

	/** @var array */
	protected $ajaxConfig;

	/** @var AjaxSelect\Entities\AbstractEntity */
	protected $ajaxEntity;

	public function setAjaxConfig(array $config) {
		$this->ajaxConfig = $config;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAjaxEntityName() {
		return $this->getAjaxEntity()->getName();
	}

	/**
	 * @return AjaxSelect\Entities\AbstractEntity
	 */
	public function getAjaxEntity() {
		return $this->ajaxEntity;
	}

	public function setAjaxEntity(AjaxSelect\Entities\AbstractEntity $ajaxEntity) {
		$this->ajaxEntity = $ajaxEntity;
	}

	protected function handleInvalidValue($value, $e) {
		if (!$this->getAjaxEntity()->isValidValue($value)) {
			throw $e;
		}

		$items = $value;

		// ensure $items is array
		if (!is_array($items)) {
			$items = [ $items ];
		}

		// list of ids combine to identity array i.e. key = value
		$items = array_combine($items, $items);

		// set items and value
		$this->setItems($items);
		$this->value = $value;

		return $this;
	}
}