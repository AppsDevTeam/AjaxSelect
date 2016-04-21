<?php

namespace ADT\Components;

class DynamicSelect extends \Nette\Forms\Controls\MultiSelectBox {

	use AjaxSelect\Traits\InvalidMultiValueTrait;

	use AjaxSelect\Traits\ItemFactoryTrait;

}