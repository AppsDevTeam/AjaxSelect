<?php

namespace ADT\Components\AjaxSelect\Traits;

trait ItemFactoryTrait {

	/** @var callable|NULL */
	protected $itemFactory;

	/**
	 * @return callable|NULL
	 */
	public function getItemFactory() {
		return $this->itemFactory;
	}

	/**
	 * @param callable|NULL $itemFactory
	 * @return $this
	 */
	public function setItemFactory($itemFactory) {
		$this->itemFactory = $itemFactory;
		return $this;
	}

	protected abstract function getItems();
	protected abstract function setItems($items);

	protected function handleInvalidValue($value, $e) {
		if ($this->itemFactory === NULL) {
			throw $e;
		}

		$item = call_user_func($this->itemFactory, $value);

		if (empty($item)) {
			throw $e;
		}

		$items = $this->getItems();
		$items[$value] = $item;
		$this->setItems($items);

		return parent::setValue($value);
	}
}