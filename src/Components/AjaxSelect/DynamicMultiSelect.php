<?php

namespace ADT\Components\AjaxSelect;

class DynamicMultiSelect extends \Nette\Forms\Controls\MultiSelectBox implements Interfaces\IMultiSelectControl {

	use Traits\ItemFactoryTrait;

	use Traits\InvalidMultiValueTrait;

}