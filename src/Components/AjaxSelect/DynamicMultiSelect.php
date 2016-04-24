<?php

namespace ADT\Components\AjaxSelect;

class DynamicMultiSelect extends \Nette\Forms\Controls\MultiSelectBox {

	use Traits\InvalidMultiValueTrait;

	use Traits\ItemFactoryTrait;

}