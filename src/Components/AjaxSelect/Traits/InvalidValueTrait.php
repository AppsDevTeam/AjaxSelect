<?php

namespace ADT\Components\AjaxSelect\Traits;

/**
 * Overrides loadHttpData.
 * Triggers overriden setValue method.
 */
trait InvalidValueTrait {

	use InvalidSetValueTrait;

	public function loadHttpData() {
		// trigger overriden setValue method

		$this->value = $this->getHttpData(\Nette\Forms\Form::DATA_TEXT);
		if ($this->value !== NULL && $this->value !== '') {
			if (is_array($this->isDisabled()) && isset($this->isDisabled()[$this->getValue()])) {
				$this->value = NULL;
			} else {
				$this->setValue($this->value);
			}
		}
	}

}