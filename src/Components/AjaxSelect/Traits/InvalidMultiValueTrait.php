<?php

namespace ADT\Components\AjaxSelect\Traits;

trait InvalidMultiValueTrait {

	use InvalidSetValueTrait;

	public function loadHttpData(): void {
		$this->setValue(array_keys(array_flip($this->getHttpData(\Nette\Forms\Form::DATA_TEXT))));
		if (is_array($this->isDisabled())) {
			$this->value = array_diff($this->getValue(), array_keys($this->isDisabled()));
		}
	}

}