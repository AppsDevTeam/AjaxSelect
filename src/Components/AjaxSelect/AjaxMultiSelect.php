<?php

namespace ADT\Components\AjaxSelect;

class AjaxMultiSelect extends \Nette\Forms\Controls\MultiSelectBox implements Interfaces\IAjaxServiceControl {

	use Traits\AjaxServiceControlTrait;

	use Traits\InvalidMultiValueTrait;

}