<?php

namespace ADT\Components;


class AjaxSelect extends \Nette\Forms\Controls\SelectBox {

	use AjaxSelect\Traits\InvalidValueTrait;

	protected function handleInvalidValue($value, $e) {
		
	}

}