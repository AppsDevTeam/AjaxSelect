<?php

namespace ADT\Components\AjaxSelect\Traits;


use ADT\Components\AjaxSelect;

trait AjaxServiceControlTrait {

	/** @var AjaxSelect\Entities\AbstractEntity */
	protected $ajaxEntity;

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

	protected abstract function handleInvalidValues($values);

	/**
	 * Processes value that could not be assigned.
	 * @param mixed|array $values
	 * @return mixed|array Value to assign.
	 */
	protected function processValues($values) {
		$validValues = [ ];
		$invalidValues = [ ];

		foreach ($this->getAjaxEntity()->areValidValues($values) as $value => $isValid) {
			if ($isValid) {
				$validValues[] = $value;
			} else {
				$invalidValues[] = $value;
			}
		}

		if (count($invalidValues) > 0) {
			$validValues = array_merge($validValues, $this->handleInvalidValues($invalidValues) ?: [ ]);
		}

		// combine list of ids to identity array i.e. key = value
		$items = array_combine($validValues, $validValues);

		// add to list of valid values
		$this->setItems($this->getItems() + $items);

		return $validValues;
	}

	public function setDefaultValue($value)
	{
		if (!$this->getForm()->isSubmitted()) {
			$items = iterator_to_array($this->getAjaxEntity()->formatValues((array) $value));
			$this->setItems($items);
		}
		parent::setDefaultValue($value);
	}
}
