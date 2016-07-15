<?php

namespace ADT\Components\AjaxSelect\Traits;

use ADT\Components\AjaxSelect;

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

	protected abstract function handleInvalidValues($value);

	/**
	 * TODO: cannot handle multiple values
	 * @param $values
	 * @return mixed
	 */
	protected function processValues($values) {
		if ($this->itemFactory === NULL) {
			return $this->handleInvalidValues($values);
		}

		if (!$this instanceof AjaxSelect\Interfaces\IMultiSelectControl && is_array($values)) {
			$values = reset($values);
		}

		$item = call_user_func($this->itemFactory, $values);

		if (empty($item)) {
			return $this->handleInvalidValues($values);
		}

		// add value to list of valid items
		$items = $this->getItems();

		if (is_array($item)) {
			$items = $items + $item;

		} else {
			$items[$values] = $item;
		}

		$this->setItems($items);

		return $values;
	}
}