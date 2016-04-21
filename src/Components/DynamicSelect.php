<?php

namespace ADT\Components;

class DynamicSelect extends \Nette\Forms\Controls\SelectBox {

	use AjaxSelect\Traits\InvalidValueTrait;

	use AjaxSelect\Traits\ItemFactoryTrait;

}