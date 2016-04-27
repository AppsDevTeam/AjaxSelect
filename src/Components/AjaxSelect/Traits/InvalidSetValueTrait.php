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
	protected abstract function handleInvalidValue($value, $exception);

	public function setValue($value) {
		try {
			// try assign value
			return parent::setValue($value);
		} catch (\Nette\InvalidArgumentException $e) {
			try {
				// try create and assign value
				return $this->handleInvalidValue($value, $e);
			} catch (\Nette\InvalidArgumentException $e) {
				// value is invalid and there is no way of assigning it

				switch ($this->invalidValueMode) {
					case AjaxSelect\DI\AjaxSelectExtension::INVALID_VALUE_MODE_EMPTY:
						$this->value = NULL;
						return $this;

					case AjaxSelect\DI\AjaxSelectExtension::INVALID_VALUE_MODE_EXCEPTION:
					default:
						throw $e;
				}
			}
		}
	}

}