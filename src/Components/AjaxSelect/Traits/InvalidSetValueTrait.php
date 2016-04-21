<?php

namespace ADT\Components\AjaxSelect\Traits;

/**
 * Overrides setValue setter.
 * If invalid value is passed, invokes handleInvalidValue.
 */
trait InvalidSetValueTrait {

	protected abstract function getValue();
	protected abstract function getHttpData($type);
	protected abstract function isDisabled();
	protected abstract function handleInvalidValue($value, $exception);

	public function setValue($value) {
		try {
			return parent::setValue($value);
		} catch (\Nette\InvalidArgumentException $e) {
			return $this->handleInvalidValue($value, $e);
		}
	}

}