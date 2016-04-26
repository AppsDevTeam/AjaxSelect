<?php

namespace ADT\Components\AjaxSelect;

class AjaxMultiSelect extends \Nette\Forms\Controls\MultiSelectBox implements Interfaces\IAjaxServiceControl, Interfaces\IMultiSelectControl {

	use Traits\AjaxServiceControlTrait;

	use Traits\InvalidMultiValueTrait;

}