<?php

namespace ADT\Components;

class AjaxMultiSelect extends \Nette\Forms\Controls\MultiSelectBox {

	use AjaxSelect\Traits\InvalidMultiValueTrait;

	protected function handleInvalidValue($value, $e) {
		
	}

}