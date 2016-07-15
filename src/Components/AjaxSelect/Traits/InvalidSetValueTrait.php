<?php

namespace ADT\Components\AjaxSelect\Traits;

use ADT\Components\AjaxSelect;


/**
 * Overrides setValue setter.
 * If invalid value is passed, invokes handleInvalidValue.
 * @property mixed $value
 */
trait InvalidSetValueTrait {

	protected $invalidValueMode = AjaxSelect\DI\AjaxSelectExtension::INVALID_VALUE_MODE_EXCEPTION;

	/**
	 * @return string
	 */
	public function getInvalidValueMode() {
		return $this->invalidValueMode;
	}

	/**
	 * @param string $mode
	 * @return $this
	 */
	public function setInvalidValueMode($mode) {
		$this->invalidValueMode = $mode;
		return $this;
	}

	protected abstract function getHttpData($type, $htmlTail = NULL);
	protected abstract function isDisabled();

	/**
	 * Processes value that could not be assigned.
	 * @param mixed|array $value
	 * @return mixed|array Value to assign.
	 */
	protected abstract function processValues($value);

	protected function handleInvalidValues($values) {
		switch ($this->getInvalidValueMode()) {
			case AjaxSelect\DI\AjaxSelectExtension::INVALID_VALUE_MODE_EXCEPTION:
				throw new \Nette\InvalidArgumentException;

			case AjaxSelect\DI\AjaxSelectExtension::INVALID_VALUE_MODE_EMPTY:
				return $this instanceof AjaxSelect\Interfaces\IMultiSelectControl
					? [ ] : NULL;
				break;
		}

		return $this;
	}

	public function setValue($value) {
		try {
			// try to assign value
			return parent::setValue($value);
		} catch (\Nette\InvalidArgumentException $e) {}

		// make sure $value is an array
		if (!is_array($value)) {
			$value = [ $value ];
		}

		// try create and assign value
		$value = $this->processValues($value);

		// revert array to single value if needed
		if (!$this instanceof AjaxSelect\Interfaces\IMultiSelectControl) {
			$value = count($value)
				? $value[0]
				: NULL;
		}

		// try to assign value
		return parent::setValue($value);
	}

}