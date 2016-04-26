<?php

namespace ADT\Components\AjaxSelect;

class DynamicSelect extends \Nette\Forms\Controls\SelectBox implements Interfaces\IPromptControl {

	use Traits\InvalidValueTrait;

	use Traits\ItemFactoryTrait;

}